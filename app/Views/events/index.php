<div id="events-page">
    <div class="page-header">
        <h2>Event Log</h2>
        <div class="subtitle">All security events from your applications</div>
    </div>

    <div class="page-body">
        <div class="filters-bar">
            <select id="event-type-filter" class="filter-select" onchange="Sentinel.loadEvents(1)">
                <option value="">All Event Types</option>
                <option value="login_success">login_success</option>
                <option value="login_failed">login_failed</option>
                <option value="signup">signup</option>
                <option value="password_change">password_change</option>
                <option value="email_change">email_change</option>
                <option value="page_view">page_view</option>
                <option value="api_call">api_call</option>
                <option value="post_create">post_create</option>
                <option value="data_export">data_export</option>
            </select>
            <button class="btn btn-ghost btn-sm" onclick="Sentinel.loadEvents(1)">↻ Refresh</button>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Risk</th>
                            <th>Device</th>
                            <th>Country</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody id="events-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div class="loading-spinner" style="margin:0 auto"></div>
                            <div style="margin-top:12px">Loading events...</div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div id="events-pagination" class="pagination"></div>
        </div>
    </div>
</div>
