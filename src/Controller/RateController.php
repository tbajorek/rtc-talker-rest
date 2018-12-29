<?php
/**
 * Created by PhpStorm.
 * User: tbajorek
 * Date: 09.08.18
 * Time: 12:22
 */

namespace RtcTalker\Controller;

use RtcTalker\Exception\NotFoundException;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\OpenedTalk;
use RtcTalker\Model\Rate;
use RtcTalker\Model\User;
use RtcTalker\Model\Availability;
use Slim\Http;

class RateController extends AbstractController
{
    public function rate(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $userId = $args['userId'];
        $user = $this->em->getRepository(User::class)->find($userId);
        if($user === null) {
            return $response->withStatus(404, 'Uzytkownik nie istnieje');
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)
            || !key_exists('rate', $parsedBody)
            || !key_exists('comment', $parsedBody)
            || !key_exists('talk_id', $parsedBody)
        ) {
            return $response->withStatus(400, 'Nie podales wszystkich wymaganych danych');
        }
        $talk = $this->em->getRepository(OpenedTalk::class)->findOneBy(['id' => $parsedBody['talk_id'], 'user' => $user]);
        if($talk === null) {
            return $response->withStatus(404, 'Rozmowa nie istnieje');
        }
        $newRate = new Rate();
        $newRate->setUser($user);
        $newRate->setRate($parsedBody['rate']);
        $newRate->setComment($parsedBody['comment']);
        $user->updateRate($parsedBody['rate']);
        $this->em->persist($newRate);
        $this->em->merge($user);
        $this->em->flush();
        return $response->withJson($newRate, 201);
    }
}