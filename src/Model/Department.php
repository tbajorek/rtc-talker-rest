<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * This class represents a department of a company
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity(repositoryClass="\RtcTalker\Repository\DepartmentRepository")
 * @ORM\Table(name="departments")
 */
class Department implements \JsonSerializable {
    /**
     * Department constructor
     */
    public function __construct()
    {
        $this->workers = new ArrayCollection();
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
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="departments")
     */
    private $workers;

    /**
     * @var \RtcTalker\Model\Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="departments")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     */
    private $company;

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getId() : \Ramsey\Uuid\UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return Collection
     */
    public function getWorkers(): Collection
    {
        return $this->workers;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return string[]
     */
    public function getWorkersIds() :array
    {
        return array_map(function(User $worker) {
            return $worker->getId();
        }, $this->getWorkers()->getValues());
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return Department
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): Department
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $name
     * @return Department
     */
    public function setName(string $name): Department
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param User[] $workers
     * @return Department
     */
    public function setWorkers(array $workers): Department
    {
        $this->workers = new ArrayCollection($workers);
        return $this;
    }

    /**
     * @param User $worker
     * @return Department
     */
    public function addWorker(User $worker): Department
    {
        $this->workers->add($worker);
        return $this;
    }

    /**
     * @param Company $company
     * @return Department
     */
    public function setCompany(Company $company): Department
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'workers' => $this->getWorkersIds(),
        ];
    }

    /**
     * @return array
     */
    public function jsonSerializeLess()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}