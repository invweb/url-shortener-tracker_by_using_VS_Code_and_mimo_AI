<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\UrlNotFoundException;
use App\Services\AnalyticsService;
use App\Services\URLService;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;
    private URLService $urlService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
        $this->urlService = new URLService();
    }

    public function stats(string $code): void
    {
        try {
            $urlModel = $this->urlService->resolve($code);
            $stats = $this->analyticsService->getStats($urlModel->getId());

            $this->json([
                'url' => $urlModel->toArray(),
                'stats' => $stats,
            ]);
        } catch (UrlNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }
}