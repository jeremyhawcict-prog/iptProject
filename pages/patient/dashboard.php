<?php
$pageTitle = 'Patient Dashboard';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);
$uid = $_SESSION['user_id'];
$pdo = getDB();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=?"); $stmt->execute([$uid]); $totalAppts = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND status='confirmed'"); $stmt->execute([$uid]); $upcomingCount = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND status='completed'"); $stmt->execute([$uid]); $completedCount = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND status='cancelled'"); $stmt->execute([$uid]); $cancelledCount = (int)$stmt->fetchColumn();

// Upcoming appointments
$stmt = $pdo->prepare(
    "SELECT a.*, ts.slot_date, ts.start_time, ts.end_time, u.full_name AS doctor_name, dp.specialization
     FROM appointments a
     JOIN time_slots ts ON ts.id = a.slot_id
     JOIN users u ON u.id = a.doctor_id
     LEFT JOIN doctor_profiles dp ON dp.user_id = a.doctor_id
     WHERE a.patient_id = ? AND a.status IN ('pending','confirmed')
     ORDER BY ts.slot_date ASC, ts.start_time ASC LIMIT 5"
);
$stmt->execute([$uid]);
$upcoming = $stmt->fetchAll();

?>

<!-- Stat Cards -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;">
  <div class="stat-card card-glass">
    <div class="stat-card-icon" style="background:var(--teal-pale,rgba(61,106,138,.1));color:var(--teal-core,#3D6A8A);"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-card-info"><div class="stat-card-value"><?= $totalAppts ?></div><div class="stat-card-label">Total Appointments</div></div>
  </div>
  <div class="stat-card card-glass">
    <div class="stat-card-icon" style="background:rgba(59,130,246,.1);color:#3B82F6;"><i class="fa-solid fa-clock"></i></div>
    <div class="stat-card-info"><div class="stat-card-value"><?= $upcomingCount ?></div><div class="stat-card-label">Upcoming</div></div>
  </div>
  <div class="stat-card card-glass">
    <div class="stat-card-icon" style="background:rgba(16,185,129,.1);color:#10B981;"><i class="fa-solid fa-circle-check"></i></div>
    <div class="stat-card-info"><div class="stat-card-value"><?= $completedCount ?></div><div class="stat-card-label">Completed</div></div>
  </div>
  <div class="stat-card card-glass">
    <div class="stat-card-icon" style="background:rgba(239,68,68,.1);color:#EF4444;"><i class="fa-solid fa-circle-xmark"></i></div>
    <div class="stat-card-info"><div class="stat-card-value"><?= $cancelledCount ?></div><div class="stat-card-label">Cancelled</div></div>
  </div>
</div>

<!-- Upcoming Appointments -->
<div class="card-glass" style="padding:24px;margin-bottom:24px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h3 style="margin:0;"><i class="fa-solid fa-calendar-days" style="color:var(--color-primary);margin-right:8px;"></i>Upcoming Appointments</h3>
    <a href="<?= BASE_URL ?>/pages/patient/book-appointment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Book New</a>
  </div>
  <?php if (empty($upcoming)): ?>
  <p class="text-muted" style="text-align:center;padding:32px 0;">No upcoming appointments. <a href="<?= BASE_URL ?>/pages/patient/book-appointment.php">Book one now!</a></p>
  <?php else: ?>
  <div class="table-responsive" style="overflow-x:auto;">
    <table class="mq-table">
      <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Specialization</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $a): ?>
      <tr data-appt-id="<?= (int) $a['id'] ?>" data-appt-status="<?= htmlspecialchars($a['status']) ?>">
        <td><?= formatDate($a['slot_date']) ?></td>
        <td><?= formatTime($a['start_time']) ?> - <?= formatTime($a['end_time']) ?></td>
        <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
        <td><?= htmlspecialchars($a['specialization'] ?? '-') ?></td>
        <td><span class="badge <?= $a['status'] === 'confirmed' ? 'badge-success' : 'badge-warning' ?> js-appt-status-badge"><?= ucfirst(str_replace('_', ' ', $a['status'])) ?></span></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-sm btn-secondary reschedule-btn" data-id="<?= $a['id'] ?>"><i class="fa-solid fa-clock-rotate-left"></i></button>
          <button class="btn btn-sm btn-danger cancel-btn" data-id="<?= $a['id'] ?>"><i class="fa-solid fa-xmark"></i></button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Reschedule Modal -->
<div class="modal-overlay" id="rescheduleModal">
  <div class="modal">
    <div class="modal-header"><h3 class="modal-title">Reschedule Appointment</h3><button class="modal-close" onclick="closeModal('rescheduleModal')">&times;</button></div>
    <div style="padding:20px;">
      <input type="hidden" id="rescheduleApptId" />
      <div class="form-group"><label class="form-label">New Date</label><input type="date" id="rescheduleDate" class="form-control" min="<?= date('Y-m-d') ?>" /></div>
      <div class="form-group"><label class="form-label">Available Slots</label><div id="rescheduleSlots" style="display:flex;flex-wrap:wrap;gap:8px;min-height:40px;"><span class="text-muted text-sm">Select a date first</span></div></div>
      <input type="hidden" id="rescheduleSlotId" />
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
        <button class="btn btn-secondary" onclick="closeModal('rescheduleModal')">Cancel</button>
        <button class="btn btn-primary" id="confirmReschedule">Confirm Reschedule</button>
      </div>
    </div>
  </div>
