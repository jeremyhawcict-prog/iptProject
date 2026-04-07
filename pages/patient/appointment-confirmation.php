<?php
$pageTitle = 'Appointment Confirmed';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);

$appointmentId = getGetInt('id');
if (!$appointmentId) {
    header('Location: ' . BASE_URL . '/pages/patient/my-appointments.php');
    exit;
}
?>

<div class="card-glass" style="padding:40px;max-width:600px;margin:0 auto;text-align:center;">
  <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
    <i class="fa-solid fa-check" style="font-size:2rem;color:#fff;"></i>
  </div>
  <h2 style="margin:0 0 8px;font-size:1.5rem;">Appointment Booked!</h2>
  <p class="text-muted" style="margin-bottom:28px;">Your appointment has been successfully submitted and is pending confirmation.</p>

  <div id="confirmDetails" style="background:var(--input-bg,rgba(232,244,251,.55));border-radius:12px;padding:24px;text-align:left;margin-bottom:24px;">
    <p class="text-muted" style="text-align:center;">Loading details…</p>
  </div>

  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/pages/patient/my-appointments.php" class="btn btn-primary"><i class="fa-solid fa-calendar-check"></i> My Appointments</a>
    <a href="<?= BASE_URL ?>/pages/patient/dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Print</button>
  </div>
</div>

<script>
(function(){
  utils.apiGet(utils.apiUrl('appointments/get.php'), {id: <?= $appointmentId ?>}, function(err, data){
    var el = document.getElementById('confirmDetails');
    if(!data || !data.success || !data.data.appointment){
      el.innerHTML='<p class="text-muted" style="text-align:center;">Could not load appointment details.</p>';
      return;
    }
    var a = data.data.appointment;
    el.innerHTML = ''
      + '<div style="display:grid;gap:12px;font-size:.92rem;">'
      + '<div><strong>Appointment ID:</strong> #'+a.id+'</div>'
      + '<div><strong>Doctor:</strong> '+(a.doctor_name||a.full_name||'N/A')+'</div>'
      + '<div><strong>Specialization:</strong> '+(a.specialization||'N/A')+'</div>'
      + '<div><strong>Date:</strong> '+utils.formatDate(a.appointment_date)+'</div>'
      + '<div><strong>Time:</strong> '+(a.start_time ? a.start_time.substring(0,5) : 'N/A')+'</div>'
      + '<div><strong>Visit Type:</strong> '+(a.visit_type||'General Checkup')+'</div>'
      + '<div><strong>Status:</strong> <span class="status-badge '+a.status+'">'+a.status+'</span></div>'
      + (a.reason_for_visit ? '<div><strong>Reason:</strong> '+utils.escapeHtml(a.reason_for_visit)+'</div>' : '')
      + '</div>';
  });
})();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
