<?php
$pageTitle = 'Home';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . getRedirectByRole());
    exit;
}

$pdo = getDB();
$totalDoctors  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='doctor' AND is_active=1")->fetchColumn();
$totalPatients = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='patient' AND is_active=1")->fetchColumn();
$totalAppts    = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

$doctors = $pdo->query(
    "SELECT u.id, u.full_name, u.profile_photo, dp.specialization,
            COALESCE(AVG(f.rating),0) AS avg_rating, COUNT(f.id) AS review_count
     FROM users u
     JOIN doctor_profiles dp ON dp.user_id = u.id
     LEFT JOIN appointments a ON a.doctor_id = u.id AND a.status='completed'
     LEFT JOIN feedback f ON f.appointment_id = a.id
     WHERE u.role='doctor' AND u.is_active=1
     GROUP BY u.id ORDER BY avg_rating DESC LIMIT 6"
)->fetchAll();

$testimonials = $pdo->query(
    "SELECT f.rating, f.comments, u.full_name AS patient_name
     FROM feedback f
     JOIN appointments a ON a.id = f.appointment_id
     JOIN users u ON u.id = a.patient_id
     WHERE f.rating >= 4 ORDER BY f.created_at DESC LIMIT 4"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>MediQueue — Smart Healthcare Queue System</title>
<meta name="csrf-token" content="<?= getCsrfToken() ?>" />
<meta name="base-url" content="<?= BASE_URL ?>" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== LANDING OVERRIDES ===== */
body.landing-page{background:#0f2132;color:#e8f0f6;overflow-x:hidden}

/* Animated gradient bg */
.landing-bg{position:fixed;inset:0;z-index:0;background:linear-gradient(135deg,#0f2132 0%,#163350 30%,#1a4068 50%,#163350 70%,#0f2132 100%);background-size:400% 400%;animation:gradShift 20s ease infinite}
@keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

/* Subtle grid pattern overlay */
.landing-grid-overlay{position:fixed;inset:0;z-index:0;opacity:.04;background-image:
  linear-gradient(rgba(255,255,255,.1) 1px,transparent 1px),
  linear-gradient(90deg,rgba(255,255,255,.1) 1px,transparent 1px);
  background-size:60px 60px;pointer-events:none}

/* Floating orbs */
.orb{position:fixed;border-radius:50%;filter:blur(80px);opacity:.15;pointer-events:none;z-index:0}
.orb-1{width:600px;height:600px;background:#3D6A8A;top:-200px;left:-150px;animation:orbFloat1 18s ease-in-out infinite}
.orb-2{width:500px;height:500px;background:#5BA3C9;bottom:-150px;right:-100px;animation:orbFloat2 22s ease-in-out infinite}
.orb-3{width:350px;height:350px;background:#10B981;top:40%;right:10%;animation:orbFloat3 15s ease-in-out infinite}
@keyframes orbFloat1{0%,100%{transform:translate(0,0)}50%{transform:translate(80px,60px)}}
@keyframes orbFloat2{0%,100%{transform:translate(0,0)}50%{transform:translate(-60px,-80px)}}
@keyframes orbFloat3{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-40px,50px) scale(1.1)}}

/* ===== NAV ===== */
.lnav{position:fixed;top:0;left:0;right:0;z-index:1000;padding:16px 40px;display:flex;align-items:center;justify-content:space-between;transition:all .35s ease;background:transparent}
.lnav.stuck{background:rgba(15,33,50,.92);backdrop-filter:blur(20px);box-shadow:0 4px 30px rgba(0,0,0,.3);padding:10px 40px}
.lnav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff}
.lnav-brand .brand-box{width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);display:grid;place-items:center;font-size:.85rem}
.lnav-brand span{font-family:'Inter',sans-serif;font-weight:800;font-size:1.25rem;letter-spacing:-.3px}
.lnav-links{display:flex;align-items:center;gap:6px}
.lnav-links a{color:rgba(232,240,246,.7);text-decoration:none;font-size:.85rem;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
.lnav-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
.lnav-links .cta-btn{background:linear-gradient(135deg,#5BA3C9,#3D6A8A);color:#fff!important;font-weight:600;padding:9px 22px;box-shadow:0 4px 16px rgba(61,106,138,.4)}
.lnav-links .cta-btn:hover{box-shadow:0 6px 24px rgba(61,106,138,.6);transform:translateY(-1px)}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px}
.hamburger span{display:block;width:20px;height:2px;background:#fff;margin:4px 0;border-radius:2px;transition:.3s}

/* ===== HERO ===== */
.hero-section{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:140px 40px 80px;display:grid;grid-template-columns:1.1fr 1fr;gap:60px;align-items:center;min-height:100vh}
.hero-content{}
.hero-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(91,163,201,.15);border:1px solid rgba(91,163,201,.25);border-radius:50px;padding:6px 16px 6px 10px;font-size:.78rem;font-weight:600;color:#7DD3FC;margin-bottom:28px}
.hero-pill .dot{width:8px;height:8px;border-radius:50%;background:#4ADE80;box-shadow:0 0 8px rgba(74,222,128,.5);animation:blink 2s ease-in-out infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
.hero-content h1{font-family:'Inter',sans-serif;font-size:3.5rem;font-weight:900;line-height:1.08;color:#fff;letter-spacing:-1.5px;margin-bottom:22px}
.hero-content h1 em{font-style:normal;background:linear-gradient(135deg,#7DD3FC,#5BA3C9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-content .lead{font-size:1.1rem;line-height:1.75;color:#a3c4d9;max-width:480px;margin-bottom:36px}
.hero-actions{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:44px}
.btn-hero{display:inline-flex;align-items:center;gap:9px;padding:14px 30px;border-radius:12px;font-family:'Inter',sans-serif;font-weight:700;font-size:.92rem;text-decoration:none;border:none;cursor:pointer;transition:all .3s cubic-bezier(.22,1,.36,1)}
.btn-fill{background:linear-gradient(135deg,#5BA3C9,#3D6A8A);color:#fff;box-shadow:0 6px 24px rgba(61,106,138,.4)}
.btn-fill:hover{transform:translateY(-2px);box-shadow:0 10px 36px rgba(61,106,138,.55)}
.btn-ghost{background:rgba(255,255,255,.06);color:#c5dce9;border:1.5px solid rgba(255,255,255,.15)}
.btn-ghost:hover{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.3)}
/* Social proof */
.social-proof{display:flex;align-items:center;gap:14px}
.sp-faces{display:flex}
.sp-faces span{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-size:.65rem;font-weight:700;color:#fff;margin-left:-8px;border:2px solid #0f2132}
.sp-faces span:first-child{margin-left:0}
.sp-text{font-size:.82rem;color:#7c9fb5;line-height:1.35}
.sp-text strong{color:#c5dce9;display:block}

/* Hero visual */
.hero-visual{position:relative;display:flex;justify-content:center;align-items:center}
.dashboard-mock{position:relative;width:100%;max-width:500px}
.mock-window{background:rgba(255,255,255,.07);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:0;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.3)}
.mock-titlebar{display:flex;align-items:center;gap:6px;padding:12px 16px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.06)}
.mock-dot{width:10px;height:10px;border-radius:50%}
.mock-body{padding:20px}
.mock-row{display:flex;gap:12px;margin-bottom:14px}
.mock-card-sm{flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:16px 14px;text-align:center}
.mock-card-sm .mc-icon{font-size:1.2rem;margin-bottom:6px}
.mock-card-sm .mc-val{font-size:1.4rem;font-weight:800;color:#fff}
.mock-card-sm .mc-label{font-size:.68rem;color:#7c9fb5;margin-top:2px}
.mock-chart{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:16px;height:120px;display:flex;align-items:flex-end;gap:8px}
.chart-bar{flex:1;border-radius:4px 4px 0 0;transition:height .6s ease}
/* Floating badges */
.float-badge{position:absolute;background:rgba(15,33,50,.85);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.3)}
.float-badge.fb-1{top:15%;right:-30px;animation:fbFloat 5s ease-in-out infinite}
.float-badge.fb-2{bottom:20%;left:-25px;animation:fbFloat 6s ease-in-out infinite 1s}
@keyframes fbFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.fb-icon{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;font-size:.9rem;color:#fff}
.fb-text .fb-val{font-size:1rem;font-weight:800;color:#fff}
.fb-text .fb-lbl{font-size:.68rem;color:#7c9fb5}

/* ===== STATS BAR ===== */
.stats-bar{position:relative;z-index:1;max-width:1200px;margin:-40px auto 0;padding:0 40px}
.stats-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:rgba(255,255,255,.06);border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 8px 32px rgba(0,0,0,.2)}
.stat-block{padding:28px 20px;text-align:center;background:rgba(15,33,50,.6);backdrop-filter:blur(12px);transition:background .3s}
.stat-block:hover{background:rgba(91,163,201,.08)}
.stat-block .sb-icon{font-size:1.1rem;margin-bottom:10px;display:block}
.stat-block .sb-num{font-family:'Inter',sans-serif;font-size:2rem;font-weight:900;color:#fff;letter-spacing:-1px}
.stat-block .sb-label{font-size:.78rem;color:#7c9fb5;margin-top:4px}

/* ===== SECTIONS COMMON ===== */
.lsection{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:100px 40px}
.sec-header{text-align:center;margin-bottom:52px}
.sec-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(91,163,201,.12);border:1px solid rgba(91,163,201,.2);border-radius:50px;padding:5px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#7DD3FC;margin-bottom:14px}
.sec-header h2{font-family:'Inter',sans-serif;font-size:2.1rem;font-weight:900;color:#fff;letter-spacing:-.6px;margin-bottom:12px}
.sec-header p{color:#8badc4;max-width:480px;margin:0 auto;font-size:.95rem;line-height:1.7}

/* ===== FEATURES ===== */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.feat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:32px 26px;position:relative;overflow:hidden;transition:all .35s cubic-bezier(.22,1,.36,1)}
.feat::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent,#5BA3C9);transform:scaleX(0);transform-origin:left;transition:transform .35s ease}
.feat:hover{background:rgba(255,255,255,.08);transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.15);border-color:rgba(255,255,255,.12)}
.feat:hover::after{transform:scaleX(1)}
.feat-ic{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;font-size:1.15rem;color:#fff;margin-bottom:18px}
.feat h3{font-size:1rem;font-weight:700;color:#e8f0f6;margin-bottom:8px}
.feat p{font-size:.85rem;color:#8badc4;line-height:1.65;margin:0}

/* ===== HOW IT WORKS ===== */
.steps-row{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;position:relative}
.steps-row::before{content:'';position:absolute;top:40px;left:16%;right:16%;height:2px;background:linear-gradient(90deg,transparent,rgba(91,163,201,.2),rgba(91,163,201,.2),transparent)}
.step-item{text-align:center;position:relative}
.step-circle{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);display:grid;place-items:center;margin:0 auto 18px;font-family:'Inter',sans-serif;font-weight:900;font-size:1.15rem;color:#fff;box-shadow:0 4px 20px rgba(61,106,138,.35);position:relative}
.step-circle::before{content:'';position:absolute;inset:-5px;border-radius:50%;border:2px solid rgba(91,163,201,.2)}
.step-item h3{font-size:1rem;font-weight:700;color:#e8f0f6;margin-bottom:8px}
.step-item p{font-size:.84rem;color:#8badc4;line-height:1.6;max-width:280px;margin:0 auto}

/* ===== DOCTORS ===== */
.doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px}
.doc{display:flex;align-items:center;gap:16px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;transition:all .3s ease}
.doc:hover{background:rgba(255,255,255,.09);transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,.15)}
.doc-photo{width:62px;height:62px;border-radius:14px;object-fit:cover;border:2px solid rgba(255,255,255,.1);background:#163350;flex-shrink:0}
.doc-meta h4{font-size:.95rem;font-weight:700;color:#e8f0f6;margin:0 0 3px}
.doc-meta .dspec{font-size:.78rem;color:#7c9fb5;margin-bottom:6px}
.doc-meta .dstars{color:#FBBF24;font-size:.8rem}
.doc-meta .dstars .dim{color:rgba(255,255,255,.15)}

/* ===== TESTIMONIALS ===== */
.test-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
.tcard{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:28px 24px;transition:all .3s ease}
.tcard:hover{background:rgba(255,255,255,.09);transform:translateY(-3px)}
.tcard .tq{font-size:1.6rem;color:rgba(91,163,201,.25);margin-bottom:10px;line-height:1}
.tcard .tstars{color:#FBBF24;font-size:.82rem;margin-bottom:12px}
.tcard blockquote{font-size:.87rem;color:#a3c4d9;line-height:1.7;font-style:italic;margin:0 0 16px;border:none;padding:0}
.tcard cite{font-style:normal;font-weight:700;color:#e8f0f6;font-size:.84rem;display:flex;align-items:center;gap:8px}
.tcard cite::before{content:'';width:16px;height:2px;background:rgba(91,163,201,.4);border-radius:2px}

/* ===== CTA ===== */
.cta-band{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 40px 60px}
.cta-inner{background:linear-gradient(135deg,rgba(61,106,138,.2),rgba(91,163,201,.1));border:1px solid rgba(91,163,201,.15);border-radius:24px;padding:64px 40px;text-align:center;position:relative;overflow:hidden}
.cta-inner::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 50% 50%,rgba(91,163,201,.06),transparent 60%);pointer-events:none}
.cta-inner h2{font-family:'Inter',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;margin-bottom:12px;letter-spacing:-.6px;position:relative}
.cta-inner>p{color:#8badc4;font-size:1rem;max-width:440px;margin:0 auto 32px;line-height:1.7;position:relative}

/* ===== FOOTER ===== */
.lfooter{position:relative;z-index:1;text-align:center;padding:28px 40px;color:#4a6e84;font-size:.78rem;border-top:1px solid rgba(255,255,255,.05)}

/* ===== SCROLL REVEAL ===== */
.sr{opacity:0;transform:translateY(28px);transition:all .7s cubic-bezier(.22,1,.36,1)}
.sr.vis{opacity:1;transform:translateY(0)}
.sr-d1{transition-delay:.08s}.sr-d2{transition-delay:.16s}.sr-d3{transition-delay:.24s}
.sr-d4{transition-delay:.32s}.sr-d5{transition-delay:.4s}

/* ===== RESPONSIVE ===== */
@media(max-width:1024px){.feat-grid{grid-template-columns:repeat(2,1fr)}.stats-inner{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
  .hero-section{grid-template-columns:1fr;text-align:center;padding:120px 24px 60px;gap:40px;min-height:auto}
  .hero-content h1{font-size:2.2rem}.hero-content .lead{margin:0 auto 32px}
  .hero-actions{justify-content:center}.social-proof{justify-content:center}
  .hero-visual{order:-1}
  .dashboard-mock{max-width:340px}.float-badge{display:none}
  .stats-inner{grid-template-columns:1fr 1fr}.stats-bar{margin-top:-20px;padding:0 20px}
  .feat-grid{grid-template-columns:1fr}.steps-row{grid-template-columns:1fr;gap:32px}
  .steps-row::before{display:none}
  .lnav{padding:12px 20px}.lnav.stuck{padding:8px 20px}
  .lnav-links{display:none;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:rgba(15,33,50,.97);backdrop-filter:blur(20px);padding:20px;gap:6px;border-bottom:1px solid rgba(255,255,255,.06)}
  .lnav-links.open{display:flex}
  .hamburger{display:block}
  .lsection{padding:60px 24px}.cta-band{padding:0 20px 40px}
  .cta-inner{padding:44px 24px}.cta-inner h2{font-size:1.6rem}
}
@media(max-width:480px){.hero-content h1{font-size:1.7rem}.stat-block .sb-num{font-size:1.5rem}}
</style>
</head>
<body class="landing-page">

<div class="landing-bg"></div>
<div class="landing-grid-overlay"></div>
<div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>

<!-- NAV -->
<nav class="lnav" id="lnav">
  <a href="<?= BASE_URL ?>" class="lnav-brand">
    <span class="brand-box"><i class="fa-solid fa-plus"></i></span>
    <span>MediQueue</span>
  </a>
  <button class="hamburger" id="hbBtn" aria-label="Menu"><span></span><span></span><span></span></button>
  <div class="lnav-links" id="navLinks">
    <a href="#features">Features</a>
    <a href="#how-it-works">How It Works</a>
    <a href="#doctors">Doctors</a>
    <a href="<?= BASE_URL ?>/pages/about.php">About</a>
    <a href="<?= BASE_URL ?>/pages/find-doctor.php">Find a Doctor</a>
    <a href="<?= BASE_URL ?>/pages/login.php" class="cta-btn"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero-section">
  <div class="hero-content">
    <div class="hero-pill"><span class="dot"></span> Serving clinics across the Philippines</div>
    <h1>Skip the Wait,<br><em>Book Smarter.</em></h1>
    <p class="lead">MediQueue helps patients book appointments, helps doctors manage schedules, and helps clinics run smoother — no more long queues.</p>
    <div class="hero-actions">
      <a href="<?= BASE_URL ?>/pages/register.php" class="btn-hero btn-fill"><i class="fa-solid fa-arrow-right"></i> Get Started Free</a>
      <a href="#how-it-works" class="btn-hero btn-ghost"><i class="fa-solid fa-play"></i> See How It Works</a>
    </div>
    <div class="social-proof">
      <div class="sp-faces">
        <span style="background:#3D6A8A">J</span>
        <span style="background:#10B981">M</span>
        <span style="background:#F59E0B">A</span>
        <span style="background:#8B5CF6">R</span>
      </div>
      <div class="sp-text"><strong><?= number_format($totalPatients) ?>+ patients joined</strong>Trusted by clinics &amp; patients</div>
    </div>
  </div>

  <div class="hero-visual">
    <div class="dashboard-mock">
      <div class="mock-window">
        <div class="mock-titlebar">
          <span class="mock-dot" style="background:#EF4444"></span>
          <span class="mock-dot" style="background:#F59E0B"></span>
          <span class="mock-dot" style="background:#22C55E"></span>
        </div>
        <div class="mock-body">
          <div class="mock-row">
            <div class="mock-card-sm"><div class="mc-icon" style="color:#5BA3C9"><i class="fa-solid fa-calendar-check"></i></div><div class="mc-val"><?= $totalAppts ?></div><div class="mc-label">Appointments</div></div>
            <div class="mock-card-sm"><div class="mc-icon" style="color:#10B981"><i class="fa-solid fa-user-doctor"></i></div><div class="mc-val"><?= $totalDoctors ?></div><div class="mc-label">Doctors</div></div>
            <div class="mock-card-sm"><div class="mc-icon" style="color:#F59E0B"><i class="fa-solid fa-users"></i></div><div class="mc-val"><?= $totalPatients ?></div><div class="mc-label">Patients</div></div>
          </div>
          <div class="mock-chart" id="mockChart"></div>
        </div>
      </div>
      <!-- floating badges -->
      <div class="float-badge fb-1">
        <div class="fb-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-check"></i></div>
        <div class="fb-text"><div class="fb-val">98%</div><div class="fb-lbl">Satisfaction</div></div>
      </div>
      <div class="float-badge fb-2">
        <div class="fb-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-bolt"></i></div>
        <div class="fb-text"><div class="fb-val">8 min</div><div class="fb-lbl">Avg wait time</div></div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-block sr"><span class="sb-icon" style="color:#5BA3C9"><i class="fa-solid fa-user-doctor"></i></span><div class="sb-num" data-target="<?= $totalDoctors ?>">0</div><div class="sb-label">Licensed Doctors</div></div>
    <div class="stat-block sr sr-d1"><span class="sb-icon" style="color:#10B981"><i class="fa-solid fa-heart-pulse"></i></span><div class="sb-num" data-target="<?= $totalPatients ?>">0</div><div class="sb-label">Happy Patients</div></div>
    <div class="stat-block sr sr-d2"><span class="sb-icon" style="color:#F59E0B"><i class="fa-solid fa-calendar-check"></i></span><div class="sb-num" data-target="<?= $totalAppts ?>">0</div><div class="sb-label">Appointments Booked</div></div>
    <div class="stat-block sr sr-d3"><span class="sb-icon" style="color:#A78BFA"><i class="fa-solid fa-face-smile"></i></span><div class="sb-num" data-target="98">0</div><div class="sb-label">% Satisfaction Rate</div></div>
  </div>
</div>

<!-- FEATURES -->
<section id="features" class="lsection">
  <div class="sec-header"><div class="sec-tag"><i class="fa-solid fa-sparkles"></i> Features</div><h2>Everything Your Clinic Needs</h2><p>Powerful tools to cut wait times, boost patient satisfaction, and streamline operations.</p></div>
  <div class="feat-grid">
    <div class="feat sr" style="--accent:#5BA3C9"><div class="feat-ic" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)"><i class="fa-solid fa-calendar-check"></i></div><h3>Smart Booking</h3><p>3-step appointment wizard with real-time slot availability, auto-confirmation, and calendar sync.</p></div>
    <div class="feat sr sr-d1" style="--accent:#10B981"><div class="feat-ic" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-user-doctor"></i></div><h3>Doctor Profiles</h3><p>Browse by specialization, compare ratings, read reviews, and pick the perfect doctor for your needs.</p></div>
    <div class="feat sr sr-d2" style="--accent:#8B5CF6"><div class="feat-ic" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-chart-line"></i></div><h3>Analytics Dashboard</h3><p>Real-time KPIs, trend charts, performance metrics, and exportable reports at your fingertips.</p></div>
    <div class="feat sr sr-d3" style="--accent:#F59E0B"><div class="feat-ic" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-bell"></i></div><h3>Smart Notifications</h3><p>Email confirmations, reminders, status updates, and in-app alerts — never miss an appointment.</p></div>
    <div class="feat sr sr-d4" style="--accent:#EF4444"><div class="feat-ic" style="background:linear-gradient(135deg,#EF4444,#DC2626)"><i class="fa-solid fa-file-medical"></i></div><h3>Patient Records</h3><p>Digitized records with diagnosis, prescriptions, and visit history. Access them securely anytime.</p></div>
    <div class="feat sr sr-d5" style="--accent:#06B6D4"><div class="feat-ic" style="background:linear-gradient(135deg,#06B6D4,#0891B2)"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure by Design</h3><p>CSRF protection, rate limiting, bcrypt hashing, and role-based access control from day one.</p></div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how-it-works" class="lsection">
  <div class="sec-header"><div class="sec-tag"><i class="fa-solid fa-route"></i> How It Works</div><h2>Book in 3 Easy Steps</h2><p>From sign-up to seeing your doctor — less than 2 minutes.</p></div>
  <div class="steps-row">
    <div class="step-item sr"><div class="step-circle">1</div><h3>Create Account</h3><p>Quick free sign-up with just your name, email, and password.</p></div>
    <div class="step-item sr sr-d1"><div class="step-circle">2</div><h3>Pick Doctor &amp; Time</h3><p>Browse specialists, check availability, and select the perfect slot.</p></div>
    <div class="step-item sr sr-d2"><div class="step-circle">3</div><h3>Confirm &amp; Visit</h3><p>Get instant email confirmation. Show up at your time — skip the queue.</p></div>
  </div>
</section>

<!-- DOCTORS -->
<section id="doctors" class="lsection">
  <div class="sec-header"><div class="sec-tag"><i class="fa-solid fa-stethoscope"></i> Medical Team</div><h2>Our Top-Rated Doctors</h2><p>Trusted professionals with proven track records and patient reviews.</p></div>
  <div class="doc-grid">
    <?php foreach ($doctors as $doc): ?>
    <div class="doc sr">
      <img class="doc-photo" src="<?= BASE_URL ?>/assets/uploads/photos/<?= htmlspecialchars($doc['profile_photo'] ?? 'default.svg') ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <div class="doc-meta">
        <h4>Dr. <?= htmlspecialchars($doc['full_name']) ?></h4>
        <div class="dspec"><?= htmlspecialchars($doc['specialization'] ?? 'General') ?></div>
        <div class="dstars"><?php $r=round($doc['avg_rating'],1); for($i=1;$i<=5;$i++) echo $i<=$r?'<i class="fa-solid fa-star"></i>':'<i class="fa-solid fa-star dim"></i>'; ?> <span style="color:#5a7d94;font-size:.72rem">(<?= (int)$doc['review_count'] ?>)</span></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($doctors)): ?><p style="color:#7c9fb5;text-align:center;grid-column:1/-1">Our doctors will appear here soon.</p><?php endif; ?>
  </div>
</section>

<!-- TESTIMONIALS -->
<?php if (!empty($testimonials)): ?>
<section class="lsection">
  <div class="sec-header"><div class="sec-tag"><i class="fa-solid fa-quote-left"></i> Testimonials</div><h2>What Patients Say</h2><p>Real feedback from patients who use MediQueue every day.</p></div>
  <div class="test-grid">
    <?php foreach ($testimonials as $idx => $t): ?>
    <div class="tcard sr sr-d<?= $idx % 4 ?>">
      <div class="tq"><i class="fa-solid fa-quote-left"></i></div>
      <div class="tstars"><?php for($i=1;$i<=5;$i++) echo '<i class="fa-solid fa-star'.($i<=$t['rating']?'':' dim').'"></i>'; ?></div>
      <blockquote>&ldquo;<?= htmlspecialchars($t['comments']) ?>&rdquo;</blockquote>
      <cite><?= htmlspecialchars($t['patient_name']) ?></cite>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<div class="cta-band">
  <div class="cta-inner">
    <h2>Ready to Skip the Queue?</h2>
    <p>Join thousands of patients and doctors already using MediQueue for faster, smarter healthcare.</p>
    <div class="hero-actions" style="justify-content:center">
      <a href="<?= BASE_URL ?>/pages/register.php" class="btn-hero btn-fill"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
      <a href="<?= BASE_URL ?>/pages/login.php" class="btn-hero btn-ghost"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
    </div>
  </div>
</div>

<footer class="lfooter">&copy; <?= date('Y') ?> MediQueue. All rights reserved.</footer>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
// Nav scroll
(function(){var n=document.getElementById('lnav');window.addEventListener('scroll',function(){n.classList.toggle('stuck',scrollY>60)})})();
// Mobile menu
(function(){var b=document.getElementById('hbBtn'),l=document.getElementById('navLinks');if(b&&l)b.addEventListener('click',function(){l.classList.toggle('open')})})();
// Count up
document.querySelectorAll('.sb-num[data-target]').forEach(function(el){
  var t=parseInt(el.dataset.target,10),s=null,dur=2200;
  function ease(x){return 1-Math.pow(1-x,3)}
  function anim(ts){if(!s)s=ts;var p=Math.min((ts-s)/dur,1);el.textContent=Math.floor(ease(p)*t);if(p<1)requestAnimationFrame(anim);else el.textContent=t.toLocaleString()}
  new IntersectionObserver(function(e,o){if(e[0].isIntersecting){requestAnimationFrame(anim);o.disconnect()}}).observe(el);
});
// Scroll reveal
(function(){var els=document.querySelectorAll('.sr');new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');}})} ,{threshold:.1,rootMargin:'0px 0px -30px 0px'}).observe.bind(null);els.forEach(function(el){new IntersectionObserver(function(es,o){if(es[0].isIntersecting){es[0].target.classList.add('vis');o.unobserve(es[0].target)}},{threshold:.1,rootMargin:'0px 0px -30px 0px'}).observe(el)})})();
// Mock chart bars
(function(){var c=document.getElementById('mockChart');if(!c)return;var heights=[45,65,40,80,55,70,90,60,75,50,85,65];heights.forEach(function(h){var bar=document.createElement('div');bar.className='chart-bar';bar.style.background='linear-gradient(to top,#3D6A8A,#5BA3C9)';bar.style.height='0';c.appendChild(bar);setTimeout(function(){bar.style.height=h+'%'},300)})})();
</script>
</body>
</html>