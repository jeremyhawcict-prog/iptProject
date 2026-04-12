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

<!-- Add / Update Medical Record Modal -->
<div class="modal-overlay" id="recordFormModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3>Add Medical Record</h3>
      <button class="modal-close" onclick="closeModal('recordFormModal')">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rf-appointment-id" value="" />
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Diagnosis <span style="color:red;">*</span></label>
        <textarea id="rf-diagnosis" class="form-control" rows="3" placeholder="Enter diagnosis…"></textarea>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Prescription</label>
        <textarea id="rf-prescription" class="form-control" rows="3" placeholder="Enter prescription (optional)…"></textarea>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Doctor Notes</label>
        <textarea id="rf-notes" class="form-control" rows="3" placeholder="Additional notes (optional)…"></textarea>
      </div>
      <div id="rf-alert"></div>
    </div>
    <div class="modal-footer" style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn btn-secondary" onclick="closeModal('recordFormModal')">Cancel</button>
      <button class="btn btn-primary" id="rf-save-btn" onclick="saveRecord()">
        <i class="fa-solid fa-save"></i> Save Record
      </button>
    </div>
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
    document.getElementById('historyTitle').textContent=name+' — Appointment History';
    var el=document.getElementById('historyContent'); el.innerHTML='<p class="text-muted">Loading…</p>';
    openModal('historyModal');

    utils.apiGet(utils.apiUrl('appointments/list.php'),{patient_id:patientId,per_page:50},function(err,data){
      if(!data||!data.success||!data.data.appointments||!data.data.appointments.length){
        el.innerHTML='<p class="text-muted text-center" style="padding:20px;">No medical records have been found.</p>';return;
      }

      var statusColors={
        completed:'#10B981',cancelled:'#EF4444',
        rescheduled:'#8B5CF6',confirmed:'#3D6A8A',pending:'#F59E0B'
      };

      el.innerHTML=data.data.appointments.map(function(a){
        var statusLabel=(a.status||'unknown');
        var badge='<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:600;background:'
          +(statusColors[statusLabel]||'#64748B')+';color:#fff;">'
          +statusLabel.charAt(0).toUpperCase()+statusLabel.slice(1)+'</span>';
        var timeStr=(a.start_time||'').substring(0,5)||'—';

        var addRecordBtn=(a.status==='completed')
          ?'<button class="btn btn-sm btn-primary" style="margin-top:8px;" onclick="openRecordForm('+a.id+', this)">'
            +'<i class="fa-solid fa-file-medical"></i> Add / Update Record</button>'
          :'';

        return '<div style="border-bottom:1px solid var(--input-border,rgba(61,106,138,.12));padding:14px 0;">'
          +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
          +'<strong style="font-size:.9rem;">'+utils.formatDate(a.appointment_date)+' &nbsp;·&nbsp; '+utils.escapeHtml(timeStr)+'</strong>'
          +badge+'</div>'
          +'<p style="margin:4px 0;font-size:.83rem;"><strong>Concern:</strong> '+utils.escapeHtml(a.reason_for_visit||'—')+'</p>'
          +addRecordBtn
          +'</div>';
      }).join('');
    });
  };

  window.openRecordForm=function(appointmentId,triggerBtn){
    document.getElementById('rf-appointment-id').value=appointmentId;
    document.getElementById('rf-diagnosis').value='';
    document.getElementById('rf-prescription').value='';
    document.getElementById('rf-notes').value='';
    document.getElementById('rf-alert').innerHTML='';
    window._rfTriggerBtn=triggerBtn||null;
    openModal('recordFormModal');
  };

  window.saveRecord=function(){
    var apptId=document.getElementById('rf-appointment-id').value;
    var diagnosis=document.getElementById('rf-diagnosis').value.trim();
    var prescription=document.getElementById('rf-prescription').value.trim();
    var notes=document.getElementById('rf-notes').value.trim();
    var alertEl=document.getElementById('rf-alert');
    var saveBtn=document.getElementById('rf-save-btn');

    if(!diagnosis){
      alertEl.innerHTML='<div class="alert alert-danger">Diagnosis is required.</div>';
      return;
    }

    saveBtn.disabled=true;
    saveBtn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    utils.apiPost(utils.apiUrl('records/save.php'),{
      appointment_id:apptId,
      diagnosis:diagnosis,
      prescription:prescription,
      notes:notes
    },function(err,res){
      saveBtn.disabled=false;
      saveBtn.innerHTML='<i class="fa-solid fa-save"></i> Save Record';

      if(err||!res||!res.success){
        alertEl.innerHTML='<div class="alert alert-danger">'
          +utils.escapeHtml((res&&res.message)||'Failed to save record. Please try again.')
          +'</div>';
        return;
      }

      closeModal('recordFormModal');
      utils.showToast('Medical record saved successfully.','success');

      if(window._rfTriggerBtn){
        window._rfTriggerBtn.outerHTML=
          '<span style="color:#10B981;font-size:.83rem;margin-top:6px;display:inline-block;">'
          +'<i class="fa-solid fa-circle-check"></i> Record saved</span>';
      }
    });
  };

  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>