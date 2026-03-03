<?php

class IndexController
{
    public function dispatch(): void
    {
        // Phase 1: Front Controller runs in parallel.
        // No Apache rewrite yet; existing public/*.php endpoints remain canonical.
        require_once __DIR__ . '/../../includes/config.php';
        require_once __DIR__ . '/../../app/core/Router.php';

        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);

        require __DIR__ . '/../../routes/web.php';

        $router->dispatch();
    }
}
