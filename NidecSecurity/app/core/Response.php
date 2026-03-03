<?php

class Response {
    public function redirect(string $location, int $statusCode = 302): void {
        http_response_code($statusCode);
        header('Location: ' . $location);
        exit;
    }

    public function notFound(): void {
        http_response_code(404);
        require __DIR__ . '/../../public/404.php';
        exit;
    }
}
