<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Click
{
    public function __construct(
        private int $urlId,
        private ?string $ipAddress,
        private ?string $userAgent,
        private ?string $referrer,
        private ?int $id = null,
        private ?string $createdAt = null
    ) {}

    public static function record(
        int $urlId,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $referrer
    ): self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO clicks (url_id, ip_address, user_agent, referrer) 
             VALUES (:url_id, :ip_address, :user_agent, :referrer)'
        );
        $stmt->execute([
            'url_id' => $urlId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
        ]);

        return new self(
            urlId: $urlId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            referrer: $referrer,
            id: (int) $pdo->lastInsertId()
        );
    }

    public static function getCountByUrlId(int $urlId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clicks WHERE url_id = :url_id');
        $stmt->execute(['url_id' => $urlId]);
        return (int) $stmt->fetchColumn();
    }

    public static function getStatsByUrlId(int $urlId): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            'SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_visitors
             FROM clicks WHERE url_id = :url_id'
        );
        $stmt->execute(['url_id' => $urlId]);
        $basic = $stmt->fetch();

        $stmt = $pdo->prepare(
            'SELECT referrer, COUNT(*) as count 
             FROM clicks 
             WHERE url_id = :url_id AND referrer IS NOT NULL AND referrer != \'\'
             GROUP BY referrer 
             ORDER BY count DESC 
             LIMIT 10'
        );
        $stmt->execute(['url_id' => $urlId]);
        $referrers = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
             FROM clicks 
             WHERE url_id = :url_id
             GROUP BY DATE(created_at)
             ORDER BY date DESC
             LIMIT 30'
        );
        $stmt->execute(['url_id' => $urlId]);
        $dailyClicks = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT user_agent, COUNT(*) as count
             FROM clicks 
             WHERE url_id = :url_id AND user_agent IS NOT NULL
             GROUP BY user_agent
             ORDER BY count DESC
             LIMIT 10'
        );
        $stmt->execute(['url_id' => $urlId]);
        $userAgents = $stmt->fetchAll();

        return [
            'total_clicks' => (int) $basic['total_clicks'],
            'unique_visitors' => (int) $basic['unique_visitors'],
            'referrers' => $referrers,
            'daily_clicks' => $dailyClicks,
            'user_agents' => $userAgents,
        ];
    }
}