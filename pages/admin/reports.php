<?php
$pageTitle = 'Reports & Analytics';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-chart-line" style="color:var(--teal-core);"></i> Reports & Analytics</h2>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-sm btn-outline" onclick="exportReport()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button class="btn btn-sm btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
  </div>
  <div id="alert-container"></div>

  <!-- Report Tabs -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;">
    <button class="btn btn-sm btn-outline report-tab active" data-tab="overview">Overview</button>
    <button class="btn btn-sm btn-outline report-tab" data-tab="appointments">Appointments</button>
    <button class="btn btn-sm btn-outline report-tab" data-tab="doctors">Doctors</button>
    <button class="btn btn-sm btn-outline report-tab" data-tab="patients">Patients</button>
  </div>

  <!-- Overview Tab -->
  <div id="tabOverview" class="report-panel">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
      <div class="card-glass" style="padding:16px;"><h4 style="margin:0 0 12px;font-size:.95rem;"><i class="fa-solid fa-chart-column" style="color:var(--teal-core);margin-right:6px;"></i>Monthly Appointments</h4><div style="position:relative;height:220px;"><canvas id="monthlyChart"></canvas></div></div>
      <div class="card-glass" style="padding:16px;display:flex;flex-direction:column;align-items:center;"><h4 style="margin:0 0 12px;font-size:.95rem;align-self:flex-start;"><i class="fa-solid fa-chart-pie" style="color:var(--teal-core);margin-right:6px;"></i>Specialization Distribution</h4><div style="position:relative;width:100%;max-width:200px;"><canvas id="specChart"></canvas></div></div>
    </div>
    <div class="card-glass" style="padding:16px;"><h4 style="margin:0 0 12px;font-size:.95rem;"><i class="fa-solid fa-chart-area" style="color:var(--teal-core);margin-right:6px;"></i>Daily Trend (Last 30 days)</h4><div style="position:relative;height:180px;"><canvas id="dailyChart"></canvas></div></div>
  </div>

  <!-- Appointments Tab -->
  <div id="tabAppointments" class="report-panel" style="display:none;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
      <input type="date" id="rptDateFrom" class="form-control" style="width:auto;" />
      <input type="date" id="rptDateTo" class="form-control" style="width:auto;" />
      <button class="btn btn-sm btn-primary" onclick="loadApptReport()"><i class="fa-solid fa-search"></i> Generate</button>
    </div>
    <div id="apptReportContent"><p class="text-muted text-center">Select date range and generate.</p></div>
  </div>

  <!-- Doctors Tab -->
  <div id="tabDoctors" class="report-panel" style="display:none;">
    <div class="table-responsive">
      <table class="data-table" id="doctorReportTable">
        <thead><tr><th>Doctor</th><th>Specialization</th><th>Total Appts</th><th>Completed</th><th>Avg Rating</th></tr></thead>
        <tbody id="doctorReportBody"><tr><td colspan="5" class="text-center text-muted">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- Patients Tab -->
  <div id="tabPatients" class="report-panel" style="display:none;">
    <div class="card-glass" style="padding:16px;margin-bottom:16px;"><h4 style="margin:0 0 8px;"><i class="fas fa-chart-line" style="color:var(--teal-core);margin-right:6px;"></i>New Registrations (Monthly)</h4><div style="position:relative;height:220px;"><canvas id="regChart"></canvas></div></div>
    <div id="patientStats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;"></div>
  </div>
</div>

