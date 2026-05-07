<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\ApiKey;

class SettingsController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function index(Request $request, Response $response): void
    {
        $apiKeyModel = new ApiKey($this->db);
        $apiKeys = $apiKeyModel->getAll();

        $response->view('settings/index', [
            'config'  => $this->config,
            'user'    => Auth::user(),
            'page'    => 'settings',
            'apiKeys' => $apiKeys,
        ]);
    }

    public function createApiKey(Request $request, Response $response): void
    {
        $label = trim($request->input('label', ''));
        if (empty($label)) {
            $response->json(['error' => 'Label is required'], 400);
            return;
        }

        $apiKeyModel = new ApiKey($this->db);
        $adminUser = Auth::user();
        $rawKey = $apiKeyModel->create($label, $adminUser['id']);

        $response->json([
            'status' => 'ok',
            'key'    => $rawKey,
            'message' => 'Copy this key now. It will not be shown again.',
        ]);
    }

    public function revokeApiKey(Request $request, Response $response, int $id): void
    {
        $apiKeyModel = new ApiKey($this->db);
        $apiKeyModel->revoke($id);

        $response->json(['status' => 'ok']);
    }
}
