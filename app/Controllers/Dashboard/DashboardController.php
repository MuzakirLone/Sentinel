<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Services\DashboardMetricsService;

class DashboardController
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
        $response->view('dashboard/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'dashboard',
        ]);
    }

    /**
     * GET /dashboard/stats — Returns JSON stats for AJAX polling.
     */
    public function stats(Request $request, Response $response): void
    {
        $metricsService = new DashboardMetricsService($this->db, $this->config);
        $response->json($metricsService->getSnapshotStats());
    }
}
