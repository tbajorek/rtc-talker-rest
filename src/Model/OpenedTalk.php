<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents a session of user
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity()
 * @ORM\Table(name="opened_talks")
 */
class OpenedTalk implements \JsonSerializable {
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
     * @var \RtcTalker\Model\User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getId(): \Ramsey\Uuid\UuidInterface
    {
        return $this->id;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return OpenedTalk
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): OpenedTalk
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return OpenedTalk
     */
    public function setUser(User $user): OpenedTalk
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId()->toString(),
            'user' => $this->getUser()->getId()->toString(),
        ];
    }
}