<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class URL
{
    private ?int $id;
    private string $originalUrl;
    private string $shortCode;
    private ?string $customAlias;
    private ?string $createdAt;
    private ?string $expiresAt;

    public function __construct(
        string $originalUrl,
        string $shortCode,
        ?string $customAlias = null,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $expiresAt = null
    ) {
        $this->id = $id;
        $this->originalUrl = $originalUrl;
        $this->shortCode = $shortCode;
        $this->customAlias = $customAlias;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function getCustomAlias(): ?string
    {
        return $this->customAlias;
    }

    public function getDisplayCode(): string
    {
        return $this->customAlias ?? $this->shortCode;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return new \DateTime($this->expiresAt) < new \DateTime();
    }

    public static function findByShortCode(string $code): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM urls WHERE short_code = :code OR custom_alias = :code'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new self(
            originalUrl: $row['original_url'],
            shortCode: $row['short_code'],
            customAlias: $row['custom_alias'],
            id: (int) $row['id'],
            createdAt: $row['created_at'],
            expiresAt: $row['expires_at']
        );
    }

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new self(
            originalUrl: $row['original_url'],
            shortCode: $row['short_code'],
            customAlias: $row['custom_alias'],
            id: (int) $row['id'],
            createdAt: $row['created_at'],
            expiresAt: $row['expires_at']
        );
    }

    public function save(): self
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO urls (original_url, short_code, custom_alias, expires_at) 
                 VALUES (:original_url, :short_code, :custom_alias, :expires_at)'
            );
            $stmt->execute([
                'original_url' => $this->originalUrl,
                'short_code' => $this->shortCode,
                'custom_alias' => $this->customAlias,
                'expires_at' => $this->expiresAt,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE urls SET original_url = :original_url, custom_alias = :custom_alias, 
                 expires_at = :expires_at WHERE id = :id'
            );
            $stmt->execute([
                'original_url' => $this->originalUrl,
                'custom_alias' => $this->customAlias,
                'expires_at' => $this->expiresAt,
                'id' => $this->id,
            ]);
        }

        return $this;
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM urls WHERE id = :id');
        return $stmt->execute(['id' => $this->id]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_url' => $this->originalUrl,
            'short_code' => $this->shortCode,
            'custom_alias' => $this->customAlias,
            'display_code' => $this->getDisplayCode(),
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}