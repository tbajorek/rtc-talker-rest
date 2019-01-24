<?php

namespace Test\Repository;

use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use RtcTalker\Model\Availability;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\Online;
use RtcTalker\Model\User;
use Test\AbstractDbTest;

final class UserRepositoryTest extends AbstractDbTest {
    private $userRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function testMozeBycZapisanyWBazieDanych(): void
    {
        $this->resetDatabase();
        try {
            $user = $this->getUser();
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Throwable $notExpected) {
            $this->fail('FAILURE! '.$notExpected->getMessage());
        }
        $this->assertTrue(true);
    }

    /**
     * @depends testMozeBycZapisanyWBazieDanych
     */
    public function testMozeBycPobranyZBazyDanych(): void
    {
        $users = $this->userRepository->findAll();
        $this->assertCount(1, $users);
        $this->assertEquals($users[0]->getName(), 'Jan');
    }

    /**
     * @depends testMozeBycZapisanyWBazieDanych
     */
    public function testZwracaPoprawnaLiczbeUzytkownikowWBazie() : void {
        $this->resetDatabase();
        $user1 = $this->getUser();
        $this->em->persist($user1);
        $user2 = $this->getUser();
        $this->em->persist($user2);
        $this->em->flush();
        $numberOfUsers = $this->userRepository->getNumberOfUsers();
        $this->assertEquals($numberOfUsers, 2);
    }

    /**
     * @depends testMozeBycZapisanyWBazieDanych
     */
    public function testMozeBycZnalezionyOnlineDlaFirmyIDepartamentu() : void {
        $this->resetDatabase();
        try {
            [
                'company' => $company,
                'user' => $user,
                'department' => $department
            ] = $this->prepareDatabase();
            $onlineUsers = $this->userRepository->getOnlineForCompanyAndDepartment($company, $department);
            $this->assertCount(1, $onlineUsers);
            $onlineUser = $onlineUsers[0];
            $this->assertEquals($onlineUser->getId()->toString(), $user->getId()->toString());
        } catch (\Throwable $notExpected) {
            $this->fail('FAILURE! '.$notExpected->getMessage());
        }
    }

    /**
     * @depends testMozeBycZnalezionyOnlineDlaFirmyIDepartamentu
     */
    public function testWybieraPoprawnieDostepnegoUzytkownika() : void {
        $this->resetDatabase();
        try {
            [
                'company' => $company,
                'user' => $user,
                'department' => $department,
                'type' => $type
            ] = $this->prepareDatabase();
            $userNotFound = $this->userRepository->getUserToTalk($company, $department, $type.'-not');
            $this->assertNull($userNotFound);
            $chosenUser = $this->userRepository->getUserToTalk($company, $department, $type);
            $this->assertEquals($chosenUser->getId()->toString(), $user->getId()->toString());
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

    private function prepareDatabase() : array {
        // tworzenie firmy
        $company = $this->getCompany();
        $this->em->persist($company);

        // tworzenie użytkownika
        $user1 = $this->getUser($company);
        $this->em->persist($user1);

        $user2 = $this->getUser($company);
        $this->em->persist($user2);

        // tworzenie departamentu
        $departmentId = Uuid::uuid4();
        $department = new Department();
        $department->setId($departmentId);
        $department->setCompany($company);
        $department->setName('');
        $department->setWorkers([$user1, $user2]);
        $this->em->persist($department);
        $user1->setDepartments([$department]);
        $user2->setDepartments([$department]);

        // dodawanie statusu online
        $online = new Online();
        $online->setUser($user1);
        $this->em->persist($online);

        // dodanie dostępnego kanału
        $availabilityId = Uuid::uuid4();
        $availability = new Availability();
        $availability->setId($availabilityId);
        $availability->setType('video');
        $availability->setUser($user1);
        $this->em->persist($availability);

        // zatwierdzenie zmian
        $this->em->flush();

        return [
            'company' => $company,
            'user' => $user1,
            'department' => $department,
            'type' => 'video'
        ];
    }
}