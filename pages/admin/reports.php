<?php
$pageTitle = 'Reports';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin', 'staff']);

// Chart data — same queries as Overview dashboard
$pdo = getDB();
$monthlyData    = $pdo->query("SELECT DATE_FORMAT(appointment_date,'%Y-%m') AS month, COUNT(*) AS cnt, status
  FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY month, status ORDER BY month")->fetchAll();
$pendingAppts   = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();
$confirmedAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='confirmed'")->fetchColumn();
$completedAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='completed'")->fetchColumn();
$cancelledAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='cancelled'")->fetchColumn();
$totalAppts     = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
?>

<div class="card-glass" style="padding:24px;">

  <!-- Header -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:28px;">
    <h2 style="margin:0;"><i class="fa-solid fa-chart-pie" style="color:var(--teal-core);margin-right:10px;"></i>Reports</h2>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-sm btn-outline" id="btnRefresh" onclick="loadAll()">
        <i class="fa-solid fa-rotate-right"></i> Refresh
      </button>
    </div>
  </div>

  <div id="alert-container"></div>

  <!-- ── Stat Cards ───────────────────────────── -->
  <div id="statsGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;">
    <!-- skeleton loaders -->
    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="rpt-stat-card rpt-skeleton" style="height:110px;"></div>
    <?php endfor; ?>
  </div>

  <!-- ── Charts Row ────────────────────────────── -->
  <div style="display:grid;grid-template-columns:5fr 3fr;gap:20px;" class="chart-row">

    <!-- Appointment Trend -->
    <div class="card-glass" style="padding:20px;">
      <h3 style="margin:0 0 12px;font-size:1rem;"><i class="fa-solid fa-chart-line" style="color:var(--teal-core);margin-right:6px;"></i>Appointment Trend</h3>
      <div id="trendLoading" class="rpt-loading"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i>Loading…</div>
      <div style="position:relative;height:240px;display:none;" id="trendWrap"><canvas id="trendChart"></canvas></div>
      <div id="trendEmpty" class="rpt-loading" style="display:none;">No trend data available.</div>
    </div>

    <!-- Status Distribution -->
    <div class="card-glass" style="padding:20px;display:flex;flex-direction:column;align-items:center;">
      <h3 style="margin:0 0 12px;font-size:1rem;align-self:flex-start;"><i class="fa-solid fa-chart-pie" style="color:var(--teal-core);margin-right:6px;"></i>Status Distribution</h3>
      <div id="statusLoading" class="rpt-loading"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i>Loading…</div>
      <div style="position:relative;width:100%;max-width:220px;display:none;" id="statusWrap"><canvas id="statusChart"></canvas></div>
      <div id="statusEmpty" class="rpt-loading" style="display:none;">No status data available.</div>
    </div>

  </div><!-- /.chart-row -->
</div><!-- /.card-glass -->

<style>
/* ── Stat cards ── */
.rpt-stat-card {
  background: var(--card-glass-bg, rgba(255,255,255,.06));
  border: 1px solid var(--input-border, rgba(61,106,138,.18));
  border-radius: 16px;
  padding: 20px 18px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  transition: box-shadow .2s, transform .2s;
}
.rpt-stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.09); transform: translateY(-2px); }
.rpt-stat-top { display:flex; align-items:center; gap:12px; width:100%; }
.rpt-stat-icon {
  width: 46px; height: 46px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}
