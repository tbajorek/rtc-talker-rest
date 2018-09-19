<?php

namespace RtcTalker\Controller;

use Interop\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RtcTalker\Exception\NotFoundException;
use Slim\Http;
use RtcTalker\Model\User;
use RtcTalker\Model\Session;
use RtcTalker\Exception\AuthException;
use RtcTalker\Provider\Permissions;

abstract class AbstractController {
    protected $container;
    protected $em;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        /**
         * @var \Doctrine\ORM\EntityManager
         */
        $this->em = $container[EntityManager::class];
    }

    protected function checkPermissions(Http\Request $request, User $user, string $permission) : bool {
        $fullToken = $this->getFullToken($request);
        $token = $request->getAttribute("token");
        if($fullToken === null || $token === null) {
            throw new AuthException('Your token can not be read');
        }
        if($user->getId()->toString() !== $token['sub']) {
            throw new AuthException('This token does not belong to you');
        }
        if(!in_array($permission, Permissions::getForRole($user->getRole()))) {
            throw new AuthException('You do not have permissions to perform this action');
        }
        return true;
    }

    protected function checkAllPermissions(Http\Request $request, User $user, array $permissions) : bool {
        $result = false;
        foreach ($permissions as $permission) {
            try {
                if($result) {
                    return true;
                }
                $result = $this->checkPermissions($request, $user, $permission);
            } catch (AuthException $e) {}
        }
        if(!$result) {
            throw new AuthException('You do not have permissions to perform this action');
        }
    }

    protected function getFullToken(Http\Request $request) :?string {
        $header = $request->getHeader('Authorization');
        if(is_array($header) && count($header)) {
            return str_replace('Bearer ', '', $header[0]);
        }
        return null;
    }

    /**
     * @param array $args
     * @return User
     * @throws NotFoundException
     */
    protected function getUserFromArgs(array $args) :User {
        $userId = $args['userId'];
        $user = $this->em->getRepository(User::class)->findOneBy(['id'=>$userId]);
        if($user === null) {
            throw new NotFoundException($userId);
        }
        return $user;
    }

    /**
     * @param Http\Request $request
     * @return User
     */
    protected function getUserFromToken(Http\Request $request) :User {
        $fullToken = $this->getFullToken($request);
        $session = $this->em->getRepository(Session::class)->findOneBy([
            "ip" => $request->getAttribute('ip_address'),
            "token" => $fullToken
        ]);
        if($session === null) {
            throw new NotFoundException($fullToken);
        }
        return $session->getUser();
    }
}