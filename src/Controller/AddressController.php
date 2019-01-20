<?php

namespace RtcTalker\Controller;

use Slim\Http;
use RtcTalker\Exception\NotFoundException;
use RtcTalker\Exception\AuthException;
use RtcTalker\Model\Address;

class AddressController extends AbstractController {
    public function createForUser(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'user.address.add');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie mozesz dodac adresu dla tego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(403, $e->getMessage());
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)
            || !key_exists('street', $parsedBody)
            || !key_exists('building_number', $parsedBody)
            || !key_exists('post_code', $parsedBody)
            || !key_exists('city', $parsedBody)
            || !key_exists('country', $parsedBody)
            || !key_exists('phone', $parsedBody)
        ) {
            return $response->withStatus(400, 'Nie podales wszystkich wymaganych danych');
        }
        $newAddress = Address::createFromRawData($parsedBody);
        $user->setAddress($newAddress);
        $this->em->merge($user);
        $this->em->persist($newAddress);
        $this->em->flush();
        return $response->withJson($user, 201);
    }

    public function updateForUser(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'user.address.update');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie mozesz zaktualizowac adresu dla tego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(403, $e->getMessage());
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)
            || !key_exists('street', $parsedBody)
            || !key_exists('building_number', $parsedBody)
            || !key_exists('post_code', $parsedBody)
            || !key_exists('city', $parsedBody)
            || !key_exists('country', $parsedBody)
            || !key_exists('phone', $parsedBody)
        ) {
            return $response->withStatus(400, 'Nie podales wszystkich wymaganych danych');
        }
        $address = $user->getAddress();
        if($address === null) {
            return $response->withStatus(404, 'Nie masz zadnego adresu');
        }
        $address->importFromRawData($parsedBody);
        $this->em->merge($address);
        $this->em->flush();
        return $response->withJson($user, 200);
    }
}
