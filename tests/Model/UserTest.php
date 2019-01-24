<?php

namespace Test\Model;

use PHPUnit\Framework\TestCase;
use RtcTalker\Model\User;

final class UserTest extends TestCase {
    protected static $userRaw;

    public static function setUpBeforeClass()
    {
        self::$userRaw = [
            'email' => 'tbajorek3@gmail.com',
            'name' => 'Tomasz',
            'surname' => 'Bajorek',
            'password' => 'XXX'
        ];
    }

    public function testMozeBycUtworzonyZSurowychDanych(): void
    {
        $this->assertInstanceOf(
            User::class,
            User::createFromRawData(self::$userRaw)
        );
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testPoprawnieKodujeHaslo(): void
    {
        $plainPassword = 'test';
        $pattern = hash('sha512', $plainPassword);
        $this->assertEquals(User::encodePassword($plainPassword), $pattern);
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testPosiadaDobreWartosci(): void
    {
        $userObject = User::createFromRawData(self::$userRaw);
        $this->assertEquals($userObject->getEmail(), self::$userRaw['email']);
        $this->assertEquals($userObject->getName(), self::$userRaw['name']);
        $this->assertEquals($userObject->getSurname(), self::$userRaw['surname']);
        $this->assertEquals($userObject->isActivated(), false);
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testJestMechanizmOcenPoprawny(): void
    {
        $userObject = User::createFromRawData(self::$userRaw);
        $this->assertEquals($userObject->getRate(), 0);
        $this->assertEquals($userObject->getRatesNumber(), 0);
        $userObject->updateRate(3.5);
        $this->assertEquals($userObject->getRate(), 3.5);
        $this->assertEquals($userObject->getRatesNumber(), 1);
        $userObject->updateRate(5);
        $this->assertEquals($userObject->getRate(), 4.25);
        $this->assertEquals($userObject->getRatesNumber(), 2);
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testJestPoprawnieZwiekszanaLiczbaRozmow(): void
    {
        $userObject = User::createFromRawData(self::$userRaw);
        $this->assertEquals($userObject->getTalksNumber(), 0);
        $userObject->increaseTalksNumber();
        $this->assertEquals($userObject->getTalksNumber(), 1);
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testMozePobracObrazZGravatara(): void
    {
        $userObject = User::createFromRawData(self::$userRaw);
        $this->assertEquals($userObject->getAvatar(), 'https://www.gravatar.com/avatar/7df84f6993740726a8258034d1200219');
        $userObject->setEmail(uniqid('', true).'@'.uniqid('', true).'.com');
        $this->assertEquals($userObject->getAvatar(), null);
    }
}