<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\ReviewItem;
use Sentinel\Models\User;

class ReviewController
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
        $response->view('review/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'review',
        ]);
    }

    public function data(Request $request, Response $response): void
    {
        $reviewModel = new ReviewItem($this->db);
        $status = $request->query('status', 'pending');

        $items = $reviewModel->getAll($status, 50, 0);
        $pending = $reviewModel->countByStatus('pending');

        $response->json([
            'items'   => $items,
            'pending' => $pending,
        ]);
    }

    public function action(Request $request, Response $response, int $id): void
    {
        $reviewModel = new ReviewItem($this->db);
        $userModel = new User($this->db);

        $action = $request->input('action', '');
        $notes = $request->input('notes', '');
        $adminUser = Auth::user();

        if (!in_array($action, ['approve', 'suspend', 'dismiss', 'block'])) {
            $response->json(['error' => 'Invalid action'], 400);
            return;
        }

        $reviewModel->takeAction($id, $adminUser['id'], $action, $notes);

        // Get the review item to find the user
        $review = $this->db->queryOne('SELECT * FROM review_queue WHERE id = :id', ['id' => $id]);
        if ($review) {
            switch ($action) {
                case 'approve':
                    $userModel->updateStatus($review['user_id'], 'active');
                    break;
                case 'suspend':
                    $userModel->updateStatus($review['user_id'], 'suspended');
                    break;
                case 'block':
                    $userModel->updateStatus($review['user_id'], 'blocked');
                    break;
                case 'dismiss':
                    $userModel->updateStatus($review['user_id'], 'active');
                    break;
            }
        }

        $response->json(['status' => 'ok', 'action' => $action]);
    }
}
