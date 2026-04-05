<?php
$pageTitle = 'Doctor Dashboard';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['doctor']);
$user = getCurrentUser();
$doctorId = $user['id'];

// Stats
$stmtToday = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN time_slots t ON a.slot_id=t.id WHERE t.doctor_id=? AND a.appointment_date=CURDATE() AND a.status IN('pending','confirmed')");
$stmtToday->execute([$doctorId]);
$todayCount = $stmtToday->fetchColumn();

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN time_slots t ON a.slot_id=t.id WHERE t.doctor_id=?");
$stmtTotal->execute([$doctorId]);
$totalCount = $stmtTotal->fetchColumn();

$stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN time_slots t ON a.slot_id=t.id WHERE t.doctor_id=? AND a.status='completed'");
$stmtCompleted->execute([$doctorId]);
$completedCount = $stmtCompleted->fetchColumn();

$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN time_slots t ON a.slot_id=t.id WHERE t.doctor_id=? AND a.status='pending'");
$stmtPending->execute([$doctorId]);
$pendingCount = $stmtPending->fetchColumn();

// Today's appointments
$stmtAppts = $pdo->prepare("SELECT a.*, u.full_name AS patient_name, t.start_time, t.end_time
  FROM appointments a
  JOIN time_slots t ON a.slot_id=t.id
  JOIN users u ON a.patient_id=u.id
  WHERE t.doctor_id=? AND a.appointment_date=CURDATE() AND a.status IN('pending','confirmed')
  ORDER BY t.start_time ASC");
$stmtAppts->execute([$doctorId]);
$todayAppts = $stmtAppts->fetchAll();

// Upcoming
$stmtUpcoming = $pdo->prepare("SELECT a.*, u.full_name AS patient_name, t.start_time, t.end_time
  FROM appointments a
  JOIN time_slots t ON a.slot_id=t.id
  JOIN users u ON a.patient_id=u.id
  WHERE t.doctor_id=? AND a.appointment_date>CURDATE() AND a.status IN('pending','confirmed')
  ORDER BY a.appointment_date ASC, t.start_time ASC LIMIT 5");
$stmtUpcoming->execute([$doctorId]);
$upcomingAppts = $stmtUpcoming->fetchAll();
?>

<!-- Stat Cards -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--teal-pale);color:var(--teal-core);"><i class="fa-solid fa-calendar-day"></i></div><div><div class="stat-value"><?= $todayCount ?></div><div class="stat-label">Today's Appointments</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--blue-pale);color:var(--blue-core);"><i class="fa-solid fa-calendar-check"></i></div><div><div class="stat-value"><?= $totalCount ?></div><div class="stat-label">Total Appointments</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--green-pale);color:var(--emerald-core);"><i class="fa-solid fa-check-circle"></i></div><div><div class="stat-value"><?= $completedCount ?></div><div class="stat-label">Completed</div></div></div>
  <div class="card-glass stat-card"><div class="stat-icon" style="background:var(--amber-pale);color:var(--amber-core);"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value"><?= $pendingCount ?></div><div class="stat-label">Pending</div></div></div>
</div>

<!-- Notifications Panel -->
<div class="card-glass" style="padding:24px;margin-bottom:24px;">
  <h3 style="margin:0 0 16px;"><i class="fa-solid fa-bell" style="color:var(--blue-core);"></i> Recent Notifications</h3>
  <div id="notificationsPanel">
    <p class="text-muted text-center" style="padding:12px;">Loading notifications…</p>
  </div>
</div>
<script>
(function(){
  function loadNotifications(){
    utils.apiGet(utils.apiUrl('notifications/list.php'), {per_page:5}, function(err, data){
      var panel = document.getElementById('notificationsPanel');
      if(!data || !data.success || !data.data.notifications.length){
        panel.innerHTML='<p class="text-muted text-center" style="padding:12px;">No notifications.</p>';
        return;
      }
      var html='<ul style="list-style:none;padding:0;margin:0;">';
      data.data.notifications.forEach(function(n){
        var icon = n.type==='email'?'fa-envelope':n.type==='sms'?'fa-comment-sms':'fa-bell';
        var date = n.created_at ? new Date(n.created_at).toLocaleDateString() : '';
        html+='<li style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--glass-border);">';
        html+='<i class="fa-solid '+icon+'" style="color:var(--teal-core);margin-top:3px;"></i>';
        html+='<div style="flex:1;"><strong>'+utils.escapeHtml(n.subject)+'</strong>';
        html+='<p style="margin:4px 0 0;font-size:.85rem;color:var(--text-secondary);">'+utils.escapeHtml(n.message)+'</p>';
        html+='<small style="color:var(--text-tertiary);">'+date+'</small></div></li>';
      });
      html+='</ul>';
      panel.innerHTML=html;
    });
  }
  if(typeof utils!=='undefined' && utils.apiGet) loadNotifications();
  else document.addEventListener('DOMContentLoaded', loadNotifications);
})();
</script>

