<?php
$pageTitle = 'Admin Dashboard';
$pageCSS   = ['dashboard.css'];
$pageJS    = [];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);

$pdo = getDB();

// KPI Stats
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDoctors  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='doctor'")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();
$totalAppts    = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppts  = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();
$confirmedAppts= $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='confirmed'")->fetchColumn();
$completedAppts= $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='completed'")->fetchColumn();
$cancelledAppts= $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='cancelled'")->fetchColumn();
$todayAppts    = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date=CURDATE()")->fetchColumn();
$avgRating     = $pdo->query("SELECT ROUND(AVG(rating),1) FROM feedback")->fetchColumn() ?: '0';

// Recent appointments
$recentAppts = $pdo->query("SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name, t.start_time, t.end_time
  FROM appointments a
  JOIN users p ON a.patient_id=p.id
  JOIN time_slots t ON a.slot_id=t.id
  JOIN users d ON t.doctor_id=d.id
  ORDER BY a.created_at DESC LIMIT 10")->fetchAll();

// Monthly data for chart (last 6 months)
$monthlyData = $pdo->query("SELECT DATE_FORMAT(appointment_date,'%Y-%m') as month, COUNT(*) as cnt, status
  FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY month, status ORDER BY month")->fetchAll();
?>

<!-- KPI Cards -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--teal-pale);color:var(--teal-core);"><i class="fa-solid fa-users"></i></div><div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--blue-pale);color:var(--blue-core);"><i class="fa-solid fa-user-doctor"></i></div><div><div class="stat-value"><?= $totalDoctors ?></div><div class="stat-label">Doctors</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--green-pale);color:var(--emerald-core);"><i class="fa-solid fa-hospital-user"></i></div><div><div class="stat-value"><?= $totalPatients ?></div><div class="stat-label">Patients</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--purple-pale,rgba(139,92,246,.1));color:#8B5CF6;"><i class="fa-solid fa-calendar-check"></i></div><div><div class="stat-value"><?= $totalAppts ?></div><div class="stat-label">Total Appointments</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--amber-pale);color:var(--amber-core);"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value"><?= $pendingAppts ?></div><div class="stat-label">Pending</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--green-pale);color:var(--emerald-core);"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-value"><?= $completedAppts ?></div><div class="stat-label">Completed</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--teal-pale);color:var(--teal-core);"><i class="fa-solid fa-calendar-day"></i></div><div><div class="stat-value"><?= $todayAppts ?></div><div class="stat-label">Today</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--amber-pale);color:var(--amber-core);"><i class="fa-solid fa-star"></i></div><div><div class="stat-value"><?= $avgRating ?></div><div class="stat-label">Avg Rating</div></div></div>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:5fr 3fr;gap:20px;margin-bottom:24px;">
  <div class="card-glass" style="padding:20px;">
    <h3 style="margin:0 0 12px;font-size:1rem;"><i class="fa-solid fa-chart-line" style="color:var(--teal-core);margin-right:6px;"></i>Appointments Trend</h3>
    <div style="position:relative;height:240px;"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="card-glass" style="padding:20px;display:flex;flex-direction:column;align-items:center;">
    <h3 style="margin:0 0 12px;font-size:1rem;align-self:flex-start;"><i class="fa-solid fa-chart-pie" style="color:var(--teal-core);margin-right:6px;"></i>Status Distribution</h3>
    <div style="position:relative;width:100%;max-width:220px;"><canvas id="statusChart"></canvas></div>
  </div>
</div>

<!-- Pending Alert -->
<?php if($pendingAppts > 0): ?>
<div class="mq-alert mq-alert-warning" style="margin-bottom:20px;">
  <i class="fa-solid fa-triangle-exclamation"></i> <strong><?= $pendingAppts ?></strong> appointment(s) awaiting confirmation.
  <a href="<?= BASE_URL ?>/pages/admin/appointments.php?status=pending" style="margin-left:8px;font-weight:700;">Review →</a>
</div>
<?php endif; ?>

