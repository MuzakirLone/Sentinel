<?php /** @var array $trackedUser */ ?>
<div id="user-detail-page" data-user-id="<?= $trackedUser['id'] ?>">
    <div class="page-header">
        <h2>User Profile</h2>
        <div class="subtitle">Single user view — activity timeline and risk analysis</div>
    </div>

    <div class="page-body">
        <!-- User Profile Header -->
        <div class="user-profile-header">
            <div class="user-avatar-large">
                <?= strtoupper(substr($trackedUser['email'] ?? $trackedUser['external_id'] ?? '?', 0, 1)) ?>
            </div>
            <div class="user-profile-info">
                <h2><?= htmlspecialchars($trackedUser['external_id']) ?></h2>
                <p><?= htmlspecialchars($trackedUser['email'] ?? 'No email') ?></p>
                <div style="margin-top:6px;display:flex;gap:8px;align-items:center">
                    <span class="badge badge-<?= htmlspecialchars($trackedUser['status'] ?? 'active') ?>"><?= htmlspecialchars($trackedUser['status'] ?? 'active') ?></span>
                    <span class="badge badge-<?= htmlspecialchars($trackedUser['risk_level'] ?? 'low') ?>"><?= htmlspecialchars($trackedUser['risk_level'] ?? 'low') ?> risk</span>
                    <?php if ($trackedUser['country']): ?>
                        <span style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($trackedUser['country']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="user-meta-grid">
                <div class="user-meta-item">
                    <div class="meta-value" style="color:<?= ($trackedUser['risk_score'] ?? 0) >= 60 ? 'var(--risk-high)' : 'var(--accent-emerald)' ?>"><?= number_format($trackedUser['risk_score'] ?? 0, 1) ?></div>
                    <div class="meta-label">Risk Score</div>
                </div>
                <div class="user-meta-item">
                    <div class="meta-value"><?= $trackedUser['total_events'] ?? 0 ?></div>
                    <div class="meta-label">Total Events</div>
                </div>
                <div class="user-meta-item">
                    <div class="meta-value" style="font-size:0.9rem"><?= $trackedUser['first_seen_at'] ? date('M j, Y', strtotime($trackedUser['first_seen_at'])) : '—' ?></div>
                    <div class="meta-label">First Seen</div>
                </div>
                <div class="user-meta-item">
                    <div class="meta-value" style="font-size:0.9rem"><?= $trackedUser['last_seen_at'] ? date('M j, Y H:i', strtotime($trackedUser['last_seen_at'])) : '—' ?></div>
                    <div class="meta-label">Last Seen</div>
                </div>
            </div>
        </div>

        <!-- Risk Profile Scores -->
        <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
            <div class="kpi-card kpi-primary">
                <div class="kpi-value" id="prof-auth" style="font-size:1.4rem">0</div>
                <div class="kpi-label">Auth Score</div>
            </div>
            <div class="kpi-card kpi-cyan">
                <div class="kpi-value" id="prof-behavior" style="font-size:1.4rem">0</div>
                <div class="kpi-label">Behavior Score</div>
            </div>
            <div class="kpi-card kpi-amber">
                <div class="kpi-value" id="prof-identity" style="font-size:1.4rem">0</div>
                <div class="kpi-label">Identity Score</div>
            </div>
            <div class="kpi-card kpi-rose">
                <div class="kpi-value" id="prof-geo" style="font-size:1.4rem">0</div>
                <div class="kpi-label">Geo Score</div>
            </div>
        </div>

        <!-- Why Flagged? Risk Factors Breakdown -->
        <div class="card animate-in" style="margin-bottom:24px" id="risk-factors-card">
            <div class="card-header">
                <h3>Why Flagged?</h3>
                <span style="font-size:0.75rem;color:var(--text-muted)">Risk score breakdown by rule</span>
            </div>
            <div class="card-body" id="risk-factors-body">
                <div style="text-align:center;padding:24px;color:var(--text-muted)">
                    <div class="loading-spinner" style="margin:0 auto"></div>
                    <div style="margin-top:12px">Analyzing risk factors...</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-item active" data-tab="tab-timeline">Activity Timeline</button>
            <button class="tab-item" data-tab="tab-sessions">Sessions</button>
        </div>

        <!-- Timeline Tab -->
        <div class="tab-content active" id="tab-timeline">
            <div class="card">
                <div class="card-body">
                    <div class="timeline" id="user-timeline">
                        <div style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div class="loading-spinner" style="margin:0 auto"></div>
                            <div style="margin-top:12px">Loading timeline...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sessions Tab -->
        <div class="tab-content" id="tab-sessions">
            <div class="card">
                <div class="card-body" style="padding:0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>IP Address</th>
                                <th>Device</th>
                                <th>Events</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody id="user-sessions-body">
                            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
