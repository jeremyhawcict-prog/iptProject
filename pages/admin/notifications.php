<?php
$pageTitle = 'Notifications';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin', 'staff']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
      <h2 style="margin:0;"><i class="fa-solid fa-bell" style="color:var(--teal-core);"></i> Notifications</h2>
      <p class="text-muted text-sm" style="margin:4px 0 0;">Stay updated on your appointments and activity.</p>
    </div>
    <button class="btn btn-sm btn-outline" id="btn-mark-all"><i class="fa-solid fa-check-double"></i> Mark All Read</button>
  </div>
  <div id="alert-container"></div>

  <div id="notifications-list">
    <div class="text-center text-muted" style="padding:40px;">Loading…</div>
  </div>
</div>

<script>
(function(){
  function loadNotifications() {
    var container = document.getElementById('notifications-list');
    container.innerHTML = '<div class="text-center text-muted" style="padding:40px;">Loading…</div>';

    utils.apiGet(utils.apiUrl('notifications/list.php'), { per_page: 50 }, function(err, res) {
      if (err || !res || !res.success) {
        container.innerHTML = '<div class="text-center text-muted" style="padding:40px;">Failed to load notifications.</div>';
        return;
      }
      var notifs = (res.data && res.data.notifications) || [];
      if (!notifs.length) {
        container.innerHTML = '<div class="text-center" style="padding:40px;"><i class="fa-regular fa-bell-slash" style="font-size:2rem;color:var(--text-white-dim);display:block;margin-bottom:12px;"></i><p class="text-muted">No notifications yet.</p></div>';
        return;
      }

      var html = '';
      notifs.forEach(function(n) {
        var unread = (n.is_read == 0) ? 'border-left:3px solid var(--teal-core);' : '';
        var icon = 'fa-bell';
        var subj = (n.subject || '').toLowerCase();
        if (subj.indexOf('confirm') !== -1) icon = 'fa-circle-check';
        else if (subj.indexOf('cancel') !== -1) icon = 'fa-circle-xmark';
        else if (subj.indexOf('complet') !== -1) icon = 'fa-flag-checkered';
        else if (subj.indexOf('new') !== -1) icon = 'fa-calendar-plus';

        html += '<div style="padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;gap:14px;align-items:flex-start;' + unread + '">'
          + '<div style="width:36px;height:36px;border-radius:50%;background:var(--teal-pale);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid ' + icon + '" style="color:var(--teal-core);font-size:.85rem;"></i></div>'
          + '<div style="flex:1;min-width:0;">'
          + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">'
          + '<strong style="font-size:.85rem;">' + utils.escapeHtml(n.subject || 'Notification') + '</strong>'
          + '<span class="text-muted" style="font-size:.75rem;white-space:nowrap;margin-left:12px;">' + (n.created_at ? utils.formatDate(n.created_at.substring(0,10)) : '') + '</span>'
          + '</div>'
          + '<p class="text-muted" style="margin:0;font-size:.82rem;line-height:1.5;">' + utils.escapeHtml(n.message || '') + '</p>'
          + '</div></div>';
      });
      container.innerHTML = html;
    });
  }

  document.getElementById('btn-mark-all').addEventListener('click', function() {
    utils.apiPost(utils.apiUrl('notifications/mark-read.php'), { all: true }, function(err, res) {
      if (!err && res && res.success) {
        utils.showToast('All notifications marked as read.', 'success');
        loadNotifications();
        var badge = document.getElementById('notif-badge');
        if (badge) badge.style.display = 'none';
      }
    });
  });

  loadNotifications();
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
