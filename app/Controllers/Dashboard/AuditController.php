<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\AuditEntry;

class AuditController
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
        $response->view('audit/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'audit',
        ]);
    }

    public function data(Request $request, Response $response): void
    {
        $auditModel = new AuditEntry($this->db);

        $page = max(1, (int) $request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;
        $userId = $request->query('user_id') ? (int) $request->query('user_id') : null;

        $entries = $auditModel->getAll($limit, $offset, $userId);
        $total = $auditModel->count($userId);

        $response->json([
            'entries' => $entries,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $limit),
        ]);
    }
}
