<?php

// Expected vars: $router (Router), $request (Request), $response (Response)
require_once __DIR__ . '/../app/controllers/AuthController.php';

$router->get('/', function (Request $request, Response $response): void {
    // Preserve legacy behavior of public/index.php
    if (isset($_SESSION['user'])) {
        $response->redirect('dashboard.php');
    }
    $response->redirect('login.php');
});

// Parallel routes (not used by existing URLs; no rewrite yet)
$router->get('/login', function (Request $request, Response $response): void {
    (new AuthController())->login();
});

$router->post('/login', function (Request $request, Response $response): void {
    (new AuthController())->login();
});

$router->get('/logout', function (Request $request, Response $response): void {
    (new AuthController())->logout();
});
