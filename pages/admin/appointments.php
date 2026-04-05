<?php
$pageTitle = 'All Appointments';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-calendar-check" style="color:var(--teal-core);"></i> All Appointments</h2>
    <button class="btn btn-sm btn-outline" onclick="utils.exportTableToCSV('apptTable','all_appointments.csv')"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
  </div>
  <div id="alert-container"></div>

  <!-- Advanced Filters -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="date" id="fDate" class="form-control" style="width:auto;" placeholder="Date" />
    <select id="fStatus" class="form-control" style="width:auto;">
      <option value="">All Statuses</option>
      <option value="pending">Pending</option><option value="confirmed">Confirmed</option>
      <option value="completed">Completed</option><option value="cancelled">Cancelled</option>
    </select>
    <input type="text" id="fSearch" class="form-control" style="width:auto;min-width:180px;" placeholder="Patient or Doctor…" />
    <button class="btn btn-sm btn-primary" onclick="loadAppts(1)"><i class="fa-solid fa-search"></i> Filter</button>
  </div>

  <!-- Bulk Actions -->
  <div style="display:flex;gap:8px;margin-bottom:12px;">
    <button class="btn btn-sm btn-success" onclick="bulkAction('confirm')"><i class="fa-solid fa-check"></i> Bulk Confirm</button>
    <button class="btn btn-sm btn-danger" onclick="bulkAction('cancel')"><i class="fa-solid fa-xmark"></i> Bulk Cancel</button>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="apptTable">
      <thead><tr><th><input type="checkbox" id="selectAll" /></th><th>#</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="apptBody"><tr><td colspan="8" class="text-center text-muted">Loading…</td></tr></tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<script>
(function(){
  var currentPage=1;
  var badgeMap={pending:'badge-warning',confirmed:'badge-success',completed:'badge-primary',cancelled:'badge-danger',rescheduled:'badge-info'};

  // Check URL params for pre-set filters
  var urlParams = new URLSearchParams(window.location.search);
  if(urlParams.get('status')) document.getElementById('fStatus').value = urlParams.get('status');

  window.loadAppts=function(page){
    currentPage=page||1;
    var params={page:currentPage,per_page:15};
    var d=document.getElementById('fDate').value;if(d){params.date_from=d;params.date_to=d;}
    var s=document.getElementById('fStatus').value;if(s)params.status=s;
    var q=document.getElementById('fSearch').value.trim();if(q)params.search=q;
    utils.apiGet(utils.apiUrl('appointments/list.php'),params,function(err,data){
      var tb=document.getElementById('apptBody');
      if(!data||!data.success||!data.data.appointments||!data.data.appointments.length){
        tb.innerHTML='<tr><td colspan="8" class="text-center text-muted">No appointments found.</td></tr>';
        document.getElementById('pagination').innerHTML='';return;
      }
      tb.innerHTML='';
      data.data.appointments.forEach(function(a,i){
        var n=data.data.pagination?((data.data.pagination.current_page-1)*data.data.pagination.per_page+i+1):(i+1);
        tb.insertAdjacentHTML('beforeend','<tr>'
          +'<td><input type="checkbox" class="row-check" value="'+a.id+'" /></td>'
          +'<td>'+n+'</td>'
          +'<td>'+utils.escapeHtml(a.patient_name||'—')+'</td>'
          +'<td>Dr. '+utils.escapeHtml(a.doctor_name||'—')+'</td>'
          +'<td>'+utils.formatDate(a.appointment_date)+'</td>'
          +'<td>'+(a.start_time?a.start_time.substring(0,5):'')+' – '+(a.end_time?a.end_time.substring(0,5):'')+'</td>'
          +'<td><span class="badge '+(badgeMap[a.status]||'badge-info')+'">'+a.status+'</span></td>'
          +'<td style="display:flex;gap:4px;">'
          +(a.status==='pending'?'<button class="btn btn-sm btn-success" onclick="actionAppt('+a.id+',\'confirm\')"><i class="fa-solid fa-check"></i></button>':'')
          +(a.status==='pending'||a.status==='confirmed'?'<button class="btn btn-sm btn-danger" onclick="actionAppt('+a.id+',\'cancel\')"><i class="fa-solid fa-xmark"></i></button>':'')
          +'</td></tr>');
      });
      var p=data.data.pagination,el=document.getElementById('pagination');
      if(!p||p.total_pages<=1){el.innerHTML='';return;}
      var h='';for(var x=1;x<=p.total_pages;x++) h+='<button class="btn btn-sm '+(x===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadAppts('+x+')">'+x+'</button>';
      el.innerHTML=h;
    });
  };

  document.getElementById('selectAll').addEventListener('change',function(){
    var c=this.checked;
    document.querySelectorAll('.row-check').forEach(function(cb){cb.checked=c;});
  });

  window.actionAppt=function(id,action){
    utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:id,action:action},function(e,d){
      if(d&&d.success){utils.showToast(action==='confirm'?'Confirmed!':'Cancelled.','success');loadAppts(currentPage);}
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  };

  window.bulkAction=function(action){
    var ids=[];document.querySelectorAll('.row-check:checked').forEach(function(c){ids.push(parseInt(c.value));});
    if(!ids.length){utils.showAlert('Select appointments first.','warning');return;}
    utils.confirmAction(action==='confirm'?'Confirm '+ids.length+' appointments?':'Cancel '+ids.length+' appointments?',function(){
      var done=0;
      ids.forEach(function(id){
        utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:id,action:action},function(){
          done++;if(done===ids.length){utils.showToast('Bulk action complete!','success');loadAppts(currentPage);}
        });
      });
    });
  };

  loadAppts(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>