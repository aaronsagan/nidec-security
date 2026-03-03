<?php

class Request {
    public function method(): string {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * Without an Apache rewrite, front-controller routing is driven by `?r=/path`.
     * If `r` is missing and the request ends in `index.php`, treat it as `/`.
     */
    public function path(): string {
        $path = $_GET['r'] ?? null;
        if ($path === null || $path === '') {
            $uriPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
            $uriPath = $uriPath === '' ? '/' : $uriPath;
            if (preg_match('#/index\.php$#i', $uriPath)) {
                $path = '/';
            } else {
                $path = $uriPath;
            }
        }

        $path = '/' . trim((string)$path, '/');
        return $path === '//' ? '/' : $path;
    }
}
