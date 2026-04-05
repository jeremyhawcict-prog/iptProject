<?php
$pageTitle = 'My Appointments';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-calendar-check" style="color:var(--teal-core);"></i> My Appointments</h2>
    <a href="<?= BASE_URL ?>/pages/patient/book-appointment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Book New</a>
  </div>

  <div id="alert-container"></div>

  <!-- Filter Tabs -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
    <button class="btn btn-sm btn-outline filter-tab active" data-status="">All</button>
    <button class="btn btn-sm btn-outline filter-tab" data-status="pending">Pending</button>
    <button class="btn btn-sm btn-outline filter-tab" data-status="confirmed">Confirmed</button>
    <button class="btn btn-sm btn-outline filter-tab" data-status="completed">Completed</button>
    <button class="btn btn-sm btn-outline filter-tab" data-status="cancelled">Cancelled</button>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="apptTable">
      <thead><tr>
        <th>#</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody id="apptBody">
        <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
      </tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<!-- Reschedule Modal -->
<div class="modal-overlay" id="rescheduleModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header"><h3>Reschedule Appointment</h3><button class="modal-close" onclick="closeModal('rescheduleModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="reschApptId" />
      <input type="hidden" id="reschDoctorId" />
      <div class="form-group"><label class="form-label">New Date</label><input type="date" id="reschDate" class="form-control" min="<?= date('Y-m-d') ?>" /></div>
      <div class="form-group"><label class="form-label">Available Slots</label><div id="reschSlots" style="display:flex;flex-wrap:wrap;gap:8px;"></div></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('rescheduleModal')">Cancel</button><button class="btn btn-primary" id="submitReschedule">Reschedule</button></div>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal-overlay" id="feedbackModal">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header"><h3>Leave Feedback</h3><button class="modal-close" onclick="closeModal('feedbackModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="fbApptId" />
      <div class="form-group"><label class="form-label">Rating</label>
        <div id="fbStars" style="display:flex;gap:6px;font-size:1.6rem;cursor:pointer;">
          <i class="fa-regular fa-star" data-v="1"></i><i class="fa-regular fa-star" data-v="2"></i><i class="fa-regular fa-star" data-v="3"></i><i class="fa-regular fa-star" data-v="4"></i><i class="fa-regular fa-star" data-v="5"></i>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Comments</label><textarea id="fbComments" class="form-control" rows="3" placeholder="Share your experience…"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('feedbackModal')">Cancel</button><button class="btn btn-primary" id="submitFeedback">Submit</button></div>
  </div>
</div>

<!-- Record Modal -->
<div class="modal-overlay" id="recordModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header"><h3>Medical Record</h3><button class="modal-close" onclick="closeModal('recordModal')">&times;</button></div>
    <div class="modal-body" id="recordContent"><p class="text-muted text-center">Loading…</p></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('recordModal')">Close</button></div>
  </div>
</div>

<style>
.filter-tab{background:transparent;border:1px solid var(--input-border,rgba(61,106,138,.22));border-radius:8px;font-weight:600;transition:all .2s;}
.filter-tab.active,.filter-tab:hover{background:var(--teal-core,#3D6A8A);color:#fff;border-color:var(--teal-core,#3D6A8A);}
.slot-btn{padding:6px 14px;border-radius:8px;border:1px solid var(--input-border);background:transparent;cursor:pointer;font-weight:600;font-size:.82rem;transition:all .2s;}
.slot-btn.selected{background:var(--teal-core);color:#fff;border-color:var(--teal-core);}
</style>

<script>
(function(){
  var currentPage = 1, currentStatus = '', reschSlotId = null, fbRating = 0;
  var badgeMap = {pending:'badge-warning',confirmed:'badge-success',completed:'badge-primary',cancelled:'badge-danger',rescheduled:'badge-info'};

  function loadAppts(page){
    currentPage = page||1;
    var params = {page:currentPage, per_page:10};
    if(currentStatus) params.status = currentStatus;
    utils.apiGet(utils.apiUrl('appointments/list.php'), params, function(err,data){
      var tb = document.getElementById('apptBody');
      if(!data||!data.success||!data.data.appointments||!data.data.appointments.length){
        tb.innerHTML='<tr><td colspan="6" class="text-center text-muted">No appointments found.</td></tr>';
        document.getElementById('pagination').innerHTML=''; return;
      }
      tb.innerHTML='';
      data.data.appointments.forEach(function(a,i){
        var row = '<tr>'
          +'<td>'+(data.data.pagination?((data.data.pagination.current_page-1)*data.data.pagination.per_page+i+1):(i+1))+'</td>'
          +'<td>Dr. '+utils.escapeHtml(a.doctor_name||'—')+'</td>'
          +'<td>'+utils.formatDate(a.appointment_date)+'</td>'
          +'<td>'+(a.start_time?a.start_time.substring(0,5):'')+' – '+(a.end_time?a.end_time.substring(0,5):'')+'</td>'
          +'<td><span class="badge '+(badgeMap[a.status]||'badge-info')+'">'+a.status+'</span></td>'
          +'<td style="display:flex;gap:4px;flex-wrap:wrap;">';
        if(a.status==='pending'||a.status==='confirmed'){
          row+='<button class="btn btn-sm btn-outline" onclick="openReschedule('+a.id+','+a.doctor_id+')"><i class="fa-solid fa-clock-rotate-left"></i></button>';
          row+='<button class="btn btn-sm btn-danger" onclick="cancelAppt('+a.id+')"><i class="fa-solid fa-xmark"></i></button>';
        }
        if(a.status==='completed'){
          if(!a.has_feedback) row+='<button class="btn btn-sm btn-outline" onclick="openFeedback('+a.id+')"><i class="fa-solid fa-star"></i> Feedback</button>';
          row+='<button class="btn btn-sm btn-outline" onclick="viewRecord('+a.id+')"><i class="fa-solid fa-file-medical"></i></button>';
        }
        row+='</td></tr>';
        tb.insertAdjacentHTML('beforeend',row);
      });
      renderPagination(data.data.pagination);
    });
  }

  function renderPagination(p){
    var el=document.getElementById('pagination'); if(!p||p.total_pages<=1){el.innerHTML='';return;}
    var h='';
    for(var i=1;i<=p.total_pages;i++){
      h+='<button class="btn btn-sm '+(i===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadAppts('+i+')">'+i+'</button>';
    }
    el.innerHTML=h;
  }
  window.loadAppts = loadAppts;

  // Tabs
  document.querySelectorAll('.filter-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
      document.querySelectorAll('.filter-tab').forEach(function(t){t.classList.remove('active');});
      this.classList.add('active');
      currentStatus = this.dataset.status;
      loadAppts(1);
    });
  });

  // Cancel
  window.cancelAppt = function(id){
    utils.confirmAction('Cancel this appointment?', function(){
      utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:id, action:'cancel'}, function(err,data){
        if(data&&data.success){ utils.showToast('Cancelled.','success'); loadAppts(currentPage); }
        else utils.showAlert(data?data.message:'Failed.','error');
      });
    });
  };

  // Reschedule
  window.openReschedule = function(apptId, doctorId){
    document.getElementById('reschApptId').value=apptId;
    document.getElementById('reschDoctorId').value=doctorId;
    document.getElementById('reschSlots').innerHTML='';
    document.getElementById('reschDate').value='';
    reschSlotId=null;
    openModal('rescheduleModal');
  };
  document.getElementById('reschDate').addEventListener('change', function(){
    var doctorId=document.getElementById('reschDoctorId').value, slotsEl=document.getElementById('reschSlots');
    slotsEl.innerHTML='<span class="text-muted text-sm">Loading…</span>'; reschSlotId=null;
    utils.apiGet(utils.apiUrl('doctors/availability.php'),{doctor_id:doctorId,date:this.value},function(err,data){
      if(!data||!data.success||!data.data.slots||!data.data.slots.length){slotsEl.innerHTML='<span class="text-muted text-sm">No slots.</span>';return;}
      slotsEl.innerHTML='';
      data.data.slots.forEach(function(s){
        var b=document.createElement('button');b.type='button';b.className='slot-btn';
        b.textContent=s.start_time.substring(0,5)+' – '+s.end_time.substring(0,5);
        b.addEventListener('click',function(){
          document.querySelectorAll('#reschSlots .slot-btn').forEach(function(x){x.classList.remove('selected');});
          this.classList.add('selected'); reschSlotId=s.slot_id;
        });
        slotsEl.appendChild(b);
      });
    });
  });
  document.getElementById('submitReschedule').addEventListener('click', function(){
    if(!reschSlotId){utils.showAlert('Select a slot.','warning');return;}
    var btn=this;btn.disabled=true;
    utils.apiPost(utils.apiUrl('appointments/update.php'),{appointment_id:parseInt(document.getElementById('reschApptId').value),action:'reschedule',new_slot_id:reschSlotId},function(err,data){
      btn.disabled=false;
      if(data&&data.success){closeModal('rescheduleModal');utils.showToast('Rescheduled!','success');loadAppts(currentPage);}
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  });

  // Feedback
  window.openFeedback = function(apptId){
    document.getElementById('fbApptId').value=apptId;
    document.getElementById('fbComments').value=''; fbRating=0;
    document.querySelectorAll('#fbStars i').forEach(function(s){s.className='fa-regular fa-star';});
    openModal('feedbackModal');
  };
  document.querySelectorAll('#fbStars i').forEach(function(star){
    star.addEventListener('click', function(){
      fbRating=parseInt(this.dataset.v);
      document.querySelectorAll('#fbStars i').forEach(function(s,idx){
        s.className=(idx<fbRating)?'fa-solid fa-star':'fa-regular fa-star';
        s.style.color=(idx<fbRating)?'var(--amber-core,#F59E0B)':'';
      });
    });
  });
  document.getElementById('submitFeedback').addEventListener('click', function(){
    if(!fbRating){utils.showAlert('Please select a rating.','warning');return;}
    var btn=this;btn.disabled=true;
    utils.apiPost(utils.apiUrl('feedback/submit.php'),{appointment_id:parseInt(document.getElementById('fbApptId').value),rating:fbRating,comments:document.getElementById('fbComments').value.trim()},function(err,data){
      btn.disabled=false;
      if(data&&data.success){closeModal('feedbackModal');utils.showToast('Thank you!','success');loadAppts(currentPage);}
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  });

  // View Record
  window.viewRecord = function(apptId){
    var el=document.getElementById('recordContent');el.innerHTML='<p class="text-muted text-center">Loading…</p>';
    openModal('recordModal');
    utils.apiGet(utils.apiUrl('records/get.php'),{appointment_id:apptId},function(err,data){
      if(!data||!data.success||!data.data.record){el.innerHTML='<p class="text-muted text-center">No record found.</p>';return;}
      var r=data.data.record;
      el.innerHTML='<div style="display:grid;gap:12px;">'
        +'<div><strong>Diagnosis:</strong><p style="margin:4px 0;">'+utils.escapeHtml(r.diagnosis||'—')+'</p></div>'
        +'<div><strong>Prescription:</strong><p style="margin:4px 0;">'+utils.escapeHtml(r.prescription||'—')+'</p></div>'
        +'<div><strong>Notes:</strong><p style="margin:4px 0;">'+utils.escapeHtml(r.notes||'—')+'</p></div>'
        +'</div>';
    });
  };

  loadAppts(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>