<div id="cases-page">
    <div class="page-header">
        <h2>Cases</h2>
        <div class="subtitle">SOC case management for high-risk alerts and investigations</div>
    </div>

    <div class="page-body">
        <div class="filters-bar">
            <select id="case-status-filter" class="filter-select" onchange="Sentinel.loadCases(1)">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
            </select>
            <input type="text" id="case-search" class="filter-input" placeholder="Search case title, user, email" onkeyup="if(event.key==='Enter'){Sentinel.loadCases(1)}">
            <button class="btn btn-ghost btn-sm" onclick="Sentinel.loadCases(1)">↻ Refresh</button>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>User</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>SLA Due</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody id="cases-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div class="loading-spinner" style="margin:0 auto"></div>
                            <div style="margin-top:12px">Loading cases...</div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div id="cases-pagination" class="pagination"></div>
        </div>
    </div>
</div>
