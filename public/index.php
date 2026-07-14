<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Database;
use App\Controllers\UrlController;
use App\Controllers\AnalyticsController;
use App\Controllers\RedirectController;

Database::migrate();

$router = new Router();

$router->get('/api/urls', function () {
    $controller = new UrlController();
    $controller->list();
});

$router->post('/api/shorten', function () {
    $controller = new UrlController();
    $controller->create();
});

$router->get('/api/urls/{id}', function (string $id) {
    $controller = new UrlController();
    $controller->show($id);
});

$router->delete('/api/urls/{id}', function (string $id) {
    $controller = new UrlController();
    $controller->delete($id);
});

$router->get('/api/stats/{code}', function (string $code) {
    $controller = new AnalyticsController();
    $controller->stats($code);
});

$router->get('/health', function () {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'timestamp' => date('c')]);
});

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (str_starts_with($uri, '/api/') || $uri === '/health') {
    $router->dispatch($method, $uri);
} else {
    $router->get('/{code}', function (string $code) {
        $controller = new RedirectController();
        $controller->redirect($code);
    });

    if ($uri === '/') {
        header('Content-Type: text/html');
        echo file_get_contents(__DIR__ . '/index.html');
    } else {
        $router->dispatch($method, $uri);
    }
}