<style>
.report-tab{background:transparent;border:1px solid var(--input-border);border-radius:8px;font-weight:600;transition:all .2s;}
.report-tab.active,.report-tab:hover{background:var(--teal-core);color:#fff;border-color:var(--teal-core);}
@media print{.sidebar,.top-header,.report-tab,.btn{display:none !important;}.card-glass{box-shadow:none !important;border:1px solid #ddd !important;}}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
  /* ── Chart.js theme defaults ── */
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#8AAEC7';
  Chart.defaults.animation.duration = 800;
  Chart.defaults.animation.easing = 'easeOutQuart';

  var tealPalette = ['#3D6A8A','#5B8DB4','#4E84A6','#2C5068','#8AAEC7','#10B981','#3B82F6','#8B5CF6'];

  // Tab switching
  document.querySelectorAll('.report-tab').forEach(function(t){
    t.addEventListener('click',function(){
      document.querySelectorAll('.report-tab').forEach(function(x){x.classList.remove('active');});
      this.classList.add('active');
      document.querySelectorAll('.report-panel').forEach(function(p){p.style.display='none';});
      document.getElementById('tab'+this.dataset.tab.charAt(0).toUpperCase()+this.dataset.tab.slice(1)).style.display='';
    });
  });

  // ── OVERVIEW ──
  utils.apiGet(utils.apiUrl('reports/overview.php'),{},function(err,data){
    if(!data||!data.success) return;
    var d=data.data;

    // Monthly Chart (bar with gradient feel)
    if(d.monthly){
      new Chart(document.getElementById('monthlyChart'),{
        type:'bar',
        data:{labels:d.monthly.map(function(m){return m.month;}),datasets:[{
          label:'Appointments',data:d.monthly.map(function(m){return m.count;}),
          backgroundColor:'rgba(61,106,138,.65)',hoverBackgroundColor:'rgba(61,106,138,.9)',
          borderRadius:8,borderSkipped:false,maxBarThickness:36
        }]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,25,40,.88)',padding:12,cornerRadius:8,boxPadding:4}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,padding:8},grid:{color:'rgba(138,174,199,.08)',drawBorder:false},border:{display:false}},x:{grid:{display:false},border:{display:false}}}}
      });
    }

    // Specialization Chart (doughnut)
    if(d.specializations){
      new Chart(document.getElementById('specChart'),{
        type:'doughnut',
        data:{labels:d.specializations.map(function(s){return s.specialization||'General';}),datasets:[{
          data:d.specializations.map(function(s){return s.count;}),
          backgroundColor:tealPalette.slice(0,d.specializations.length),
          borderWidth:0,hoverOffset:6,spacing:2
        }]},
        options:{responsive:true,maintainAspectRatio:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,pointStyle:'circle',padding:12,font:{size:11,weight:'500'}}},tooltip:{backgroundColor:'rgba(15,25,40,.88)',padding:12,cornerRadius:8,boxPadding:4,callbacks:{label:function(ctx){var total=ctx.dataset.data.reduce(function(a,b){return a+b;},0);var pct=total?Math.round(ctx.raw/total*100):0;return ' '+ctx.label+': '+ctx.raw+' ('+pct+'%)';}}}}}
      });
    }

    // Daily Chart (area line)
    if(d.daily){
      new Chart(document.getElementById('dailyChart'),{
        type:'line',
        data:{labels:d.daily.map(function(x){return x.date;}),datasets:[{
          label:'Appointments',data:d.daily.map(function(x){return x.count;}),
          borderColor:'#3D6A8A',backgroundColor:'rgba(61,106,138,.10)',fill:true,tension:.4,
          borderWidth:2.5,pointRadius:2,pointHoverRadius:6,pointBackgroundColor:'#3D6A8A',pointBorderColor:'#fff',pointBorderWidth:2
        }]},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,25,40,.88)',padding:12,cornerRadius:8}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,padding:8},grid:{color:'rgba(138,174,199,.08)',drawBorder:false},border:{display:false}},x:{grid:{display:false},border:{display:false},ticks:{maxTicksLimit:10}}}}
      });
    }
  });

  // ── DOCTORS REPORT ──
  utils.apiGet(utils.apiUrl('reports/doctors.php'),{},function(err,data){
    if(!data||!data.success) return;
    var tb=document.getElementById('doctorReportBody'); tb.innerHTML='';
    (data.data.doctors||[]).forEach(function(d){
      tb.insertAdjacentHTML('beforeend','<tr><td>Dr. '+utils.escapeHtml(d.full_name)+'</td><td>'+utils.escapeHtml(d.specialization||'—')+'</td><td>'+d.total_appointments+'</td><td>'+d.completed+'</td><td>'+(d.avg_rating?'<span style="color:#F59E0B;">'+parseFloat(d.avg_rating).toFixed(1)+' ★</span>':'<span class="text-muted">—</span>')+'</td></tr>');
    });
  });

  // ── APPOINTMENTS REPORT ──
  window.loadApptReport=function(){
    var from=document.getElementById('rptDateFrom').value, to=document.getElementById('rptDateTo').value;
    if(!from||!to){utils.showAlert('Select both dates.','warning');return;}
    var el=document.getElementById('apptReportContent');el.innerHTML='<p class="text-muted text-center">Loading…</p>';
    utils.apiGet(utils.apiUrl('reports/appointments.php'),{date_from:from,date_to:to},function(err,data){
      if(!data||!data.success){el.innerHTML='<p class="text-muted">Failed to load.</p>';return;}
      var d=data.data;
      el.innerHTML='<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px;">'
        +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+d.total+'</div><div class="stat-label">Total</div></div>'
        +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+d.completed+'</div><div class="stat-label">Completed</div></div>'
        +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+d.cancelled+'</div><div class="stat-label">Cancelled</div></div>'
        +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+(d.completion_rate||0)+'%</div><div class="stat-label">Completion Rate</div></div>'
        +'</div>'
        +(d.by_status?'<div style="position:relative;height:200px;"><canvas id="apptStatusChart"></canvas></div>':'');
      if(d.by_status){
        var statusColors={pending:'#F59E0B',confirmed:'#3D6A8A',completed:'#10B981',cancelled:'#EF4444',rescheduled:'#8B5CF6'};
        new Chart(document.getElementById('apptStatusChart'),{
          type:'bar',data:{labels:d.by_status.map(function(s){return s.status.charAt(0).toUpperCase()+s.status.slice(1);}),datasets:[{label:'Count',data:d.by_status.map(function(s){return s.count;}),backgroundColor:d.by_status.map(function(s){return statusColors[s.status]||'#3D6A8A';}),borderRadius:8,borderSkipped:false,maxBarThickness:40}]},
          options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,25,40,.88)',padding:12,cornerRadius:8}},scales:{y:{beginAtZero:true,grid:{color:'rgba(138,174,199,.08)',drawBorder:false},border:{display:false}},x:{grid:{display:false},border:{display:false}}}}
        });
      }
    });
  };

  // ── PATIENTS REPORT ──
  utils.apiGet(utils.apiUrl('reports/patients.php'),{},function(err,data){
    if(!data||!data.success) return;
    var d=data.data;
    var el=document.getElementById('patientStats');
    el.innerHTML='<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+d.total_patients+'</div><div class="stat-label">Total Patients</div></div>'
      +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+d.active_patients+'</div><div class="stat-label">Active (with appts)</div></div>'
      +'<div class="card-glass stat-card" style="padding:14px;"><div class="stat-value">'+(d.avg_age||'—')+'</div><div class="stat-label">Avg Age</div></div>';
    if(d.registrations){
      new Chart(document.getElementById('regChart'),{
        type:'line',data:{labels:d.registrations.map(function(r){return r.month;}),datasets:[{
          label:'New Patients',data:d.registrations.map(function(r){return r.count;}),
          borderColor:'#3D6A8A',backgroundColor:'rgba(61,106,138,.10)',fill:true,tension:.4,
          borderWidth:2.5,pointRadius:4,pointHoverRadius:7,pointBackgroundColor:'#3D6A8A',pointBorderColor:'#fff',pointBorderWidth:2
        }]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,25,40,.88)',padding:12,cornerRadius:8}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,padding:8},grid:{color:'rgba(138,174,199,.08)',drawBorder:false},border:{display:false}},x:{grid:{display:false},border:{display:false}}}}
      });
    }
  });

  window.exportReport=function(){
    var activeTab=document.querySelector('.report-tab.active').dataset.tab;
    if(activeTab==='doctors') utils.exportTableToCSV('doctorReportTable','doctor_report.csv');
    else utils.showAlert('CSV export available for Doctors tab. Use Print for other reports.','info');
  };
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>