<?php

namespace Test\Model;

use PHPUnit\Framework\TestCase;
use RtcTalker\Model\Address;

final class AddressTest extends TestCase {
    protected static $addressRaw;

    public static function setUpBeforeClass()
    {
        self::$addressRaw = [
            'street' => 'Kwiatowa',
            'building_number' => '57/2',
            'post_code' => '30-987',
            'city' => 'Kraków',
            'country' => 'Polska',
            'phone' => '965478521',
        ];
    }

    public function testMozeBycUtworzonyZSurowychDanych(): void
    {
        $this->assertInstanceOf(
            Address::class,
            Address::createFromRawData(self::$addressRaw)
        );
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testMaDobreWartosci(): void
    {
        $addressObject = Address::createFromRawData(self::$addressRaw);
        $this->assertEquals($addressObject->getStreet(), self::$addressRaw['street']);
        $this->assertEquals($addressObject->getBuildingNumber(), self::$addressRaw['building_number']);
        $this->assertEquals($addressObject->getPostCode(), self::$addressRaw['post_code']);
        $this->assertEquals($addressObject->getCity(), self::$addressRaw['city']);
        $this->assertEquals($addressObject->getCountry(), self::$addressRaw['country']);
        $this->assertEquals($addressObject->getPhone(), self::$addressRaw['phone']);
    }

    /**
     * @depends testMozeBycUtworzonyZSurowychDanych
     */
    public function testJestPoprawnieSerializowany(): void
    {
        $addressObject = Address::createFromRawData(self::$addressRaw);
        $serialized = $addressObject->jsonSerialize();
        $this->assertEquals($serialized['street'], self::$addressRaw['street']);
        $this->assertEquals($serialized['buildingNumber'], self::$addressRaw['building_number']);
        $this->assertEquals($serialized['postCode'], self::$addressRaw['post_code']);
        $this->assertEquals($serialized['city'], self::$addressRaw['city']);
        $this->assertEquals($serialized['country'], self::$addressRaw['country']);
        $this->assertEquals($serialized['phone'], self::$addressRaw['phone']);
    }
}