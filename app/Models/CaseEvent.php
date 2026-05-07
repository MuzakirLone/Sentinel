<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class CaseEvent
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function addNote(int $caseId, string $note, ?int $adminUserId = null): int
    {
        return $this->db->insert('case_events', [
            'case_id'  => $caseId,
            'note'     => $note,
            'added_by' => $adminUserId,
        ]);
    }

    public function addEvent(int $caseId, int $eventId, ?int $adminUserId = null): int
    {
        return $this->db->insert('case_events', [
            'case_id'  => $caseId,
            'event_id' => $eventId,
            'added_by' => $adminUserId,
        ]);
    }

    public function getByCase(int $caseId): array
    {
        return $this->db->query(
            "SELECT ce.*, e.event_type, e.risk_score, e.created_at as event_created_at, u.display_name as added_by_name
             FROM case_events ce
             LEFT JOIN events e ON ce.event_id = e.id
             LEFT JOIN admin_users u ON ce.added_by = u.id
             WHERE ce.case_id = :case_id
             ORDER BY ce.created_at DESC",
            ['case_id' => $caseId]
        );
    }
}
