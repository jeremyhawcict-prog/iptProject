<?php
$pageTitle = 'Find a Doctor';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section style="padding:40px 20px;max-width:1100px;margin:0 auto;">
  <div style="text-align:center;margin-bottom:32px;">
    <h1 style="font-size:2rem;font-weight:800;">Find a <span style="color:var(--teal-core);">Doctor</span></h1>
    <p class="text-muted" style="max-width:500px;margin:8px auto 0;">Browse our doctors by name or specialization and book an appointment.</p>
  </div>

  <!-- Search & Filter -->
  <div class="card-glass" style="padding:20px;margin-bottom:24px;">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <input type="text" id="doctorSearch" class="form-control" placeholder="Search by doctor name…" style="flex:1;min-width:200px;" />
      <select id="specFilter" class="form-control" style="width:auto;min-width:180px;">
        <option value="">All Specializations</option>
      </select>
    </div>
  </div>

  <!-- Results -->
  <div id="doctorGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;min-height:200px;">
    <p class="text-muted" style="text-align:center;grid-column:1/-1;padding:40px;">Loading doctors…</p>
  </div>
</section>

<style>
.doc-card{background:var(--card-bg,rgba(255,254,252,.72));backdrop-filter:blur(10px);border:1px solid var(--glass-border,rgba(61,106,138,.12));border-radius:16px;padding:24px;transition:all .3s ease;display:flex;flex-direction:column;gap:16px;}
.doc-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.08);}
.doc-header{display:flex;align-items:center;gap:14px;}
.doc-photo{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--glass-border,rgba(61,106,138,.15));background:var(--input-bg);}
.doc-name{font-weight:700;font-size:1.05rem;margin:0;}
.doc-spec{font-size:.85rem;color:var(--text-muted);margin:2px 0 4px;}
.doc-stars{color:#F59E0B;font-size:.85rem;}
.doc-details{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.82rem;}
.doc-detail{display:flex;align-items:center;gap:6px;color:var(--text-muted);}
.doc-detail i{color:var(--teal-core);width:16px;text-align:center;}
.doc-actions{display:flex;gap:8px;margin-top:auto;}
.doc-actions .btn{font-size:.82rem;padding:8px 16px;}
</style>

<script>
(function(){
  // Load specializations
  utils.apiGet(utils.apiUrl('doctors/specializations.php'), function(err,data){
    if(!data||!data.success) return;
    var sel = document.getElementById('specFilter');
    (data.data.specializations||data.data||[]).forEach(function(s){
      var o = document.createElement('option'); o.value=s; o.textContent=s; sel.appendChild(o);
    });
  });

  function loadDoctors(){
    var q = document.getElementById('doctorSearch').value.trim();
    var spec = document.getElementById('specFilter').value;
    var params = {per_page:50}; if(q) params.search=q; if(spec) params.specialization=spec;
    utils.apiGet(utils.apiUrl('doctors/list.php'), params, function(err,data){
      var el = document.getElementById('doctorGrid');
      if(!data||!data.success||!data.data.doctors||!data.data.doctors.length){
        el.innerHTML='<p class="text-muted" style="text-align:center;grid-column:1/-1;padding:40px;">No doctors found matching your criteria.</p>';
        return;
      }
      el.innerHTML='';
      data.data.doctors.forEach(function(d){
        var stars = '';
        var rating = parseFloat(d.avg_rating)||0;
        for(var i=1;i<=5;i++) stars+='<i class="fa-'+(i<=Math.round(rating)?'solid':'regular')+' fa-star"></i>';
        var reviews = d.review_count||0;

        var card = document.createElement('div');
        card.className='doc-card';
        card.innerHTML=
          '<div class="doc-header">'
          + '<img src="'+utils.BASE_URL+'/assets/uploads/photos/'+(d.profile_photo||'default.svg')+'" class="doc-photo" onerror="this.src=\''+utils.BASE_URL+'/assets/uploads/photos/default.svg\'" />'
          + '<div>'
          +   '<h3 class="doc-name">'+utils.escapeHtml(d.full_name)+'</h3>'
          +   '<p class="doc-spec">'+(d.specialization||'General Medicine')+'</p>'
          +   '<div class="doc-stars">'+stars+' <span class="text-muted" style="font-size:.78rem;">('+reviews+')</span></div>'
          + '</div>'
          + '</div>'
          + '<div class="doc-details">'
          +   '<div class="doc-detail"><i class="fa-solid fa-briefcase-medical"></i> '+utils.escapeHtml(String(d.years_experience||0))+' yrs exp</div>'
          +   '<div class="doc-detail"><i class="fa-solid fa-peso-sign"></i> '+(d.consultation_fee||'0.00')+'</div>'
          +   '<div class="doc-detail"><i class="fa-solid fa-clock"></i> '+(d.consultation_duration||30)+' min</div>'
          +   '<div class="doc-detail"><i class="fa-solid fa-location-dot"></i> '+(d.clinic_address?utils.escapeHtml(d.clinic_address).substring(0,25)+'…':'N/A')+'</div>'
          + '</div>'
          + '<div class="doc-actions">'
          + (<?= isLoggedIn() && hasRole('patient') ? 'true' : 'false' ?>
              ? '<a href="'+utils.BASE_URL+'/pages/patient/book-appointment.php?doctor='+d.id+'" class="btn btn-primary" style="flex:1;text-align:center;"><i class="fa-solid fa-calendar-plus"></i> Book Now</a>'
              : '<a href="'+utils.BASE_URL+'/pages/login.php" class="btn btn-primary" style="flex:1;text-align:center;"><i class="fa-solid fa-right-to-bracket"></i> Login to Book</a>')
          + '</div>';
        el.appendChild(card);
      });
    });
  }

  loadDoctors();
  document.getElementById('doctorSearch').addEventListener('input', utils.debounce(loadDoctors, 400));
  document.getElementById('specFilter').addEventListener('change', loadDoctors);
})();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
