<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class AuditEntry
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        return $this->db->insert('audit_trail', [
            'user_id'     => $data['user_id'] ?? null,
            'entity_type' => $data['entity_type'],
            'entity_id'   => $data['entity_id'],
            'field_name'  => $data['field_name'],
            'old_value'   => $data['old_value'] ?? null,
            'new_value'   => $data['new_value'] ?? null,
            'changed_by'  => $data['changed_by'] ?? null,
            'ip_address'  => $data['ip_address'] ?? null,
        ]);
    }

    public function getAll(int $limit = 50, int $offset = 0, ?int $userId = null): array
    {
        $where = '';
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($userId) {
            $where = 'WHERE a.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        return $this->db->query(
            "SELECT a.*, u.external_id, u.email
             FROM audit_trail a
             LEFT JOIN users u ON a.user_id = u.id
             {$where}
             ORDER BY a.created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function count(?int $userId = null): int
    {
        if ($userId) {
            return (int) $this->db->queryScalar(
                'SELECT COUNT(*) FROM audit_trail WHERE user_id = :user_id',
                ['user_id' => $userId]
            );
        }
        return (int) $this->db->queryScalar('SELECT COUNT(*) FROM audit_trail');
    }
}
