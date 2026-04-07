<?php
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Under Maintenance | MediQueue</title>
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
  <div class="card-glass" style="max-width:520px;padding:48px;text-align:center;">
    <div style="font-size:4rem;color:var(--teal-core,#3D6A8A);margin-bottom:16px;"><i class="fa-solid fa-wrench"></i></div>
    <h2 style="margin:0 0 8px;font-size:1.5rem;">We'll Be Right Back</h2>
    <p class="text-muted" style="margin-bottom:24px;">MediQueue is currently undergoing scheduled maintenance to improve your experience. We apologize for the inconvenience.</p>
    <div class="card-glass" style="padding:16px;margin-bottom:24px;background:rgba(61,106,138,.06);">
      <p style="margin:0;font-size:.88rem;"><i class="fa-solid fa-info-circle" style="color:var(--teal-core);"></i> <strong>Expected downtime:</strong> Less than 1 hour</p>
    </div>
    <p class="text-muted" style="font-size:.85rem;">If you need immediate assistance, please contact us at <strong>admin@mediqueue.com</strong></p>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
</body>
</html>
