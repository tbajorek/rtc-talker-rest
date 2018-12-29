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
use RtcTalker\Model\User;
use RtcTalker\Model\Availability;
use Slim\Http;

class OptionsController extends AbstractController
{
    public function options(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $companyId = $args['companyId'];
        $company = $this->em->getRepository(Company::class)->find($companyId);
        if($company === null) {
            return $response->withStatus(404, 'Firma nie istnieje');
        }
        $onlineDepartments = $this->em->getRepository(Department::class)->getOnlineForCompany($company);

        //var_dump($onlineUsers);
        //die();
        return $response->withJson(['departments' => $onlineDepartments], 200);
    }

    public function users(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            list($company, $department) = $this->findCompanyAndDepartment($args);
        } catch (NotFoundException $e) {
            return $response->withStatus(404, $e->getMessage());
        }
        $onlineUsers = $this->em->getRepository(User::class)->getOnlineForCompanyAndDepartment($company, $department);
        return $response->withJson(
            ['users' => array_map(function (User $user) : array {
                $availability = array_map(function (Availability $availability) {
                    return $availability->getType();
                }, $user->getAvailability()->getValues());
                return ['id' => $user->getId(), 'availability' => $availability];
            }, $onlineUsers)]
            , 200);
    }

    public function chooseUser(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            list($company, $department) = $this->findCompanyAndDepartment($args);
        } catch (NotFoundException $e) {
            return $response->withStatus(404, $e->getMessage());
        }
        $type = $args['type'];
        $chosenUser = $this->em->getRepository(User::class)->getUserToTalk($company, $department, $type);
        if($chosenUser !== null) {
            $chosenUser->increaseTalksNumber();
            $openedTalk = new OpenedTalk();
            $openedTalk->setUser($chosenUser);
            $this->em->persist($openedTalk);
            $this->em->merge($chosenUser);
            $this->em->flush();
            return $response->withJson(['user' => $chosenUser->jsonPublicSerialize(), 'talkId' => $openedTalk->getId()->toString()], 200);
        } else {
            return $response->withStatus(404, 'Żaden użytkownik nie został dla Ciebie znaleziony');
        }
    }

    private function findCompanyAndDepartment($args) {
        $companyId = $args['companyId'];
        $departmentId = $args['departmentId'];
        $company = $this->em->getRepository(Company::class)->find($companyId);
        if($company === null) {
            throw new NotFoundException('Firma nie istnieje');
        }
        $department = $this->em->getRepository(Department::class)->find($departmentId);
        if($department === null) {
            throw new NotFoundException('Departament nie istnieje');
        }
        return [$company, $department];
    }
}