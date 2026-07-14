<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Click;

class AnalyticsService
{
    public function recordClick(int $urlId): void
    {
        Click::record(
            urlId: $urlId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
            userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
            referrer: $_SERVER['HTTP_REFERER'] ?? null
        );
    }

    public function getStats(int $urlId): array
    {
        return Click::getStatsByUrlId($urlId);
    }
}