<!-- Today's Appointments -->
<div class="card-glass" style="padding:24px;margin-bottom:24px;">
  <h3 style="margin:0 0 16px;"><i class="fa-solid fa-stethoscope" style="color:var(--teal-core);"></i> Today's Appointments</h3>
  <?php if(empty($todayAppts)): ?>
    <p class="text-muted text-center" style="padding:24px;">No appointments today.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Time</th><th>Patient</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($todayAppts as $a): ?>
          <tr>
            <td><?= substr($a['start_time'],0,5) ?> – <?= substr($a['end_time'],0,5) ?></td>
            <td><?= htmlspecialchars($a['patient_name']) ?></td>
            <td><span class="badge <?= $a['status']==='confirmed'?'badge-success':'badge-warning' ?>"><?= $a['status'] ?></span></td>
            <td style="display:flex;gap:6px;">
              <?php if($a['status']==='pending'): ?>
                <button class="btn btn-sm btn-success" onclick="confirmAppt(<?= $a['id'] ?>)"><i class="fa-solid fa-check"></i> Confirm</button>
              <?php endif; ?>
              <?php if($a['status']==='confirmed'): ?>
                <button class="btn btn-sm btn-primary" onclick="openComplete(<?= $a['id'] ?>,<?= $a['patient_id'] ?>)"><i class="fa-solid fa-clipboard-check"></i> Complete</button>
              <?php endif; ?>
              <button class="btn btn-sm btn-danger" onclick="cancelAppt(<?= $a['id'] ?>)"><i class="fa-solid fa-xmark"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Upcoming -->
<div class="card-glass" style="padding:24px;">
  <h3 style="margin:0 0 16px;"><i class="fa-solid fa-calendar" style="color:var(--blue-core);"></i> Upcoming Appointments</h3>
  <?php if(empty($upcomingAppts)): ?>
    <p class="text-muted text-center" style="padding:24px;">No upcoming appointments.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($upcomingAppts as $a): ?>
          <tr>
            <td><?= formatDate($a['appointment_date']) ?></td>
            <td><?= substr($a['start_time'],0,5) ?> – <?= substr($a['end_time'],0,5) ?></td>
            <td><?= htmlspecialchars($a['patient_name']) ?></td>
            <td><span class="badge <?= $a['status']==='confirmed'?'badge-success':'badge-warning' ?>"><?= $a['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Complete Appointment Modal -->
<div class="modal-overlay" id="completeModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header"><h3>Complete Appointment</h3><button class="modal-close" onclick="closeModal('completeModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="compApptId" /><input type="hidden" id="compPatientId" />
      <div class="form-group"><label class="form-label">Diagnosis *</label><textarea id="compDiagnosis" class="form-control" rows="2" required></textarea></div>
      <div class="form-group"><label class="form-label">Prescription</label><textarea id="compPrescription" class="form-control" rows="3"></textarea></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="compNotes" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('completeModal')">Cancel</button><button class="btn btn-primary" id="submitComplete"><i class="fa-solid fa-check"></i> Complete & Save Record</button></div>
  </div>
</div>

<div id="alert-container"></div>

<script>
(function(){
  window.confirmAppt = function(id){
    utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:id, action:'confirm'}, function(err,data){
      if(data&&data.success){ utils.showToast('Confirmed!','success'); setTimeout(function(){location.reload();},800); }
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  };

  window.cancelAppt = function(id){
    utils.confirmAction('Cancel this appointment?', function(){
      utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:id, action:'cancel'}, function(err,data){
        if(data&&data.success){ utils.showToast('Cancelled.','success'); setTimeout(function(){location.reload();},800); }
        else utils.showAlert(data?data.message:'Failed.','error');
      });
    });
  };

  window.openComplete = function(apptId, patientId){
    document.getElementById('compApptId').value=apptId;
    document.getElementById('compPatientId').value=patientId;
    document.getElementById('compDiagnosis').value='';
    document.getElementById('compPrescription').value='';
    document.getElementById('compNotes').value='';
    openModal('completeModal');
  };

  document.getElementById('submitComplete').addEventListener('click', function(){
    var diag=document.getElementById('compDiagnosis').value.trim();
    if(!diag){utils.showAlert('Diagnosis is required.','warning');return;}
    var btn=this; btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    // First complete the appointment
    utils.apiPost(utils.apiUrl('appointments/update.php'), {appointment_id:parseInt(document.getElementById('compApptId').value), action:'complete'}, function(err,data){
      if(!data||!data.success){ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-check"></i> Complete & Save Record'; utils.showAlert(data?data.message:'Failed.','error'); return; }
      // Then save the record
      utils.apiPost(utils.apiUrl('records/save.php'), {
        appointment_id: parseInt(document.getElementById('compApptId').value),
        patient_id: parseInt(document.getElementById('compPatientId').value),
        diagnosis: diag,
        prescription: document.getElementById('compPrescription').value.trim(),
        notes: document.getElementById('compNotes').value.trim()
      }, function(err2,data2){
        btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-check"></i> Complete & Save Record';
        if(data2&&data2.success){ closeModal('completeModal'); utils.showToast('Done!','success'); setTimeout(function(){location.reload();},800); }
        else { utils.showToast('Appointment completed but record save failed.','warning'); setTimeout(function(){location.reload();},1200); }
      });
    });
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>