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

<!-- View Doctor Modal -->
<div class="modal-overlay" id="viewDocModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header"><h3>Doctor Profile</h3><button class="modal-close" onclick="closeModal('viewDocModal')">&times;</button></div>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:16px;">
        <img id="vDocImg" src="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
        <h4 id="vDocName" style="margin:0;color:var(--text-dark);"></h4>
        <div id="vDocSpec" style="color:var(--text-muted);font-size:0.9rem;"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
        <div><strong>Rating:</strong> <span id="vDocRating"></span></div>
        <div><strong>Patients:</strong> <span id="vDocPatients"></span></div>
        <div style="grid-column: span 2;"><strong>Available Days:</strong> <span id="vDocDays"></span></div>
        <div style="grid-column: span 2;"><strong>Bio:</strong> <p id="vDocBio" style="margin:4px 0 0;font-size:0.95rem;color:var(--text-dark);"></p></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('viewDocModal')">Close</button>
    </div>
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
      window._loadedDoctors = data.data.doctors;
      data.data.doctors.forEach(function(d){
        tb.insertAdjacentHTML('beforeend','<tr>'
          +'<td><div style="display:flex;align-items:center;gap:8px;">'
          +'<img src="'+utils.BASE_URL+'/assets/uploads/photos/'+(d.profile_photo||'default.svg')+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" onerror="this.src=\''+utils.BASE_URL+'/assets/uploads/photos/default.svg\'" />'
          +'<strong>Dr. '+utils.escapeHtml(d.full_name)+'</strong></div></td>'
          +'<td>'+utils.escapeHtml(d.specialization||'—')+'</td>'
          +'<td>'+(d.avg_rating?utils.renderStars(d.avg_rating):'—')+'</td>'
          +'<td>'+(d.patient_count||0)+'</td>'
          +'<td><button class="btn btn-sm btn-outline" onclick="openViewDoc('+d.id+')"><i class="fa-solid fa-eye"></i> View</button></td>'
          +'</tr>');
      });
      var p=data.data.pagination,el=document.getElementById('pagination');
      if(!p||p.total_pages<=1){el.innerHTML='';return;}
      var h='';for(var x=1;x<=p.total_pages;x++) h+='<button class="btn btn-sm '+(x===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadDocs('+x+')">'+x+'</button>';
      el.innerHTML=h;
    });
  }
  window.loadDocs=load;
  document.getElementById('docSearch').addEventListener('input',utils.debounce(function(){load(1);},400));

  window.openViewDoc = function(id) {
    try {
      if (!window._loadedDoctors) throw new Error("Doctors data not loaded.");
      var d = window._loadedDoctors.find(function(x) { return String(x.id) === String(id); });
      if (!d) throw new Error("Doctor ID " + id + " not found.");
      
      document.getElementById('vDocImg').src = utils.BASE_URL + '/assets/uploads/photos/' + (d.profile_photo || 'default.svg');
      document.getElementById('vDocName').textContent = 'Dr. ' + (d.full_name || 'N/A');
      document.getElementById('vDocSpec').textContent = d.specialization || '—';
      document.getElementById('vDocRating').innerHTML = d.avg_rating ? utils.renderStars(d.avg_rating) : '—';
      document.getElementById('vDocPatients').textContent = d.patient_count || 0;
      
      var daysStr = 'Not specified';
      if (d.available_days) {
          if (Array.isArray(d.available_days)) {
              daysStr = d.available_days.join(', ');
          } else if (typeof d.available_days === 'string') {
              daysStr = d.available_days.split(',').map(function(s){return s.trim();}).join(', ');
          } else {
              daysStr = String(d.available_days);
          }
      }
      
      document.getElementById('vDocDays').textContent = daysStr;
      document.getElementById('vDocBio').textContent = d.bio || 'No bio provided.';
      
      if (typeof openModal === 'function') {
        openModal('viewDocModal');
      } else {
        throw new Error("openModal function is not defined globally.");
      }
    } catch (err) {
      alert("Error viewing doctor: " + err.message);
      console.error(err);
    }
  };

  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
