<?php
$pageTitle = 'Manage Doctors';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);
?>

<div class="card-glass" style="padding:24px;">
  <h2 style="margin:0 0 20px;"><i class="fa-solid fa-user-doctor" style="color:var(--teal-core);"></i> Manage Doctors</h2>
  <div id="alert-container"></div>

  <div style="margin-bottom:16px;">
    <input type="text" id="docSearch" class="form-control" placeholder="Search by name or specialization…" style="max-width:320px;" />
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Doctor</th><th>Specialization</th><th>Rating</th><th>Patients</th><th>Actions</th></tr></thead>
      <tbody id="docBody"><tr><td colspan="5" class="text-center text-muted">Loading…</td></tr></tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal-overlay" id="editDocModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header"><h3>Edit Doctor Profile</h3><button class="modal-close" onclick="closeModal('editDocModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="edDocId" />
      <div class="form-group"><label class="form-label">Specialization</label><input type="text" id="edSpec" class="form-control" /></div>
      <div class="form-group"><label class="form-label">Bio</label><textarea id="edBio" class="form-control" rows="3"></textarea></div>
      <div class="form-group"><label class="form-label">Available Days</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <label><input type="checkbox" class="ed-day" value="Monday" /> Mon</label>
          <label><input type="checkbox" class="ed-day" value="Tuesday" /> Tue</label>
          <label><input type="checkbox" class="ed-day" value="Wednesday" /> Wed</label>
          <label><input type="checkbox" class="ed-day" value="Thursday" /> Thu</label>
          <label><input type="checkbox" class="ed-day" value="Friday" /> Fri</label>
          <label><input type="checkbox" class="ed-day" value="Saturday" /> Sat</label>
          <label><input type="checkbox" class="ed-day" value="Sunday" /> Sun</label>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('editDocModal')">Cancel</button><button class="btn btn-primary" id="submitEditDoc">Save</button></div>
  </div>
</div>

<!-- Generate Slots Modal -->
<div class="modal-overlay" id="genSlotsModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header"><h3>Generate Slots for <span id="genDocName"></span></h3><button class="modal-close" onclick="closeModal('genSlotsModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="genDocId" />
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div class="form-group"><label class="form-label">Start Date</label><input type="date" id="gsStart" class="form-control" min="<?= date('Y-m-d') ?>" /></div>
        <div class="form-group"><label class="form-label">End Date</label><input type="date" id="gsEnd" class="form-control" min="<?= date('Y-m-d') ?>" /></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div class="form-group"><label class="form-label">Start Time</label><input type="time" id="gsStartTime" class="form-control" value="09:00" /></div>
        <div class="form-group"><label class="form-label">End Time</label><input type="time" id="gsEndTime" class="form-control" value="17:00" /></div>
      </div>
      <div class="form-group"><label class="form-label">Duration (min)</label><input type="number" id="gsDuration" class="form-control" value="<?= SLOT_DURATION_MIN ?>" min="10" max="120" /></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('genSlotsModal')">Cancel</button><button class="btn btn-primary" id="submitGenSlots"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button></div>
  </div>
</div>

<script>
(function(){
  var currentPage=1;

  function load(page){
    currentPage=page||1;
    var params={page:currentPage,per_page:15};
    var q=document.getElementById('docSearch').value.trim();if(q)params.search=q;
    utils.apiGet(utils.apiUrl('doctors/list.php'),params,function(err,data){
      var tb=document.getElementById('docBody');
      if(!data||!data.success||!data.data.doctors||!data.data.doctors.length){
        tb.innerHTML='<tr><td colspan="5" class="text-center text-muted">No doctors found.</td></tr>';
        document.getElementById('pagination').innerHTML='';return;
      }
      tb.innerHTML='';
      data.data.doctors.forEach(function(d){
        tb.insertAdjacentHTML('beforeend','<tr>'
          +'<td><div style="display:flex;align-items:center;gap:8px;">'
          +'<img src="'+utils.BASE_URL+'/assets/uploads/photos/'+(d.profile_photo||'default.svg')+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" onerror="this.src=\''+utils.BASE_URL+'/assets/uploads/photos/default.svg\'" />'
          +'<strong>Dr. '+utils.escapeHtml(d.full_name)+'</strong></div></td>'
          +'<td>'+utils.escapeHtml(d.specialization||'—')+'</td>'
          +'<td>'+(d.avg_rating?utils.renderStars(d.avg_rating):'—')+'</td>'
          +'<td>'+(d.patient_count||0)+'</td>'
          +'<td style="display:flex;gap:4px;">'
          +'<button class="btn btn-sm btn-outline" onclick=\'openEditDoc('+JSON.stringify(d).replace(/'/g,"&#39;")+')\' ><i class="fa-solid fa-pen"></i></button>'
          +'<button class="btn btn-sm btn-primary" onclick="openGenSlots('+d.id+',\''+utils.escapeHtml(d.full_name)+'\')"><i class="fa-solid fa-calendar-plus"></i></button>'
          +'</td></tr>');
      });
      var p=data.data.pagination,el=document.getElementById('pagination');
      if(!p||p.total_pages<=1){el.innerHTML='';return;}
      var h='';for(var x=1;x<=p.total_pages;x++) h+='<button class="btn btn-sm '+(x===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadDocs('+x+')">'+x+'</button>';
      el.innerHTML=h;
    });
  }
  window.loadDocs=load;
  document.getElementById('docSearch').addEventListener('input',utils.debounce(function(){load(1);},400));

  window.openEditDoc=function(d){
    document.getElementById('edDocId').value=d.id;
    document.getElementById('edSpec').value=d.specialization||'';
    document.getElementById('edBio').value=d.bio||'';
    var days=(d.available_days||'').split(',');
    document.querySelectorAll('.ed-day').forEach(function(c){c.checked=days.indexOf(c.value)!==-1;});
    openModal('editDocModal');
  };

  document.getElementById('submitEditDoc').addEventListener('click',function(){
    var days=[];document.querySelectorAll('.ed-day:checked').forEach(function(c){days.push(c.value);});
    var btn=this;btn.disabled=true;
    utils.apiPost(utils.apiUrl('doctors/profile.php'),{
      doctor_id:parseInt(document.getElementById('edDocId').value),
      specialization:document.getElementById('edSpec').value.trim(),
      bio:document.getElementById('edBio').value.trim(),
      available_days:days.join(',')
    },function(e,d){
      btn.disabled=false;
      if(d&&d.success){closeModal('editDocModal');utils.showToast('Updated!','success');load(currentPage);}
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  });

  window.openGenSlots=function(id,name){
    document.getElementById('genDocId').value=id;
    document.getElementById('genDocName').textContent='Dr. '+name;
    openModal('genSlotsModal');
  };

  document.getElementById('submitGenSlots').addEventListener('click',function(){
    var btn=this;btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Generating…';
    utils.apiPost(utils.apiUrl('appointments/slots/generate.php'),{
      doctor_id:parseInt(document.getElementById('genDocId').value),
      start_date:document.getElementById('gsStart').value,
      end_date:document.getElementById('gsEnd').value,
      start_time:document.getElementById('gsStartTime').value,
      end_time:document.getElementById('gsEndTime').value,
      duration:parseInt(document.getElementById('gsDuration').value)
    },function(e,d){
      btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Generate';
      if(d&&d.success){closeModal('genSlotsModal');utils.showToast('Slots generated!','success');}
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  });

  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>