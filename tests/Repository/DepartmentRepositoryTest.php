<?php

namespace Test\Repository;

use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\Online;
use RtcTalker\Model\User;
use Test\AbstractDbTest;

final class DepartmentRepositoryTest extends AbstractDbTest {
    private $departmentRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->departmentRepository = $this->em->getRepository(Department::class);
    }

    public function testMozeBycZapisanyWBazieDanych(): void
    {
        $this->resetDatabase();
        try {

            $department = new Department();
            $department->setName('Testowy');
            $this->em->persist($department);
            $this->em->flush();
        } catch (\Throwable $notExpected) {
            $this->fail();
        }
        $this->assertTrue(true);
    }

    /**
     * @depends testMozeBycZapisanyWBazieDanych
     */
    public function testMozeBycPobranyZBazyDanych(): void
    {
        $departments = $this->departmentRepository->findAll();
        $this->assertCount(1, $departments);
        $this->assertEquals($departments[0]->getName(), 'Testowy');
    }

    public function testMozeBycZnalezionyOnlineDlaFirmy() : void {
        $this->resetDatabase();
        try {
            // tworzenie firmy
            $company = $this->getCompany();
            $this->em->persist($company);

            // tworzenie użytkownika
            $user = $this->getUser($company);
            $this->em->persist($user);

            // tworzenie departamentu
            $departmentId = Uuid::uuid4();
            $department = new Department();
            $department->setId($departmentId);
            $department->setName('');
            $department->setCompany($company);
            $department->setWorkers([$user]);
            $this->em->persist($department);
            $user->setDepartments([$department]);

            // dodawanie statusu online
            $online = new Online();
            $online->setUser($user);
            $this->em->persist($online);

            // zatwierdzenie zmian
            $this->em->flush();

            $onlineDepartments = $this->departmentRepository->getOnlineForCompany($company);
            $this->assertCount(1, $onlineDepartments);
            $onlineId = $onlineDepartments[0]['id'];
            $this->assertEquals($onlineId->toString(), $department->getId()->toString());
        } catch (\Throwable $notExpected) {
            $this->fail('FAILURE! '.$notExpected->getMessage());
        }
    }

    private function getUser(?Company $company = null) : User {
        // tworzenie użytkownika
        $userId = Uuid::uuid4();
        $user = new User();
        $user->setId($userId);
        $user->setName('Jan');
        $user->setSurname('');
        $user->setEmail(uniqid('', true));
        $user->setPassword('');
        $user->setRole(1);
        $user->setRate(0);
        $user->setRatesNumber(0);
        $user->setTalksNumber(0);
        $user->setActivated(true);
        $user->setRegisteredAt(new \DateTimeImmutable());
        if($company !== null) {
            $user->setCompany($company);
        }
        return $user;
    }

    private function getCompany() : Company {
        $companyId = Uuid::uuid4();
        $company = new Company();
        $company->setId($companyId);
        $company->setName('');
        $company->setNip('');
        $company->setActivated(true);
        return $company;
    }
}