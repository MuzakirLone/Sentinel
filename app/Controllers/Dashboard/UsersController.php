<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\User;
use Sentinel\Services\UserTimelineService;

class UsersController
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
        $response->view('users/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'users',
        ]);
    }

    public function data(Request $request, Response $response): void
    {
        $userModel = new User($this->db);

        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 25)));
        $offset = ($page - 1) * $limit;
        $sort = $request->query('sort', 'risk_score');
        $order = $request->query('order', 'DESC');
        $search = $request->query('search');

        $users = $userModel->getAll($limit, $offset, $sort, $order, $search);
        $total = $userModel->count($search);

        $response->json([
            'users' => $users,
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    public function show(Request $request, Response $response, int $id): void
    {
        $userModel = new User($this->db);
        $user = $userModel->findById($id);

        if (!$user) {
            $response->redirect('/users');
            return;
        }

        $response->view('users/show', [
            'config'      => $this->config,
            'user'        => Auth::user(),
            'page'        => 'users',
            'trackedUser' => $user,
        ]);
    }

    public function timeline(Request $request, Response $response, int $id): void
    {
        $timelineService = new UserTimelineService($this->db);
        $payload = $timelineService->getFullTimeline($id);

        if (!$payload) {
            $response->json(['error' => 'User not found'], 404);
            return;
        }

        $response->json($payload);
    }
}
