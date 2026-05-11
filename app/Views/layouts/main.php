<?php /** @var string $pageContent */ /** @var array $config */ /** @var array $user */ /** @var string $page */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel — Security Monitoring Dashboard</title>
    <meta name="description" content="Self-hosted security monitoring framework for threat detection and risk scoring.">
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
    <link rel="stylesheet" href="/public/css/sentinel.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" data-mobile-menu>☰</button>
    <div class="mobile-scrim" data-mobile-scrim></div>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <h1>Sentinel</h1>
                    <span class="version">v1.0.0</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Overview</div>
                    <a href="/dashboard" class="nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></span>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Operations</div>
                    <a href="/alerts" class="nav-item <?= ($page ?? '') === 'alerts' ? 'active' : '' ?>" id="nav-alerts">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                        <span>Alerts</span>
                        <span class="nav-badge" id="alert-badge" style="display:none">0</span>
                    </a>
                    <a href="/cases" class="nav-item <?= ($page ?? '') === 'cases' ? 'active' : '' ?>" id="nav-cases">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-9"/><path d="M14 17H5"/><path d="M20 17h-3"/><path d="M3 7h3"/><path d="M7 12h10"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></span>
                        <span>Cases</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Investigation</div>
                    <a href="/users" class="nav-item <?= ($page ?? '') === 'users' ? 'active' : '' ?>" id="nav-users">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                        <span>Users</span>
                    </a>
                    <a href="/events" class="nav-item <?= ($page ?? '') === 'events' ? 'active' : '' ?>" id="nav-events">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
                        <span>Events</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Configuration</div>
                    <a href="/rules" class="nav-item <?= ($page ?? '') === 'rules' ? 'active' : '' ?>" id="nav-rules">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                        <span>Rules</span>
                    </a>
                    <a href="/integrations" class="nav-item <?= ($page ?? '') === 'integrations' ? 'active' : '' ?>" id="nav-integrations">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2.1a6 6 0 0 1 0 11.8"/><path d="M7 2.1a6 6 0 0 0 0 11.8"/><path d="M12 6v12"/><path d="M6 12h12"/></svg></span>
                        <span>Integrations</span>
                    </a>
                    <a href="/audit" class="nav-item <?= ($page ?? '') === 'audit' ? 'active' : '' ?>" id="nav-audit">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span>
                        <span>Audit Trail</span>
                    </a>
                    <a href="/settings" class="nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>" id="nav-settings">
                        <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg></span>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <button class="command-trigger" type="button" data-command-open aria-label="Open command center">
                    <span>⌘</span>
                    <strong>Command Center</strong>
                    <kbd>Ctrl K</kbd>
                </button>
                <div class="sidebar-user">
                    <div class="avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
                <a href="/logout" class="nav-item" style="margin-top:8px;font-size:0.8rem;color:var(--text-muted)">
                    <span class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?= $pageContent ?>
        </main>
    </div>

    <div class="command-palette" id="command-palette" aria-hidden="true">
        <div class="command-panel" role="dialog" aria-modal="true" aria-labelledby="command-title">
            <div class="command-search">
                <span class="command-search-icon">⌕</span>
                <input id="command-input" type="search" placeholder="Search pages, workflows, and quick actions..." autocomplete="off">
                <kbd>Esc</kbd>
            </div>
            <div class="command-meta" id="command-title">Navigate Sentinel</div>
            <div class="command-list" id="command-list">
                <a href="/dashboard" class="command-item" data-command-keywords="overview metrics home dashboard">
                    <span class="command-icon">◫</span>
                    <span><strong>Dashboard</strong><small>Live overview, charts, KPIs</small></span>
                </a>
                <a href="/alerts" class="command-item" data-command-keywords="triage review alerts queue pending">
                    <span class="command-icon">⚠</span>
                    <span><strong>Alerts</strong><small>Triage high-risk detections</small></span>
                </a>
                <a href="/cases" class="command-item" data-command-keywords="cases investigations incidents sla">
                    <span class="command-icon">▦</span>
                    <span><strong>Cases</strong><small>Investigations and SLA tracking</small></span>
                </a>
                <a href="/events" class="command-item" data-command-keywords="events logs telemetry search">
                    <span class="command-icon">ϟ</span>
                    <span><strong>Events</strong><small>Raw telemetry and risk context</small></span>
                </a>
                <a href="/users" class="command-item" data-command-keywords="users accounts identity risk timeline">
                    <span class="command-icon">◎</span>
                    <span><strong>Users</strong><small>Entity profiles and behavior</small></span>
                </a>
                <a href="/rules" class="command-item" data-command-keywords="rules detections mitre tuning">
                    <span class="command-icon">◈</span>
                    <span><strong>Rules</strong><small>Detection coverage and tuning</small></span>
                </a>
                <a href="/integrations" class="command-item" data-command-keywords="integrations siem webhook slack ticketing">
                    <span class="command-icon">↗</span>
                    <span><strong>Integrations</strong><small>SIEM, chat, and ticketing outputs</small></span>
                </a>
                <a href="/audit" class="command-item" data-command-keywords="audit history changes accountability compliance">
                    <span class="command-icon">◷</span>
                    <span><strong>Audit Trail</strong><small>Field history and accountability</small></span>
                </a>
                <a href="/settings" class="command-item" data-command-keywords="settings keys configuration security profile">
                    <span class="command-icon">⚙</span>
                    <span><strong>Settings</strong><small>API keys and workspace controls</small></span>
                </a>
            </div>
        </div>
    </div>

    <script src="/public/js/charts.js"></script>
    <script src="/public/js/sentinel.js"></script>
    <script src="/public/js/cursor.js"></script>
</body>
</html>
