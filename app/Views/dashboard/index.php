<div id="dashboard-page">
    <div class="page-header">
        <h2>Dashboard</h2>
        <div class="subtitle">Real-time security overview <span class="pulse" style="margin-left:8px"></span> <span style="font-size:0.75rem;color:var(--text-muted)">Live</span></div>
    </div>

    <div class="page-body">
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-primary animate-in">
                <div class="kpi-icon" style="background:rgba(99,102,241,0.1);color:var(--accent-primary)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
                <div class="kpi-value" id="total-events">0</div>
                <div class="kpi-label">Events (24h)</div>
            </div>
            <div class="kpi-card kpi-cyan animate-in">
                <div class="kpi-icon" style="background:rgba(34,211,238,0.1);color:var(--accent-cyan)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div class="kpi-value" id="active-users">0</div>
                <div class="kpi-label">Active Users (24h)</div>
            </div>
            <div class="kpi-card kpi-rose animate-in">
                <div class="kpi-icon" style="background:rgba(251,113,133,0.1);color:var(--accent-rose)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                <div class="kpi-value" id="high-risk">0</div>
                <div class="kpi-label">High-Risk Users</div>
            </div>
            <div class="kpi-card kpi-amber animate-in">
                <div class="kpi-icon" style="background:rgba(251,191,36,0.1);color:var(--accent-amber)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></div>
                <div class="kpi-value" id="pending-reviews">0</div>
                <div class="kpi-label">Pending Reviews</div>
            </div>
            <div class="kpi-card kpi-emerald animate-in">
                <div class="kpi-icon" style="background:rgba(239,68,68,0.1);color:var(--accent-rose)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
                <div class="kpi-value" id="blocked-users">0</div>
                <div class="kpi-label">Blocked Users</div>
            </div>
            <div class="kpi-card kpi-violet animate-in">
                <div class="kpi-icon" style="background:rgba(167,139,250,0.1);color:var(--accent-violet)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg></div>
                <div class="kpi-value" id="avg-risk">0</div>
                <div class="kpi-label">Avg Risk Score</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-grid">
            <div class="card animate-in">
                <div class="card-header">
                    <h3>Events Over Time</h3>
                    <span style="font-size:0.75rem;color:var(--text-muted)">Last 24 hours</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="events-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="card animate-in">
                <div class="card-header">
                    <h3>Risk Distribution</h3>
                    <span style="font-size:0.75rem;color:var(--text-muted)">Last 7 days</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="risk-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second charts row + recent events -->
        <div class="charts-grid" style="grid-template-columns: 1fr 1fr">
            <div class="card animate-in">
                <div class="card-header">
                    <h3>Top Event Types</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height:220px">
                        <canvas id="types-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="card animate-in">
                <div class="card-header">
                    <h3>Top Risky Users</h3>
                    <a href="/users?sort=risk_score&order=DESC" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="data-table" id="risky-users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Risk Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="risky-users-body">
                            <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--text-muted)">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Events Table -->
        <div class="card animate-in" style="margin-top:16px">
            <div class="card-header">
                <h3>Recent Events</h3>
                <a href="/events" class="btn btn-ghost btn-sm">View All →</a>
            </div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Risk</th>
                            <th>Device</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody id="recent-events-body">
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div style="font-size:2rem;margin-bottom:8px;opacity:0.3"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
                            <div>Waiting for events...</div>
                            <div style="font-size:0.75rem;margin-top:4px">Send your first event via the API to see data here.</div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
