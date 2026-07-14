<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidUrlException;
use App\Exceptions\UrlNotFoundException;
use App\Models\URL;

class URLService
{
    private const CODE_LENGTH = 6;
    private const BASE62_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function shorten(string $url, ?string $customAlias = null): URL
    {
        $this->validateUrl($url);

        if ($customAlias !== null) {
            $this->validateAlias($customAlias);
            $existing = URL::findByShortCode($customAlias);
            if ($existing !== null) {
                throw new InvalidUrlException("Custom alias '{$customAlias}' is already taken");
            }
        }

        $shortCode = $customAlias ?? $this->generateUniqueCode();

        $urlModel = new URL(
            originalUrl: $url,
            shortCode: $shortCode,
            customAlias: $customAlias
        );

        return $urlModel->save();
    }

    public function resolve(string $code): URL
    {
        $url = URL::findByShortCode($code);

        if ($url === null) {
            throw new UrlNotFoundException("URL with code '{$code}' not found");
        }

        if ($url->isExpired()) {
            throw new UrlNotFoundException("URL with code '{$code}' has expired");
        }

        return $url;
    }

    public function getById(int $id): URL
    {
        $url = URL::findById($id);

        if ($url === null) {
            throw new UrlNotFoundException("URL with ID {$id} not found");
        }

        return $url;
    }

    public function delete(int $id): bool
    {
        $url = $this->getById($id);
        return $url->delete();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = $this->generateCode();
        } while (URL::findByShortCode($code) !== null);

        return $code;
    }

    private function generateCode(): string
    {
        $code = '';
        $maxIndex = strlen(self::BASE62_CHARS) - 1;

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::BASE62_CHARS[random_int(0, $maxIndex)];
        }

        return $code;
    }

    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException("Invalid URL format");
        }

        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new InvalidUrlException("URL must include scheme and host");
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new InvalidUrlException("URL scheme must be http or https");
        }
    }

    private function validateAlias(string $alias): void
    {
        if (strlen($alias) < 3 || strlen($alias) > 50) {
            throw new InvalidUrlException("Custom alias must be between 3 and 50 characters");
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $alias)) {
            throw new InvalidUrlException("Custom alias can only contain letters, numbers, underscores, and hyphens");
        }

        $reserved = ['api', 'admin', 'stats', 'health'];
        if (in_array(strtolower($alias), $reserved)) {
            throw new InvalidUrlException("This alias is reserved");
        }
    }
}