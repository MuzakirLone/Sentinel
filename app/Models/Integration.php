<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class Integration
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM integrations ORDER BY name');
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM integrations WHERE id = :id', ['id' => $id]);
    }

    public function toggle(int $id): ?bool
    {
        $integration = $this->findById($id);
        if (!$integration) {
            return null;
        }

        $newStatus = $integration['status'] === 'enabled' ? 'disabled' : 'enabled';
        $this->db->update('integrations', [
            'status'     => $newStatus,
            'updated_at' => date('c'),
        ], ['id' => $id]);

        return $newStatus === 'enabled';
    }

    public function updateConfig(int $id, array $config): void
    {
        $this->db->update('integrations', [
            'config'     => json_encode($config),
            'updated_at' => date('c'),
        ], ['id' => $id]);
    }
}
