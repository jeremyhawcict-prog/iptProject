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
<title>MediQueue &mdash; Smart Healthcare Queue System</title>
<meta name="csrf-token" content="<?= getCsrfToken() ?>" />
<meta name="base-url" content="<?= BASE_URL ?>" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />
<style>
/* ---- NAV ---- */
.landing-nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:18px 48px;backdrop-filter:blur(18px);background:rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.1);transition:all .4s ease}
.landing-nav.scrolled{background:rgba(20,50,72,.85);padding:12px 48px;box-shadow:0 4px 30px rgba(0,0,0,.2)}
.landing-logo{display:flex;align-items:center;gap:12px;color:#fff;font-family:'Inter',sans-serif;font-weight:900;font-size:1.4rem;text-decoration:none;letter-spacing:-.5px}
.landing-logo .logo-dot{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
.landing-logo .logo-dot::after{content:'';position:absolute;top:-50%;left:-100%;width:60%;height:200%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);transform:skewX(-20deg);animation:logoShimmer 3s ease-in-out infinite}
@keyframes logoShimmer{0%{left:-100%}40%{left:200%}100%{left:200%}}
.landing-logo .logo-dot i{color:#fff;font-size:.9rem}
.landing-nav-links{display:flex;gap:8px;align-items:center}
.landing-nav-links a{color:rgba(255,255,255,.75);text-decoration:none;font-size:.88rem;font-weight:600;padding:8px 16px;border-radius:10px;transition:all .25s ease}
.landing-nav-links a:hover{color:#fff;background:rgba(255,255,255,.1)}
.nav-cta{background:linear-gradient(135deg,#5BA3C9,#3D6A8A)!important;color:#fff!important;padding:10px 24px!important;box-shadow:0 4px 18px rgba(61,106,138,.35)}
.nav-cta:hover{box-shadow:0 6px 28px rgba(61,106,138,.5)!important;transform:translateY(-1px)}
.nav-mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;border:none;background:none}
.nav-mobile-toggle span{display:block;width:22px;height:2px;background:#fff;border-radius:2px;transition:all .3s ease}

/* ---- HERO ---- */
.landing-section{padding:100px 48px 60px;max-width:1240px;margin:0 auto;position:relative;z-index:1}
.hero{display:flex;align-items:center;gap:80px;min-height:92vh;padding-top:80px}
.hero-text{flex:1.1}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 16px 6px 8px;border-radius:50px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.85);font-size:.78rem;font-weight:600;margin-bottom:24px;backdrop-filter:blur(8px)}
.hero-badge .badge-dot{width:8px;height:8px;border-radius:50%;background:#4ADE80;animation:pulse-dot 2s ease-in-out infinite}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}
.hero-text h1{font-family:'Inter',sans-serif;font-size:3.6rem;font-weight:900;color:#fff;line-height:1.1;margin-bottom:24px;letter-spacing:-1.5px}
.hero-text h1 .gradient-text{background:linear-gradient(135deg,#7DD3FC,#5BA3C9,#93E9BE);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-text .hero-desc{font-size:1.15rem;color:rgba(255,255,255,.72);max-width:500px;line-height:1.75;margin-bottom:36px}
.hero-btns{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:48px}
.hero-btn{display:inline-flex;align-items:center;gap:10px;padding:15px 34px;border-radius:14px;font-family:'Inter',sans-serif;font-weight:700;font-size:.95rem;border:none;cursor:pointer;text-decoration:none;transition:all .3s cubic-bezier(.22,1,.36,1)}
.hero-btn-primary{color:#fff;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);box-shadow:0 8px 30px rgba(61,106,138,.4)}
.hero-btn-primary:hover{transform:translateY(-3px);box-shadow:0 14px 40px rgba(61,106,138,.55)}
.hero-btn-secondary{color:#fff;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.2);backdrop-filter:blur(8px)}
.hero-btn-secondary:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.35)}
.hero-trust{display:flex;align-items:center;gap:16px}
.hero-trust-avatars{display:flex}
.hero-trust-avatars .avatar-circle{width:36px;height:36px;border-radius:50%;border:2px solid rgba(20,50,72,.6);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;margin-left:-10px}
.hero-trust-avatars .avatar-circle:first-child{margin-left:0}
.hero-trust-text{font-size:.82rem;color:rgba(255,255,255,.6);line-height:1.4}
.hero-trust-text strong{color:rgba(255,255,255,.9);display:block;font-size:.88rem}
.hero-visual{flex:1;display:flex;justify-content:center;align-items:center;position:relative}
.hero-card-stack{position:relative;width:420px;height:400px}
.hero-float-card{position:absolute;background:rgba(255,255,255,.1);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:24px;transition:all .4s cubic-bezier(.22,1,.36,1);animation:floatUp 6s ease-in-out infinite}
.hero-float-card:hover{transform:translateY(-6px)!important;background:rgba(255,255,255,.16)}
.hfc-main{width:340px;height:260px;top:40px;left:10px;z-index:2;animation-delay:0s}
.hfc-small-1{width:200px;top:0;right:0;z-index:3;animation-delay:1.5s}
.hfc-small-2{width:220px;bottom:0;left:30px;z-index:1;animation-delay:3s}
@keyframes floatUp{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.hfc-header{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.hfc-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff}
.hfc-icon.teal{background:linear-gradient(135deg,#3D6A8A,#2E5575)}
.hfc-icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.hfc-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.hfc-title{font-size:.88rem;font-weight:700;color:#fff}
.hfc-subtitle{font-size:.72rem;color:rgba(255,255,255,.5)}
.hfc-bar{height:8px;border-radius:4px;background:rgba(255,255,255,.1);margin-bottom:10px;overflow:hidden}
.hfc-bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#5BA3C9,#93E9BE);animation:barGrow 2s ease-out forwards}
@keyframes barGrow{0%{width:0}100%{width:var(--w,70%)}}
.hfc-rows{display:flex;flex-direction:column;gap:8px}
.hfc-row{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;background:rgba(255,255,255,.06)}
.hfc-row-dot{width:8px;height:8px;border-radius:50%}
.hfc-row-label{font-size:.76rem;color:rgba(255,255,255,.7);flex:1}
.hfc-row-val{font-size:.76rem;font-weight:700;color:#fff}
.hfc-stat-big{font-size:2.2rem;font-weight:900;color:#fff;letter-spacing:-1px}
.hfc-stat-label{font-size:.75rem;color:rgba(255,255,255,.55);margin-top:2px}
/* ---- STATS ---- */
.stats-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;padding:0 48px;max-width:1240px;margin:-30px auto 0;position:relative;z-index:2}
.stat-card{background:rgba(255,255,255,.1);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:28px 24px;text-align:center;transition:all .35s ease}
.stat-card:hover{background:rgba(255,255,255,.16);transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.12)}
.stat-card .stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.2rem;color:#fff}
.stat-number{font-family:'Inter',sans-serif;font-size:2.4rem;font-weight:900;color:#fff;letter-spacing:-1px}
.stat-label{font-size:.82rem;color:rgba(255,255,255,.6);margin-top:4px;font-weight:500}

/* ---- FEATURES ---- */
.section-heading{text-align:center;margin-bottom:56px}
.section-heading .section-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:50px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.7);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
.section-heading h2{font-family:'Inter',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;margin-bottom:14px;letter-spacing:-.8px}
.section-heading p{color:rgba(255,255,255,.6);max-width:520px;margin:0 auto;font-size:1rem;line-height:1.7}
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.feature-card{background:rgba(255,255,255,.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:36px 30px;transition:all .35s cubic-bezier(.22,1,.36,1);position:relative;overflow:hidden}
.feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--fc-color,#5BA3C9),transparent);opacity:0;transition:opacity .35s ease}
.feature-card:hover{background:rgba(255,255,255,.12);transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.1);border-color:rgba(255,255,255,.2)}
.feature-card:hover::before{opacity:1}
.feature-icon{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;margin-bottom:20px;position:relative}
.feature-card h3{font-family:'Inter',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:10px}
.feature-card p{color:rgba(255,255,255,.6);font-size:.88rem;line-height:1.65;margin:0}

/* ---- HOW IT WORKS ---- */
.steps-wrapper{position:relative;display:flex;gap:32px;justify-content:center;flex-wrap:wrap}
.steps-wrapper::before{content:'';position:absolute;top:36px;left:20%;right:20%;height:2px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),rgba(255,255,255,.15),transparent);z-index:0}
.step-card{flex:1;min-width:260px;max-width:340px;text-align:center;position:relative;z-index:1}
.step-num{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:'Inter',sans-serif;font-weight:900;font-size:1.2rem;color:#fff;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);box-shadow:0 6px 24px rgba(61,106,138,.35);position:relative}
.step-num::after{content:'';position:absolute;inset:-4px;border-radius:50%;border:2px dashed rgba(255,255,255,.15);animation:spin 12s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.step-card h3{font-family:'Inter',sans-serif;font-size:1.05rem;font-weight:700;color:#fff;margin-bottom:10px}
.step-card p{color:rgba(255,255,255,.6);font-size:.86rem;line-height:1.65}

/* ---- DOCTORS ---- */
.doctors-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
.doctor-card{background:rgba(255,255,255,.08);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:24px;display:flex;align-items:center;gap:18px;transition:all .35s cubic-bezier(.22,1,.36,1)}
.doctor-card:hover{background:rgba(255,255,255,.14);transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.1)}
.doctor-photo{width:68px;height:68px;border-radius:16px;object-fit:cover;border:2px solid rgba(255,255,255,.15);background:#1a3a52;flex-shrink:0}
.doctor-info h4{font-family:'Inter',sans-serif;font-size:1rem;font-weight:700;color:#fff;margin:0 0 4px}
.doctor-info .spec{font-size:.8rem;color:rgba(255,255,255,.5);margin-bottom:8px}
.doctor-info .stars{color:#FBBF24;font-size:.82rem}
.doctor-info .stars .dim{color:rgba(255,255,255,.2)}

/* ---- TESTIMONIALS ---- */
.testimonials-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
.testimonial{background:rgba(255,255,255,.07);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:32px 28px;position:relative;transition:all .35s ease}
.testimonial:hover{background:rgba(255,255,255,.12);transform:translateY(-4px)}
.testimonial .quote-icon{font-size:2rem;color:rgba(91,163,201,.3);margin-bottom:12px;line-height:1}
.testimonial .stars{color:#FBBF24;font-size:.85rem;margin-bottom:14px}
.testimonial blockquote{color:rgba(255,255,255,.78);font-size:.9rem;line-height:1.7;margin:0 0 18px;font-style:italic;border:none;padding:0}
.testimonial cite{font-style:normal;font-weight:700;color:#fff;font-size:.85rem;display:flex;align-items:center;gap:8px}
.testimonial cite::before{content:'';width:20px;height:2px;background:rgba(255,255,255,.3);border-radius:2px}

/* ---- CTA ---- */
.cta-section{text-align:center;padding:100px 48px;position:relative}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(61,106,138,.15) 0%,transparent 70%);pointer-events:none}
.cta-section h2{font-family:'Inter',sans-serif;font-size:2.6rem;font-weight:900;color:#fff;margin-bottom:16px;letter-spacing:-1px;position:relative}
.cta-section p{color:rgba(255,255,255,.65);font-size:1.1rem;max-width:480px;margin:0 auto 40px;line-height:1.7;position:relative}

/* ---- FOOTER ---- */
.landing-footer{text-align:center;padding:36px;color:rgba(255,255,255,.4);font-size:.82rem;border-top:1px solid rgba(255,255,255,.08)}

/* ---- RESPONSIVE ---- */
@media(max-width:1024px){.features-grid{grid-template-columns:repeat(2,1fr)}.stats-strip{grid-template-columns:repeat(2,1fr);gap:16px}}
@media(max-width:768px){
  .hero{flex-direction:column;text-align:center;gap:48px;padding-top:100px;min-height:auto}
  .hero-text h1{font-size:2.2rem}.hero-btns{justify-content:center}
  .hero-visual{order:-1}.hero-card-stack{width:300px;height:300px}
  .hfc-main{width:260px;height:200px}.hfc-small-1{width:160px}.hfc-small-2{width:170px}
  .hero-trust{justify-content:center}.hero-text .hero-desc{margin-left:auto;margin-right:auto}
  .stats-strip{grid-template-columns:1fr 1fr;margin-top:-20px;padding:0 20px;gap:12px}
  .features-grid{grid-template-columns:1fr}.steps-wrapper::before{display:none}
  .landing-nav{padding:12px 20px}.landing-section{padding:60px 20px 40px}
  .cta-section{padding:60px 20px}.cta-section h2{font-size:1.8rem}
  .landing-nav-links{display:none}.nav-mobile-toggle{display:flex}
  .landing-nav-links.mobile-open{display:flex;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:rgba(20,50,72,.95);backdrop-filter:blur(20px);padding:20px;gap:8px;border-bottom:1px solid rgba(255,255,255,.1)}
}
@media(max-width:480px){.hero-text h1{font-size:1.8rem}.stat-number{font-size:1.8rem}.hero-card-stack{width:260px;height:260px}}

/* ---- SCROLL REVEAL ---- */
.reveal{opacity:0;transform:translateY(30px);transition:all .7s cubic-bezier(.22,1,.36,1)}.reveal.visible{opacity:1;transform:translateY(0)}
.reveal-delay-1{transition-delay:.1s}.reveal-delay-2{transition-delay:.2s}.reveal-delay-3{transition-delay:.3s}
.reveal-delay-4{transition-delay:.4s}.reveal-delay-5{transition-delay:.5s}
</style>
</head>
<body>
<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="bg-blob blob-1"></div><div class="bg-blob blob-2"></div><div class="bg-blob blob-3"></div><div class="bg-blob blob-4"></div><div class="bg-blob blob-5"></div>
<!-- ===== NAV ===== -->
<nav class="landing-nav" id="landingNav">
  <a href="<?= BASE_URL ?>" class="landing-logo">
    <span class="logo-dot"><i class="fa-solid fa-plus"></i></span>
    MediQueue
  </a>
  <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <div class="landing-nav-links" id="navLinks">
    <a href="#features">Features</a>
    <a href="#how-it-works">How It Works</a>
    <a href="#doctors">Doctors</a>
    <a href="<?= BASE_URL ?>/pages/about.php">About</a>
    <a href="<?= BASE_URL ?>/pages/find-doctor.php">Find a Doctor</a>
    <a href="<?= BASE_URL ?>/pages/login.php" class="nav-cta"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  </div>
</nav>

<!-- ===== HERO ===== -->
<section class="landing-section hero">
  <div class="hero-text">
    <div class="hero-badge"><span class="badge-dot"></span> Now serving clinics across the Philippines</div>
    <h1>Skip the Wait.<br><span class="gradient-text">Book Smarter.</span></h1>
    <p class="hero-desc">The all-in-one platform that lets patients book appointments, doctors manage schedules, and clinics run smoother &mdash; no more long queues.</p>
    <div class="hero-btns">
      <a href="<?= BASE_URL ?>/pages/register.php" class="hero-btn hero-btn-primary"><i class="fa-solid fa-arrow-right"></i> Get Started Free</a>
      <a href="#how-it-works" class="hero-btn hero-btn-secondary"><i class="fa-solid fa-play"></i> See How It Works</a>
    </div>
    <div class="hero-trust">
      <div class="hero-trust-avatars">
        <span class="avatar-circle" style="background:#3D6A8A">J</span>
        <span class="avatar-circle" style="background:#10B981">M</span>
        <span class="avatar-circle" style="background:#F59E0B">A</span>
        <span class="avatar-circle" style="background:#8B5CF6">R</span>
      </div>
      <div class="hero-trust-text">
        <strong><?= number_format($totalPatients) ?>+ patients joined</strong>
        Trusted by clinics &amp; patients alike
      </div>
    </div>
  </div>

  <div class="hero-visual">
    <div class="hero-card-stack">
      <div class="hero-float-card hfc-main">
        <div class="hfc-header">
          <div class="hfc-icon teal"><i class="fa-solid fa-calendar-check"></i></div>
          <div><div class="hfc-title">Today's Queue</div><div class="hfc-subtitle">Live appointments</div></div>
        </div>
        <div class="hfc-bar"><div class="hfc-bar-fill" style="--w:78%"></div></div>
        <div class="hfc-rows">
          <div class="hfc-row"><span class="hfc-row-dot" style="background:#4ADE80"></span><span class="hfc-row-label">Completed</span><span class="hfc-row-val">12</span></div>
          <div class="hfc-row"><span class="hfc-row-dot" style="background:#FBBF24"></span><span class="hfc-row-label">In Progress</span><span class="hfc-row-val">3</span></div>
          <div class="hfc-row"><span class="hfc-row-dot" style="background:#5BA3C9"></span><span class="hfc-row-label">Upcoming</span><span class="hfc-row-val">8</span></div>
        </div>
      </div>
      <div class="hero-float-card hfc-small-1" style="padding:20px">
        <div class="hfc-header" style="margin-bottom:8px">
          <div class="hfc-icon green"><i class="fa-solid fa-star"></i></div>
          <div><div class="hfc-title">Satisfaction</div></div>
        </div>
        <div class="hfc-stat-big">98%</div>
        <div class="hfc-stat-label">Patient approval</div>
      </div>
      <div class="hero-float-card hfc-small-2" style="padding:20px">
        <div class="hfc-header" style="margin-bottom:8px">
          <div class="hfc-icon amber"><i class="fa-solid fa-clock"></i></div>
          <div><div class="hfc-title">Avg Wait Time</div></div>
        </div>
        <div class="hfc-stat-big">8 min</div>
        <div class="hfc-stat-label">Down from 45 min</div>
      </div>
    </div>
  </div>
</section>
<!-- ===== STATS ===== -->
<div class="stats-strip">
  <div class="stat-card reveal">
    <div class="stat-icon" style="background:linear-gradient(135deg,#3D6A8A,#2E5575)"><i class="fa-solid fa-user-doctor"></i></div>
    <div class="stat-number" data-target="<?= $totalDoctors ?>">0</div>
    <div class="stat-label">Licensed Doctors</div>
  </div>
  <div class="stat-card reveal reveal-delay-1">
    <div class="stat-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-heart-pulse"></i></div>
    <div class="stat-number" data-target="<?= $totalPatients ?>">0</div>
    <div class="stat-label">Happy Patients</div>
  </div>
  <div class="stat-card reveal reveal-delay-2">
    <div class="stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-number" data-target="<?= $totalAppts ?>">0</div>
    <div class="stat-label">Appointments Booked</div>
  </div>
  <div class="stat-card reveal reveal-delay-3">
    <div class="stat-icon" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-face-smile"></i></div>
    <div class="stat-number" data-target="98">0</div>
    <div class="stat-label">% Satisfaction Rate</div>
  </div>
</div>

<!-- ===== FEATURES ===== -->
<section id="features" class="landing-section" style="padding-top:120px">
  <div class="section-heading">
    <div class="section-tag"><i class="fa-solid fa-sparkles"></i> Platform Features</div>
    <h2>Everything Your Clinic Needs</h2>
    <p>Powerful tools designed to cut wait times, boost patient satisfaction, and give your team superpowers.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card reveal" style="--fc-color:#5BA3C9">
      <div class="feature-icon" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)"><i class="fa-solid fa-calendar-check"></i></div>
      <h3>Smart Booking</h3>
      <p>3-step appointment wizard with real-time slot availability, auto-confirmation, and calendar sync.</p>
    </div>
    <div class="feature-card reveal reveal-delay-1" style="--fc-color:#10B981">
      <div class="feature-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-user-doctor"></i></div>
      <h3>Doctor Profiles</h3>
      <p>Browse by specialization, compare ratings, read reviews, and pick the perfect doctor for you.</p>
    </div>
    <div class="feature-card reveal reveal-delay-2" style="--fc-color:#8B5CF6">
      <div class="feature-icon" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-chart-line"></i></div>
      <h3>Analytics Dashboard</h3>
      <p>Real-time KPIs, trend charts, doctor performance metrics, and exportable reports at your fingertips.</p>
    </div>
    <div class="feature-card reveal reveal-delay-3" style="--fc-color:#F59E0B">
      <div class="feature-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-bell"></i></div>
      <h3>Smart Notifications</h3>
      <p>Email confirmations, appointment reminders, status updates, and in-app alerts &mdash; never miss a beat.</p>
    </div>
    <div class="feature-card reveal reveal-delay-4" style="--fc-color:#EF4444">
      <div class="feature-icon" style="background:linear-gradient(135deg,#EF4444,#DC2626)"><i class="fa-solid fa-file-medical"></i></div>
      <h3>Patient Records</h3>
      <p>Digitized records with diagnosis, prescriptions, and visit history. Patients access them securely anytime.</p>
    </div>
    <div class="feature-card reveal reveal-delay-5" style="--fc-color:#06B6D4">
      <div class="feature-icon" style="background:linear-gradient(135deg,#06B6D4,#0891B2)"><i class="fa-solid fa-shield-halved"></i></div>
      <h3>Secure by Design</h3>
      <p>Built with CSRF protection, rate limiting, bcrypt hashing, and role-based access control from day one.</p>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section id="how-it-works" class="landing-section">
  <div class="section-heading">
    <div class="section-tag"><i class="fa-solid fa-route"></i> Simple Process</div>
    <h2>Book in 3 Easy Steps</h2>
    <p>From sign-up to seeing your doctor &mdash; it takes less than 2 minutes.</p>
  </div>
  <div class="steps-wrapper">
    <div class="step-card reveal">
      <div class="step-num">1</div>
      <h3>Create Your Account</h3>
      <p>Quick, free sign-up. Just your name, email, and password &mdash; you're ready in seconds.</p>
    </div>
    <div class="step-card reveal reveal-delay-1">
      <div class="step-num">2</div>
      <h3>Pick Doctor &amp; Time</h3>
      <p>Browse specialists, check availability, and select a slot that fits your schedule perfectly.</p>
    </div>
    <div class="step-card reveal reveal-delay-2">
      <div class="step-num">3</div>
      <h3>Confirm &amp; Visit</h3>
      <p>Get instant email confirmation with details. Show up at your time &mdash; skip the queue entirely.</p>
    </div>
  </div>
</section>
<!-- ===== TOP DOCTORS ===== -->
<section id="doctors" class="landing-section">
  <div class="section-heading">
    <div class="section-tag"><i class="fa-solid fa-stethoscope"></i> Medical Team</div>
    <h2>Our Top-Rated Doctors</h2>
    <p>Trusted healthcare professionals with proven track records and patient reviews.</p>
  </div>
  <div class="doctors-grid">
    <?php foreach ($doctors as $doc): ?>
    <div class="doctor-card reveal">
      <img class="doctor-photo" src="<?= BASE_URL ?>/assets/uploads/photos/<?= htmlspecialchars($doc['profile_photo'] ?? 'default.svg') ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <div class="doctor-info">
        <h4>Dr. <?= htmlspecialchars($doc['full_name']) ?></h4>
        <div class="spec"><?= htmlspecialchars($doc['specialization'] ?? 'General') ?></div>
        <div class="stars"><?php $r=round($doc['avg_rating'],1); for($i=1;$i<=5;$i++) echo $i<=$r?'<i class="fa-solid fa-star"></i>':'<i class="fa-solid fa-star dim"></i>'; ?> <span style="color:rgba(255,255,255,.45);font-size:.78rem">(<?= (int)$doc['review_count'] ?>)</span></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($doctors)): ?><p style="color:rgba(255,255,255,.55);text-align:center;grid-column:1/-1;font-size:.95rem">Our doctors will appear here soon.</p><?php endif; ?>
  </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<?php if (!empty($testimonials)): ?>
<section class="landing-section">
  <div class="section-heading">
    <div class="section-tag"><i class="fa-solid fa-quote-left"></i> Testimonials</div>
    <h2>What Patients Say</h2>
    <p>Real feedback from real patients who use MediQueue every day.</p>
  </div>
  <div class="testimonials-grid">
    <?php foreach ($testimonials as $idx => $t): ?>
    <div class="testimonial reveal reveal-delay-<?= $idx % 4 ?>">
      <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
      <div class="stars"><?php for($i=1;$i<=5;$i++) echo '<i class="fa-solid fa-star'.($i<=$t['rating']?'':' dim').'"></i>'; ?></div>
      <blockquote>&ldquo;<?= htmlspecialchars($t['comments']) ?>&rdquo;</blockquote>
      <cite><?= htmlspecialchars($t['patient_name']) ?></cite>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== CTA ===== -->
<section class="cta-section">
  <h2>Ready to Skip the Queue?</h2>
  <p>Join thousands of patients and doctors already using MediQueue for faster, smarter healthcare.</p>
  <div class="hero-btns" style="justify-content:center;position:relative">
    <a href="<?= BASE_URL ?>/pages/register.php" class="hero-btn hero-btn-primary"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
    <a href="<?= BASE_URL ?>/pages/login.php" class="hero-btn hero-btn-secondary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  </div>
</section>

<footer class="landing-footer"><p>&copy; <?= date('Y') ?> MediQueue. All rights reserved.</p></footer>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
// Navbar scroll effect
(function(){
  var nav=document.getElementById('landingNav');
  window.addEventListener('scroll',function(){nav.classList.toggle('scrolled',window.scrollY>60);});
})();

// Mobile menu toggle
(function(){
  var btn=document.getElementById('mobileToggle'),links=document.getElementById('navLinks');
  if(btn&&links){btn.addEventListener('click',function(){links.classList.toggle('mobile-open');});}
})();

// Counter animation
document.querySelectorAll('.stat-number[data-target]').forEach(function(el){
  var target=parseInt(el.dataset.target,10),startTime=null,duration=2200;
  function easeOut(t){return 1-Math.pow(1-t,3);}
  function animate(ts){if(!startTime)startTime=ts;var p=Math.min((ts-startTime)/duration,1);el.textContent=Math.floor(easeOut(p)*target);if(p<1)requestAnimationFrame(animate);else el.textContent=target.toLocaleString();}
  new IntersectionObserver(function(e,o){if(e[0].isIntersecting){requestAnimationFrame(animate);o.disconnect();}}).observe(el);
});

// Scroll reveal
(function(){
  var els=document.querySelectorAll('.reveal');
  var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});},{threshold:0.12,rootMargin:'0px 0px -40px 0px'});
  els.forEach(function(el){obs.observe(el);});
})();
</script>
</body>
</html>