<?php
$pageTitle = 'My Patients';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['doctor']);
?>

<div class="card-glass" style="padding:24px;">
  <h2 style="margin:0 0 20px;"><i class="fa-solid fa-users" style="color:var(--teal-core);"></i> My Patients</h2>
  <div id="alert-container"></div>

  <div style="margin-bottom:16px;">
    <input type="text" id="patientSearch" class="form-control" placeholder="Search by patient name…" style="max-width:320px;" />
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Patient</th><th>Visits</th><th>Last Visit</th><th>Actions</th></tr></thead>
      <tbody id="patientBody"><tr><td colspan="4" class="text-center text-muted">Loading…</td></tr></tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="historyModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header"><h3 id="historyTitle">Patient History</h3><button class="modal-close" onclick="closeModal('historyModal')">&times;</button></div>
    <div class="modal-body" id="historyContent" style="max-height:400px;overflow-y:auto;"><p class="text-muted">Loading…</p></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('historyModal')">Close</button></div>
  </div>
</div>

<script>
(function(){
  var currentPage=1;

  function load(page){
    currentPage=page||1;
    var params={page:currentPage,per_page:15};
    var q=document.getElementById('patientSearch').value.trim();
    if(q) params.search=q;
    utils.apiGet(utils.apiUrl('doctors/my-patients.php'),params,function(err,data){
      var tb=document.getElementById('patientBody');
      if(!data||!data.success||!data.data.patients||!data.data.patients.length){
        tb.innerHTML='<tr><td colspan="4" class="text-center text-muted">No patients found.</td></tr>';
        document.getElementById('pagination').innerHTML='';return;
      }
      tb.innerHTML='';
      data.data.patients.forEach(function(p){
        tb.insertAdjacentHTML('beforeend',
          '<tr><td>'+utils.escapeHtml(p.full_name)+'</td>'
          +'<td>'+p.visit_count+'</td>'
          +'<td>'+(p.last_visit?utils.formatDate(p.last_visit):'—')+'</td>'
          +'<td><button class="btn btn-sm btn-outline" onclick="viewHistory('+p.id+',\''+utils.escapeHtml(p.full_name)+'\')"><i class="fa-solid fa-clock-rotate-left"></i> History</button></td></tr>'
        );
      });
      var pg=data.data.pagination,el=document.getElementById('pagination');
      if(!pg||pg.total_pages<=1){el.innerHTML='';return;}
      var h='';for(var i=1;i<=pg.total_pages;i++) h+='<button class="btn btn-sm '+(i===pg.current_page?'btn-primary':'btn-outline')+'" onclick="loadPatients('+i+')">'+i+'</button>';
      el.innerHTML=h;
    });
  }
  window.loadPatients=load;

  document.getElementById('patientSearch').addEventListener('input', utils.debounce(function(){load(1);},400));

  window.viewHistory=function(patientId, name){
    document.getElementById('historyTitle').textContent=name+' — History';
    var el=document.getElementById('historyContent'); el.innerHTML='<p class="text-muted">Loading…</p>';
    openModal('historyModal');
    utils.apiGet(utils.apiUrl('records/list.php'),{patient_id:patientId},function(err,data){
      if(!data||!data.success||!data.data.records||!data.data.records.length){el.innerHTML='<p class="text-muted">No records.</p>';return;}
      el.innerHTML=data.data.records.map(function(r){
        return '<div style="border-bottom:1px solid var(--input-border,rgba(61,106,138,.12));padding:12px 0;">'
          +'<div style="display:flex;justify-content:space-between;"><strong>'+utils.formatDate(r.appointment_date||r.created_at)+'</strong></div>'
          +'<p style="margin:4px 0;"><strong>Diagnosis:</strong> '+utils.escapeHtml(r.diagnosis||'—')+'</p>'
          +'<p style="margin:4px 0;"><strong>Prescription:</strong> '+utils.escapeHtml(r.prescription||'—')+'</p>'
          +'</div>';
      }).join('');
    });
  };

  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>