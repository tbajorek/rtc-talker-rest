<?php

namespace RtcTalker\Controller;

use RtcTalker\Exception\AuthException;
use RtcTalker\Exception\NotFoundException;
use RtcTalker\Model\Company;
use RtcTalker\Model\Department;
use RtcTalker\Model\User;
use Slim\Http;

class DepartmentController extends AbstractController
{
    public function getDepartments(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $company = $this->getCompany($request, $response, $args, 'user.view.company.departments', 'admin.view.company.departments');
        if(!($company instanceof Company)) {
            return $company;
        }
        $departments = $company->getDepartments();
        if(count($departments->getKeys()) < 1) {
            return $response->withStatus(404, 'Zaden departament nie istnieje dla Twojej firmy');
        }
        return $response->withJson(['companyId' => $company->getId()->toString(), 'departments' => $departments->getValues()], 200);
    }

    public function createDepartment(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $company = $this->getCompany($request, $response, $args, 'manager.department.create', 'admin.department.create');
        if(!($company instanceof Company)) {
            return $company;
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody) || !key_exists('name', $parsedBody)) {
            return $response->withStatus(400, 'Nie podales wszystkich potrzebnych danych');
        }
        $newDepartment = new Department();
        $newDepartment->setName($parsedBody['name'])
                      ->setCompany($company);
        $workers = [];
        if(key_exists('workers', $parsedBody) && is_array($parsedBody['workers'])) {
            foreach ($parsedBody['workers'] as $workerId) {
                $worker = $this->em->getRepository(User::class)->find($workerId);
                if($worker !== null) {
                    $workers[count($workers)] = $worker;
                    $worker->addDepartment($newDepartment);
                }
            }
            $newDepartment->setWorkers($workers);
        }
        foreach ($workers as $worker) {
            $this->em->merge($worker);
        }
        $company->addDepartment($newDepartment);
        $this->em->persist($newDepartment);
        $this->em->merge($company);
        $this->em->flush();
        return $response->withJson($newDepartment, 201);
    }

    public function updateDepartment(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $company = $this->getCompany($request, $response, $args, 'manager.department.update', 'admin.department.update');
        if(!($company instanceof Company)) {
            return $company;
        }
        $departmentId = $args['departmentId'];
        $department = $this->em->getRepository(Department::class)->find($departmentId);
        if($department === null) {
            return $response->withStatus(404, 'Departament nie istnieje');
        }
        $parsedBody = $request->getParsedBody();
        if(!is_array($parsedBody)) {
            return $response->withStatus(400, 'Nie podales wszystkich potrzebnych danych');
        }
        if(key_exists('name', $parsedBody)) {
            $department->setName($parsedBody['name']);
        }
        if(key_exists('workers', $parsedBody) && is_array($parsedBody['workers'])) {
            $workers = [];
            foreach ($parsedBody['workers'] as $workerId) {
                $worker = $this->em->getRepository(User::class)->find($workerId);
                if($worker !== null) {
                    $workers[count($workers)] = $worker;
                    $worker->addDepartment($department);
                    $this->em->merge($worker);
                }
            }
            $department->setWorkers($workers);
        }
        $this->em->merge($department);
        $this->em->flush();
        return $response->withJson($department, 201);
    }

    public function removeDepartment(Http\Request $request, Http\Response $response, array $args): Http\Response {
        $company = $this->getCompany($request, $response, $args, 'manager.department.update', 'admin.department.update');
        if(!($company instanceof Company)) {
            return $company;
        }

        $departmentId = $args['departmentId'];
        $department = $this->em->getRepository(Department::class)->find($departmentId);
        if($department === null) {
            return $response->withStatus(404, 'Departament nie istnieje');
        }
        $this->em->remove($department);
        $this->em->flush();
        return $response->withStatus(204, 'Departament zostal usuniety');
    }

    private function getCompany(Http\Request $request, Http\Response $response, array $args, $lowPermissions, $highPermissions) {
        try {
            $user = $this->getUserFromToken($request);
        } catch (NotFoundException $e) {
            return $response->withStatus(404, 'Nie mozesz wykonac tej akcji');
        }
        $userCompany = $user->getCompany();
        if($userCompany === null) {
            return $response->withStatus(404, 'Firma uzytkownika nie istnieje');
        }
        $requestedCompanyId = $args['companyId'];
        try {
            if($userCompany->getId()->toString() === $requestedCompanyId) {
                $this->checkPermissions($request, $user, $lowPermissions);
            } else {
                $this->checkPermissions($request, $user, $highPermissions);
            }
        } catch (AuthException $e) {
            return $response->withStatus(401, $e->getMessage());
        }
        $company = $this->em->getRepository(Company::class)->find($requestedCompanyId);
        if($company === null) {
            return $response->withStatus(404, 'Firma nie istnieje');
        }
        return $company;
    }
}