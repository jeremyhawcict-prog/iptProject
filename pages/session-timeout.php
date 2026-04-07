<?php
$pageTitle = 'Session Expired';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Session Expired | MediQueue</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />
</head>
<body>
<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="bg-blob blob-1"></div><div class="bg-blob blob-2"></div><div class="bg-blob blob-3"></div>

<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;">
  <div class="card-glass" style="max-width:480px;padding:48px;text-align:center;">
    <div style="font-size:4rem;color:var(--color-warning,#F59E0B);margin-bottom:16px;"><i class="fa-solid fa-clock"></i></div>
    <h2 style="margin:0 0 8px;font-size:1.5rem;">Session Expired</h2>
    <p class="text-muted" style="margin-bottom:8px;">Your session has timed out due to inactivity for security reasons.</p>
    <p class="text-muted" style="margin-bottom:24px;">You will be redirected to the login page in <strong id="countdown">10</strong> seconds.</p>
    <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary" style="display:inline-flex;padding:12px 28px;"><i class="fa-solid fa-right-to-bracket"></i> Sign In Now</a>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
(function(){
  var count = 10;
  var el = document.getElementById('countdown');
  var timer = setInterval(function(){
    count--;
    el.textContent = count;
    if(count <= 0){
      clearInterval(timer);
      window.location.href = '<?= BASE_URL ?>/pages/login.php';
    }
  }, 1000);
})();
</script>
</body>
</html>
