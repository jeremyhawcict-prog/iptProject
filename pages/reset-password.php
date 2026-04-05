<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) { header('Location: ' . getRedirectByRole()); exit; }
$token = isset($_GET['token']) ? $_GET['token'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" />
<title>MediQueue &mdash; Reset Password</title>
<meta name="csrf-token" content="<?= getCsrfToken() ?>" /><meta name="base-url" content="<?= BASE_URL ?>" />
<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />
</head>
<body>
<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="bg-blob blob-1"></div><div class="bg-blob blob-2"></div><div class="bg-blob blob-3"></div><div class="bg-blob blob-4"></div><div class="bg-blob blob-5"></div>

<div class="container">
  <div class="login-card" style="max-width:480px;">
    <div class="card-form" style="flex:1;">
      <div class="corner-accent top-right"></div><div class="corner-accent bottom-left"></div>
      <div class="form-content">

        <?php if (!$token): ?>
        <!-- Step 1: Request reset -->
        <div class="form-header"><h2>Forgot Password</h2><p>Enter your email to receive a reset link</p></div>
        <div id="alert-container"></div>
        <form id="forgotForm" novalidate>
          <div class="input-group">
            <div class="input-icon"><i class="fa-solid fa-envelope" style="font-size:16px;opacity:.5"></i></div>
            <input type="email" id="resetEmail" placeholder="Email address" required />
            <div class="input-border"></div>
          </div>
          <button type="submit" class="login-btn" id="forgotBtn"><span class="btn-text">Send Reset Link</span><span class="btn-arrow"><i class="fa-solid fa-paper-plane"></i></span></button>
        </form>
        <?php else: ?>
        <!-- Step 2: New password -->
        <div class="form-header"><h2>Set New Password</h2><p>Enter your new password below</p></div>
        <div id="alert-container"></div>
        <form id="resetForm" novalidate>
          <input type="hidden" id="resetToken" value="<?= htmlspecialchars($token) ?>" />
          <div class="input-group">
            <div class="input-icon"><i class="fa-solid fa-lock" style="font-size:16px;opacity:.5"></i></div>
            <input type="password" id="newPassword" placeholder="New password (min 8 chars)" required />
            <div class="input-border"></div>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fa-solid fa-lock" style="font-size:16px;opacity:.5"></i></div>
            <input type="password" id="confirmPassword" placeholder="Confirm new password" required />
            <div class="input-border"></div>
          </div>
          <button type="submit" class="login-btn" id="resetBtn"><span class="btn-text">Reset Password</span><span class="btn-arrow"><i class="fa-solid fa-check"></i></span></button>
        </form>
        <?php endif; ?>

        <div class="divider"><span></span></div>
        <p style="text-align:center;font-size:.9rem;"><a href="<?= BASE_URL ?>/pages/login.php" style="font-weight:700;">Back to Sign In</a></p>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
(function(){
  var forgotForm = document.getElementById('forgotForm');
  if (forgotForm) {
    forgotForm.addEventListener('submit', function(e){
      e.preventDefault();
      var email = document.getElementById('resetEmail').value.trim();
      if (!email) { utils.showAlert('Please enter your email.','warning'); return; }
      var btn = document.getElementById('forgotBtn');
      btn.disabled = true; btn.querySelector('.btn-text').textContent = 'Sending…';
      utils.apiPost(utils.apiUrl('auth/forgot-password.php'), {email:email}, function(err,data){
        btn.disabled = false; btn.querySelector('.btn-text').textContent = 'Send Reset Link';
        if (err||!data.success) { utils.showAlert(data?data.message:'Request failed.','error'); return; }
        utils.showAlert('Reset link sent to your email. Link expires in 1 hour.','success');
      });
    });
  }
  var resetForm = document.getElementById('resetForm');
  if (resetForm) {
    resetForm.addEventListener('submit', function(e){
      e.preventDefault();
      var pw = document.getElementById('newPassword').value, c = document.getElementById('confirmPassword').value;
      if (pw.length < 8) { utils.showAlert('Password must be at least 8 characters.','warning'); return; }
      if (pw !== c) { utils.showAlert('Passwords do not match.','warning'); return; }
      var btn = document.getElementById('resetBtn');
      btn.disabled = true; btn.querySelector('.btn-text').textContent = 'Resetting…';
      utils.apiPost(utils.apiUrl('auth/reset-password.php'), {
        token: document.getElementById('resetToken').value,
        password: pw
      }, function(err,data){
        btn.disabled = false; btn.querySelector('.btn-text').textContent = 'Reset Password';
        if (err||!data.success) { utils.showAlert(data?data.message:'Reset failed.','error'); return; }
        utils.showAlert('Password reset! Redirecting to login…','success');
        setTimeout(function(){ window.location.href='<?= BASE_URL ?>/pages/login.php'; },1500);
      });
    });
  }
})();
</script>
</body>
</html>