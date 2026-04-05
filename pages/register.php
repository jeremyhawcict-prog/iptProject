<?php
$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) { header('Location: ' . getRedirectByRole()); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" />
<title>MediQueue &mdash; Create Account</title>
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
  <div class="login-card">
    <!-- Left Panel -->
    <div class="card-visual">
      <div class="logo-container">
        <div class="logo-icon-wrap"><svg viewBox="0 0 28 28" fill="none"><rect x="11" y="3" width="6" height="22" rx="3" fill="white"/><rect x="3" y="11" width="22" height="6" rx="3" fill="white"/></svg></div>
        <div class="brand-text"><span class="brand-name">MediQueue</span><span class="brand-sub">Healthcare</span></div>
      </div>
      <div class="visual-content">
        <div class="visual-center">
          <div class="pulse-rings"><div class="pulse-ring r1"></div><div class="pulse-ring r2"></div><div class="pulse-ring r3"></div><div class="pulse-ring r4"></div>
            <div class="pulse-center"><svg viewBox="0 0 28 28" fill="none" width="24" height="24"><rect x="11" y="3" width="6" height="22" rx="3" fill="white"/><rect x="3" y="11" width="22" height="6" rx="3" fill="white"/></svg></div>
          </div>
          <p class="visual-tagline">Join MediQueue Today</p>
        </div>
      </div>
      <div class="deco-orb orb-1"></div><div class="deco-orb orb-2"></div><div class="deco-orb orb-3"></div>
    </div>

    <!-- Right Panel — Register Form -->
    <div class="card-form">
      <div class="corner-accent top-right"></div><div class="corner-accent bottom-left"></div>
      <div class="form-content">
        <div class="form-header"><h2>Create Account</h2><p>Fill in your details to get started</p></div>
        <div id="alert-container"></div>

        <!-- Tabs -->
        <div class="reg-tabs" style="display:flex;gap:8px;margin-bottom:20px;">
          <button type="button" class="reg-tab active" data-tab="1" style="flex:1;padding:8px;border:1px solid rgba(61,106,138,.3);background:rgba(61,106,138,.1);border-radius:8px;font-weight:600;font-size:.85rem;cursor:pointer;font-family:Inter,sans-serif;color:#3D6A8A;">1. Account Info</button>
          <button type="button" class="reg-tab" data-tab="2" style="flex:1;padding:8px;border:1px solid rgba(61,106,138,.2);background:transparent;border-radius:8px;font-weight:600;font-size:.85rem;cursor:pointer;font-family:Inter,sans-serif;color:#64748B;">2. Personal Info</button>
        </div>

        <form id="registerForm" novalidate>
          <!-- Tab 1: Account -->
          <div class="reg-panel" id="regTab1">
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-envelope" style="font-size:16px;opacity:.5"></i></div>
              <input type="email" id="regEmail" placeholder="Email address" autocomplete="email" required />
              <div class="input-border"></div>
            </div>
            <div id="emailCheck" style="font-size:.8rem;margin:-8px 0 8px 4px;"></div>
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-lock" style="font-size:16px;opacity:.5"></i></div>
              <input type="password" id="regPassword" placeholder="Password (min 8 chars)" required />
              <div class="input-border"></div>
            </div>
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-lock" style="font-size:16px;opacity:.5"></i></div>
              <input type="password" id="regConfirm" placeholder="Confirm password" required />
              <div class="input-border"></div>
            </div>
            <button type="button" id="toTab2" class="login-btn" style="margin-top:8px;"><span class="btn-text">Next Step</span><span class="btn-arrow"><i class="fa-solid fa-arrow-right"></i></span></button>
          </div>

          <!-- Tab 2: Personal -->
          <div class="reg-panel" id="regTab2" style="display:none;">
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-user" style="font-size:16px;opacity:.5"></i></div>
              <input type="text" id="regName" placeholder="Full name" required />
              <div class="input-border"></div>
            </div>
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-phone" style="font-size:16px;opacity:.5"></i></div>
              <input type="tel" id="regPhone" placeholder="Phone number" />
              <div class="input-border"></div>
            </div>
            <div class="input-group">
              <div class="input-icon"><i class="fa-solid fa-calendar" style="font-size:16px;opacity:.5"></i></div>
              <input type="date" id="regDob" />
              <div class="input-border"></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="button" id="backTab1" class="login-btn" style="flex:1;background:rgba(100,116,139,.15);"><span class="btn-text" style="color:#475569;">Back</span></button>
              <button type="submit" class="login-btn" id="regBtn" style="flex:2;"><span class="btn-text">Create Account</span><span class="btn-arrow"><i class="fa-solid fa-check"></i></span></button>
            </div>
          </div>
        </form>

        <div class="divider"><span>Already have an account?</span></div>
        <p style="text-align:center;font-size:.9rem;color:var(--text-secondary,rgba(26,58,82,.6));"><a href="<?= BASE_URL ?>/pages/login.php" style="font-weight:700;">Sign in</a></p>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
(function(){
  var tabs = document.querySelectorAll('.reg-tab');
  function showTab(n){
    document.getElementById('regTab1').style.display = n===1?'block':'none';
    document.getElementById('regTab2').style.display = n===2?'block':'none';
    tabs.forEach(function(t){ t.classList.toggle('active', +t.dataset.tab===n);
      t.style.background = +t.dataset.tab===n ? 'rgba(61,106,138,.1)' : 'transparent';
      t.style.borderColor = +t.dataset.tab===n ? 'rgba(61,106,138,.3)' : 'rgba(100,116,139,.2)';
      t.style.color = +t.dataset.tab===n ? '#3D6A8A' : '#64748B';
    });
  }
  tabs.forEach(function(t){ t.addEventListener('click', function(){ showTab(+this.dataset.tab); }); });
  document.getElementById('toTab2').addEventListener('click', function(){
    var e=document.getElementById('regEmail').value.trim(), p=document.getElementById('regPassword').value, c=document.getElementById('regConfirm').value;
    if(!e||!p||!c){ utils.showAlert('Please fill in all fields.','warning'); return; }
    if(p.length<8){ utils.showAlert('Password must be at least 8 characters.','warning'); return; }
    if(p!==c){ utils.showAlert('Passwords do not match.','warning'); return; }
    showTab(2);
  });
  document.getElementById('backTab1').addEventListener('click', function(){ showTab(1); });

  // Real-time email check
  var emailTimer;
  document.getElementById('regEmail').addEventListener('input', function(){
    clearTimeout(emailTimer);
    var el=document.getElementById('emailCheck'), v=this.value.trim();
    if(v.length<5){ el.textContent=''; return; }
    emailTimer = setTimeout(function(){
      utils.apiGet(utils.apiUrl('auth/check-email.php'),{email:v}, function(err,d){
        if(!d) return;
        el.style.color = d.data && d.data.available ? '#10B981' : '#EF4444';
        el.textContent = d.data && d.data.available ? 'Email available' : 'Email already taken';
      });
    }, 500);
  });

  // Submit
  document.getElementById('registerForm').addEventListener('submit', function(e){
    e.preventDefault();
    var name=document.getElementById('regName').value.trim();
    if(!name){ utils.showAlert('Full name is required.','warning'); return; }
    var btn=document.getElementById('regBtn');
    btn.disabled=true; btn.querySelector('.btn-text').textContent='Creating…';
    utils.apiPost(utils.apiUrl('auth/register.php'),{
      email:document.getElementById('regEmail').value.trim(),
      password:document.getElementById('regPassword').value,
      full_name:name,
      phone:document.getElementById('regPhone').value.trim(),
      date_of_birth:document.getElementById('regDob').value
    }, function(err,data){
      btn.disabled=false; btn.querySelector('.btn-text').textContent='Create Account';
      if(err||!data.success){ utils.showAlert(data?data.message:'Registration failed.','error'); return; }
      utils.showAlert('Account created! Redirecting…','success');
      setTimeout(function(){ window.location.href=data.data.redirect||'<?= BASE_URL ?>/pages/patient/dashboard.php'; },1500);
    });
  });
})();
</script>
</body>
</html>