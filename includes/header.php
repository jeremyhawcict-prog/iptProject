<?php
/**
 * MediQueue — Reusable Header / Navigation
 * Sidebar layout for logged-in users, horizontal navbar for guests.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Defaults
$pageTitle = $pageTitle ?? 'MediQueue';
$pageCSS   = $pageCSS   ?? [];
$user      = isLoggedIn() ? getCurrentUser() : null;
$role      = $_SESSION['user_role'] ?? '';

// Build user initials for the avatar circle
$userInitials = '';
if ($user) {
    $nameParts    = explode(' ', $user['full_name']);
    $userInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $userInitials .= strtoupper(substr(end($nameParts), 0, 1));
    }
}

// Flash message
$flashMessage = null;
$flashType    = 'info';
if (isset($_SESSION['flash'])) {
    $flashMessage = $_SESSION['flash']['message'] ?? '';
    $flashType    = $_SESSION['flash']['type'] ?? 'info';
    unset($_SESSION['flash']);
}

// Determine current page for sidebar active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Sidebar nav items per role
$sidebarItems = [];
if (isLoggedIn()) {
    if (hasRole('patient')) {
        $sidebarItems = [
            ['label' => 'Overview',     'icon' => 'fa-solid fa-gauge-high',    'href' => BASE_URL . '/pages/patient/dashboard.php',       'page' => 'dashboard.php'],
            ['label' => 'Book Appointment', 'icon' => 'fa-solid fa-calendar-plus', 'href' => BASE_URL . '/pages/patient/book-appointment.php', 'page' => 'book-appointment.php'],
            ['label' => 'My Appointments',  'icon' => 'fa-solid fa-calendar-check','href' => BASE_URL . '/pages/patient/my-appointments.php',  'page' => 'my-appointments.php'],
            ['label' => 'My Records',       'icon' => 'fa-solid fa-file-medical',  'href' => BASE_URL . '/pages/patient/my-records.php',       'page' => 'my-records.php'],
            ['label' => 'Notifications',    'icon' => 'fa-solid fa-bell',          'href' => BASE_URL . '/pages/patient/notifications.php',    'page' => 'notifications.php'],
            ['divider' => true],
            ['label' => 'My Profile',   'icon' => 'fa-solid fa-user',          'href' => BASE_URL . '/pages/patient/profile.php',         'page' => 'profile.php'],
        ];
    } elseif (hasRole('doctor')) {
        $sidebarItems = [
            ['label' => 'Overview',     'icon' => 'fa-solid fa-gauge-high',    'href' => BASE_URL . '/pages/doctor/dashboard.php',  'page' => 'dashboard.php'],
            ['label' => 'My Schedule',  'icon' => 'fa-solid fa-clock',         'href' => BASE_URL . '/pages/doctor/schedule.php',   'page' => 'schedule.php'],
            ['label' => 'Appointments', 'icon' => 'fa-solid fa-calendar-day',  'href' => BASE_URL . '/pages/doctor/appointments.php','page' => 'appointments.php'],
            ['label' => 'My Patients',  'icon' => 'fa-solid fa-users',         'href' => BASE_URL . '/pages/doctor/patients.php',   'page' => 'patients.php'],
            ['divider' => true],
            ['label' => 'My Profile',   'icon' => 'fa-solid fa-user-doctor',   'href' => BASE_URL . '/pages/doctor/profile.php',    'page' => 'profile.php'],
        ];
    } elseif (hasRole('admin') || hasRole('staff')) {
        $sidebarItems = [
            ['label' => 'Overview',     'icon' => 'fa-solid fa-gauge-high',    'href' => BASE_URL . '/pages/admin/dashboard.php',   'page' => 'dashboard.php'],
            ['label' => 'Users',        'icon' => 'fa-solid fa-user-gear',     'href' => BASE_URL . '/pages/admin/users.php',       'page' => 'users.php'],
            ['label' => 'Doctors',      'icon' => 'fa-solid fa-user-doctor',   'href' => BASE_URL . '/pages/admin/doctors.php',     'page' => 'doctors.php'],
            ['label' => 'Appointments', 'icon' => 'fa-solid fa-calendar',      'href' => BASE_URL . '/pages/admin/appointments.php','page' => 'appointments.php'],
            ['label' => 'Reports',      'icon' => 'fa-solid fa-chart-pie',     'href' => BASE_URL . '/pages/admin/reports.php',     'page' => 'reports.php'],
            ['label' => 'Audit Logs',   'icon' => 'fa-solid fa-clipboard-list','href' => BASE_URL . '/pages/admin/audit-logs.php',  'page' => 'audit-logs.php'],
            ['label' => 'Settings',     'icon' => 'fa-solid fa-gear',          'href' => BASE_URL . '/pages/admin/settings.php',    'page' => 'settings.php'],
            ['divider' => true],
            ['label' => 'My Profile',   'icon' => 'fa-solid fa-user',          'href' => BASE_URL . '/pages/admin/profile.php',     'page' => 'profile.php'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= getCsrfToken() ?>" />
  <meta name="base-url" content="<?= BASE_URL ?>" />
  <meta name="description" content="MediQueue — Modern Clinic Appointment & Booking System" />
  <title><?= htmlspecialchars($pageTitle) ?> | MediQueue</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Font Awesome 6 CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Global CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />

  <!-- Page-specific CSS -->
  <?php foreach ($pageCSS as $css): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($css) ?>" />
  <?php endforeach; ?>

  <!-- Core JS (loaded early so inline page scripts can use utils) -->
  <script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
</head>
<body>

<!-- Background Layers (Premium animated) -->
<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>
<div class="bg-blob blob-3"></div>
<div class="bg-blob blob-4"></div>
<div class="bg-blob blob-5"></div>

<!-- ========== TOP NAVIGATION BAR ========== -->
<nav class="navbar" id="main-navbar">
    <div class="nav-container">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>/pages/index.php" class="nav-logo" id="nav-logo">
            <span class="logo-icon"><i class="fa-solid fa-plus"></i></span>
            <span class="logo-text">Medi<span class="logo-highlight">Queue</span></span>
        </a>

        <?php if (!isLoggedIn()): ?>
        <!-- Hamburger Toggle (mobile, guest only) -->
        <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        <?php endif; ?>

        <!-- Navigation Links (guest) / User controls (logged in) -->
        <div class="nav-menu" id="nav-menu">
            <?php if (!isLoggedIn()): ?>
            <ul class="nav-links">
                <li><a href="<?= BASE_URL ?>/pages/index.php" class="nav-link"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="<?= BASE_URL ?>/pages/about.php" class="nav-link"><i class="fa-solid fa-circle-info"></i> About</a></li>
            </ul>
            <?php endif; ?>

            <!-- Right side: user menu or auth buttons -->
            <div class="nav-right">
                <?php if (isLoggedIn() && $user): ?>
                    <!-- Dark Mode Toggle -->
                    <button class="header-icon-btn" id="dark-mode-toggle" aria-label="Toggle dark mode" data-tooltip="Dark mode">
                        <i class="fa-solid fa-moon icon-moon"></i>
                        <i class="fa-solid fa-sun icon-sun"></i>
                    </button>

                    <!-- Notification Bell -->
                    <div class="notification-wrapper" id="notification-wrapper" style="position:relative;">
                        <?php if (hasRole('patient')): ?>
                        <a href="<?= BASE_URL ?>/pages/patient/notifications.php" class="header-icon-btn" id="notification-bell" aria-label="Notifications" style="text-decoration:none;">
                            <i class="fa-solid fa-bell"></i>
                            <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                        </a>
                        <?php else: ?>
                        <button class="header-icon-btn" id="notification-bell" aria-label="Notifications">
                            <i class="fa-solid fa-bell"></i>
                            <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Logged-in user dropdown -->
                    <div class="user-dropdown" id="user-dropdown">
                        <button class="user-dropdown-trigger" id="user-dropdown-trigger" aria-expanded="false">
                            <?php if ($user['profile_photo'] && $user['profile_photo'] !== 'default.svg'): ?>
                            <img src="<?= profilePhotoUrl($user['profile_photo']) ?>" class="user-avatar-img" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display=''" />
                            <span class="user-avatar" style="display:none;"><?= $userInitials ?></span>
                            <?php else: ?>
                            <span class="user-avatar"><?= $userInitials ?></span>
                            <?php endif; ?>
                            <span class="user-name"><?= htmlspecialchars($user['full_name']) ?></span>
                            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="user-dropdown-menu" id="user-dropdown-menu">
                            <div class="dropdown-header">
                                <span class="dropdown-role"><?= ucfirst($role) ?></span>
                                <span class="dropdown-email"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <?php
                                $profilePage = 'patient/profile.php';
                                if (hasRole('doctor'))                       $profilePage = 'doctor/profile.php';
                                if (hasRole('admin') || hasRole('staff'))    $profilePage = 'admin/profile.php';
                            ?>
                            <a href="<?= BASE_URL ?>/pages/<?= $profilePage ?>" class="dropdown-item">
                                <i class="fa-solid fa-user"></i> My Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>/api/auth/logout.php" class="dropdown-item dropdown-item-danger">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Dark Mode Toggle (guest) -->
                    <button class="header-icon-btn" id="dark-mode-toggle" aria-label="Toggle dark mode" data-tooltip="Dark mode" style="margin-right:8px">
                        <i class="fa-solid fa-moon icon-moon"></i>
                        <i class="fa-solid fa-sun icon-sun"></i>
                    </button>
                    <!-- Guest buttons -->
                    <div class="nav-auth-buttons">
                        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline" id="btn-login">Login</a>
                        <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary" id="btn-register">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- /.nav-menu -->

    </div><!-- /.nav-container -->
</nav>

<!-- ========== FLASH / ALERT CONTAINER ========== -->
<div id="alert-container" class="alert-container">
    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?>" id="flash-alert">
            <span class="alert-message"><?= htmlspecialchars($flashMessage) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove();" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>
</div>

<?php if (isLoggedIn() && !empty($sidebarItems)): ?>
<!-- ========== DASHBOARD LAYOUT (Sidebar + Main) ========== -->
<div class="dashboard-wrapper">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="main-sidebar">
        <!-- Brand -->
        <a href="<?= BASE_URL ?>/pages/index.php" class="sidebar-brand">
            <div class="sidebar-logo">
                <i class="fa-solid fa-plus" style="color:#fff;font-size:16px;"></i>
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">MediQueue</span>
                <span class="sidebar-brand-sub"><?= ucfirst($role) ?> Panel</span>
            </div>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($sidebarItems as $item): ?>
                    <?php if (!empty($item['divider'])): ?>
                        <li class="sidebar-divider"></li>
                    <?php else: ?>
                        <li class="sidebar-item<?= $currentPage === $item['page'] ? ' active' : '' ?>">
                            <a href="<?= $item['href'] ?>">
                                <i class="<?= $item['icon'] ?>"></i>
                                <span><?= $item['label'] ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <li class="sidebar-divider"></li>
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/api/auth/logout.php" style="color:rgba(255,100,100,0.85);">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Sidebar Toggle Button (mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Main Content Area -->
    <div class="dashboard-main">
<?php else: ?>
<!-- ========== PUBLIC PAGE LAYOUT ========== -->
<main class="main-content">
<div class="page-content">
<?php endif; ?>

<script>
(function () {
    /* ── Mobile hamburger menu toggle (guest) ── */
    var toggle = document.getElementById('nav-toggle');
    var menu   = document.getElementById('nav-menu');
    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('nav-menu--open');
            toggle.classList.toggle('nav-toggle--active');
            toggle.setAttribute('aria-expanded', isOpen);
        });
    }

    /* ── Sidebar toggle (mobile, logged in) ── */
    var sidebarToggle  = document.getElementById('sidebar-toggle');
    var sidebar        = document.getElementById('main-sidebar');
    var sidebarOverlay = document.getElementById('sidebar-overlay');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    /* ── User dropdown toggle ── */
    var dropdownTrigger = document.getElementById('user-dropdown-trigger');
    var dropdownMenu    = document.getElementById('user-dropdown-menu');
    if (dropdownTrigger && dropdownMenu) {
        dropdownTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = dropdownMenu.classList.toggle('dropdown--open');
            dropdownTrigger.setAttribute('aria-expanded', isOpen);
        });
        document.addEventListener('click', function (e) {
            if (!dropdownTrigger.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('dropdown--open');
                dropdownTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ── Flash auto-dismiss ── */
    var flash = document.getElementById('flash-alert');
    if (flash) setTimeout(function () { flash.style.opacity = '0'; setTimeout(function () { flash.remove(); }, 400); }, 5000);

    /* ── Notification bell polling ── */
    var badge = document.getElementById('notif-badge');
    if (badge) {
        function pollNotifs() {
            var base = document.querySelector('meta[name="base-url"]');
            if (!base) return;
            fetch(base.content + '/api/notifications/count.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.success && d.data && d.data.count > 0) {
                        badge.textContent = d.data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }).catch(function () {});
        }
        pollNotifs();
        setInterval(pollNotifs, 30000);
    }
})();
</script>
