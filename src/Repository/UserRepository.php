<?php

namespace RtcTalker\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\Online;
use RtcTalker\Model\User;

class UserRepository extends EntityRepository{
    public function getNumberOfUsers(): ?int
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT COUNT(u) FROM \\RtcTalker\\Model\\User u');
        $result = $query->getSingleScalarResult();
        if($result !== null) {
            return (int)$result;
        } else {
            return -1;
        }
    }

    public function getOnlineForCompanyAndDepartment(Company $company, Department $department) {
        $em = $this->getEntityManager();

        $onlineQueryBuilder = $em->createQueryBuilder();
        $onlineQueryBuilder->select('o')
            ->from(Online::class, 'o')
            ->where('u.id = o.user');

        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('u')
            ->join('u.departments', 'd')
            ->join('u.availability', 'a')
            ->where('u.company = ?1')
            ->andWhere('d.id = ?2')
            ->andWhere($onlineQueryBuilder->expr()->exists($onlineQueryBuilder->getDql()))
            ->setParameter(1, $company->getId()->toString())
            ->setParameter(2, $department->getId()->toString());

        return $queryBuilder->getQuery()->getResult();
    }

    public function getUserToTalk(Company $company, Department $department, string $type) {
        $em = $this->getEntityManager();

        $onlineQueryBuilder = $em->createQueryBuilder();
        $onlineQueryBuilder->select('o')
            ->from(Online::class, 'o')
            ->where('u.id = o.user');

        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('u')
            ->join('u.departments', 'd')
            ->join('u.availability', 'a')
            ->where('u.company = ?1')
            ->andWhere('d.id = ?2')
            ->andWhere('a.type = ?3')
            ->andWhere($onlineQueryBuilder->expr()->exists($onlineQueryBuilder->getDql()))
            ->orderBy('u.rate', 'desc')
            ->setParameter(1, $company->getId()->toString())
            ->setParameter(2, $department->getId()->toString())
            ->setParameter(3, $type);
        $query = $queryBuilder->getQuery();
        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $results =  $query->getResult();
            if(count($results)) {
                return $results[0];
            } else {
                return null;
            }
        }
    }
}