<!-- Recent Appointments (auto-refresh) -->
<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h3 style="margin:0;"><i class="fa-solid fa-list" style="color:var(--teal-core);"></i> Recent Appointments</h3>
    <span class="text-muted text-sm" id="lastRefresh">Updated just now</span>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
      <tbody id="recentBody">
        <?php foreach($recentAppts as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['patient_name']) ?></td>
          <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
          <td><?= formatDate($a['appointment_date']) ?></td>
          <td><?= substr($a['start_time'],0,5) ?> – <?= substr($a['end_time'],0,5) ?></td>
          <td><span class="badge badge-<?= $a['status']==='pending'?'warning':($a['status']==='confirmed'?'success':($a['status']==='completed'?'primary':'danger')) ?>"><?= $a['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
  /* ── Chart.js global defaults (match MediQueue theme) ── */
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#8AAEC7';
  Chart.defaults.animation.duration = 800;
  Chart.defaults.animation.easing = 'easeOutQuart';

  // Prepare monthly data from PHP
  var rawData = <?= json_encode($monthlyData) ?>;
  var months = [], completed = {}, pending = {}, cancelled = {};
  rawData.forEach(function(r){
    if(months.indexOf(r.month)===-1) months.push(r.month);
    if(!completed[r.month]) completed[r.month]=0;
    if(!pending[r.month]) pending[r.month]=0;
    if(!cancelled[r.month]) cancelled[r.month]=0;
    if(r.status==='completed') completed[r.month]=parseInt(r.cnt);
    else if(r.status==='pending'||r.status==='confirmed') pending[r.month]+=parseInt(r.cnt);
    else if(r.status==='cancelled') cancelled[r.month]=parseInt(r.cnt);
  });
  months.sort();

  // ── Trend Chart (line) ──
  new Chart(document.getElementById('trendChart'),{
    type:'line',
    data:{
      labels:months.map(function(m){var d=new Date(m+'-01');return d.toLocaleString('default',{month:'short',year:'2-digit'});}),
      datasets:[
        {label:'Completed',data:months.map(function(m){return completed[m]||0;}),borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.12)',fill:true,tension:.4,borderWidth:2.5,pointRadius:4,pointHoverRadius:7,pointBackgroundColor:'#10B981',pointBorderColor:'#fff',pointBorderWidth:2},
        {label:'Pending / Confirmed',data:months.map(function(m){return pending[m]||0;}),borderColor:'#3D6A8A',backgroundColor:'rgba(61,106,138,.10)',fill:true,tension:.4,borderWidth:2.5,pointRadius:4,pointHoverRadius:7,pointBackgroundColor:'#3D6A8A',pointBorderColor:'#fff',pointBorderWidth:2},
        {label:'Cancelled',data:months.map(function(m){return cancelled[m]||0;}),borderColor:'#EF4444',backgroundColor:'rgba(239,68,68,.06)',fill:true,tension:.4,borderWidth:2,pointRadius:3,pointHoverRadius:6,pointBackgroundColor:'#EF4444',pointBorderColor:'#fff',pointBorderWidth:2}
      ]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{position:'bottom',labels:{usePointStyle:true,pointStyle:'circle',padding:16,font:{size:11,weight:'500'}}},
        tooltip:{backgroundColor:'rgba(15,25,40,.88)',titleFont:{weight:'600'},bodySpacing:6,padding:12,cornerRadius:8,displayColors:true,boxPadding:4}
      },
      scales:{
        y:{beginAtZero:true,ticks:{stepSize:1,padding:8},grid:{color:'rgba(138,174,199,.08)',drawBorder:false},border:{display:false}},
        x:{grid:{display:false},border:{display:false},ticks:{padding:6}}
      }
    }
  });

  // ── Status Doughnut ──
  new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{
      labels:['Pending','Confirmed','Completed','Cancelled'],
      datasets:[{
        data:[<?= $pendingAppts ?>,<?= $confirmedAppts ?>,<?= $completedAppts ?>,<?= $cancelledAppts ?>],
        backgroundColor:['#F59E0B','#3D6A8A','#10B981','#EF4444'],
        hoverBackgroundColor:['#D97706','#2C5068','#059669','#DC2626'],
        borderWidth:0,
        hoverOffset:6,
        spacing:2
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:true,
      cutout:'62%',
      plugins:{
        legend:{position:'bottom',labels:{usePointStyle:true,pointStyle:'circle',padding:14,font:{size:11,weight:'500'}}},
        tooltip:{backgroundColor:'rgba(15,25,40,.88)',titleFont:{weight:'600'},padding:12,cornerRadius:8,boxPadding:4,callbacks:{
          label:function(ctx){var total=ctx.dataset.data.reduce(function(a,b){return a+b;},0);var pct=total?Math.round(ctx.raw/total*100):0;return ' '+ctx.label+': '+ctx.raw+' ('+pct+'%)';}
        }}
      }
    }
  });

  // Auto-refresh recent table every 30s
  setInterval(function(){
    utils.apiGet(utils.apiUrl('appointments/list.php'),{per_page:10,sort:'newest'},function(err,data){
      if(!data||!data.success) return;
      var badgeMap={pending:'badge-warning',confirmed:'badge-success',completed:'badge-primary',cancelled:'badge-danger'};
      var tb=document.getElementById('recentBody'); tb.innerHTML='';
      (data.data.appointments||[]).forEach(function(a){
        tb.insertAdjacentHTML('beforeend','<tr><td>'+utils.escapeHtml(a.patient_name||'—')+'</td><td>Dr. '+utils.escapeHtml(a.doctor_name||'—')+'</td><td>'+utils.formatDate(a.appointment_date)+'</td><td>'+(a.start_time?a.start_time.substring(0,5):'')+' – '+(a.end_time?a.end_time.substring(0,5):'')+'</td><td><span class="badge '+(badgeMap[a.status]||'badge-info')+'">'+a.status+'</span></td></tr>');
      });
      document.getElementById('lastRefresh').textContent='Updated '+new Date().toLocaleTimeString();
    });
  },30000);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>