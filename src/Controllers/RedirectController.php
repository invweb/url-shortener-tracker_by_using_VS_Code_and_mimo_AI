<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\UrlNotFoundException;
use App\Services\AnalyticsService;
use App\Services\URLService;

class RedirectController
{
    private URLService $urlService;
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        $this->urlService = new URLService();
        $this->analyticsService = new AnalyticsService();
    }

    public function redirect(string $code): void
    {
        try {
            $urlModel = $this->urlService->resolve($code);

            $this->analyticsService->recordClick($urlModel->getId());

            header('Location: ' . $urlModel->getOriginalUrl(), true, 302);
            exit;
        } catch (UrlNotFoundException $e) {
            http_response_code(404);
            header('Content-Type: text/html');
            echo '<h1>404 - URL Not Found</h1>';
            echo '<p>The short URL you requested does not exist or has expired.</p>';
            echo '<a href="/">Create a new short URL</a>';
        }
    }
}