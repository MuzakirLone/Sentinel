<div id="audit-page">
    <div class="page-header">
        <h2>Audit Trail</h2>
        <div class="subtitle">Field-level change tracking for compliance and investigation</div>
    </div>

    <div class="page-body">
        <div class="card">
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Field Changed</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Changed By</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody id="audit-table-body">
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                            <div class="loading-spinner" style="margin:0 auto"></div>
                            <div style="margin-top:12px">Loading audit trail...</div>
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div id="audit-pagination" class="pagination"></div>
        </div>
    </div>
</div>
