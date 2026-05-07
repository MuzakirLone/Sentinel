<div id="alerts-page">
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:12px">
            <h2>Alert Queue</h2>
            <span class="badge badge-pending" id="alert-pending-count" style="font-size:0.85rem;padding:4px 12px">0</span>
        </div>
        <div class="subtitle">Triage alerts, escalate to cases, and track investigation outcomes</div>
    </div>

    <div class="page-body">
        <div class="filters-bar">
            <select id="alert-status-filter" class="filter-select" onchange="Sentinel.loadAlertsQueue(this.value)">
                <option value="pending">Pending</option>
                <option value="in_case">In Case</option>
                <option value="resolved">Resolved</option>
                <option value="dismissed">Dismissed</option>
            </select>
            <button class="btn btn-ghost btn-sm" onclick="Sentinel.loadAlertsQueue(document.getElementById('alert-status-filter').value)">↻ Refresh</button>
        </div>

        <div id="alert-items">
            <div style="text-align:center;padding:40px;color:var(--text-muted)">
                <div class="loading-spinner" style="margin:0 auto"></div>
                <div style="margin-top:12px">Loading alert queue...</div>
            </div>
        </div>
    </div>
</div>
