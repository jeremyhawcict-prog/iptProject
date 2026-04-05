<?php
$pageTitle = 'Book Appointment';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);
?>

<!-- 3-Step Wizard -->
<div class="card-glass" style="padding:32px;max-width:800px;margin:0 auto;">
  <!-- Step Indicator -->
  <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:32px;">
    <div class="wizard-step active" id="stepInd1"><span class="wizard-num">1</span> Choose Doctor</div>
    <div style="width:40px;height:2px;background:var(--glass-border,rgba(255,255,255,.3));border-radius:2px;"></div>
    <div class="wizard-step" id="stepInd2"><span class="wizard-num">2</span> Pick Time</div>
    <div style="width:40px;height:2px;background:var(--glass-border,rgba(255,255,255,.3));border-radius:2px;"></div>
    <div class="wizard-step" id="stepInd3"><span class="wizard-num">3</span> Confirm</div>
  </div>

  <div id="alert-container"></div>

  <!-- Step 1: Doctor Search/Filter -->
  <div id="wizardStep1">
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <input type="text" id="doctorSearch" class="form-control" placeholder="Search doctor by name…" style="flex:1;min-width:200px;" />
      <select id="specFilter" class="form-control" style="width:auto;min-width:180px;">
        <option value="">All Specializations</option>
      </select>
    </div>
    <div id="doctorList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;min-height:100px;">
      <p class="text-muted" style="text-align:center;grid-column:1/-1;padding:20px;">Loading doctors…</p>
    </div>
  </div>

  <!-- Step 2: Date + Slots -->
  <div id="wizardStep2" style="display:none;">
    <div id="selectedDoctorInfo" style="background:var(--input-bg,rgba(232,244,251,.55));border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;gap:12px;"></div>
    <div class="form-group">
      <label class="form-label">Select Date</label>
      <input type="date" id="apptDate" class="form-control" min="<?= date('Y-m-d') ?>" style="max-width:260px;" />
    </div>
    <div class="form-group">
      <label class="form-label">Available Slots</label>
      <div id="timeSlots" style="display:flex;flex-wrap:wrap;gap:8px;min-height:48px;"></div>
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;">
      <button class="btn btn-secondary" onclick="showStep(1)"><i class="fa-solid fa-arrow-left"></i> Back</button>
      <button class="btn btn-primary" id="toStep3">Continue <i class="fa-solid fa-arrow-right"></i></button>
    </div>
  </div>

  <!-- Step 3: Summary + Confirm -->
  <div id="wizardStep3" style="display:none;">
    <div style="background:var(--input-bg,rgba(232,244,251,.55));border-radius:16px;padding:24px;">
      <h3 style="margin:0 0 20px;"><i class="fa-solid fa-clipboard-check" style="color:var(--color-primary);"></i> Booking Summary</h3>
      <div id="summaryContent" style="display:grid;gap:12px;"></div>
    </div>
    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Notes (optional)</label>
      <textarea id="apptNotes" class="form-control" rows="3" placeholder="Any symptoms or notes for the doctor…"></textarea>
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;">
      <button class="btn btn-secondary" onclick="showStep(2)"><i class="fa-solid fa-arrow-left"></i> Back</button>
      <button class="btn btn-primary" id="confirmBooking"><i class="fa-solid fa-check"></i> Confirm Booking</button>
    </div>
  </div>
</div>

