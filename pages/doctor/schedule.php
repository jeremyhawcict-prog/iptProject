<?php
$pageTitle = 'My Schedule';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['doctor']);
$user = getCurrentUser();
$doctorId = $user['id'];
$doctorProfile = getDoctorProfile($doctorId);

$availRaw = $doctorProfile['available_days'] ?? '';
$availArr = json_decode((string) $availRaw, true);
if (!is_array($availArr)) {
  $availArr = $availRaw ? explode(',', (string) $availRaw) : [];
}
$availDays = array_values(array_unique(array_filter(array_map(
  static fn($d) => strtolower(trim((string) $d)),
  $availArr
))));

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>

<div style="display:grid;gap:20px;max-width:900px;">
  <div id="alert-container"></div>

  <!-- Generate Slots -->
  <div class="card-glass" style="padding:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
      <h2 style="margin:0;"><i class="fa-solid fa-calendar-plus" style="color:var(--teal-core);"></i> My Schedule</h2>
      <button class="btn btn-primary" onclick="openModal('generateModal')"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Slots</button>
    </div>

    <!-- Week Overview -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
      <?php
      $startOfWeek = date('Y-m-d', strtotime('monday this week'));
      for($i=0;$i<7;$i++):
        $d = date('Y-m-d', strtotime($startOfWeek . " +$i days"));
        $dayName = date('D', strtotime($d));
        $isToday = $d === date('Y-m-d');
      ?>
        <button class="btn btn-sm <?= $isToday ? 'btn-primary' : 'btn-outline' ?> day-btn" data-date="<?= $d ?>" onclick="loadSlots('<?= $d ?>')" style="min-width:80px;">
          <?= $dayName ?><br><small><?= date('M j', strtotime($d)) ?></small>
        </button>
      <?php endfor; ?>
    </div>

    <div id="slotsContainer" style="min-height:60px;">
      <p class="text-muted text-center">Select a day to view slots.</p>
    </div>
  </div>
</div>

<!-- Generate Slots Modal -->
<div class="modal-overlay" id="generateModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header"><h3>Generate Time Slots</h3><button class="modal-close" onclick="closeModal('generateModal')">&times;</button></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Date Range</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <input type="date" id="genStartDate" class="form-control" min="<?= date('Y-m-d') ?>" />
          <input type="date" id="genEndDate" class="form-control" min="<?= date('Y-m-d') ?>" />
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div class="form-group"><label class="form-label">Start Time</label><input type="time" id="genStartTime" class="form-control" value="09:00" /></div>
        <div class="form-group"><label class="form-label">End Time</label><input type="time" id="genEndTime" class="form-control" value="17:00" /></div>
      </div>
      <div class="form-group"><label class="form-label">Slot Duration (min)</label><input type="number" id="genDuration" class="form-control" value="<?= SLOT_DURATION_MIN ?>" min="10" max="120" /></div>
      <div class="form-group"><label class="form-label">Days</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php foreach($days as $idx=>$day): ?>
            <?php $dayKey = strtolower($day); $isDefaultChecked = !empty($availDays) ? in_array($dayKey, $availDays, true) : ($idx < 5); ?>
            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" class="gen-day" value="<?= $dayKey ?>" <?= $isDefaultChecked ? 'checked' : '' ?> /> <?= substr($day,0,3) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('generateModal')">Cancel</button><button class="btn btn-primary" id="submitGenerate"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button></div>
  </div>
</div>

<style>
.slot-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.85rem;font-weight:600;border:1px solid var(--input-border,rgba(61,106,138,.22));}
.slot-chip.available{background:var(--green-pale,rgba(16,185,129,.1));border-color:var(--emerald-core,#10B981);color:var(--emerald-core);}
.slot-chip.booked{background:var(--blue-pale,rgba(59,130,246,.08));border-color:var(--blue-core,#3B82F6);color:var(--blue-core);}
</style>

<script>
(function(){
  window.loadSlots = function(date){
    document.querySelectorAll('.day-btn').forEach(function(b){ b.classList.toggle('btn-primary', b.dataset.date===date); b.classList.toggle('btn-outline', b.dataset.date!==date); });
    var el=document.getElementById('slotsContainer');
    el.innerHTML='<p class="text-muted text-center">Loading…</p>';
    utils.apiGet(utils.apiUrl('doctors/schedule.php'), {doctor_id:<?= $doctorId ?>, from:date, to:date}, function(err,data){
      if(!data||!data.success||!data.data.slots||!data.data.slots.length){el.innerHTML='<p class="text-muted text-center">No slots for this day.</p>';return;}
      el.innerHTML='<div style="display:flex;flex-wrap:wrap;gap:8px;">'+data.data.slots.map(function(s){
        return '<div class="slot-chip '+(s.is_booked?'booked':'available')+'">'
          +s.start_time.substring(0,5)+' – '+s.end_time.substring(0,5)
          +(s.is_booked?' <i class="fa-solid fa-user-check"></i>':' <i class="fa-solid fa-circle-check"></i>')
          +'</div>';
      }).join('')+'</div>';
    });
  };

  // Generate
  document.getElementById('submitGenerate').addEventListener('click', function(){
    var days=[];
    document.querySelectorAll('.gen-day:checked').forEach(function(c){days.push(c.value);});
    if(!days.length){utils.showAlert('Select at least one day.','warning');return;}
    var start=document.getElementById('genStartDate').value, end=document.getElementById('genEndDate').value;
    if(!start||!end){utils.showAlert('Select date range.','warning');return;}
    var btn=this;btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Generating…';
    var startTime = document.getElementById('genStartTime').value;
    var endTime = document.getElementById('genEndTime').value;
    if(!startTime || !endTime){
      btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Generate';
      utils.showAlert('Select start and end time.','warning');
      return;
    }

    utils.apiPost(utils.apiUrl('appointments/slots/generate.php'),{
      doctor_id:<?= $doctorId ?>,
      from_date:start, to_date:end,
      start_time:startTime,
      end_time:endTime,
      slot_duration:parseInt(document.getElementById('genDuration').value),
      days:days,
      break_start_hour:12,
      break_end_hour:13
    },function(err,data){
      btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Generate';
      if(data&&data.success){closeModal('generateModal');utils.showToast('Slots generated!','success');}
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  });

  // Load today by default
  loadSlots('<?= date('Y-m-d') ?>');
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>