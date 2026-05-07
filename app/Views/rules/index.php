<?php /** @var array $rules */ /** @var array $triggerCounts */ ?>
<div id="rules-page">
    <div class="page-header">
        <h2>Risk Rules</h2>
        <div class="subtitle">Configure threat detection rules and scoring weights</div>
    </div>

    <div class="page-body">
        <?php
        $categories = [];
        foreach ($rules as $rule) {
            $categories[$rule['category']][] = $rule;
        }
        $triggerMap = [];
        foreach ($triggerCounts as $tc) {
            $triggerMap[$tc['slug']] = $tc['trigger_count'];
        }
        $categoryIcons = [
            'authentication' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'automation' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/></svg>',
            'content' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'identity' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'behavior' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>',
            'geo' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            'fraud' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
            'access' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
        ];
        ?>

        <?php foreach ($categories as $category => $catRules): ?>
            <div style="margin-bottom:24px">
                <h3 style="font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;display:flex;align-items:center;gap:8px">
                    <span style="display:inline-flex;align-items:center"><?= $categoryIcons[$category] ?? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' ?></span>
                    <?= htmlspecialchars(ucfirst($category)) ?>
                </h3>
                <?php foreach ($catRules as $rule): ?>
                    <div class="rule-card">
                        <div class="rule-info">
                            <div class="rule-name" style="display:flex;align-items:center;gap:8px">
                                <?= htmlspecialchars($rule['name']) ?>
                                <span class="badge <?= $rule['is_enabled'] ? 'badge-active' : 'badge-dismissed' ?>" id="rule-status-<?= $rule['id'] ?>">
                                    <?= $rule['is_enabled'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </div>
                            <div class="rule-desc"><?= htmlspecialchars($rule['description']) ?></div>
                            <div style="margin-top:6px;font-size:0.72rem;color:var(--text-muted)">
                                Triggered: <strong style="color:var(--text-secondary)"><?= $triggerMap[$rule['slug']] ?? 0 ?></strong> times
                            </div>
                        </div>
                        <div class="rule-meta">
                            <div>
                                <div style="font-size:0.7rem;color:var(--text-muted);margin-bottom:4px;text-align:center">Weight</div>
                                <input type="number" step="0.1" min="0.1" max="5.0"
                                       class="weight-input" id="rule-weight-<?= $rule['id'] ?>"
                                       value="<?= number_format((float)$rule['weight'], 1) ?>"
                                       onchange="Sentinel.updateRuleWeight(<?= $rule['id'] ?>)">
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" <?= $rule['is_enabled'] ? 'checked' : '' ?>
                                       onchange="Sentinel.toggleRule(<?= $rule['id'] ?>)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
