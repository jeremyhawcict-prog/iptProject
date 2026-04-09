<?php
$pageTitle  = 'Sign In';
$isAuthPage = true;
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) { header('Location: ' . getRedirectByRole()); exit; }

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="login-card">

    <!-- Left Panel — Branding -->
    <div class="card-visual">
      <div class="logo-container">
        <div class="logo-icon-wrap">
          <svg viewBox="0 0 28 28" fill="none"><rect x="11" y="3" width="6" height="22" rx="3" fill="white"/><rect x="3" y="11" width="22" height="6" rx="3" fill="white"/></svg>
        </div>
        <div class="brand-text">
          <span class="brand-name">MediQueue</span>
          <span class="brand-sub">Healthcare</span>
        </div>
      </div>
      <div class="visual-content">
        <div class="visual-center">
          <div class="pulse-rings"><div class="pulse-ring r1"></div><div class="pulse-ring r2"></div><div class="pulse-ring r3"></div><div class="pulse-ring r4"></div>
            <div class="pulse-center"><svg viewBox="0 0 28 28" fill="none" width="24" height="24"><rect x="11" y="3" width="6" height="22" rx="3" fill="white"/><rect x="3" y="11" width="22" height="6" rx="3" fill="white"/></svg></div>
          </div>
          <div class="heartbeat-container">
            <svg class="heartbeat-svg" viewBox="0 0 300 60" preserveAspectRatio="none">
              <defs><linearGradient id="hbGrad" x1="0%" y1="0%" x2="100%"><stop offset="0%" stop-color="#5B8DB4" stop-opacity="0"/><stop offset="15%" stop-color="#8AAEC7"/><stop offset="50%" stop-color="#fff"/><stop offset="85%" stop-color="#8AAEC7"/><stop offset="100%" stop-color="#5B8DB4" stop-opacity="0"/></linearGradient></defs>
              <path class="heartbeat-path" d="M0,30 L45,30 58,30 66,10 76,50 84,30 106,30 118,22 125,38 134,30 152,30 164,4 174,56 182,30 208,30 218,22 225,36 233,30 255,30 300,30" stroke="url(#hbGrad)" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <p class="visual-tagline">Smart Healthcare Queue Management</p>
        </div>
        <div class="visual-stats">
          <div class="stat-pill"><span class="stat-value">2.4k+</span><span class="stat-label">Patients</span></div>
          <div class="stat-pill"><span class="stat-value">98%</span><span class="stat-label">Satisfaction</span></div>
          <div class="stat-pill"><span class="stat-value">24/7</span><span class="stat-label">Support</span></div>
        </div>
      </div>
      <div class="deco-orb orb-1"></div><div class="deco-orb orb-2"></div><div class="deco-orb orb-3"></div>
    </div>

    <!-- Right Panel — Login Form -->
    <div class="card-form">
      <div class="corner-accent top-right"></div>
      <div class="corner-accent bottom-left"></div>
      <div class="form-content">
        <div class="form-header">
          <h2>Welcome back</h2>
          <p>Sign in to your MediQueue account</p>
        </div>

        <div id="alert-container"></div>

        <?php
        $verify = $_GET['verify'] ?? '';
        if ($verify === 'success'): ?>
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:.85rem;color:#059669;">
          <i class="fa-solid fa-check-circle"></i> Email verified successfully! You can now sign in.
        </div>
        <?php elseif ($verify === 'already'): ?>
        <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:.85rem;color:#2563EB;">
          <i class="fa-solid fa-info-circle"></i> Your email is already verified.
        </div>
        <?php elseif ($verify === 'invalid'): ?>
        <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:.85rem;color:#dc2626;">
          <i class="fa-solid fa-exclamation-circle"></i> Invalid or expired verification link.
        </div>
        <?php endif; ?>

        <form id="loginForm" novalidate>
          <div class="input-group">
            <div class="input-icon"><i class="fa-solid fa-envelope" style="font-size:16px;opacity:.5"></i></div>
            <input type="email" id="loginEmail" name="email" placeholder="Email address" autocomplete="email" required />
            <div class="input-border"></div>
          </div>
          <div class="input-group">
            <div class="input-icon"><i class="fa-solid fa-lock" style="font-size:16px;opacity:.5"></i></div>
            <input type="password" id="loginPassword" name="password" placeholder="Password" autocomplete="current-password" required />
            <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
            <div class="input-border"></div>
          </div>
          <div class="form-options">
            <label class="remember-me"><input type="checkbox" /><span>Remember me</span></label>
            <a href="<?= BASE_URL ?>/pages/reset-password.php" class="forgot-pass">Forgot password?</a>
          </div>
          <button type="submit" class="login-btn" id="loginBtn">
            <span class="btn-text">Sign In</span>
            <span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span>
          </button>
        </form>

        <div class="divider"><span>New here?</span></div>
        <p style="text-align:center;font-size:.9rem;color:var(--text-secondary,rgba(26,58,82,.6));">
          <a href="<?= BASE_URL ?>/pages/register.php" style="font-weight:700;">Create an account</a>
        </p>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // Password toggle
  var tog = document.getElementById('togglePassword');
  var pw  = document.getElementById('loginPassword');
  var eye = document.getElementById('eyeIcon');
  if (tog) tog.addEventListener('click', function(){
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    eye.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
  });

  // Form submit
  document.getElementById('loginForm').addEventListener('submit', function(e){
    e.preventDefault();
    var email = document.getElementById('loginEmail').value.trim();
    var password = document.getElementById('loginPassword').value;
    if (!email || !password) { utils.showAlert('Please fill in all fields.','warning'); return; }

    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Signing in\u2026';

    utils.apiPost(utils.apiUrl('auth/login.php'), {email: email, password: password}, function(err, data){
      btn.disabled = false;
      btn.querySelector('.btn-text').textContent = 'Sign In';
      if (err || !data.success) {
        utils.showAlert(data ? data.message : 'Login failed.', 'error');
        return;
      }
      window.location.href = data.data && data.data.redirect ? data.data.redirect : '<?= BASE_URL ?>/pages/index.php';
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>