<style>
.wizard-step{display:flex;align-items:center;gap:8px;font-size:.88rem;font-weight:600;color:var(--text-muted,#64748B);transition:color .3s;}
.wizard-step.active{color:var(--teal-core,#3D6A8A);}
.wizard-step.active .wizard-num{background:var(--teal-core,#3D6A8A);color:#fff;}
.wizard-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;background:var(--input-bg,rgba(232,244,251,.55));transition:all .3s;}
.doctor-pick{background:var(--card-bg,rgba(255,254,252,.72));border:2px solid transparent;border-radius:14px;padding:16px;cursor:pointer;transition:all .25s ease;display:flex;align-items:center;gap:12px;}
.doctor-pick:hover{border-color:var(--teal-core,#3D6A8A);transform:translateY(-2px);}
.doctor-pick.selected{border-color:var(--teal-core,#3D6A8A);background:var(--input-focus-bg,rgba(61,106,138,.07));}
.slot-btn{padding:8px 16px;border-radius:8px;border:1px solid var(--input-border,rgba(61,106,138,.22));background:transparent;cursor:pointer;font-weight:600;font-size:.85rem;transition:all .2s;}
.slot-btn:hover{border-color:var(--teal-core,#3D6A8A);}
.slot-btn.selected{background:var(--teal-core,#3D6A8A);color:#fff;border-color:var(--teal-core,#3D6A8A);}
</style>

<script>
(function(){
  var selectedDoctor = null, selectedSlot = null;

  function showStep(n){
    document.getElementById('wizardStep1').style.display = n===1?'block':'none';
    document.getElementById('wizardStep2').style.display = n===2?'block':'none';
    document.getElementById('wizardStep3').style.display = n===3?'block':'none';
    for(var i=1;i<=3;i++) document.getElementById('stepInd'+i).classList.toggle('active', i<=n);
  }
  window.showStep = showStep;

  // Load specializations
  utils.apiGet(utils.apiUrl('doctors/specializations.php'), function(err,data){
    if(!data||!data.success) return;
    var sel = document.getElementById('specFilter');
    (data.data.specializations||data.data||[]).forEach(function(s){
      var o = document.createElement('option'); o.value = s; o.textContent = s; sel.appendChild(o);
    });
  });

  function loadDoctors(){
    var q = document.getElementById('doctorSearch').value.trim();
    var spec = document.getElementById('specFilter').value;
    var params = {}; if(q) params.search=q; if(spec) params.specialization=spec;
    utils.apiGet(utils.apiUrl('doctors/list.php'), params, function(err,data){
      var el = document.getElementById('doctorList');
      if(!data||!data.success||!data.data.doctors||!data.data.doctors.length){
        el.innerHTML='<p class="text-muted" style="text-align:center;grid-column:1/-1;padding:20px;">No doctors found.</p>'; return;
      }
      el.innerHTML='';
      data.data.doctors.forEach(function(d){
        var card = document.createElement('div');
        card.className='doctor-pick';
        card.innerHTML='<img src="'+utils.BASE_URL+'/assets/uploads/photos/'+(d.profile_photo||'default.svg')+'" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" onerror="this.src=\''+utils.BASE_URL+'/assets/uploads/photos/default.svg\'" />'
          +'<div><strong>Dr. '+utils.escapeHtml(d.full_name)+'</strong><br><span class="text-muted text-sm">'+(d.specialization||'General')+'</span>'
          +(d.avg_rating?'<br>'+utils.renderStars(d.avg_rating):'')+'</div>';
        card.addEventListener('click', function(){
          document.querySelectorAll('.doctor-pick').forEach(function(c){c.classList.remove('selected');});
          this.classList.add('selected');
          selectedDoctor = d;
          document.getElementById('selectedDoctorInfo').innerHTML =
            '<img src="'+utils.BASE_URL+'/assets/uploads/photos/'+(d.profile_photo||'default.svg')+'" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" />'
            +'<div><strong>Dr. '+utils.escapeHtml(d.full_name)+'</strong><br><span class="text-muted text-sm">'+(d.specialization||'General')+'</span></div>';
          showStep(2);
        });
        el.appendChild(card);
      });
    });
  }

  loadDoctors();
  document.getElementById('doctorSearch').addEventListener('input', utils.debounce(loadDoctors, 400));
  document.getElementById('specFilter').addEventListener('change', loadDoctors);

  // Date change → load slots
  document.getElementById('apptDate').addEventListener('change', function(){
    if(!selectedDoctor) return;
    var slotsEl = document.getElementById('timeSlots');
    slotsEl.innerHTML = '<span class="text-muted text-sm">Loading…</span>';
    selectedSlot = null;
    utils.apiGet(utils.apiUrl('doctors/availability.php'), {doctor_id:selectedDoctor.id, date:this.value}, function(err,data){
      if(!data||!data.success||!data.data.slots||!data.data.slots.length){ slotsEl.innerHTML='<span class="text-muted text-sm">No slots available for this date.</span>'; return; }
      slotsEl.innerHTML='';
      data.data.slots.forEach(function(s){
        var btn=document.createElement('button'); btn.type='button'; btn.className='slot-btn';
        btn.textContent=s.start_time.substring(0,5)+' – '+s.end_time.substring(0,5);
        btn.addEventListener('click', function(){
          document.querySelectorAll('.slot-btn').forEach(function(b){b.classList.remove('selected');});
          this.classList.add('selected'); selectedSlot=s;
        });
        slotsEl.appendChild(btn);
      });
    });
  });

  // Step 2 → 3
  document.getElementById('toStep3').addEventListener('click', function(){
    if(!selectedSlot){ utils.showAlert('Please select a time slot.','warning'); return; }
    document.getElementById('summaryContent').innerHTML =
      '<div><strong>Doctor:</strong> Dr. '+utils.escapeHtml(selectedDoctor.full_name)+' ('+utils.escapeHtml(selectedDoctor.specialization||'General')+')</div>'
      +'<div><strong>Date:</strong> '+utils.formatDate(document.getElementById('apptDate').value)+'</div>'
      +'<div><strong>Time:</strong> '+selectedSlot.start_time.substring(0,5)+' – '+selectedSlot.end_time.substring(0,5)+'</div>';
    showStep(3);
  });

  // Confirm booking
  document.getElementById('confirmBooking').addEventListener('click', function(){
    var btn=this; btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Booking…';
    utils.apiPost(utils.apiUrl('appointments/book.php'), {
      slot_id: selectedSlot.slot_id,
      notes: document.getElementById('apptNotes').value.trim()
    }, function(err,data){
      btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-check"></i> Confirm Booking';
      if(data&&data.success){
        utils.showToast('Appointment booked!','success');
        setTimeout(function(){ window.location.href=utils.pageUrl('patient/my-appointments.php'); },1200);
      } else { utils.showAlert(data?data.message:'Booking failed.','error'); }
    });
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>