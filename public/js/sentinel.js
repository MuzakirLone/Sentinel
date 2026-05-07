/**
 * Sentinel — Dashboard JavaScript
 */

const Sentinel = {
    // Auto-refresh interval (ms)
    REFRESH_INTERVAL: 30000,
    refreshTimer: null,

    init() {
        this.initNavigation();
        this.initModals();
        this.initTabs();
        
        // Auto-refresh for dashboard
        if (document.getElementById('dashboard-page')) {
            this.loadDashboardStats();
            this.refreshTimer = setInterval(() => this.loadDashboardStats(), this.REFRESH_INTERVAL);
        }

        // Data tables
        if (document.getElementById('users-page')) this.loadUsers();
        if (document.getElementById('events-page')) this.loadEvents();
        if (document.getElementById('review-page')) this.loadReviewQueue();
        if (document.getElementById('audit-page')) this.loadAuditTrail();
        if (document.getElementById('user-detail-page')) this.loadUserTimeline();

        // Handle clickable rows globally with robust drag detection
        let mouseDownPos = { x: 0, y: 0 };
        document.body.addEventListener('mousedown', (e) => {
            mouseDownPos = { x: e.clientX, y: e.clientY };
        });

        document.body.addEventListener('click', (e) => {
            const tr = e.target.closest('tr[data-href]');
            if (tr) {
                // If mouse moved more than 5px, it was a drag (e.g. text selection)
                const moveDist = Math.hypot(e.clientX - mouseDownPos.x, e.clientY - mouseDownPos.y);
                if (moveDist > 5) return;
                
                // Ignore if user clicked a link or button inside the row
                if (e.target.closest('a, button')) return;
                
                window.location = tr.dataset.href;
            }
        });
    },

    // ── Navigation ──────────────────────────────────────
    initNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === window.location.pathname) {
                item.classList.add('active');
            }
        });
    },

    // ── Modals ──────────────────────────────────────────
    initModals() {
        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-modal-open');
                document.getElementById(id)?.classList.add('active');
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal-overlay')?.classList.remove('active');
            });
        });
    },

    // ── Tabs ────────────────────────────────────────────
    initTabs() {
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', () => {
                const group = tab.closest('.tabs');
                const target = tab.dataset.tab;

                group.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(target)?.classList.add('active');
            });
        });
    },

    // ── API Helpers ─────────────────────────────────────
    async api(url, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(url, {
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-Token': csrfToken,
                    ...options.headers 
                },
                credentials: 'same-origin',
                ...options,
            });
            if (!res.ok) {
                let errorMsg = `Server error (${res.status})`;
                try {
                    const errBody = await res.text();
                    // Try to extract a meaningful message
                    const jsonErr = JSON.parse(errBody);
                    errorMsg = jsonErr.error || errorMsg;
                } catch(e) {
                    // Response was not JSON (likely HTML error page)
                }
                console.error('API Error:', errorMsg, url);
                return { _error: true, message: errorMsg, status: res.status };
            }
            return await res.json();
        } catch (err) {
            console.error('API Error:', err);
            return { _error: true, message: err.message };
        }
    },

    // ── Dashboard Stats ─────────────────────────────────
    async loadDashboardStats() {
        const data = await this.api('/dashboard/stats');
        if (!data) return;

        // Update KPI cards
        this.updateKPI('total-events', data.total_events_24h);
        this.updateKPI('active-users', data.active_users_24h);
        this.updateKPI('high-risk', data.high_risk_users);
        this.updateKPI('pending-reviews', data.pending_reviews);
        this.updateKPI('blocked-users', data.blocked_users);
        this.updateKPI('avg-risk', data.avg_risk_score);

        // Update review badge in sidebar
        const badge = document.getElementById('review-badge');
        if (badge && data.pending_reviews > 0) {
            badge.textContent = data.pending_reviews;
            badge.style.display = 'inline';
        }

        // Update charts
        if (typeof SentinelCharts !== 'undefined') {
            SentinelCharts.updateEventsChart(data.events_by_hour || []);
            SentinelCharts.updateRiskChart(data.risk_distribution || []);
            SentinelCharts.updateTypesChart(data.top_event_types || []);
        }

        // Update recent events table
        this.renderRecentEvents(data.recent_events || []);
    },

    updateKPI(id, value) {
        const el = document.getElementById(id);
        if (el) {
            const formatted = typeof value === 'number' ?
                (value >= 1000 ? (value / 1000).toFixed(1) + 'k' : value.toString()) :
                (value || '0');
            el.textContent = formatted;
        }
    },

    renderRecentEvents(events) {
        const tbody = document.getElementById('recent-events-body');
        if (!tbody) return;

        if (events.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty-state"><p>No events yet. Send your first event via the API.</p></td></tr>`;
            return;
        }

        tbody.innerHTML = events.map(e => `
            <tr data-href="/events" class="clickable-row">
                <td><span class="event-badge">${this.escapeHtml(e.event_type)}</span></td>
                <td>${e.user_email ? this.escapeHtml(e.user_email) : '<span style="color:var(--text-muted)">—</span>'}</td>
                <td><code style="color:var(--accent-cyan);font-size:0.75rem">${this.escapeHtml(e.ip_address || '—')}</code></td>
                <td>${this.riskScoreHtml(e.risk_score)}</td>
                <td>${e.browser ? this.escapeHtml(e.browser) : '—'} / ${e.os || '—'}</td>
                <td style="color:var(--text-muted);font-size:0.75rem;font-family:'JetBrains Mono',monospace">${this.timeAgo(e.created_at)}</td>
            </tr>
        `).join('');
    },

    // ── Users ───────────────────────────────────────────
    usersPage: 1,
    usersSort: 'risk_score',
    usersOrder: 'DESC',

    async loadUsers(page = 1) {
        this.usersPage = page;
        const search = document.getElementById('users-search')?.value || '';
        const data = await this.api(`/users/data?page=${page}&sort=${this.usersSort}&order=${this.usersOrder}&search=${encodeURIComponent(search)}`);
        if (!data) return;

        const tbody = document.getElementById('users-table-body');
        if (!tbody) return;

        if (data.users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state"><p>No users found.</p></td></tr>`;
            return;
        }

        tbody.innerHTML = data.users.map(u => `
            <tr data-href="/users/${u.id}" class="clickable-row">
                <td><strong style="color:var(--text-primary)">${this.escapeHtml(u.external_id)}</strong></td>
                <td>${u.email ? this.escapeHtml(u.email) : '—'}</td>
                <td>${this.riskScoreHtml(u.risk_score)}</td>
                <td><span class="badge badge-${(u.risk_level || 'low').toLowerCase()}">${u.risk_level || 'low'}</span></td>
                <td><span class="badge badge-${(u.status || 'active').toLowerCase()}">${u.status || 'active'}</span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">${u.total_events || 0}</td>
                <td style="color:var(--text-muted);font-size:0.75rem">${this.timeAgo(u.last_seen_at)}</td>
            </tr>
        `).join('');

        this.renderPagination('users-pagination', data.page, data.pages, data.total, (p) => this.loadUsers(p));
    },

    // ── Events ──────────────────────────────────────────
    eventsPage: 1,

    async loadEvents(page = 1) {
        this.eventsPage = page;
        const eventType = document.getElementById('event-type-filter')?.value || '';
        const data = await this.api(`/events/data?page=${page}&event_type=${encodeURIComponent(eventType)}`);
        if (!data) return;

        const tbody = document.getElementById('events-table-body');
        if (!tbody) return;

        if (data.events.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state"><p>No events found.</p></td></tr>`;
            return;
        }

        tbody.innerHTML = data.events.map(e => `
            <tr>
                <td><span class="event-badge">${this.escapeHtml(e.event_type)}</span></td>
                <td>${e.user_email ? `<a href="/users/${e.user_id}" style="color:var(--accent-cyan);text-decoration:none">${this.escapeHtml(e.user_email)}</a>` : '—'}</td>
                <td><code style="color:var(--accent-cyan);font-size:0.75rem">${this.escapeHtml(e.ip_address || '—')}</code></td>
                <td>${this.riskScoreHtml(e.risk_score)}</td>
                <td>${e.is_bot ? '<span class="badge badge-elevated">Bot</span>' : (e.browser || '—')}</td>
                <td>${e.ip_country || '—'}</td>
                <td style="color:var(--text-muted);font-size:0.75rem;font-family:'JetBrains Mono',monospace">${this.timeAgo(e.created_at)}</td>
            </tr>
        `).join('');

        this.renderPagination('events-pagination', data.page, data.pages, data.total, (p) => this.loadEvents(p));
    },

    // ── Review Queue ────────────────────────────────────
    async loadReviewQueue(status = 'pending') {
        const data = await this.api(`/review/data?status=${status}`);
        if (!data) return;

        const container = document.getElementById('review-items');
        if (!container) return;

        const pendingEl = document.getElementById('review-pending-count');
        if (pendingEl) pendingEl.textContent = data.pending;

        if (data.items.length === 0) {
            container.innerHTML = `<div class="empty-state"><div class="empty-icon">✓</div><h3>All Clear</h3><p>No items pending review.</p></div>`;
            return;
        }

        container.innerHTML = data.items.map(item => `
            <div class="card" style="margin-bottom:12px">
                <div class="card-body" style="display:flex;align-items:center;gap:16px">
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                            <a href="/users/${item.user_id}" style="color:var(--text-primary);font-weight:600;text-decoration:none">${this.escapeHtml(item.external_id || item.email || 'Unknown')}</a>
                            <span class="badge badge-${item.priority}">${item.priority}</span>
                            <span class="badge badge-${item.user_status || 'active'}">${item.user_status || 'active'}</span>
                        </div>
                        <p style="font-size:0.8rem;color:var(--text-muted)">${this.escapeHtml(item.reason)}</p>
                        <div style="margin-top:6px">${this.riskScoreHtml(item.risk_score)}</div>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-success btn-sm" onclick="Sentinel.reviewAction(${item.id}, 'approve')">✓ Approve</button>
                        <button class="btn btn-danger btn-sm" onclick="Sentinel.reviewAction(${item.id}, 'suspend')">⊘ Suspend</button>
                        <button class="btn btn-ghost btn-sm" onclick="Sentinel.reviewAction(${item.id}, 'dismiss')">✕ Dismiss</button>
                    </div>
                </div>
            </div>
        `).join('');
    },

    async reviewAction(id, action) {
        const data = await this.api(`/review/${id}/action`, {
            method: 'POST',
            body: JSON.stringify({ action }),
        });
        if (data && data.status === 'ok') {
            this.loadReviewQueue();
        }
    },

    // ── User Timeline ───────────────────────────────────
    async loadUserTimeline() {
        const userId = document.getElementById('user-detail-page')?.dataset.userId;
        if (!userId) return;

        const data = await this.api(`/users/${userId}/timeline`);
        if (!data) return;

        // Risk profile
        if (data.risk_profile) {
            const rp = data.risk_profile;
            document.getElementById('prof-overall')?.setAttribute('style', `--score:${rp.overall_score}%`);
            this.updateProfileScore('prof-auth', rp.auth_score);
            this.updateProfileScore('prof-behavior', rp.behavior_score);
            this.updateProfileScore('prof-identity', rp.identity_score);
            this.updateProfileScore('prof-geo', rp.geo_score);
        }

        // "Why Flagged?" Risk Factors Panel
        this.renderRiskFactors(data.risk_factors || []);

        // Timeline
        const timeline = document.getElementById('user-timeline');
        if (timeline && data.timeline) {
            if (data.timeline.length === 0) {
                timeline.innerHTML = '<div class="empty-state"><p>No events recorded for this user.</p></div>';
                return;
            }

            timeline.innerHTML = data.timeline.map(e => {
                const riskClass = e.risk_score >= 60 ? 'risk-critical' : (e.risk_score >= 30 ? 'risk-high' : '');
                return `
                    <div class="timeline-item ${riskClass}">
                        <div class="timeline-time">${this.formatDate(e.created_at)}</div>
                        <div class="timeline-content">
                            <span class="event-badge">${this.escapeHtml(e.event_type)}</span>
                            ${e.ip_address ? `<code style="color:var(--text-muted);font-size:0.72rem;margin-left:8px">${e.ip_address}</code>` : ''}
                            ${e.ip_country ? `<span style="margin-left:6px;font-size:0.72rem">${e.ip_country}</span>` : ''}
                            ${e.risk_score > 0 ? `<span style="margin-left:8px">${this.riskScoreHtml(e.risk_score)}</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Sessions
        const sessionsBody = document.getElementById('user-sessions-body');
        if (sessionsBody && data.sessions) {
            sessionsBody.innerHTML = data.sessions.map(s => `
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem">${this.escapeHtml(s.session_id?.substring(0, 16) || '—')}...</td>
                    <td><code style="color:var(--accent-cyan);font-size:0.75rem">${this.escapeHtml(s.ip_address || '—')}</code></td>
                    <td>${s.browser || '—'} / ${s.os || '—'}</td>
                    <td>${s.event_count}</td>
                    <td>${s.is_suspicious ? '<span class="badge badge-critical">Suspicious</span>' : '<span class="badge badge-low">Normal</span>'}</td>
                    <td style="color:var(--text-muted);font-size:0.75rem">${this.timeAgo(s.last_activity)}</td>
                </tr>
            `).join('');
        }
    },

    updateProfileScore(id, score) {
        const el = document.getElementById(id);
        if (el) el.textContent = Math.round(score || 0);
    },

    renderRiskFactors(factors) {
        const body = document.getElementById('risk-factors-body');
        const card = document.getElementById('risk-factors-card');
        if (!body) return;

        if (!factors || factors.length === 0) {
            body.innerHTML = `<div style="text-align:center;padding:24px;color:var(--text-muted)">
                <div style="font-size:1.5rem;margin-bottom:8px;opacity:0.3">✓</div>
                <div>No risk factors triggered for this user.</div>
            </div>`;
            return;
        }

        // Deduplicate by rule slug, keeping highest score
        const ruleMap = {};
        factors.forEach(f => {
            const slug = f.slug || f.rule || 'unknown';
            if (!ruleMap[slug] || (parseFloat(f.score) > parseFloat(ruleMap[slug].score))) {
                ruleMap[slug] = f;
            }
        });

        const unique = Object.values(ruleMap).sort((a, b) => parseFloat(b.score) - parseFloat(a.score));
        const maxScore = Math.max(...unique.map(f => parseFloat(f.score) || 0), 1);

        body.innerHTML = unique.map(f => {
            const score = parseFloat(f.score) || 0;
            const pct = Math.min(100, (score / maxScore) * 100);
            const name = f.name || f.slug || 'Unknown Rule';
            const category = (f.category || 'other').toUpperCase();
            let details = [];
            try {
                details = typeof f.details === 'string' ? JSON.parse(f.details) : (f.details || []);
            } catch(e) { details = []; }
            
            let color = 'var(--risk-low)';
            if (score >= 60) color = 'var(--risk-critical)';
            else if (score >= 40) color = 'var(--risk-high)';
            else if (score >= 20) color = 'var(--risk-elevated)';

            return `
                <div style="margin-bottom:16px;padding:12px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-weight:600;color:var(--text-primary)">${this.escapeHtml(name)}</span>
                            <span class="badge" style="font-size:0.6rem;padding:2px 6px;opacity:0.7">${category}</span>
                        </div>
                        <span style="font-weight:700;font-size:1.1rem;color:${color}">+${score.toFixed(0)}</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.05);border-radius:3px;overflow:hidden;margin-bottom:8px">
                        <div style="height:100%;width:${pct}%;background:${color};border-radius:3px;transition:width 0.5s ease"></div>
                    </div>
                    ${details.length > 0 ? `<div style="font-size:0.75rem;color:var(--text-muted);line-height:1.5">
                        ${details.slice(0, 3).map(d => `<div>└─ ${this.escapeHtml(typeof d === 'string' ? d : JSON.stringify(d))}</div>`).join('')}
                    </div>` : ''}
                </div>
            `;
        }).join('');
    },

    // ── Audit Trail ─────────────────────────────────────
    auditPage: 1,

    async loadAuditTrail(page = 1) {
        this.auditPage = page;
        const data = await this.api(`/audit/data?page=${page}`);
        if (!data) return;

        const tbody = document.getElementById('audit-table-body');
        if (!tbody) return;

        if (data.entries.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="empty-state"><p>No audit entries found.</p></td></tr>`;
            return;
        }

        tbody.innerHTML = data.entries.map(e => `
            <tr>
                <td>${e.email ? `<a href="/users/${e.user_id}" style="color:var(--accent-cyan);text-decoration:none">${this.escapeHtml(e.email)}</a>` : '—'}</td>
                <td><span class="event-badge">${this.escapeHtml(e.entity_type)}.${this.escapeHtml(e.field_name)}</span></td>
                <td style="color:var(--accent-rose);font-size:0.8rem;font-family:'JetBrains Mono',monospace">${this.escapeHtml(e.old_value || '—')}</td>
                <td style="color:var(--accent-emerald);font-size:0.8rem;font-family:'JetBrains Mono',monospace">${this.escapeHtml(e.new_value || '—')}</td>
                <td>${this.escapeHtml(e.changed_by || '—')}</td>
                <td><code style="font-size:0.72rem;color:var(--text-muted)">${this.escapeHtml(e.ip_address || '—')}</code></td>
                <td style="color:var(--text-muted);font-size:0.72rem">${this.timeAgo(e.created_at)}</td>
            </tr>
        `).join('');

        this.renderPagination('audit-pagination', data.page, data.pages, data.total, (p) => this.loadAuditTrail(p));
    },

    // ── Settings: API Keys ──────────────────────────────
    async createApiKey() {
        const label = document.getElementById('api-key-label')?.value;
        if (!label) return alert('Please enter a label');

        const data = await this.api('/settings/api-keys', {
            method: 'POST',
            body: JSON.stringify({ label }),
        });

        if (data && data._error) {
            alert('Failed to create API key: ' + data.message);
            return;
        }

        if (data && data.key) {
            const display = document.getElementById('new-key-display');
            if (display) {
                display.innerHTML = `
                    <div class="alert alert-success">
                        <strong>API Key Created!</strong> Copy it now — it won't be shown again.
                    </div>
                    <div class="api-key-display">${data.key}</div>
                `;
            }
            document.getElementById('api-key-label').value = '';
            setTimeout(() => location.reload(), 5000);
        }
    },

    async revokeApiKey(id) {
        if (!confirm('Revoke this API key? Applications using it will stop working.')) return;

        const data = await this.api(`/settings/api-keys/${id}/revoke`, { method: 'POST' });
        if (data && data._error) {
            alert('Failed to revoke API key: ' + data.message);
            return;
        }
        if (data && data.status === 'ok') {
            location.reload();
        }
    },

    // ── Rules ───────────────────────────────────────────
    async toggleRule(id) {
        const data = await this.api(`/rules/${id}/toggle`, { method: 'POST' });
        if (data) {
            const badge = document.getElementById(`rule-status-${id}`);
            if (badge) {
                badge.textContent = data.enabled ? 'Active' : 'Disabled';
                badge.className = `badge ${data.enabled ? 'badge-active' : 'badge-dismissed'}`;
            }
        }
    },

    async updateRuleWeight(id) {
        const input = document.getElementById(`rule-weight-${id}`);
        if (!input) return;
        const weight = parseFloat(input.value);
        await this.api(`/rules/${id}/weight`, {
            method: 'POST',
            body: JSON.stringify({ weight }),
        });
    },

    // ── Utilities ───────────────────────────────────────
    riskScoreHtml(score) {
        score = parseFloat(score) || 0;
        let color = 'var(--risk-low)';
        if (score >= 80) color = 'var(--risk-critical)';
        else if (score >= 60) color = 'var(--risk-high)';
        else if (score >= 40) color = 'var(--risk-elevated)';
        else if (score >= 20) color = 'var(--risk-moderate)';

        return `<span class="risk-score-display"><span style="color:${color}">${score.toFixed(1)}</span><span class="risk-bar"><span class="risk-bar-fill" style="width:${Math.min(score, 100)}%;background:${color}"></span></span></span>`;
    },

    timeAgo(dateStr) {
        if (!dateStr) return '—';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = (now - date) / 1000;

        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        return date.toLocaleDateString();
    },

    formatDate(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleString();
    },

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    renderPagination(containerId, currentPage, totalPages, total, callback) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const start = ((currentPage - 1) * 25) + 1;
        const end = Math.min(currentPage * 25, total);

        let html = `<div class="pagination-info">Showing ${start}–${end} of ${total}</div><div class="pagination-controls">`;

        if (currentPage > 1) {
            html += `<button onclick="void(0)" data-page="${currentPage - 1}">← Prev</button>`;
        }

        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            html += `<button class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            html += `<button onclick="void(0)" data-page="${currentPage + 1}">Next →</button>`;
        }

        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => callback(parseInt(btn.dataset.page)));
        });
    },
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => Sentinel.init());
