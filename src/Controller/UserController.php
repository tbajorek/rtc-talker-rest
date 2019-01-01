<?php
/**
 * Created by PhpStorm.
 * User: tbajorek
 * Date: 09.08.18
 * Time: 12:22
 */

namespace RtcTalker\Controller;

use RtcTalker\Exception\AuthException;
use RtcTalker\Exception\InputDataException;
use RtcTalker\Model\Availability;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\Session;
use RtcTalker\Exception\NotFoundException;
use Slim\Http;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use RtcTalker\Model\User;
use RtcTalker\Provider\Permissions;

class UserController extends AbstractController
{
    /**
     * Register action
     *
     * @param Http\Request $request
     * @param Http\Response $response
     * @return Http\Response
     * @throws \Exception
     */
    public function register(Http\Request $request, Http\Response $response): Http\Response {
        $parsedBody = $request->getParsedBody();
        if($parsedBody === null) {
            return $response->withStatus(400, 'Zly format JSON');
        }
        $user = User::createFromRawData($parsedBody);
        $firstUser = ($this->em->getRepository(User::class)->getNumberOfUsers() === 0);
        if(is_string($parsedBody['company'])) {
            $company = $this->em->getRepository(Company::class)->find($parsedBody['company']);
            if($company === null) {
                return $response->withStatus(404, 'Firma o podanym identyfikatorze nie istnieje');
            }
            $user->setCompany($company);
            $user->setRole(USER::$USER);
        } else {
            if($firstUser) {
                $user->setRole(USER::$ADMIN);
                $user->setActivated(true);
            } else {
                $user->setRole(USER::$MANAGER);
            }
        }
        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return $response->withStatus(409, 'Uzytkownik o podanych parametrach juz istnieje');
        }
        return $response->withStatus(201, 'Nowy uzytkownik zostal zarejestrowany');
    }

    /**
     * Login action
     *
     * @param Http\Request $request
     * @param Http\Response $response
     * @return Http\Response
     */
    public function login(Http\Request $request, Http\Response $response): Http\Response {
        $parsedBody = $request->getParsedBody();
        if($parsedBody === null) {
            return $response->withStatus(400, 'Zly format JSON');
        }

        $user = $this->em->getRepository(User::class)->findOneBy(["email"=>$parsedBody['email']]);
        if($user === null || !$user->hasSamePassword($parsedBody['password'])) {
            return $response->withStatus(403, 'Nie mozesz zostac zalogowany w systemie');
        }
        if(!$user->isActivated()) {
            return $response->withStatus(403, 'Twoje konto nie jest aktywowane');
        }
        /**
         * @var Session
         */
        $sessionExisted = $this->em->getRepository(Session::class)->findOneBy([
            "user"=>$user,
            "ip"=>$request->getAttribute('ip_address')
        ]);
        if($sessionExisted !== null) {
            $now = (new \DateTime())->getTimestamp();
            if($sessionExisted->getValidUntil()->getTimestamp() > $now) {
                return $response->withJson($sessionExisted, 200);
            }
            $this->em->remove($sessionExisted);
        }
        $session = Session::createForUser($request, $user, Permissions::getForRole($user->getRole()));
        $this->em->persist($session);
        $this->em->flush();
        return $response->withJson($session, 201);
    }

    /**
     * Logout action
     *
     * @param Http\Request $request
     * @param Http\Response $response
     * @param array $args
     * @return Http\Response
     */
    public function logout(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromArgs($args);
            $this->checkPermissions($request, $user, 'user.logout');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie znaleziono zalogowanego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $fullToken = $this->getFullToken($request);
        $session = $this->em->getRepository(Session::class)->findOneBy([
            "user" => $user,
            "ip" => $request->getAttribute('ip_address'),
            "token" => $fullToken
        ]);
        if($session === null) {
            return $response->withStatus(404, 'Nie mozna znalezc sesji');
        }
        $this->em->remove($session);
        $this->em->flush();
        return $response->withStatus(204, 'Zostales pozytywnie wylogowany');
    }

    public function availability(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromArgs($args);
            $this->checkPermissions($request, $user, 'user.change.availability');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie znaleziono zalogowanego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        try {
            $rawAvailabilities = $this->getPayload($request, $response, function($availabilities) {
                if(!is_array($availabilities)) {
                    throw new InputDataException('Przeslane dane powinny byc tablica');
                }
            });
        } catch (InputDataException $e) {
            return $response->withStatus(400, $e->getMessage());
        }
        $availabilities = array_map(function (string $type) use ($user) :Availability {
            $availability = new Availability();
            $availability->setType($type)
                         ->setUser($user);
            return $availability;
        }, $rawAvailabilities);
        foreach ($user->getAvailability() as $oldAvailability) {
            $this->em->remove($oldAvailability);
        }
        $user->setAvailability($availabilities);
        $this->em->merge($user);
        $this->em->flush();
        return $response->withJson($user, 200);
    }

    /**
     * @param Http\Request $request
     * @param Http\Response $response
     * @param array $args
     * @return Http\Response
     */
    public function departments(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromArgs($args);
            $tokenUser = $this->getUserFromToken($request);
            if($user->getCompany()->getId() === $tokenUser->getCompany()->getId()) {
                $this->checkPermissions($request, $tokenUser, 'manager.change.departments');
            } else {
                $this->checkPermissions($request, $tokenUser, 'admin.change.departments');
            }
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie znaleziono zalogowanego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        try {
            $rawDepartments = $this->getPayload($request, $response, function(array $departments) {
                if(!is_array($departments)) {
                    throw new InputDataException('Przeslane dane powinny byc tablica');
                }
            });
        } catch (InputDataException $e) {
            return $response->withStatus(400, $e->getMessage());
        }
        $departments = [];
        $user->setDepartments($departments);//if there is no department, then list has to be erased
        foreach ($rawDepartments as $departmentId) {
            $department = $this->em->getRepository(Department::class)->find($departmentId);
            if($department !== null) {
                $departments[count($departments)] = $department;
                $department->addWorker($user);
                $this->em->merge($department);
            }
        }
        $user->setDepartments($departments);
        $this->em->merge($user);
        $this->em->flush();
        return $response->withJson($user, 200);
    }

    public function activate(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $actionUser = $this->getUserFromToken($request);
            $this->checkAllPermissions($request, $actionUser, ['manager.activate.user', 'admin.activate.user']);
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie znaleziono zalogowanego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        try {
            $activate = $this->getPayload($request, $response, function($activate) {
                if(!is_int($activate)) {
                    throw new InputDataException('Przeslane dane sa nieprawidlowe');
                }
            });
        } catch (InputDataException $e) {
            return $response->withStatus(400, $e->getMessage());
        }
        $user = $this->getUserFromArgs($args);
        $user->setActivated((bool)$activate);
        $this->em->merge($user);
        $this->em->flush();
        return $response->withJson($user, 200);
    }

    public function getUserList(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie znaleziono zalogowanego uzytkownika');
        }
        $companyId = $args['companyId'] ?? null;
        if($companyId) {
            try {
                $this->checkPermissions($request, $user, 'manager.list.users');
                $users = $this->em->getRepository(User::class)->findBy(['company'=>$companyId]);
            } catch (AuthException $e) {
                return $response->withStatus(401, $e->getMessage());
            } catch (\Doctrine\DBAL\Types\ConversionException $e) {
                return $response->withStatus(400, 'Nieprawidlowy typ identyfikatora firmy');
            }
        } else {
            try {
                $this->checkPermissions($request, $user, 'manager.list.users');
                $users = $this->em->getRepository(User::class)->findAll();
            } catch (AuthException $e) {
                return $response->withStatus(401, $e->getMessage());
            }
        }
        return $response->withJson(['users' => $users], 200);
    }

    public function invite(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'manager.invite.user');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie mozesz zaprosic nowego uzytkownika');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)
            || !key_exists('email', $parsedBody)
            || !key_exists('name', $parsedBody)
            || !key_exists('surname', $parsedBody)
        ) {
            return $response->withStatus(400, 'Nie dostarczyles wszystkich wymaganych danych');
        }

        $foundUser = $this->em->getRepository(User::class)->findOneBy(['email'=>$parsedBody['email']]);
        if($foundUser!== null) {
            return $response->withStatus(400, 'Nie mozesz zaprosic juz istniejacego uzytkownika');
        }
        $company = $user->getCompany();
        $mail = new Message();
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]:8080/register?companyId=".$company->getId()->toString();
        $body = "Witaj ".$parsedBody['name'].' '.$parsedBody['surname']
            .'!<br />Chcialbym Cie zaprosic do dolaczenia do naszej firmy w systemie RTC Talker. '
            .'Aby to zrobic kliknij w <a href="'.$link.'" target="_blank">TEN LINK</a>.<br /><br />'
            .'Pozdrawiam serdecznie<br />'.$user->getName().' '.$user->getSurname();

        $mail->setFrom($user->getEmail(), $user->getName().' '.$user->getSurname())
            ->addTo($parsedBody['email'])
            ->setSubject('Zaproszenie do firmy '.$company->getName())
            ->setHTMLBody($body);
        try {
            $mailer = new SendmailMailer();
            $mailer->send($mail);
        } catch (\Exception $e) {
            return $response->withStatus(418, 'Mechanizm wysylania maili nie dziala prawidlowo');
        }
        return $response->withStatus(200, 'Zaproszenie zostalo wyslane');
    }

    private function getPayload(Http\Request $request, Http\Response $response, ?callable $checkFn = null) {
        if($checkFn === null) {
            $checkFn = function($input) : bool {return true;};
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody) || !key_exists('payload', $parsedBody)) {
            throw new InputDataException('Przeslane dane sa niekompletne');
        }
        $payload = $parsedBody['payload'];
        $checkFn($payload);
        return $payload;
    }
}