<?php

namespace Test\Model;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RtcTalker\Model\Session;
use RtcTalker\Model\User;
use function RtcTalker\Utility\getSecret;
use Slim\Http\Headers;
use Slim\Http\RequestBody;
use Slim\Http\Uri;

final class SessionTest extends TestCase {
    private $user;
    private $session;
    private $requestMock;

    public function setUp()
    {
        $this->requestMock = $this->getRequestMock();
        $this->user = (new User())->setId(Uuid::uuid4());
        $this->session = Session::createForUser($this->requestMock, $this->user);
    }

    public function testJestPoprawnieUtworzonaDlaUzytkownika() : void {
        $this->assertEquals($this->session->getIp(), '127.0.0.1');
        $this->assertEquals($this->session->getUser()->getId(), $this->user->getId());
    }

    public function testPosiadaPoprawnieWygenerowanyToken() : void {
        $token = $this->session->getToken();
        $decryptedToken = JWT::decode($token, getSecret(dirname(__DIR__, 2).'/secret.key'), ['HS256']);
        $this->assertEquals($decryptedToken->iss, $this->requestMock->getUrl());
        $this->assertEquals($decryptedToken->aud, $this->requestMock->getUrl());
        $this->assertEquals($decryptedToken->sub, $this->user->getId());
        $start = new \DateTime();
        $end = new \DateTime("now +9 hours");
        $this->assertGreaterThanOrEqual($decryptedToken->nbf, $start->getTimestamp());
        $this->assertGreaterThan($end->getTimestamp(), $decryptedToken->exp);
    }

    private function getRequestMock() {
        return new class('POST', new Uri('https', 'example.com'), new Headers(), [], [], new RequestBody()) extends \Slim\Http\Request{
            public function getAttribute($name, $default = null)
            {
                if($name === 'ip_address') {
                    return '127.0.0.1';
                } else {
                    return $default;
                }
            }
            public function getUrl() : string {
                return $this->uri->getScheme().'://'.$this->uri->getHost();
            }
        };
    }
}