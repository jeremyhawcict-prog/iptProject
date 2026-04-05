<?php
$pageTitle = 'Page Not Found';
$pageCSS = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:6rem;font-weight:800;font-family:'Inter',sans-serif;color:var(--color-primary,#3D6A8A);opacity:.3;">404</div>
  <h2 style="margin:16px 0 8px;">Page Not Found</h2>
  <p class="text-muted" style="margin-bottom:32px;">The page you're looking for doesn't exist or has been moved.</p>
  <a href="<?= BASE_URL ?>/pages/index.php" class="btn-g btn-g-primary"><i class="fa-solid fa-house"></i> Go Home</a>
</div>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>