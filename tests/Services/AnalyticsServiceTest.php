<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Core\Database;
use App\Services\AnalyticsService;
use App\Services\URLService;
use PHPUnit\Framework\TestCase;

class AnalyticsServiceTest extends TestCase
{
    private AnalyticsService $analyticsService;
    private URLService $urlService;

    protected function setUp(): void
    {
        $this->analyticsService = new AnalyticsService();
        $this->urlService = new URLService();

        Database::migrate();

        $pdo = Database::getConnection();
        $pdo->exec('DELETE FROM clicks');
        $pdo->exec('DELETE FROM urls');
    }

    public function testRecordClickIncreasesCount(): void
    {
        $url = $this->urlService->shorten('https://example.com');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';
        $_SERVER['HTTP_REFERER'] = 'https://google.com';

        $this->analyticsService->recordClick($url->getId());
        $this->analyticsService->recordClick($url->getId());

        $stats = $this->analyticsService->getStats($url->getId());
        $this->assertEquals(2, $stats['total_clicks']);
    }

    public function testGetStatsReturnsReferrers(): void
    {
        $url = $this->urlService->shorten('https://example.com');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        $_SERVER['HTTP_REFERER'] = 'https://google.com';

        $this->analyticsService->recordClick($url->getId());
        $this->analyticsService->recordClick($url->getId());

        $_SERVER['HTTP_REFERER'] = 'https://github.com';
        $this->analyticsService->recordClick($url->getId());

        $stats = $this->analyticsService->getStats($url->getId());
        $this->assertCount(2, $stats['referrers']);
    }
}