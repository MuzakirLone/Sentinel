<div id="review-page">
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:12px">
            <h2>Review Queue</h2>
            <span class="badge badge-pending" id="review-pending-count" style="font-size:0.85rem;padding:4px 12px">0</span>
        </div>
        <div class="subtitle">Flagged accounts requiring manual investigation</div>
    </div>

    <div class="page-body">
        <div class="filters-bar">
            <select id="review-status-filter" class="filter-select" onchange="Sentinel.loadReviewQueue(this.value)">
                <option value="pending">Pending</option>
                <option value="resolved">Resolved</option>
                <option value="dismissed">Dismissed</option>
            </select>
            <button class="btn btn-ghost btn-sm" onclick="Sentinel.loadReviewQueue(document.getElementById('review-status-filter').value)">↻ Refresh</button>
        </div>

        <div id="review-items">
            <div style="text-align:center;padding:40px;color:var(--text-muted)">
                <div class="loading-spinner" style="margin:0 auto"></div>
                <div style="margin-top:12px">Loading review queue...</div>
            </div>
        </div>
    </div>
</div>
