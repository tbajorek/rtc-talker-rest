<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use forxer\Gravatar\Gravatar;
use Curl\Curl;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This class represents an user of the system
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity(repositoryClass="\RtcTalker\Repository\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements \JsonSerializable {

    public static $GUEST = 1;
    public static $USER = 2;
    public static $MANAGER = 3;
    public static $ADMIN = 4;

    public function __construct()
    {
        $this->departments = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->availability = new ArrayCollection();
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
     * @ORM\Column(type="string", nullable=false)
     */
    private $surname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $password;

    /**
     * @var string
     */
    private $avatar;

    /**
     * @var \RtcTalker\Model\Address
     *
     * @ORM\ManyToOne(targetEntity="Address", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    /**
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $role;

    /**
     * @var \RtcTalker\Model\Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="workers")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     */
    private $company;

    /**
     * @var \RtcTalker\Model\Department[]
     *
     * @ORM\ManyToMany(targetEntity="Department", inversedBy="workers")
     * @ORM\JoinTable(name="users_departments")
     */
    private $departments;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activated;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetimetz_immutable", nullable=false)
     */
    private $registeredAt;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", nullable=false, precision=2, scale=1, options={"default" : 0.0})
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $ratesNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $talksNumber;

    /**
     * @var \RtcTalker\Model\Session[]
     *
     * @ORM\OneToMany(targetEntity="Session", mappedBy="user")
     */
    private $sessions;

    /**
     * @var \RtcTalker\Model\Availability[]
     *
     * @ORM\OneToMany(targetEntity="Availability", mappedBy="user", cascade={"persist", "remove"})
     */
    private $availability;

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
     * @return string
     */
    public function getSurname() : string
    {
        return $this->surname;
    }

    /**
     * @return string
     */
    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword() : string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getAvatar() : ?string
    {
        $originalImg = Gravatar::image($this->getEmail());
        $imgUrl = clone $originalImg;
        try {
            $curl = new Curl();
            $curl->get('https:'.$imgUrl->defaultImage('404'));
            if($curl->error) {
                return null;
            } else {
                return 'https:'.$originalImg;
            }
        } catch (\ErrorException $e) {
            return null;
        }
    }

    /**
     * @return Address
     */
    public function getAddress() : ?Address
    {
        return $this->address;
    }

    /**
     * @return integer
     */
    public function getRole() : int
    {
        return $this->role;
    }

    /**
     * @return \RtcTalker\Model\Company
     */
    public function getCompany() : ?Company
    {
        return $this->company;
    }

    /**
     * @return Collection
     */
    public function getDepartments() : Collection
    {
        return $this->departments;
    }

    /**
     * @return bool
     */
    public function isActivated() : bool
    {
        return $this->activated;
    }


    /**
     * @return \DateTimeImmutable
     */
    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /**
     * @return float
     */
    public function getRate(): float
    {
        return (float)$this->rate;
    }

    /**
     * @return int
     */
    public function getRatesNumber(): int
    {
        return $this->ratesNumber;
    }

    /**
     * @return int
     */
    public function getTalksNumber(): int
    {
        return $this->talksNumber;
    }

    /**
     * @return Collection
     */
    public function getSessions() : Collection
    {
        return $this->sessions;
    }

    /**
     * @return Collection
     */
    public function getAvailability() : Collection
    {
        return $this->availability;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return User
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $name
     * @return User
     */
    public function setName(string $name): User
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $surname
     * @return User
     */
    public function setSurname(string $surname): User
    {
        $this->surname = $surname;
        return $this;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param string $avatar
     * @return User
     */
    public function setAvatar(string $avatar): User
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * @param Address $address
     * @return User
     */
    public function setAddress(Address $address): User
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @param int $role
     * @return User
     */
    public function setRole(int $role): User
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @param Company $company
     * @return User
     */
    public function setCompany(Company $company): User
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @param Department[] $departments
     * @return User
     */
    public function setDepartments(array $departments): User
    {
        $this->departments = new ArrayCollection($departments);
        return $this;
    }

    /**
     * @param Department $department
     * @return User
     */
    public function addDepartment(Department $department): User
    {
        $this->departments->add($department);
        return $this;
    }

    /**
     * @param bool $activated
     * @return User
     */
    public function setActivated(bool $activated): User
    {
        $this->activated = $activated;
        return $this;
    }

    /**
     * @param Session[] $sessions
     * @return User
     */
    public function setSessions(array $sessions): User
    {
        $this->sessions = new ArrayCollection($sessions);
        return $this;
    }

    /**
     * @param Availability[] $availability
     * @return User
     */
    public function setAvailability(array $availability): User
    {
        $this->availability = new ArrayCollection($availability);
        return $this;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function hasSamePassword(string $password) : bool {
        return $this->getPassword() === User::encodePassword($password);
    }

    /**
     * @param \DateTimeImmutable $registeredAt
     * @return User
     */
    public function setRegisteredAt(\DateTimeImmutable $registeredAt): User
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * @param float $rate
     * @return User
     */
    public function setRate(float $rate): User
    {
        $this->rate = $rate;
        return $this;
    }

    /**
     * @param int $ratesNumber
     * @return User
     */
    public function setRatesNumber(int $ratesNumber): User
    {
        $this->ratesNumber = $ratesNumber;
        return $this;
    }

    /**
     * @param int $talksNumber
     * @return User
     */
    public function setTalksNumber(int $talksNumber): User
    {
        $this->talksNumber = $talksNumber;
        return $this;
    }


    public function increaseTalksNumber() {
        $this->setTalksNumber($this->getTalksNumber() + 1);
        return $this;
    }

    public function updateRate($newRate) {
        $calculatedRate = ($this->getRatesNumber()*$this->getRate() +$newRate)/($this->getRatesNumber() + 1);
        $this->setRate($calculatedRate);
        $this->setRatesNumber($this->getRatesNumber() + 1);
        return $this;
    }

    public static function createFromRawData(array $inputData): User
    {
        $user = new User();
        $user->setName($inputData['name'])
             ->setSurname($inputData['surname'])
             ->setEmail($inputData['email'])
             ->setPassword(self::encodePassword($inputData['password']))
             ->setActivated(false)
             ->setRate(0)
             ->setRatesNumber(0)
             ->setTalksNumber(0)
             ->setRegisteredAt(new \DateTimeImmutable());
        return $user;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'surname' => $this->getSurname(),
            'email' => $this->getEmail(),
            'avatar' => $this->getAvatar(),
            'role' => $this->getRole(),
            'activated' => $this->isActivated(),
            'registered_at' => $this->getRegisteredAt()
                ->format(\DateTime::ATOM)
        ];
        $data['address'] = $this->getAddress() !== null ? $this->getAddress()->jsonSerialize() : null;
        $data['company'] = $this->getCompany() !== null ? ['id'=> $this->getCompany()->getId(), 'name' => $this->getCompany()->getName()] : null;
        $data['departments'] = count($this->getDepartments()->getKeys()) > 0 ? $this->getDepartments()->getValues() : [];
        $data['availability'] = array_map(function (Availability $availability) {
            return $availability->getType();
        }, $this->getAvailability()->getValues()) ?? [];
        return $data;
    }

    /**
     * @return array
     */
    public function jsonPublicSerialize(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'surname' => $this->getSurname(),
            'avatar' => $this->getAvatar(),
        ];
        $data['availability'] = array_map(function (Availability $availability) {
                return $availability->getType();
            }, $this->getAvailability()->getValues()) ?? [];
        return $data;
    }

    public static function encodePassword(string $password) : string {
        return hash('sha512', $password);
    }
}