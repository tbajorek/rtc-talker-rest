<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents an address of users or companies
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity()
 * @ORM\Table(name="addresses")
 */
class Address implements \JsonSerializable {
    /**
     * @var \Ramsey\Uuid\UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $buildingNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $postCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $phone;

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getBuildingNumber()
    {
        return $this->buildingNumber;
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return Address
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): Address
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $street
     * @return Address
     */
    public function setStreet(string $street): Address
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @param string $buildingNumber
     * @return Address
     */
    public function setBuildingNumber(string $buildingNumber): Address
    {
        $this->buildingNumber = $buildingNumber;
        return $this;
    }

    /**
     * @param string $postCode
     * @return Address
     */
    public function setPostCode(string $postCode): Address
    {
        $this->postCode = $postCode;
        return $this;
    }

    /**
     * @param string $city
     * @return Address
     */
    public function setCity(string $city): Address
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @param string $country
     * @return Address
     */
    public function setCountry(string $country): Address
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @param string $phone
     * @return Address
     */
    public function setPhone(string $phone): Address
    {
        $this->phone = $phone;
        return $this;
    }

    public function importFromRawData(array $data) : Address {
        return $this->setStreet($data['street'])
                    ->setBuildingNumber($data['building_number'])
                    ->setPostCode($data['post_code'])
                    ->setCity($data['city'])
                    ->setCountry($data['country'])
                    ->setPhone($data['phone']);
    }

    /**
     * @param array $data
     * @return Address
     */
    public static function createFromRawData(array $data) {
        $address = new self();
        return $address->importFromRawData($data);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'street' => $this->getStreet(),
            'buildingNumber' => $this->getBuildingNumber(),
            'postCode' => $this->getPostCode(),
            'city' => $this->getCity(),
            'country' => $this->getCountry(),
            'phone' => $this->getPhone(),
        ];
    }
}