</div>



<script>
(function(){
  function statusLabel(status){
    if(!status) return 'Unknown';
    var text = String(status).replace(/_/g, ' ');
    return text.charAt(0).toUpperCase() + text.slice(1);
  }

  function statusClass(status){
    var map = {
      pending: 'badge-warning',
      confirmed: 'badge-success',
      in_progress: 'badge-info',
      completed: 'badge-primary',
      cancelled: 'badge-danger',
      no_show: 'badge-danger',
      rescheduled: 'badge-info'
    };
    return map[status] || 'badge-info';
  }

  function refreshUpcomingStatuses(){
    document.querySelectorAll('tr[data-appt-id]').forEach(function(row){
      var appointmentId = parseInt(row.getAttribute('data-appt-id'), 10);
      if(!appointmentId) return;

      utils.apiGet(utils.apiUrl('appointments/get.php'), {id: appointmentId}, function(err, data){
        if(err || !data || !data.success || !data.data || !data.data.appointment) return;

        var latestStatus = data.data.appointment.status || '';
        var previousStatus = row.getAttribute('data-appt-status') || '';
        if(!latestStatus || latestStatus === previousStatus) return;

        row.setAttribute('data-appt-status', latestStatus);
        var badge = row.querySelector('.js-appt-status-badge');
        if(badge){
          badge.className = 'badge ' + statusClass(latestStatus) + ' js-appt-status-badge';
          badge.textContent = statusLabel(latestStatus);
        }

        if(previousStatus === 'pending' && latestStatus === 'confirmed') {
          utils.showToast('Your appointment is now confirmed.', 'success', 5000);
        } else {
          utils.showToast('Appointment status updated to ' + statusLabel(latestStatus) + '.', 'info', 4500);
        }
      });
    });
  }

  // Cancel appointment
  document.querySelectorAll('.cancel-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = this.dataset.id;
      utils.confirmAction('Cancel this appointment?', function(){
        utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:+id, action:'cancel'}, function(err,data){
          if(data&&data.success){ utils.showToast('Appointment cancelled.','success'); setTimeout(function(){location.reload();},1000); }
          else utils.showToast(data?data.message:'Failed.','error');
        });
      });
    });
  });

  // Reschedule
  var currentDoctorId = null;
  document.querySelectorAll('.reschedule-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.getElementById('rescheduleApptId').value = this.dataset.id;
      document.getElementById('rescheduleSlots').innerHTML = '<span class="text-muted text-sm">Select a date first</span>';
      openModal('rescheduleModal');
      // fetch appointment to get doctor_id
      utils.apiGet(utils.apiUrl('appointments/get.php'), {id: this.dataset.id}, function(err,data){
        if(data&&data.success&&data.data.appointment) currentDoctorId = data.data.appointment.doctor_id;
      });
    });
  });

  document.getElementById('rescheduleDate').addEventListener('change', function(){
    if(!currentDoctorId) return;
    var slotsEl = document.getElementById('rescheduleSlots');
    slotsEl.innerHTML = '<span class="text-muted text-sm">Loading…</span>';
    utils.apiGet(utils.apiUrl('doctors/availability.php'), {doctor_id:currentDoctorId, date:this.value}, function(err,data){
      if(!data||!data.success||!data.data.slots||!data.data.slots.length){ slotsEl.innerHTML='<span class="text-muted text-sm">No slots available</span>'; return; }
      slotsEl.innerHTML = '';
      data.data.slots.forEach(function(s){
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-secondary slot-pick';
        btn.textContent = s.start_time.substring(0,5) + ' - ' + s.end_time.substring(0,5);
        btn.dataset.slotId = s.slot_id;
        btn.addEventListener('click', function(){
          document.querySelectorAll('.slot-pick').forEach(function(b){b.classList.remove('btn-primary');b.classList.add('btn-secondary');});
          this.classList.remove('btn-secondary'); this.classList.add('btn-primary');
          document.getElementById('rescheduleSlotId').value = s.slot_id;
        });
        slotsEl.appendChild(btn);
      });
    });
  });

  document.getElementById('confirmReschedule').addEventListener('click', function(){
    var apptId = document.getElementById('rescheduleApptId').value;
    var slotId = document.getElementById('rescheduleSlotId').value;
    if(!slotId){ utils.showToast('Please select a time slot.','warning'); return; }
    utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:+apptId, action:'reschedule', new_slot_id:+slotId}, function(err,data){
      if(data&&data.success){ utils.showToast('Rescheduled!','success'); closeModal('rescheduleModal'); setTimeout(function(){location.reload();},1000); }
      else utils.showToast(data?data.message:'Failed.','error');
    });
  });

  refreshUpcomingStatuses();
  setInterval(function(){
    if(!document.hidden) refreshUpcomingStatuses();
  }, 8000);
  document.addEventListener('mq:notification:new', refreshUpcomingStatuses);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
