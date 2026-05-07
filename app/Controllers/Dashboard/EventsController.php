<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\Event;

class EventsController
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
        $response->view('events/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'events',
        ]);
    }

    public function data(Request $request, Response $response): void
    {
        $eventModel = new Event($this->db);

        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 25)));
        $offset = ($page - 1) * $limit;
        $eventType = $request->query('event_type');
        $userId = $request->query('user_id') ? (int) $request->query('user_id') : null;
        $riskLevel = $request->query('risk_level');
        $search = $request->query('search');

        $events = $eventModel->getAll($limit, $offset, $eventType, $userId, $riskLevel, $search);
        $total = $eventModel->count($eventType, $userId, $riskLevel, $search);

        $response->json([
            'events' => $events,
            'total'  => $total,
            'page'   => $page,
            'pages'  => ceil($total / $limit),
        ]);
    }
}
