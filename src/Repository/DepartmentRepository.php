<?php

namespace RtcTalker\Repository;

use Doctrine\ORM\EntityRepository;
use RtcTalker\Model\Company;
use RtcTalker\Model\Online;

class DepartmentRepository extends EntityRepository{
    public function getOnlineForCompany(Company $company): array
    {
        $em = $this->getEntityManager();

        $onlineQueryBuilder = $em->createQueryBuilder();
        $onlineQueryBuilder->select('o')
            ->from(Online::class, 'o')
            ->where('u.id = o.user');

        $queryBuilder = $this->createQueryBuilder('d');
        $queryBuilder->select('d.id, d.name')
                     ->join('d.workers', 'u')
                     ->where('u.company = ?1')
                     ->andWhere($onlineQueryBuilder->expr()->exists($onlineQueryBuilder->getDql()))
                     ->setParameter(1, $company->getId());

        return $queryBuilder->getQuery()->getArrayResult();
    }
}