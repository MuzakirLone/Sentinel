<?php /** @var array $integrations */ ?>
<div id="integrations-page">
    <div class="page-header">
        <h2>Integrations</h2>
        <div class="subtitle">Connect Sentinel to SIEMs, collaboration tools, and ticketing workflows</div>
    </div>

    <div class="page-body">
        <div class="card" style="margin-bottom:16px">
            <div class="card-body" style="display:flex;flex-wrap:wrap;gap:12px">
                <span class="badge badge-active">Elastic / Splunk</span>
                <span class="badge badge-pending">Webhooks</span>
                <span class="badge badge-resolved">Slack / Teams</span>
                <span class="badge badge-elevated">Jira</span>
            </div>
        </div>

        <?php foreach ($integrations as $integration): ?>
            <?php $config = $integration['config'] ?? []; ?>
            <div class="card" style="margin-bottom:16px">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <div>
                        <h3><?= htmlspecialchars($integration['name']) ?></h3>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Type: <?= htmlspecialchars(strtoupper($integration['integration_type'])) ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px">
                        <span class="badge <?= ($integration['status'] ?? '') === 'enabled' ? 'badge-active' : 'badge-dismissed' ?>" id="integration-status-<?= $integration['id'] ?>">
                            <?= ($integration['status'] ?? '') === 'enabled' ? 'Enabled' : 'Disabled' ?>
                        </span>
                        <label class="toggle-switch">
                            <input type="checkbox" <?= ($integration['status'] ?? '') === 'enabled' ? 'checked' : '' ?> onchange="Sentinel.toggleIntegration(<?= $integration['id'] ?>)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($config)): ?>
                        <p style="color:var(--text-secondary);font-size:0.85rem">No configuration required.</p>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                            <?php foreach ($config as $key => $value): ?>
                                <div class="form-group" style="margin:0">
                                    <label class="form-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></label>
                                    <input
                                        type="text"
                                        class="form-input"
                                        data-integration-id="<?= $integration['id'] ?>"
                                        data-integration-field="<?= htmlspecialchars($key) ?>"
                                        value="<?= htmlspecialchars((string) $value) ?>"
                                        placeholder="Enter <?= htmlspecialchars($key) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:12px;display:flex;justify-content:flex-end">
                            <button class="btn btn-ghost btn-sm" onclick="Sentinel.saveIntegration(<?= $integration['id'] ?>)">Save Configuration</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
