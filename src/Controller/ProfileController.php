<?php

namespace RtcTalker\Controller;

use Slim\Http;
use RtcTalker\Exception\NotFoundException;
use RtcTalker\Exception\AuthException;
use RtcTalker\Model\User;

class ProfileController extends AbstractController {
    public function myProfile(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'user.view.my.profile');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'You can not find user with the token');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        return $response->withJson($user, 200);
    }

    public function profile(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromArgs($args);
            $this->checkPermissions($request, $user, 'user.view.profile');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'You can not remove session for this user');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        return $response->withJson($user, 200);
    }

    public function update(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'user.update.my.profile');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'You can not remove session for this user');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $parsedBody = $request->getParsedBody();
        $this->updateUserValue($user, $parsedBody, 'email');
        $this->updateUserValue($user, $parsedBody, 'name');
        $this->updateUserValue($user, $parsedBody, 'surname');
        $this->em->merge($user);
        $this->em->flush();
        return $response->withJson($user, 200);
    }

    private function updateUserValue(User $user, array $values, string $name) : bool {
        if(!key_exists($name, $values)) {
            return false;
        }
        $value = $values[$name];
        $method = 'set'.ucfirst($name);
        if(!method_exists($user, $method)) {
            return false;
        } else {
            $user->$method($value);
            return true;
        }
    }
}