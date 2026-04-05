<?php
$pageTitle = 'Audit Logs';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin', 'staff']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-clipboard-list" style="color:var(--teal-core);"></i> Audit Logs</h2>
  </div>
  <div id="alert-container"></div>

  <!-- Filters -->
  <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;margin-bottom:16px;">
    <input type="text" class="form-control" id="filter-action" placeholder="Filter by action (e.g. login)…" />
    <select class="form-control" id="filter-entity">
      <option value="">All Entity Types</option>
      <option value="user">User</option>
      <option value="appointment">Appointment</option>
      <option value="feedback">Feedback</option>
    </select>
    <button class="btn btn-primary" id="btn-filter"><i class="fa-solid fa-filter"></i> Apply</button>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="logs-table">
      <thead>
        <tr><th>#</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th><th>Date</th></tr>
      </thead>
      <tbody id="logs-body">
        <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
      </tbody>
    </table>
  </div>

  <div id="logs-pagination" style="display:none;justify-content:space-between;align-items:center;margin-top:16px;">
    <button class="btn btn-sm btn-outline" id="btn-prev">Previous</button>
    <span class="text-sm text-muted" id="page-info">Page 1</span>
    <button class="btn btn-sm btn-outline" id="btn-next">Next</button>
  </div>
</div>

<script>
(function(){
  var currentPage = 1;

  function loadLogs() {
    var body = document.getElementById('logs-body');
    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>';

    var params = { page: currentPage, limit: 20 };
    var actionFilter = document.getElementById('filter-action').value.trim();
    var entityFilter = document.getElementById('filter-entity').value;
    if (actionFilter) params.action = actionFilter;
    if (entityFilter) params.entity_type = entityFilter;

    utils.apiGet(utils.apiUrl('reports/audit-logs.php'), params, function(err, res) {
      if (err || !res || !res.success) {
        body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Failed to load logs.</td></tr>';
        return;
      }
      var logs = res.data.logs || [];
      var pag  = res.data.pagination;

      if (!logs.length) {
        body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No audit logs found.</td></tr>';
        document.getElementById('logs-pagination').style.display = 'none';
        return;
      }

      var html = '';
      logs.forEach(function(log) {
        var badgeCls = 'badge-secondary';
        if (log.action.indexOf('login') !== -1) badgeCls = 'badge-success';
        else if (log.action.indexOf('create') !== -1 || log.action.indexOf('register') !== -1) badgeCls = 'badge-primary';
        else if (log.action.indexOf('cancel') !== -1) badgeCls = 'badge-danger';

        html += '<tr>'
          + '<td>#' + log.id + '</td>'
          + '<td>' + utils.escapeHtml(log.user_name || 'System') + '</td>'
          + '<td><span class="badge ' + badgeCls + '">' + utils.escapeHtml(log.action) + '</span></td>'
          + '<td>' + (log.entity_type ? utils.escapeHtml(log.entity_type) + (log.entity_id ? ' #' + log.entity_id : '') : '—') + '</td>'
          + '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + utils.escapeHtml(log.details || '—') + '</td>'
          + '<td>' + utils.escapeHtml(log.ip_address || '—') + '</td>'
          + '<td>' + utils.escapeHtml(log.created_at_formatted || log.created_at || '—') + '</td>'
          + '</tr>';
      });
      body.innerHTML = html;

      if (pag && pag.total_pages > 1) {
        document.getElementById('logs-pagination').style.display = 'flex';
        document.getElementById('page-info').textContent = 'Page ' + pag.current_page + ' of ' + pag.total_pages;
        document.getElementById('btn-prev').disabled = !pag.has_prev;
        document.getElementById('btn-next').disabled = !pag.has_next;
      } else {
        document.getElementById('logs-pagination').style.display = 'none';
      }
    });
  }

  document.getElementById('btn-filter').addEventListener('click', function(){ currentPage = 1; loadLogs(); });
  document.getElementById('btn-prev').addEventListener('click', function(){ currentPage--; loadLogs(); });
  document.getElementById('btn-next').addEventListener('click', function(){ currentPage++; loadLogs(); });
  document.getElementById('filter-action').addEventListener('keyup', function(e){ if(e.key==='Enter'){currentPage=1;loadLogs();} });

  loadLogs();
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
