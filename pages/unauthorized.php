<?php
$pageTitle = 'Unauthorized';
$pageCSS = ['dashboard.css'];
require_once __DIR__ . '/../includes/header.php';
?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:5rem;color:var(--color-danger,#EF4444);opacity:.3;"><i class="fa-solid fa-shield-halved"></i></div>
  <h2 style="margin:16px 0 8px;">Access Denied</h2>
  <p class="text-muted" style="margin-bottom:32px;">You don't have permission to view this page.</p>
  <?php if (isLoggedIn()): ?>
  <a href="<?= getRedirectByRole() ?>" class="btn-g btn-g-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/pages/login.php" class="btn-g btn-g-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  <?php endif; ?>
</div>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>