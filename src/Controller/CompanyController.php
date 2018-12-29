<?php

namespace RtcTalker\Controller;

use RtcTalker\Exception\InputDataException;
use RtcTalker\Model\Address;
use RtcTalker\Model\Availability;
use RtcTalker\Model\Company;
use RtcTalker\Model\OpenedTalk;
use Slim\Http;
use RtcTalker\Exception\NotFoundException;
use RtcTalker\Exception\AuthException;

class CompanyController extends AbstractController {
    public function myCompany(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'user.view.my.company');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie możesz widzieć danych tej firmy');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $company = $user->getCompany();
        if($company === null) {
            return $response->withStatus(404, 'Firma nie istnieje');
        }
        return $response->withJson($company, 200);
    }

    public function profile(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $companyId = null;
        try {
            $user = $this->getUserFromToken($request);
            $companyId = $args['companyId'];
            if($user->getCompany()->getId()->toString() === $companyId) {
                return $this->myCompany($request, $response, $args);
            }
            $this->checkPermissions($request, $user, 'admin.view.company');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie możesz widzieć danych tej firmy');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $company = $this->em->getRepository(Company::class)->find($companyId);
        if($company === null) {
            return $response->withStatus(404, 'Firma nie istnieje');
        }
        return $response->withJson($company, 200);
    }

    public function create(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'company.create');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie masz uprawnień do dodania firmy');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody) || !key_exists('name', $parsedBody) || !key_exists('nip', $parsedBody) ||!key_exists('address', $parsedBody)) {
            return $response->withStatus(400, 'Nie podałeś wszystkich wymaganych danych');
        }
        $newCompany = new Company();
        $newCompany->setName($parsedBody['name'])
                   ->setNip($parsedBody['nip'])
                   ->setWorkers([$user])
                   ->setActivated(false);
        $newAddress = Address::createFromRawData($parsedBody['address']);
        $newCompany->setAddress($newAddress);
        $user->setCompany($newCompany);
        $this->em->merge($user);
        $this->em->persist($newCompany);
        $this->em->flush();
        return $response->withJson($newCompany, 201);
    }

    public function update(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'company.update');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie możesz zmieniać danych tej firmy');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $company = $user->getCompany();
        if($company === null) {
            return $response->withStatus(404, 'Nie posiadasz swojej firmy');
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)) {
            return $response->withStatus(400, 'Nie podałeś wszystkich wymaganych danych');
        }
        if(key_exists('name', $parsedBody)) {
            $company->setName($parsedBody['name']);
        }
        if(key_exists('nip', $parsedBody)) {
            $company->setNip($parsedBody['nip']);
        }
        if(key_exists('address', $parsedBody)) {
            $address = $company->getAddress();
            $address->importFromRawData($parsedBody['address']);
            $this->em->merge($address);
        }
        $this->em->merge($company);
        $this->em->flush();
        return $response->withJson($company, 200);
    }

    public function getAll(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'admin.view.all.companies');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie możesz widzieć wszystkich firm');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $companies = $this->em->getRepository(Company::class)->findAll();
        if(count($companies) === 0) {
            return $response->withStatus(404, 'Firma nie została znaleziona');
        }
        return $response->withJson(['companies'=>array_map(function (Company $company) : array {return $company->jsonSerializeMore();}, $companies)], 200);
    }

    public function activate(Http\Request $request, Http\Response $response, array $args): Http\Response {
        try {
            $user = $this->getUserFromToken($request);
            $this->checkPermissions($request, $user, 'admin.activate.company');
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie możesz zmienić dostępności dla tej firmy');
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        try {
            $activate = $this->getPayload($request, $response, function($activate) {
                if(!is_int($activate)) {
                    throw new InputDataException('Dane są niepoprawne');
                }
            });
        } catch (InputDataException $e) {
            return $response->withStatus(400, $e->getMessage());
        }
        $companyId = $args['companyId'];
        $company = $this->em->getRepository(Company::class)->find($companyId);
        if($company === null) {
            return $response->withStatus(404, 'Firma nie istnieje');
        }
        $company->setActivated((bool)$activate);
        $this->em->merge($company);
        $this->em->flush();
        return $response->withJson($company, 200);
    }

    public function getPayload(Http\Request $request, Http\Response $response, ?callable $checkFn = null) {
        if($checkFn === null) {
            $checkFn = function($input) : bool {return true;};
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody) || !key_exists('payload', $parsedBody)) {
            throw new InputDataException('Twoje zapytanie nie zawiera potrzebnych danych');
        }
        $payload = $parsedBody['payload'];
        $checkFn($payload);
        return $payload;
    }
}