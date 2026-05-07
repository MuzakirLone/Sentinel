<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\Rule;

class RulesController
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
        $ruleModel = new Rule($this->db);
        $rules = $ruleModel->getAll();
        $triggerCounts = $ruleModel->getTriggeredCountByRule();

        $response->view('rules/index', [
            'config'        => $this->config,
            'user'          => Auth::user(),
            'page'          => 'rules',
            'rules'         => $rules,
            'triggerCounts' => $triggerCounts,
        ]);
    }

    public function toggle(Request $request, Response $response, int $id): void
    {
        $ruleModel = new Rule($this->db);
        $newState = $ruleModel->toggle($id);

        $response->json([
            'status'  => 'ok',
            'enabled' => $newState,
        ]);
    }

    public function updateWeight(Request $request, Response $response, int $id): void
    {
        $weight = (float) $request->input('weight', 1.0);
        $ruleModel = new Rule($this->db);
        $ruleModel->updateWeight($id, $weight);

        $response->json(['status' => 'ok', 'weight' => $weight]);
    }
}
