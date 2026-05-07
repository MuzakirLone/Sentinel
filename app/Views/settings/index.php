<?php /** @var array $apiKeys */ ?>
<div id="settings-page">
    <div class="page-header">
        <h2>Settings</h2>
        <div class="subtitle">API key management and application configuration</div>
    </div>

    <div class="page-body">
        <!-- API Keys Section -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header">
                <h3>API Keys</h3>
                <button class="btn btn-primary btn-sm" data-modal-open="create-key-modal">+ New API Key</button>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($apiKeys)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>
                        <h3>No API Keys</h3>
                        <p>Create an API key to start sending events from your applications.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Key Prefix</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Last Used</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td><strong style="color:var(--text-primary)"><?= htmlspecialchars($key['label']) ?></strong></td>
                                    <td><code class="api-key-display" style="padding:4px 8px;font-size:0.72rem"><?= htmlspecialchars($key['key_prefix']) ?></code></td>
                                    <td><span class="badge <?= $key['is_active'] ? 'badge-active' : 'badge-dismissed' ?>"><?= $key['is_active'] ? 'Active' : 'Revoked' ?></span></td>
                                    <td><?= htmlspecialchars($key['created_by_name'] ?? '—') ?></td>
                                    <td style="color:var(--text-muted);font-size:0.8rem"><?= $key['last_used_at'] ? date('M j, H:i', strtotime($key['last_used_at'])) : 'Never' ?></td>
                                    <td style="color:var(--text-muted);font-size:0.8rem"><?= date('M j, Y', strtotime($key['created_at'])) ?></td>
                                    <td>
                                        <?php if ($key['is_active']): ?>
                                            <button class="btn btn-danger btn-sm" onclick="Sentinel.revokeApiKey(<?= $key['id'] ?>)">Revoke</button>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:0.75rem">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Integration Guide -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Integration</h3>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary);margin-bottom:16px;font-size:0.85rem">
                    Send events to Sentinel using a simple HTTP POST request. Include your API key in the <code style="color:var(--accent-cyan)">X-API-Key</code> header.
                </p>
                <pre style="background:var(--bg-tertiary);padding:16px;border-radius:var(--radius-md);border:1px solid var(--border-glass);overflow-x:auto;font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-secondary);line-height:1.8"><code><span style="color:var(--accent-amber)">curl</span> -X POST <?= htmlspecialchars($config['app']['url'] ?? 'http://localhost:8585') ?>/api/v1/events \
  -H <span style="color:var(--accent-emerald)">"Content-Type: application/json"</span> \
  -H <span style="color:var(--accent-emerald)">"X-API-Key: YOUR_API_KEY"</span> \
  -d <span style="color:var(--accent-cyan)">'{
    "event_type": "login_success",
    "user_id": "usr_12345",
    "email": "user@example.com",
    "ip": "203.0.113.42",
    "user_agent": "Mozilla/5.0..."
  }'</span></code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div class="modal-overlay" id="create-key-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>Create API Key</h3>
            <button class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <div id="new-key-display"></div>
            <div class="form-group">
                <label class="form-label">Label</label>
                <input type="text" id="api-key-label" class="form-input" placeholder="e.g. Production App, Staging...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('create-key-modal').classList.remove('active')">Cancel</button>
            <button class="btn btn-primary" onclick="Sentinel.createApiKey()">Create Key</button>
        </div>
    </div>
</div>