.rpt-stat-info { display:flex; flex-direction:column; gap:2px; }
.rpt-stat-value { font-size: 2rem; font-weight: 800; line-height: 1; color: var(--text-main, #1e293b); }
.rpt-stat-label { font-size: .78rem; color: var(--text-muted, #64748B); font-weight: 500; letter-spacing:.02em; }

/* ── Skeleton ── */
.rpt-skeleton {
  background: linear-gradient(90deg,
    var(--input-border, rgba(61,106,138,.12)) 25%,
    var(--card-glass-bg, rgba(61,106,138,.06)) 50%,
    var(--input-border, rgba(61,106,138,.12)) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
  border-radius: 16px;
}
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* ── Loading / empty ── */
.rpt-loading { text-align:center; padding:32px 0; color:var(--text-muted,#64748B); font-size:.9rem; }

/* ── Responsive ── */
@media (max-width:720px) { .chart-row { grid-template-columns:1fr !important; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
  'use strict';

  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#8AAEC7';
  Chart.defaults.animation.duration = 700;

  var STATUS_COLORS = {
    pending:     '#F59E0B',
    confirmed:   '#3D6A8A',
    in_progress: '#06B6D4',
    completed:   '#10B981',
    cancelled:   '#EF4444',
    no_show:     '#F97316',
    rescheduled: '#8B5CF6'
  };

  var _trendChart  = null;
  var _statusChart = null;

  /* ── Helpers ── */
  function show(id) { var e = document.getElementById(id); if (e) e.style.display = ''; }
  function hide(id) { var e = document.getElementById(id); if (e) e.style.display = 'none'; }
  function showFlex(id) { var e = document.getElementById(id); if (e) e.style.display = 'flex'; }
  function capLabel(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g,' ') : s; }

  function animateNumber(el, target) {
    target = parseInt(target, 10) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    var start = performance.now(), dur = 1000;
    (function tick(now) {
      var t = Math.min((now - start) / dur, 1);
      t = 1 - Math.pow(1 - t, 3);
      el.textContent = Math.round(target * t).toLocaleString();
      if (t < 1) requestAnimationFrame(tick);
    })(performance.now());
  }

  /* ── Stat Cards ── */
  function loadStats() {
    utils.apiGet(utils.apiUrl('reports/dashboard-stats.php'), {}, function (err, res) {
      var grid = document.getElementById('statsGrid');
      if (err || !res || !res.success) {
        grid.innerHTML = '<p class="rpt-loading" style="grid-column:1/-1;">Failed to load stats.</p>';
        return;
      }
      var d = res.data;

      var cards = [
        { label: 'Total Users',        value: d.total_users,    icon: 'fa-users',         color: '#3D6A8A' },
        { label: 'Doctors',            value: d.total_doctors,  icon: 'fa-user-doctor',   color: '#06B6D4' },
        { label: 'Patients',           value: d.total_patients, icon: 'fa-user-injured',  color: '#10B981' },
        { label: 'Total Appointments', value: <?= $totalAppts ?>,              icon: 'fa-calendar-check', color: '#8B5CF6' },
      ];

      grid.innerHTML = cards.map(function (c) {
        return '<div class="rpt-stat-card">'
          + '<div class="rpt-stat-top">'
          + '<div class="rpt-stat-icon" style="background:' + c.color + '18;color:' + c.color + ';">'
          + '<i class="fa-solid ' + c.icon + '"></i></div>'
          + '<div class="rpt-stat-info">'
          + '<div class="rpt-stat-value" id="sv-' + c.icon.replace(/[^a-z]/g,'') + '">0</div>'
          + '<div class="rpt-stat-label">' + c.label + '</div>'
          + '</div></div></div>';
      }).join('');

      cards.forEach(function (c) {
        var el = document.getElementById('sv-' + c.icon.replace(/[^a-z]/g,''));
        if (el) animateNumber(el, c.value);
      });
    });
  }

  /* ── Appointment Trend — PHP-embedded data, mirrors dashboard exactly ── */
  (function () {
    var rawData = <?= json_encode($monthlyData) ?>;
    var months = [], completed = {}, pending = {}, cancelled = {};
    rawData.forEach(function (r) {
      if (months.indexOf(r.month) === -1) months.push(r.month);
      if (!completed[r.month]) completed[r.month] = 0;
      if (!pending[r.month])   pending[r.month]   = 0;
      if (!cancelled[r.month]) cancelled[r.month] = 0;
      if (r.status === 'completed')                              completed[r.month]  = parseInt(r.cnt);
      else if (r.status === 'pending' || r.status === 'confirmed') pending[r.month] += parseInt(r.cnt);
      else if (r.status === 'cancelled')                         cancelled[r.month]  = parseInt(r.cnt);
    });
    months.sort();

    hide('trendLoading');
    if (months.length) {
      show('trendWrap');
      new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
          labels: months.map(function (m) { var d = new Date(m + '-01'); return d.toLocaleString('default', { month: 'short', year: '2-digit' }); }),
          datasets: [
            { label: 'Completed',           data: months.map(function (m) { return completed[m]  || 0; }), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 7, pointBackgroundColor: '#10B981', pointBorderColor: '#fff', pointBorderWidth: 2 },
            { label: 'Pending / Confirmed', data: months.map(function (m) { return pending[m]    || 0; }), borderColor: '#3D6A8A', backgroundColor: 'rgba(61,106,138,.10)',  fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 7, pointBackgroundColor: '#3D6A8A', pointBorderColor: '#fff', pointBorderWidth: 2 },
            { label: 'Cancelled',           data: months.map(function (m) { return cancelled[m]  || 0; }), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,.06)',    fill: true, tension: .4, borderWidth: 2,   pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: '#EF4444', pointBorderColor: '#fff', pointBorderWidth: 2 }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '500' } } },
            tooltip: { backgroundColor: 'rgba(15,25,40,.88)', titleFont: { weight: '600' }, bodySpacing: 6, padding: 12, cornerRadius: 8, displayColors: true, boxPadding: 4 }
          },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, padding: 8 }, grid: { color: 'rgba(138,174,199,.08)', drawBorder: false }, border: { display: false } },
            x: { grid: { display: false }, border: { display: false }, ticks: { padding: 6 } }
          }
        }
      });
    } else {
      show('trendEmpty');
    }
  })();

  /* ── Status Distribution — PHP-embedded data, mirrors dashboard exactly ── */
  (function () {
    hide('statusLoading');
    show('statusWrap');
    new Chart(document.getElementById('statusChart'), {
      type: 'doughnut',
      data: {
        labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
        datasets: [{
          data: [<?= $pendingAppts ?>, <?= $confirmedAppts ?>, <?= $completedAppts ?>, <?= $cancelledAppts ?>],
          backgroundColor:      ['#F59E0B', '#3D6A8A', '#10B981', '#EF4444'],
          hoverBackgroundColor: ['#D97706', '#2C5068', '#059669', '#DC2626'],
          borderWidth: 0, hoverOffset: 6, spacing: 2
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: true, cutout: '62%',
        plugins: {
          legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 11, weight: '500' } } },
          tooltip: { backgroundColor: 'rgba(15,25,40,.88)', titleFont: { weight: '600' }, padding: 12, cornerRadius: 8, boxPadding: 4, callbacks: {
            label: function (ctx) { var t = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0); return ' ' + ctx.label + ': ' + ctx.raw + ' (' + (t ? Math.round(ctx.raw / t * 100) : 0) + '%)'; }
          }}
        }
      }
    });
  })();

  /* ── Load all ── */
  window.loadAll = function () {
    loadStats();
    loadTrend();
    loadStatus();
  };

  loadAll();
})();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>