<?php

namespace Sentinel\Models;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;

class ApiKey
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(string $label, int $adminUserId): string
    {
        $rawKey = Auth::generateApiKey();
        $keyHash = Auth::hashApiKey($rawKey);
        $keyPrefix = substr($rawKey, 0, 10) . '...';

        $this->db->insert('api_keys', [
            'key_hash'      => $keyHash,
            'key_prefix'    => $keyPrefix,
            'label'         => $label,
            'admin_user_id' => $adminUserId,
        ]);

        return $rawKey; // Return raw key only once
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT ak.*, au.display_name as created_by_name
             FROM api_keys ak
             LEFT JOIN admin_users au ON ak.admin_user_id = au.id
             ORDER BY ak.created_at DESC'
        );
    }

    public function revoke(int $id): void
    {
        $key = $this->db->queryOne('SELECT key_hash FROM api_keys WHERE id = :id', ['id' => $id]);
        if ($key) {
            $this->db->update('api_keys', ['is_active' => 'false'], ['id' => $id]);
            \Sentinel\Core\Cache::delete('api_key:' . $key['key_hash']);
        }
    }
}
