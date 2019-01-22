<?php

use Slim\Http\Request;
use Slim\Http\Response;

use RtcTalker\Controller\UserController;
use RtcTalker\Controller\ProfileController;
use RtcTalker\Controller\CompanyController;
use RtcTalker\Controller\DepartmentController;
use RtcTalker\Controller\AddressController;
use RtcTalker\Controller\OptionsController;
use RtcTalker\Controller\RateController;

// Routes
$app->post('/user/account', UserController::class.':register');//registration
$app->post('/user/session', UserController::class.':login');//log in
$app->delete('/user/{userId}/session', UserController::class.':logout');//logout
$app->put('/user/{userId}/availability', UserController::class.':availability');//check availability
$app->put('/user/{userId}/activated', UserController::class.':activate');//change activated flag for user
$app->put('/user/{userId}/departments', UserController::class.':departments');//change departments of user
$app->get('/users[/{companyId}]', UserController::class.':getUserList');//list all users or workers of the company

$app->get('/user/me/company', CompanyController::class.':myCompany');//view own company
$app->post('/user/me/company', CompanyController::class.':create');//create own data
$app->put('/user/me/company', CompanyController::class.':update');//update own company data
$app->get('/company/{companyId}', CompanyController::class.':profile');//view other's company
$app->put('/company/{companyId}/activated', CompanyController::class.':activate');//activate a company
$app->get('/companies', CompanyController::class.':getAll');//view all companies

$app->get('/user/{userId}/profile', ProfileController::class.':profile');//view other's profile
$app->get('/user/me', ProfileController::class.':myProfile');//view own profile
$app->put('/user/me', ProfileController::class.':update');//update own profile

$app->post('/user/me/address', AddressController::class.':createForUser');//create address for myself
$app->put('/user/me/address', AddressController::class.':updateForUser');//update address for myself

$app->get('/company/{companyId}/departments', DepartmentController::class.':getDepartments');//view departments of a company
$app->post('/company/{companyId}/departments', DepartmentController::class.':createDepartment');//create department of a company
$app->put('/company/{companyId}/departments/{departmentId}', DepartmentController::class.':updateDepartment');//update data of department of a company (including workers)
$app->delete('/company/{companyId}/departments/{departmentId}', DepartmentController::class.':removeDepartment');//remote department of a company

$app->put('/user/me/online', UserController::class.':online');//set online status - for debugging

$app->get('/options/{companyId}', OptionsController::class.':options');//get options for a site
$app->get('/options/{companyId}/departments/{departmentId}', OptionsController::class.':users');//list of users available for the department
$app->get('/options/{companyId}/departments/{departmentId}/types/{type}', OptionsController::class.':chooseUser');//choose user which meets the criteria

$app->post('/rates/{userId}', RateController::class.':rate');//rate a user after talk

//route for enabling CORS
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});