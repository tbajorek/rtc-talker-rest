<?php
// Application middleware

use function RtcTalker\Utility\getSecret;

$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secret" => getSecret('../secret.key'),
    "secure" => getenv('DEV_MODE') == 1,
    "path" => ['/user', '/company', '/users', '/departments', '/companies'],
    "ignore" => ["/user/account", "/user/session", "/options", "/rates"],
    "logger" => $app->getContainer()->get('logger'),
    "algorithm" => ["HS256"]
]));

$app->add(new RKA\Middleware\IpAddress());

// CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
