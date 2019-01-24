<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use function foo\func;

/**
 * This class represents a company
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity()
 * @ORM\Table(name="companies")
 */
class Company implements \JsonSerializable {
    /**
     * Department constructor
     */
    public function __construct()
    {
        $this->workers = new ArrayCollection();
        $this->departments = new ArrayCollection();
    }

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
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    private $nip;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activated;

    /**
     * @var \RtcTalker\Model\Address
     *
     * @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="company")
     */
    private $workers;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Department", mappedBy="company")
     */
    private $departments;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNip()
    {
        return $this->nip;
    }

    /**
     * @return bool
     */
    public function isActivated()
    {
        return $this->activated;
    }

    /**
     * @return \RtcTalker\Model\Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return Collection
     */
    public function getWorkers() : Collection
    {
        return $this->workers;
    }

    /**
     * @return Collection
     */
    public function getDepartments() : Collection
    {
        return $this->departments;
    }

    /**
     * @return string[]
     */
    public function getDepartmentsIds() : array
    {
        return array_map(function(Department $department) {
            return $department->getId();
        }, $this->getDepartments()->getValues());
    }

    /**
     * @return string[]
     */
    public function getWorkersIds() : array
    {
        return array_map(function(User $worker) {
            return $worker->getId();
        }, $this->getWorkers()->getValues());
    }

    /**
     * @param User $worker
     * @return bool
     */
    public function hasWorker(User $worker) : bool {
        $foundWorkers = array_filter($this->getWorkers()->getValues(), function (User $assignedWorker) use ($worker) : bool {
            return $assignedWorker->getId() === $worker->getId();
        });
        return count($foundWorkers) > 0;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return Company
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): Company
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $name
     * @return Company
     */
    public function setName(string $name): Company
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $nip
     * @return Company
     */
    public function setNip(string $nip): Company
    {
        $this->nip = $nip;
        return $this;
    }

    /**
     * @param bool $activated
     * @return Company
     */
    public function setActivated(bool $activated): Company
    {
        $this->activated = $activated;
        return $this;
    }

    /**
     * @param Address $address
     * @return Company
     */
    public function setAddress(Address $address): Company
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @param User[] $workers
     * @return Company
     */
    public function setWorkers(array $workers): Company
    {
        $this->workers = new ArrayCollection($workers);
        return $this;
    }

    /**
     * @param Department[] $departments
     * @return Company
     */
    public function setDepartments(array $departments): Company
    {
        $this->departments = new ArrayCollection($departments);
        return $this;
    }

    /**
     * @param Department $department
     * @return Company
     */
    public function addDepartment(Department $department): Company
    {
        $this->departments->add($department);
        return $this;
    }

    public function validateNip(string $nip) : bool {
        $nipWithoutDashes = str_replace('-', '', $nip);
        $reg = '/^\d{10}$/';
        if(preg_match($reg, $nipWithoutDashes)===false) {
            return false;
        } else {
            $digits = str_split($nipWithoutDashes);
            $checksum = (6*(int)$digits[0] + 5* (int)$digits[1] + 7* (int)$digits[2] + 2* (int)$digits[3] + 3* (int)$digits[4] + 4* (int)$digits[5] + 5* (int)$digits[6] + 6* (int)$digits[7] + 7* (int)$digits[8])%11;

            return ((int)$digits[9] === $checksum);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'nip' => $this->getNip(),
            'activated' => $this->isActivated(),
            'workers' => $this->getWorkersIds(),
            'departments' => $this->getDepartmentsIds(),
            'address' => $this->getAddress()->jsonSerialize(),
        ];
    }

    /**
     * @return array
     */
    public function jsonSerializeMore()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'nip' => $this->getNip(),
            'activated' => $this->isActivated(),
            'workers' => array_map(function(User $worker) : array {return $worker->jsonPublicSerialize();}, $this->getWorkers()->toArray()),
            'departments' => array_map(function(Department $department) : array {return $department->jsonSerializeLess();}, $this->getDepartments()->toArray()),
            'address' => $this->getAddress()->jsonSerialize(),
        ];
    }
}