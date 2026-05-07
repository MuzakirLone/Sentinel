<div id="users-page">
    <div class="page-header">
        <h2>Users</h2>
        <div class="subtitle">Tracked user accounts and risk profiles</div>
    </div>

    <div class="page-body">
        <div class="filters-bar">
            <div class="search-bar">
                <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                <input type="text" id="users-search" class="form-input" placeholder="Search by email, ID, or username..." style="padding-left:36px" onkeyup="if(event.key==='Enter')Sentinel.loadUsers(1)">
            </div>
            <select id="users-sort" class="filter-select" onchange="Sentinel.usersSort=this.value;Sentinel.loadUsers(1)">
                <option value="risk_score">Sort by Risk Score</option>
                <option value="last_seen_at">Sort by Last Seen</option>
                <option value="total_events">Sort by Event Count</option>
                <option value="first_seen_at">Sort by First Seen</option>
            </select>
            <select id="users-order" class="filter-select" onchange="Sentinel.usersOrder=this.value;Sentinel.loadUsers(1)">
                <option value="DESC">Descending</option>
                <option value="ASC">Ascending</option>
            </select>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Risk Score</th>
                            <th>Risk Level</th>
                            <th>Status</th>
                            <th>Events</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div class="loading-spinner" style="margin:0 auto"></div>
                            <div style="margin-top:12px">Loading users...</div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div id="users-pagination" class="pagination"></div>
        </div>
    </div>
</div>
