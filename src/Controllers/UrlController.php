<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\InvalidUrlException;
use App\Exceptions\UrlNotFoundException;
use App\Services\URLService;

class UrlController extends Controller
{
    private URLService $urlService;

    public function __construct()
    {
        $this->urlService = new URLService();
    }

    public function create(): void
    {
        try {
            $url = $this->input('url');
            $customAlias = $this->input('custom_alias');

            if (empty($url)) {
                $this->json(['error' => 'URL is required'], 400);
                return;
            }

            $urlModel = $this->urlService->shorten($url, $customAlias);

            $this->json([
                'message' => 'URL shortened successfully',
                'data' => $urlModel->toArray(),
            ], 201);
        } catch (InvalidUrlException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $code): void
    {
        try {
            $urlModel = $this->urlService->resolve($code);
            $this->json(['data' => $urlModel->toArray()]);
        } catch (UrlNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->urlService->delete((int) $id);
            $this->json(['message' => 'URL deleted successfully']);
        } catch (UrlNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function list(): void
    {
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM urls ORDER BY created_at DESC LIMIT 50');
        $urls = $stmt->fetchAll();

        $this->json(['data' => $urls]);
    }
}