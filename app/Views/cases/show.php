<?php /** @var array $case */ /** @var array|null $review */ /** @var array $events */ /** @var array $caseEvents */ ?>
<?php
    $status = $case['status'] ?? 'open';
    $priority = $case['priority'] ?? 'medium';
    $slaDue = $case['sla_due_at'] ?? null;
    $slaBreached = $slaDue && in_array($status, ['open', 'in_progress'], true) && strtotime($slaDue) < time();

    $timeline = [];
    foreach ($events as $event) {
        $timeline[] = [
            'type' => 'event',
            'timestamp' => $event['created_at'],
            'event' => $event,
        ];
    }
    foreach ($caseEvents as $note) {
        $timeline[] = [
            'type' => 'note',
            'timestamp' => $note['created_at'],
            'note' => $note,
        ];
    }

    usort($timeline, function($a, $b) {
        return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
    });
?>
<div id="case-detail-page" data-case-id="<?= $case['id'] ?>">
    <div class="page-header">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
            <div>
                <h2><?= htmlspecialchars($case['title']) ?></h2>
                <div class="subtitle">Case file for alert escalation and response tracking</div>
            </div>
            <?php if ($status !== 'resolved'): ?>
                <button class="btn btn-primary" data-modal-open="resolve-case-modal">Resolve Case</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-body">
        <div class="card" style="margin-bottom:16px">
            <div class="card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
                <div>
                    <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Priority</div>
                    <div style="margin-top:6px"><span class="badge badge-<?= htmlspecialchars($priority) ?>"><?= htmlspecialchars($priority) ?></span></div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Status</div>
                    <div style="margin-top:6px"><span class="badge badge-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(str_replace('_', ' ', $status)) ?></span></div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Assignee</div>
                    <div style="margin-top:6px;font-size:0.9rem;color:var(--text-primary)"><?= htmlspecialchars($case['assigned_to_name'] ?? 'Unassigned') ?></div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">SLA Due</div>
                    <div style="margin-top:6px;font-size:0.85rem;color:<?= $slaBreached ? 'var(--risk-critical)' : 'var(--text-primary)' ?>">
                        <?= $slaDue ? date('M j, H:i', strtotime($slaDue)) : '—' ?>
                        <?php if ($slaBreached): ?>
                            <span class="badge badge-critical" style="margin-left:6px">Breached</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <h3>Case Summary</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary);font-size:0.9rem">
                    <?= htmlspecialchars($case['summary'] ?? 'No summary available.') ?>
                </p>
                <?php if (!empty($case['resolution_notes'])): ?>
                    <div style="margin-top:12px;padding:10px 12px;border-radius:10px;background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.2)">
                        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px">Resolution Notes</div>
                        <div style="font-size:0.85rem;color:var(--text-secondary)"><?= htmlspecialchars($case['resolution_notes']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($review)): ?>
                    <div style="margin-top:16px;padding:12px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--border-glass)">
                        <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.08em;margin-bottom:6px">Alert Context</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:6px">
                            <span class="badge badge-<?= htmlspecialchars($review['priority'] ?? 'medium') ?>"><?= htmlspecialchars($review['priority'] ?? 'medium') ?></span>
                            <span class="badge badge-<?= htmlspecialchars($review['status'] ?? 'pending') ?>"><?= htmlspecialchars($review['status'] ?? 'pending') ?></span>
                            <span style="font-size:0.8rem;color:var(--text-secondary)">User: <?= htmlspecialchars($review['external_id'] ?? 'Unknown') ?></span>
                        </div>
                        <div style="font-size:0.85rem;color:var(--text-secondary)"><?= htmlspecialchars($review['reason'] ?? '') ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Evidence Timeline</h3>
                <span style="font-size:0.75rem;color:var(--text-muted)">Latest events and analyst notes</span>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php if (empty($timeline)): ?>
                        <div class="empty-state" style="padding:24px">No evidence recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($timeline as $item): ?>
                            <?php if ($item['type'] === 'event'): ?>
                                <?php $event = $item['event']; ?>
                                <?php $riskClass = ($event['risk_score'] ?? 0) >= 60 ? 'risk-critical' : (($event['risk_score'] ?? 0) >= 30 ? 'risk-high' : ''); ?>
                                <div class="timeline-item <?= $riskClass ?>">
                                    <div class="timeline-time"><?= date('M j, H:i', strtotime($event['created_at'])) ?></div>
                                    <div class="timeline-content">
                                        <span class="event-badge"><?= htmlspecialchars($event['event_type']) ?></span>
                                        <?php if (!empty($event['ip_address'])): ?>
                                            <code style="color:var(--text-muted);font-size:0.72rem;margin-left:8px"><?= htmlspecialchars($event['ip_address']) ?></code>
                                        <?php endif; ?>
                                        <?php if (!empty($event['ip_country'])): ?>
                                            <span style="margin-left:6px;font-size:0.72rem"><?= htmlspecialchars($event['ip_country']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($event['risk_score'])): ?>
                                            <span style="margin-left:8px"><?= number_format((float) $event['risk_score'], 1) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php $note = $item['note']; ?>
                                <div class="timeline-item">
                                    <div class="timeline-time"><?= date('M j, H:i', strtotime($note['created_at'])) ?></div>
                                    <div class="timeline-content">
                                        <span class="badge badge-active" style="margin-right:8px">Analyst Note</span>
                                        <span style="font-size:0.85rem;color:var(--text-secondary)"><?= htmlspecialchars($note['note'] ?? '') ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($status !== 'resolved'): ?>
<div class="modal-overlay" id="resolve-case-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>Resolve Case</h3>
            <button class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Resolution Notes</label>
                <textarea id="case-resolution-notes" class="form-input" rows="4" placeholder="Summarize findings, actions, and next steps."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('resolve-case-modal').classList.remove('active')">Cancel</button>
            <button class="btn btn-primary" onclick="Sentinel.resolveCase(<?= $case['id'] ?>)">Resolve Case</button>
        </div>
    </div>
</div>
<?php endif; ?>
