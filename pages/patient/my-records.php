<?php
$pageTitle = 'My Records';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);
?>

<div class="card-glass" style="padding:24px;">
  <h2 style="margin:0 0 20px;"><i class="fa-solid fa-file-medical" style="color:var(--teal-core);"></i> My Medical Records</h2>
  <div id="alert-container"></div>
  <div id="recordsList" style="display:grid;gap:14px;">
    <p class="text-muted text-center" style="padding:32px;">Loading records…</p>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<style>
.record-card{background:var(--card-bg,rgba(255,254,252,.72));border-radius:14px;border:1px solid var(--input-border,rgba(61,106,138,.22));overflow:hidden;transition:box-shadow .2s;}
.record-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.06);}
.record-header{padding:16px 20px;display:flex;justify-content:space-between;align-items:center;cursor:pointer;gap:12px;}
.record-header:hover{background:var(--input-bg,rgba(232,244,251,.35));}
.record-body{padding:0 20px 20px;display:none;border-top:1px solid var(--input-border,rgba(61,106,138,.12));}
.record-body.open{display:block;padding-top:16px;}
.record-field{margin-bottom:12px;}
.record-field strong{display:block;font-size:.82rem;color:var(--text-muted,#64748B);margin-bottom:2px;text-transform:uppercase;letter-spacing:.4px;}
</style>

<script>
(function(){
  var currentPage = 1;

  function load(page){
    currentPage=page||1;
    utils.apiGet(utils.apiUrl('records/list.php'),{page:currentPage,per_page:10},function(err,data){
      var el=document.getElementById('recordsList');
      if(!data||!data.success||!data.data.records||!data.data.records.length){
        el.innerHTML='<p class="text-muted text-center" style="padding:32px;">No medical records have been found.</p>';
        document.getElementById('pagination').innerHTML='';return;
      }
      el.innerHTML='';
      data.data.records.forEach(function(r){
        var apptTime = (r.appointment_time || '').substring(0, 5) || '—';
        var statusColors = {
          completed: '#10B981', cancelled: '#EF4444',
          rescheduled: '#8B5CF6', confirmed: '#3D6A8A', pending: '#F59E0B'
        };
        var statusLabel = r.appointment_status || 'unknown';
        var statusBadge = '<span style="display:inline-block;padding:2px 10px;border-radius:999px;'
          + 'font-size:.75rem;font-weight:600;background:' + (statusColors[statusLabel] || '#64748B')
          + ';color:#fff;">' + statusLabel.charAt(0).toUpperCase() + statusLabel.slice(1) + '</span>';
        var card=document.createElement('div');card.className='record-card';
        card.innerHTML='<div class="record-header" onclick="this.nextElementSibling.classList.toggle(\'open\');this.querySelector(\'.chevron\').classList.toggle(\'fa-chevron-down\');this.querySelector(\'.chevron\').classList.toggle(\'fa-chevron-up\');">''
          +'<div><strong>'+utils.formatDate(r.visit_date||r.created_at)+'</strong>'
          +'<span class="text-muted text-sm" style="margin-left:6px;">· '+apptTime+'</span>'
          +'<span class="text-muted text-sm" style="margin-left:8px;">Dr. '+utils.escapeHtml(r.doctor_name||'—')+'</span></div>'
          +'<i class="fa-solid fa-chevron-down chevron" style="color:var(--text-muted);"></i></div>'
          +'<div class="record-body">'
          +'<div class="record-field"><strong>Appointment Time</strong>'+utils.escapeHtml(apptTime)+'</div>'
          +'<div class="record-field"><strong>Status</strong>'+statusBadge+'</div>'
          +'<div class="record-field"><strong>Reason for Visit / Concern</strong>'+utils.escapeHtml(r.reason_for_visit||'—')+'</div>'
          +'<div class="record-field"><strong>Diagnosis</strong>'+utils.escapeHtml(r.diagnosis||'—')+'</div>'
          +'<div class="record-field"><strong>Prescription</strong><pre style="white-space:pre-wrap;margin:0;font-family:inherit;">'+utils.escapeHtml(r.prescription||'—')+'</pre></div>'
          +'<div class="record-field"><strong>Doctor Notes</strong>'+utils.escapeHtml(r.notes||'—')+'</div>'
          +'</div>';
        el.appendChild(card);
      });
      renderPag(data.data.pagination);
    });
  }

  function renderPag(p){
    var el=document.getElementById('pagination');if(!p||p.total_pages<=1){el.innerHTML='';return;}
    var h='';for(var i=1;i<=p.total_pages;i++) h+='<button class="btn btn-sm '+(i===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadRecords('+i+')">'+i+'</button>';
    el.innerHTML=h;
  }
  window.loadRecords = load;
  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>