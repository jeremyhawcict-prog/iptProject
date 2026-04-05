<?php
$pageTitle = 'System Settings';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);
?>

<div class="card-glass" style="padding:24px;">
  <h2 style="margin:0 0 16px;"><i class="fa-solid fa-gear" style="color:var(--teal-core);"></i> System Settings</h2>
  <p class="text-muted text-sm" style="margin-bottom:20px;">Manage clinic information and appointment rules.</p>
  <div id="alert-container"></div>

  <div id="settings-loading" class="text-center" style="padding:40px;"><span class="text-muted">Loading…</span></div>

  <form id="settings-form" style="display:none;" autocomplete="off">

    <!-- Clinic Information -->
    <div class="card-glass" style="padding:20px;margin-bottom:16px;">
      <h3 style="margin:0 0 14px;font-size:1rem;"><i class="fa-solid fa-hospital" style="color:var(--teal-core);"></i> Clinic Information</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label class="form-label">Clinic Name</label><input type="text" class="form-control" id="s-clinic_name" maxlength="150" /></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" id="s-clinic_email" maxlength="150" /></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="text" class="form-control" id="s-clinic_phone" maxlength="20" /></div>
        <div class="form-group"><label class="form-label">Address</label><input type="text" class="form-control" id="s-clinic_address" maxlength="255" /></div>
      </div>
    </div>

    <!-- Operating Hours -->
    <div class="card-glass" style="padding:20px;margin-bottom:16px;">
      <h3 style="margin:0 0 14px;font-size:1rem;"><i class="fa-solid fa-clock" style="color:var(--teal-core);"></i> Operating Hours</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label class="form-label">Opens at</label><input type="time" class="form-control" id="s-operating_hours_start" /></div>
        <div class="form-group"><label class="form-label">Closes at</label><input type="time" class="form-control" id="s-operating_hours_end" /></div>
        <div class="form-group"><label class="form-label">Lunch Break Start</label><input type="time" class="form-control" id="s-lunch_break_start" /></div>
        <div class="form-group"><label class="form-label">Lunch Break End</label><input type="time" class="form-control" id="s-lunch_break_end" /></div>
      </div>
    </div>

    <!-- Appointment Rules -->
    <div class="card-glass" style="padding:20px;margin-bottom:16px;">
      <h3 style="margin:0 0 14px;font-size:1rem;"><i class="fa-solid fa-sliders" style="color:var(--teal-core);"></i> Appointment Rules</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div class="form-group"><label class="form-label">Slot Duration (min)</label><input type="number" class="form-control" id="s-default_slot_duration" min="10" max="120" step="5" /></div>
        <div class="form-group"><label class="form-label">Max Advance Booking (days)</label><input type="number" class="form-control" id="s-max_advance_booking_days" min="1" max="365" /></div>
        <div class="form-group"><label class="form-label">Cancel Window (hours)</label><input type="number" class="form-control" id="s-cancellation_window_hours" min="0" max="168" /></div>
      </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button type="button" class="btn btn-outline" id="btn-reset">Reset</button>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Settings</button>
    </div>
  </form>
</div>

<script>
(function(){
  var form    = document.getElementById('settings-form');
  var loader  = document.getElementById('settings-loading');
  var original = {};

  var keys = [
    'clinic_name','clinic_email','clinic_phone','clinic_address',
    'operating_hours_start','operating_hours_end','lunch_break_start','lunch_break_end',
    'default_slot_duration','max_advance_booking_days','cancellation_window_hours'
  ];

  function populate(settings) {
    keys.forEach(function(k) {
      var el = document.getElementById('s-' + k);
      if (el) el.value = settings[k] || '';
    });
    original = Object.assign({}, settings);
  }

  function loadSettings() {
    utils.apiGet(utils.apiUrl('settings/get.php'), {}, function(err, res) {
      loader.style.display = 'none';
      if (err || !res || !res.success) {
        utils.showAlert('Failed to load settings.', 'error');
        return;
      }
      populate(res.data.settings || {});
      form.style.display = 'block';
    });
  }

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var settings = {};
    keys.forEach(function(k) {
      var el = document.getElementById('s-' + k);
      if (el) settings[k] = el.value.trim();
    });
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    utils.apiPost(utils.apiUrl('settings/update.php'), { settings: settings }, function(err, res) {
      btn.disabled = false;
      if (err || !res || !res.success) {
        utils.showAlert(res ? res.message : 'Save failed.', 'error');
        return;
      }
      original = Object.assign({}, settings);
      utils.showToast('Settings saved!', 'success');
    });
  });

  document.getElementById('btn-reset').addEventListener('click', function() { populate(original); });

  loadSettings();
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
