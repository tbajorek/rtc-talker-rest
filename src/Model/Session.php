<?php

namespace RtcTalker\Model;

use Doctrine\ORM\Mapping as ORM;
use Slim\Http\Request;
use Firebase\JWT\JWT;

use function RtcTalker\Utility\getSecret;

/**
 * This class represents a session of user
 *
 * @package RtcTalker\Model
 *
 * @ORM\Entity()
 * @ORM\Table(name="sessions")
 */
class Session implements \JsonSerializable {
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
     * @ORM\Column(type="text", length=2048)
     */
    private $token;

    /**
     * @var \RtcTalker\Model\User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="sessions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=15, nullable=false)
     */
    private $ip;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetimetz_immutable", nullable=false)
     */
    private $validUntil;

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    public function getId(): \Ramsey\Uuid\UuidInterface
    {
        return $this->id;
    }

    /**
     * @param \Ramsey\Uuid\UuidInterface $id
     * @return Session
     */
    public function setId(\Ramsey\Uuid\UuidInterface $id): Session
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return Session
     */
    public function setToken(string $token): Session
    {
        $this->token = $token;
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
     * @return Session
     */
    public function setUser(User $user): Session
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp() : string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return Session
     */
    public function setIp(string $ip): Session
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getValidUntil(): \DateTimeImmutable
    {
        return $this->validUntil;
    }

    /**
     * @param \DateTimeImmutable $validUntil
     * @return Session
     */
    public function setValidUntil(\DateTimeImmutable $validUntil): Session
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'token' => $this->getToken(),
            'user' => $this->getUser()->jsonSerialize(),
            'ip' => $this->getIp(),
            'validUntil' => $this->getValidUntil()
                ->format(\DateTime::ATOM)
        ];
    }

    public static function createForUser(Request $request, User $user) {
        $uri = $request->getUri();
        $url = $uri->getScheme().'://'.$uri->getHost();
        $start = new \DateTime();
        $end = new \DateTime("now +10 hours");
        $data = [
            "iss" => $url,
            "aud" => $url,
            "nbf" => $start->getTimestamp(),
            "exp" => $end->getTimestamp(),
            "sub" => $user->getId()
        ];
        $token = JWT::encode($data, getSecret(dirname(__DIR__, 2).'/secret.key'), 'HS256');
        $session = new self();
        $session->setToken($token)
                ->setUser($user)
                ->setIp($request->getAttribute('ip_address'))
                ->setValidUntil(\DateTimeImmutable::createFromMutable($end));
        return $session;
    }
}