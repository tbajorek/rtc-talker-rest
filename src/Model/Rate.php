<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents a session of user
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity()
 * @ORM\Table(name="rates")
 */
class Rate implements \JsonSerializable {
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
     * @var string
     *
     * @ORM\Column(type="decimal", precision=2, scale=1, nullable=false)
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $comment;

    /**
     * @var \RtcTalker\Model\OpenedTalk
     *
     * @ORM\OneToOne(targetEntity="OpenedTalk")
     * @ORM\JoinColumn(name="rate_id", referencedColumnName="id")
     */
    private $openedTalk;

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getId(): \Ramsey\Uuid\UuidInterface
    {
        return $this->id;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return Rate
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): Rate
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
     * @return Rate
     */
    public function setUser(User $user): Rate
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getRate(): string
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     * @return Rate
     */
    public function setRate(string $rate): Rate
    {
        $this->rate = $rate;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Rate
     */
    public function setComment(string $comment): Rate
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return OpenedTalk
     */
    public function getOpenedTalk(): OpenedTalk
    {
        return $this->openedTalk;
    }

    /**
     * @param OpenedTalk $openedTalk
     */
    public function setOpenedTalk(OpenedTalk $openedTalk): void
    {
        $this->openedTalk = $openedTalk;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId()->toString(),
            'rate' => $this->getRate(),
            'comment' => $this->getComment(),
        ];
    }
}