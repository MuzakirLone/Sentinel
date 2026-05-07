<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\Integration;

class IntegrationsController
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
        $integrationModel = new Integration($this->db);
        $integrations = $integrationModel->getAll();

        foreach ($integrations as &$integration) {
            $integration['config'] = $this->decodeConfig($integration['config'] ?? null);
        }

        $response->view('integrations/index', [
            'config'       => $this->config,
            'user'         => Auth::user(),
            'page'         => 'integrations',
            'integrations' => $integrations,
        ]);
    }

    public function toggle(Request $request, Response $response, int $id): void
    {
        $integrationModel = new Integration($this->db);
        $enabled = $integrationModel->toggle($id);

        if ($enabled === null) {
            $response->json(['error' => 'Integration not found'], 404);
            return;
        }

        $response->json([
            'status'  => 'ok',
            'enabled' => $enabled,
        ]);
    }

    public function update(Request $request, Response $response, int $id): void
    {
        $integrationModel = new Integration($this->db);
        $integration = $integrationModel->findById($id);

        if (!$integration) {
            $response->json(['error' => 'Integration not found'], 404);
            return;
        }

        $config = $this->decodeConfig($integration['config'] ?? null);
        $payload = (array) $request->input('config', []);

        foreach ($payload as $key => $value) {
            $config[$key] = $value;
        }

        $integrationModel->updateConfig($id, $config);

        $response->json(['status' => 'ok']);
    }

    private function decodeConfig(?string $config): array
    {
        if (!$config) {
            return [];
        }

        $decoded = json_decode($config, true);
        return is_array($decoded) ? $decoded : [];
    }
}
