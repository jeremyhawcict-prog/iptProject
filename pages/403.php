<?php
$pageTitle = 'Forbidden';
$pageCSS = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:6rem;font-weight:800;font-family:'Inter',sans-serif;color:var(--color-danger,#EF4444);opacity:.3;">403</div>
  <h2 style="margin:16px 0 8px;">Access Forbidden</h2>
  <p class="text-muted" style="margin-bottom:32px;">You don't have permission to access this page. If you believe this is an error, please contact the clinic administrator.</p>
  <?php if (isLoggedIn()): ?>
  <a href="<?= getRedirectByRole() ?>" class="btn-g btn-g-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/pages/login.php" class="btn-g btn-g-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  <?php endif; ?>
</div>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
