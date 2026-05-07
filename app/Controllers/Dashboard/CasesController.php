<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\CaseFile;
use Sentinel\Models\CaseEvent;
use Sentinel\Models\Event;

class CasesController
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
        $response->view('cases/index', [
            'config' => $this->config,
            'user'   => Auth::user(),
            'page'   => 'cases',
        ]);
    }

    public function data(Request $request, Response $response): void
    {
        $caseModel = new CaseFile($this->db);
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 25)));
        $offset = ($page - 1) * $limit;
        $status = $request->query('status');
        $search = $request->query('search');

        $cases = $caseModel->getAll($status, $limit, $offset, $search);
        $total = $caseModel->count($status, $search);

        $response->json([
            'cases' => $cases,
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    public function show(Request $request, Response $response, int $id): void
    {
        $caseModel = new CaseFile($this->db);
        $case = $caseModel->findById($id);

        if (!$case) {
            $response->redirect('/cases');
            return;
        }

        $review = null;
        if (!empty($case['review_item_id'])) {
            $review = $this->db->queryOne(
                'SELECT rq.*, u.external_id, u.email FROM review_queue rq LEFT JOIN users u ON rq.user_id = u.id WHERE rq.id = :id',
                ['id' => $case['review_item_id']]
            );
        }

        $events = [];
        if (!empty($case['user_id'])) {
            $eventModel = new Event($this->db);
            $events = $eventModel->getUserTimeline((int) $case['user_id'], 30);
        }

        $caseEventModel = new CaseEvent($this->db);
        $caseEvents = $caseEventModel->getByCase($id);

        $response->view('cases/show', [
            'config'     => $this->config,
            'user'       => Auth::user(),
            'page'       => 'cases',
            'case'       => $case,
            'review'     => $review,
            'events'     => $events,
            'caseEvents' => $caseEvents,
        ]);
    }

    public function createFromReview(Request $request, Response $response, int $reviewId): void
    {
        $caseModel = new CaseFile($this->db);
        $adminUser = Auth::user();

        $caseId = $caseModel->createFromReview($reviewId, $adminUser['id']);
        if (!$caseId) {
            $response->json(['error' => 'Review item not found'], 404);
            return;
        }

        $caseEventModel = new CaseEvent($this->db);
        $caseEventModel->addNote($caseId, 'Case created from alert queue.', $adminUser['id']);

        $response->json([
            'status' => 'ok',
            'case_id' => $caseId,
            'redirect' => '/cases/' . $caseId,
        ]);
    }

    public function resolve(Request $request, Response $response, int $id): void
    {
        $notes = trim((string) $request->input('notes', ''));
        $adminUser = Auth::user();

        $caseModel = new CaseFile($this->db);
        $caseModel->resolve($id, $adminUser['id'], $notes);

        $case = $caseModel->findById($id);
        if ($case && !empty($case['review_item_id'])) {
            $this->db->update('review_queue', [
                'status'       => 'resolved',
                'reviewed_by'  => $adminUser['id'],
                'reviewed_at'  => date('c'),
                'action_taken' => 'case_resolved',
                'notes'        => $notes,
                'updated_at'   => date('c'),
            ], ['id' => $case['review_item_id']]);
        }

        if ($notes !== '') {
            $caseEventModel = new CaseEvent($this->db);
            $caseEventModel->addNote($id, 'Resolution notes: ' . $notes, $adminUser['id']);
        }

        $response->json(['status' => 'ok']);
    }
}
