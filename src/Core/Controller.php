<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $body = json_decode(file_get_contents('php://input'), true);
        return $body[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}