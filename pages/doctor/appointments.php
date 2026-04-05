<?php
$pageTitle = 'Appointments';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['doctor']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-list-check" style="color:var(--teal-core);"></i> All Appointments</h2>
    <button class="btn btn-sm btn-outline" onclick="exportCSV()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
  </div>
  <div id="alert-container"></div>

  <!-- Filters -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="date" id="filterDate" class="form-control" style="width:auto;" />
    <select id="filterStatus" class="form-control" style="width:auto;">
      <option value="">All Statuses</option>
      <option value="pending">Pending</option><option value="confirmed">Confirmed</option>
      <option value="completed">Completed</option><option value="cancelled">Cancelled</option>
    </select>
    <button class="btn btn-sm btn-primary" onclick="loadAppts(1)"><i class="fa-solid fa-search"></i> Filter</button>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="apptTable">
      <thead><tr><th>#</th><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="apptBody"><tr><td colspan="6" class="text-center text-muted">Loading…</td></tr></tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<script>
(function(){
  var currentPage=1;
  var badgeMap={pending:'badge-warning',confirmed:'badge-success',completed:'badge-primary',cancelled:'badge-danger',rescheduled:'badge-info'};

  window.loadAppts = function(page){
    currentPage=page||1;
    var params={page:currentPage,per_page:15};
    var d=document.getElementById('filterDate').value; if(d){ params.date_from=d; params.date_to=d; }
    var s=document.getElementById('filterStatus').value; if(s) params.status=s;
    utils.apiGet(utils.apiUrl('appointments/list.php'),params,function(err,data){
      var tb=document.getElementById('apptBody');
      if(!data||!data.success||!data.data.appointments||!data.data.appointments.length){
        tb.innerHTML='<tr><td colspan="6" class="text-center text-muted">No appointments found.</td></tr>';
        document.getElementById('pagination').innerHTML='';return;
      }
      tb.innerHTML='';
      data.data.appointments.forEach(function(a,i){
        var n=data.data.pagination?((data.data.pagination.current_page-1)*data.data.pagination.per_page+i+1):(i+1);
        var row='<tr><td>'+n+'</td>'
          +'<td>'+utils.escapeHtml(a.patient_name||'—')+'</td>'
          +'<td>'+utils.formatDate(a.appointment_date)+'</td>'
          +'<td>'+(a.start_time?a.start_time.substring(0,5):'')+' – '+(a.end_time?a.end_time.substring(0,5):'')+'</td>'
          +'<td><span class="badge '+(badgeMap[a.status]||'badge-info')+'">'+a.status+'</span></td>'
          +'<td style="display:flex;gap:4px;">';
        if(a.status==='pending') row+='<button class="btn btn-sm btn-success" onclick="confirmA('+a.id+')"><i class="fa-solid fa-check"></i></button>';
        if(a.status==='confirmed') row+='<button class="btn btn-sm btn-primary" onclick="completeA('+a.id+','+a.patient_id+')"><i class="fa-solid fa-clipboard-check"></i></button>';
        if(a.status==='pending'||a.status==='confirmed') row+='<button class="btn btn-sm btn-danger" onclick="cancelA('+a.id+')"><i class="fa-solid fa-xmark"></i></button>';
        row+='</td></tr>';
        tb.insertAdjacentHTML('beforeend',row);
      });
      var p=data.data.pagination;
      var pe=document.getElementById('pagination');
      if(!p||p.total_pages<=1){pe.innerHTML='';return;}
      var h='';for(var x=1;x<=p.total_pages;x++) h+='<button class="btn btn-sm '+(x===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadAppts('+x+')">'+x+'</button>';
      pe.innerHTML=h;
    });
  };

  window.confirmA=function(id){
    utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:id,action:'confirm'},function(e,d){
      if(d&&d.success){utils.showToast('Confirmed!','success');loadAppts(currentPage);}else utils.showAlert(d?d.message:'Failed.','error');
    });
  };
  window.cancelA=function(id){
    utils.confirmAction('Cancel?',function(){
      utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:id,action:'cancel'},function(e,d){
        if(d&&d.success){utils.showToast('Cancelled.','success');loadAppts(currentPage);}else utils.showAlert(d?d.message:'Failed.','error');
      });
    });
  };
  window.completeA=function(apptId,patientId){
    // Redirect to dashboard complete flow or inline
    utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:apptId,action:'complete'},function(e,d){
      if(d&&d.success){utils.showToast('Completed!','success');loadAppts(currentPage);}else utils.showAlert(d?d.message:'Failed.','error');
    });
  };

  window.exportCSV=function(){
    utils.exportTableToCSV('apptTable','doctor_appointments.csv');
  };

  loadAppts(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>