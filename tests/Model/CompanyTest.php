<?php

namespace Test\Model;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\User;

final class CompanyTest extends TestCase {
    private static $companyRaw;
    private $company;

    public static function setUpBeforeClass()
    {
        self::$companyRaw = [
            'name' => 'tbajorek3@gmail.com',
            'nip' => '1164871356'
        ];
    }

    public function setUp()
    {
        $this->company = new Company();
    }

    public function testNipJestPoprawnieWalidowany() : void {
        $this->assertEquals($this->company->validateNip(self::$companyRaw['nip']), true);
        $this->assertEquals($this->company->validateNip('1236547895'), false);
    }

    public function testMozeMiecTylkoIdentyfikatorUuid() : void {
        $this->expectException(\TypeError::class);
        (new User())->setId(1);
    }

    public function testZwracaDobreWartosciIdentyfikatorowDepartamentow() : void {
        $id1 = Uuid::uuid4();
        $id2 = Uuid::uuid4();
        $this->company
            ->addDepartment((new Department())->setId($id1))
            ->addDepartment((new Department())->setId($id2));
        $departmentsIds = $this->company->getDepartmentsIds();
        $this->assertCount(2, $departmentsIds);
        $this->assertContains($id1, $departmentsIds);
        $this->assertContains($id2, $departmentsIds);
    }

    public function testSprawdzaPoprawnieUstawionychPracownikow() : void {
        $user1 = (new User())->setId(Uuid::uuid4());
        $user2 = (new User())->setId(Uuid::uuid4());
        $this->company
            ->setWorkers([
                $user1, $user2
        ]);
        $this->assertEquals($this->company->hasWorker($user1), true);
        $this->assertEquals($this->company->hasWorker($user2), true);
        $this->assertEquals($this->company->hasWorker((new User())->setId(Uuid::uuid4())), false);
    }
}