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

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ===== LANDING-SPECIFIC STYLES ===== */

/* Hero */
.landing-hero {
  padding: 60px 0 40px;
  max-width: var(--container-max);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: 48px;
  align-items: center;
}
.landing-hero-content { position: relative; z-index: 1; }
.hero-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(61,106,138,0.08);
  border: 1px solid rgba(61,106,138,0.15);
  border-radius: var(--radius-pill);
  padding: 6px 16px 6px 10px;
  font-size: var(--fs-xs);
  font-weight: 600;
  color: var(--teal-core);
  margin-bottom: 22px;
}
.hero-pill .dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 8px rgba(46,204,113,0.4);
  animation: pulseGreen 2s ease-in-out infinite;
}
@keyframes pulseGreen { 0%,100%{opacity:1}50%{opacity:.4} }

.landing-hero-content h1 {
  font-family: var(--font-heading);
  font-size: var(--fs-5xl);
  font-weight: 900;
  line-height: 1.08;
  color: var(--text-primary);
  letter-spacing: -1.5px;
  margin-bottom: 18px;
}
.landing-hero-content h1 .accent {
  color: var(--teal-core);
}
.landing-hero-content .lead {
  font-size: var(--fs-lg);
  line-height: 1.7;
  color: var(--text-secondary);
  max-width: 460px;
  margin-bottom: 32px;
}
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 36px; }
.hero-actions .btn { padding: 14px 28px; font-size: var(--fs-base); border-radius: var(--radius-md); }
.hero-actions .btn i { font-size: var(--fs-sm); }

/* Social proof */
.social-proof { display: flex; align-items: center; gap: 14px; }
.sp-faces { display: flex; }
.sp-faces span {
  width: 34px; height: 34px; border-radius: 50%;
  display: grid; place-items: center;
  font-size: 11px; font-weight: 700; color: #fff;
  margin-left: -8px;
  border: 2.5px solid var(--card-bg-solid);
}
.sp-faces span:first-child { margin-left: 0; }
.sp-text { font-size: var(--fs-sm); color: var(--text-muted); line-height: 1.35; }
.sp-text strong { color: var(--text-primary); display: block; }

/* Hero visual — mock dashboard */
.landing-hero-visual { position: relative; display: flex; justify-content: center; align-items: center; }
.mock-window {
  width: 100%; max-width: 460px;
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.40);
  border-radius: var(--radius-lg);
  box-shadow: var(--card-shadow);
  overflow: hidden;
}
.mock-titlebar {
  display: flex; align-items: center; gap: 6px;
  padding: 12px 16px;
  background: rgba(61,106,138,0.04);
  border-bottom: 1px solid rgba(61,106,138,0.08);
}
.mock-dot { width: 10px; height: 10px; border-radius: 50%; }
.mock-body { padding: 18px; }
.mock-row { display: flex; gap: 10px; margin-bottom: 12px; }
.mock-card-sm {
  flex: 1;
  background: rgba(61,106,138,0.04);
  border: 1px solid rgba(61,106,138,0.08);
  border-radius: var(--radius-sm);
  padding: 14px 10px; text-align: center;
}
.mock-card-sm .mc-icon { font-size: 1.1rem; margin-bottom: 4px; }
.mock-card-sm .mc-val { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); }
.mock-card-sm .mc-label { font-size: 10px; color: var(--text-muted); margin-top: 2px; }
.mock-chart {
  background: rgba(61,106,138,0.03);
  border: 1px solid rgba(61,106,138,0.06);
  border-radius: var(--radius-sm);
  padding: 14px; height: 110px;
  display: flex; align-items: flex-end; gap: 6px;
}
.chart-bar-el { flex: 1; border-radius: 4px 4px 0 0; transition: height .6s ease; }

/* Floating badges */
.float-badge {
  position: absolute;
  background: var(--card-bg-solid);
  backdrop-filter: blur(14px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-md);
  padding: 10px 14px;
  display: flex; align-items: center; gap: 10px;
  box-shadow: var(--shadow-lg);
}
.float-badge.fb-1 { top: 10%; right: -20px; animation: fbFloat 5s ease-in-out infinite; }
.float-badge.fb-2 { bottom: 18%; left: -15px; animation: fbFloat 6s ease-in-out infinite 1s; }
@keyframes fbFloat { 0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)} }
.fb-icon {
  width: 34px; height: 34px; border-radius: var(--radius-sm);
  display: grid; place-items: center; font-size: .85rem; color: #fff;
}
.fb-text .fb-val { font-size: var(--fs-base); font-weight: 800; color: var(--text-primary); }
.fb-text .fb-lbl { font-size: 10px; color: var(--text-muted); }

/* Stats bar */
.landing-stats {
  max-width: var(--container-max);
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding-bottom: 20px;
}
.landing-stat {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-lg);
  padding: 24px 18px;
  text-align: center;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
}
.landing-stat:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); }
.landing-stat .ls-icon { font-size: 1rem; margin-bottom: 8px; display: block; }
.landing-stat .ls-num {
  font-family: var(--font-heading);
  font-size: var(--fs-2xl);
  font-weight: 900;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}
.landing-stat .ls-label { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 2px; }

/* Section common */
.landing-section {
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 60px 0;
}
.lsec-header { text-align: center; margin-bottom: 44px; }
.lsec-tag {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(61,106,138,0.08);
  border: 1px solid rgba(61,106,138,0.12);
  border-radius: var(--radius-pill);
  padding: 5px 14px;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .8px;
  color: var(--teal-core);
  margin-bottom: 12px;
}
.lsec-header h2 {
  font-family: var(--font-heading);
  font-size: var(--fs-3xl);
  font-weight: 900;
  color: var(--text-primary);
  letter-spacing: -0.5px;
  margin-bottom: 10px;
}
.lsec-header p { color: var(--text-muted); max-width: 460px; margin: 0 auto; font-size: var(--fs-base); line-height: 1.7; }

/* Features */
.feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.feat-card {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-lg);
  padding: 28px 22px;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}
.feat-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--accent-start, var(--teal-core)), var(--accent-end, var(--teal-bright)));
  transform: scaleX(0); transform-origin: left;
  transition: transform .35s ease;
}
.feat-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); }
.feat-card:hover::before { transform: scaleX(1); }
.feat-card .fc-icon {
  width: 46px; height: 46px; border-radius: 13px;
  display: grid; place-items: center; font-size: 1.1rem; color: #fff;
  margin-bottom: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.feat-card h3 { font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
.feat-card p { font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.65; margin: 0; }

/* How it works */
.steps-row {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
  position: relative;
}
.steps-row::before {
  content: '';
  position: absolute; top: 38px; left: 18%; right: 18%; height: 2px;
  background: linear-gradient(90deg, transparent, rgba(61,106,138,0.12), rgba(61,106,138,0.12), transparent);
}
.step-item { text-align: center; position: relative; }
.step-num {
  width: 52px; height: 52px; border-radius: 50%;
  background: linear-gradient(135deg, var(--teal-core), var(--teal-bright));
  display: grid; place-items: center; margin: 0 auto 16px;
  font-family: var(--font-heading); font-weight: 900; font-size: 1.1rem; color: #fff;
  box-shadow: 0 4px 16px rgba(61,106,138,.3);
  position: relative;
}
.step-num::before {
  content: ''; position: absolute; inset: -4px; border-radius: 50%;
  border: 2px solid rgba(61,106,138,0.12);
}
.step-item h3 { font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
.step-item p { font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.6; max-width: 260px; margin: 0 auto; }

/* Doctors grid */
.landing-doc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}
.landing-doc {
  display: flex; align-items: center; gap: 14px;
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-md);
  padding: 18px;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
}
.landing-doc:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); }
.landing-doc-photo {
  width: 58px; height: 58px; border-radius: var(--radius-md);
  object-fit: cover; border: 2px solid rgba(255,255,255,0.35);
  background: var(--input-bg); flex-shrink: 0;
}
.landing-doc-meta h4 { font-size: var(--fs-base); font-weight: 700; color: var(--text-primary); margin: 0 0 2px; }
.landing-doc-meta .doc-spec { font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 4px; }
.landing-doc-meta .doc-stars { color: #F59E0B; font-size: var(--fs-sm); }
.landing-doc-meta .doc-stars .dim { color: rgba(61,106,138,0.15); }

/* Testimonials */
.test-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 16px;
}
.test-card {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-lg);
  padding: 26px 22px;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
}
.test-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); }
.test-card .tq { font-size: 1.4rem; color: rgba(61,106,138,0.15); margin-bottom: 8px; line-height: 1; }
.test-card .tstars { color: #F59E0B; font-size: var(--fs-sm); margin-bottom: 10px; }
.test-card blockquote {
  font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.7;
  font-style: italic; margin: 0 0 14px; border: none; padding: 0;
}
.test-card cite {
  font-style: normal; font-weight: 700; color: var(--text-primary);
  font-size: var(--fs-sm);
  display: flex; align-items: center; gap: 8px;
}
.test-card cite::before {
  content: ''; width: 14px; height: 2px;
  background: rgba(61,106,138,0.25); border-radius: 2px;
}

/* CTA */
.landing-cta {
  max-width: var(--container-max);
  margin: 0 auto;
  padding-bottom: 40px;
}
.landing-cta-inner {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-xl);
  padding: 52px 36px;
  text-align: center;
  box-shadow: var(--card-shadow);
  position: relative; overflow: hidden;
}
.landing-cta-inner::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #22d3ee, var(--teal-core), #a78bfa);
}
.landing-cta-inner h2 {
  font-family: var(--font-heading);
  font-size: var(--fs-3xl); font-weight: 900;
  color: var(--text-primary); margin-bottom: 10px; letter-spacing: -0.5px;
}
.landing-cta-inner > p {
  color: var(--text-muted); font-size: var(--fs-base);
  max-width: 420px; margin: 0 auto 28px; line-height: 1.7;
}

/* Scroll reveal */
.sr { opacity: 0; transform: translateY(24px); transition: all .65s cubic-bezier(.22,1,.36,1); }
.sr.vis { opacity: 1; transform: translateY(0); }
.sr-d1{transition-delay:.08s}.sr-d2{transition-delay:.16s}.sr-d3{transition-delay:.24s}
.sr-d4{transition-delay:.32s}.sr-d5{transition-delay:.4s}

/* Page transition */
.page-content { animation: pageIn 0.45s var(--ease-out) both; }
@keyframes pageIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 1024px) {
  .feat-grid { grid-template-columns: repeat(2, 1fr); }
  .landing-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .landing-hero { grid-template-columns: 1fr; text-align: center; padding: 30px 0; gap: 30px; }
  .landing-hero-content .lead { margin: 0 auto 28px; }
  .hero-actions { justify-content: center; }
  .social-proof { justify-content: center; }
  .landing-hero-visual { order: -1; }
  .mock-window { max-width: 320px; }
  .float-badge { display: none; }
  .landing-stats { grid-template-columns: 1fr 1fr; }
  .feat-grid { grid-template-columns: 1fr; }
  .steps-row { grid-template-columns: 1fr; gap: 28px; }
  .steps-row::before { display: none; }
  .landing-hero-content h1 { font-size: var(--fs-3xl); }
}
@media (max-width: 480px) {
  .landing-hero-content h1 { font-size: var(--fs-2xl); }
  .landing-stats { grid-template-columns: 1fr; }
}
</style>

<!-- ===== HERO ===== -->
<section class="landing-hero container">
  <div class="landing-hero-content">
    <div class="hero-pill"><span class="dot"></span> Serving clinics across the Philippines</div>
    <h1>Skip the Wait,<br><span class="accent">Book Smarter.</span></h1>
    <p class="lead">MediQueue helps patients book appointments, helps doctors manage schedules, and helps clinics run smoother — no more long queues.</p>
    <div class="hero-actions">
      <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-arrow-right"></i> Get Started Free</a>
      <a href="#how-it-works" class="btn btn-secondary btn-lg"><i class="fa-solid fa-play"></i> See How It Works</a>
    </div>
    <div class="social-proof">
      <div class="sp-faces">
        <span style="background:var(--teal-core)">J</span>
        <span style="background:#10B981">M</span>
        <span style="background:#F59E0B">A</span>
        <span style="background:#8B5CF6">R</span>
      </div>
      <div class="sp-text"><strong><?= number_format($totalPatients) ?>+ patients joined</strong>Trusted by clinics &amp; patients</div>
    </div>
  </div>

  <div class="landing-hero-visual">
    <div class="mock-window">
      <div class="mock-titlebar">
        <span class="mock-dot" style="background:#EF4444"></span>
        <span class="mock-dot" style="background:#F59E0B"></span>
        <span class="mock-dot" style="background:#22C55E"></span>
      </div>
      <div class="mock-body">
        <div class="mock-row">
          <div class="mock-card-sm"><div class="mc-icon" style="color:var(--teal-core)"><i class="fa-solid fa-calendar-check"></i></div><div class="mc-val"><?= $totalAppts ?></div><div class="mc-label">Appointments</div></div>
          <div class="mock-card-sm"><div class="mc-icon" style="color:#10B981"><i class="fa-solid fa-user-doctor"></i></div><div class="mc-val"><?= $totalDoctors ?></div><div class="mc-label">Doctors</div></div>
          <div class="mock-card-sm"><div class="mc-icon" style="color:#F59E0B"><i class="fa-solid fa-users"></i></div><div class="mc-val"><?= $totalPatients ?></div><div class="mc-label">Patients</div></div>
        </div>
        <div class="mock-chart" id="mockChart"></div>
      </div>
    </div>
    <div class="float-badge fb-1">
      <div class="fb-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-check"></i></div>
      <div class="fb-text"><div class="fb-val">98%</div><div class="fb-lbl">Satisfaction</div></div>
    </div>
    <div class="float-badge fb-2">
      <div class="fb-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-bolt"></i></div>
      <div class="fb-text"><div class="fb-val">8 min</div><div class="fb-lbl">Avg wait time</div></div>
    </div>
  </div>
</section>

<!-- ===== STATS ===== -->
<div class="landing-stats container">
  <div class="landing-stat sr"><span class="ls-icon" style="color:var(--teal-core)"><i class="fa-solid fa-user-doctor"></i></span><div class="ls-num" data-count="<?= $totalDoctors ?>">0</div><div class="ls-label">Licensed Doctors</div></div>
  <div class="landing-stat sr sr-d1"><span class="ls-icon" style="color:#10B981"><i class="fa-solid fa-heart-pulse"></i></span><div class="ls-num" data-count="<?= $totalPatients ?>">0</div><div class="ls-label">Happy Patients</div></div>
  <div class="landing-stat sr sr-d2"><span class="ls-icon" style="color:#F59E0B"><i class="fa-solid fa-calendar-check"></i></span><div class="ls-num" data-count="<?= $totalAppts ?>">0</div><div class="ls-label">Appointments Booked</div></div>
  <div class="landing-stat sr sr-d3"><span class="ls-icon" style="color:#8B5CF6"><i class="fa-solid fa-face-smile"></i></span><div class="ls-num" data-count="98">0</div><div class="ls-label">% Satisfaction Rate</div></div>
</div>

<!-- ===== FEATURES ===== -->
<section id="features" class="landing-section container">
  <div class="lsec-header"><div class="lsec-tag"><i class="fa-solid fa-sparkles"></i> Features</div><h2>Everything Your Clinic Needs</h2><p>Powerful tools to cut wait times, boost patient satisfaction, and streamline operations.</p></div>
  <div class="feat-grid">
    <div class="feat-card sr" style="--accent-start:#3D6A8A;--accent-end:#5BA3C9"><div class="fc-icon" style="background:linear-gradient(135deg,#3D6A8A,#5BA3C9)"><i class="fa-solid fa-calendar-check"></i></div><h3>Smart Booking</h3><p>3-step appointment wizard with real-time slot availability and auto-confirmation.</p></div>
    <div class="feat-card sr sr-d1" style="--accent-start:#059669;--accent-end:#34d399"><div class="fc-icon" style="background:linear-gradient(135deg,#059669,#34d399)"><i class="fa-solid fa-user-doctor"></i></div><h3>Doctor Profiles</h3><p>Browse by specialization, compare ratings, read reviews, and pick the perfect doctor.</p></div>
    <div class="feat-card sr sr-d2" style="--accent-start:#7C3AED;--accent-end:#A78BFA"><div class="fc-icon" style="background:linear-gradient(135deg,#7C3AED,#A78BFA)"><i class="fa-solid fa-chart-line"></i></div><h3>Analytics Dashboard</h3><p>Real-time KPIs, trend charts, performance metrics, and exportable reports.</p></div>
    <div class="feat-card sr sr-d3" style="--accent-start:#D97706;--accent-end:#FBBF24"><div class="fc-icon" style="background:linear-gradient(135deg,#D97706,#FBBF24)"><i class="fa-solid fa-bell"></i></div><h3>Smart Notifications</h3><p>Email confirmations, reminders, status updates, and in-app alerts automatically.</p></div>
    <div class="feat-card sr sr-d4" style="--accent-start:#DC2626;--accent-end:#F87171"><div class="fc-icon" style="background:linear-gradient(135deg,#DC2626,#F87171)"><i class="fa-solid fa-file-medical"></i></div><h3>Patient Records</h3><p>Digitized records with diagnosis, prescriptions, and visit history. Secure access.</p></div>
    <div class="feat-card sr sr-d5" style="--accent-start:#0891B2;--accent-end:#22D3EE"><div class="fc-icon" style="background:linear-gradient(135deg,#0891B2,#22D3EE)"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure by Design</h3><p>CSRF protection, rate limiting, bcrypt hashing, and role-based access control.</p></div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section id="how-it-works" class="landing-section container">
  <div class="lsec-header"><div class="lsec-tag"><i class="fa-solid fa-route"></i> How It Works</div><h2>Book in 3 Easy Steps</h2><p>From sign-up to seeing your doctor — less than 2 minutes.</p></div>
  <div class="steps-row">
    <div class="step-item sr"><div class="step-num">1</div><h3>Create Account</h3><p>Quick free sign-up with just your name, email, and password.</p></div>
    <div class="step-item sr sr-d1"><div class="step-num">2</div><h3>Pick Doctor &amp; Time</h3><p>Browse specialists, check availability, and select the perfect slot.</p></div>
    <div class="step-item sr sr-d2"><div class="step-num">3</div><h3>Confirm &amp; Visit</h3><p>Get instant email confirmation. Show up at your time — skip the queue.</p></div>
  </div>
</section>

<!-- ===== DOCTORS ===== -->
<section id="doctors" class="landing-section container">
  <div class="lsec-header"><div class="lsec-tag"><i class="fa-solid fa-stethoscope"></i> Medical Team</div><h2>Our Top-Rated Doctors</h2><p>Trusted professionals with proven track records and patient reviews.</p></div>
  <div class="landing-doc-grid">
    <?php foreach ($doctors as $doc): ?>
    <div class="landing-doc sr">
      <img class="landing-doc-photo" src="<?= BASE_URL ?>/assets/uploads/photos/<?= htmlspecialchars($doc['profile_photo'] ?? 'default.svg') ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <div class="landing-doc-meta">
        <h4>Dr. <?= htmlspecialchars($doc['full_name']) ?></h4>
        <div class="doc-spec"><?= htmlspecialchars($doc['specialization'] ?? 'General') ?></div>
        <div class="doc-stars"><?php $r=round($doc['avg_rating'],1); for($i=1;$i<=5;$i++) echo $i<=$r?'<i class="fa-solid fa-star"></i>':'<i class="fa-solid fa-star dim"></i>'; ?> <span class="text-muted" style="font-size:11px">(<?= (int)$doc['review_count'] ?>)</span></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($doctors)): ?><p class="text-muted" style="text-align:center;grid-column:1/-1;padding:40px 0">Our doctors will appear here soon.</p><?php endif; ?>
  </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<?php if (!empty($testimonials)): ?>
<section class="landing-section container">
  <div class="lsec-header"><div class="lsec-tag"><i class="fa-solid fa-quote-left"></i> Testimonials</div><h2>What Patients Say</h2><p>Real feedback from patients who use MediQueue every day.</p></div>
  <div class="test-grid">
    <?php foreach ($testimonials as $idx => $t): ?>
    <div class="test-card sr sr-d<?= $idx % 4 ?>">
      <div class="tq"><i class="fa-solid fa-quote-left"></i></div>
      <div class="tstars"><?php for($i=1;$i<=5;$i++) echo '<i class="fa-solid fa-star'.($i<=$t['rating']?'':' dim').'"></i>'; ?></div>
      <blockquote>&ldquo;<?= htmlspecialchars($t['comments']) ?>&rdquo;</blockquote>
      <cite><?= htmlspecialchars($t['patient_name']) ?></cite>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== CTA ===== -->
<div class="landing-cta container">
  <div class="landing-cta-inner sr">
    <h2>Ready to Skip the Queue?</h2>
    <p>Join thousands of patients and doctors already using MediQueue for faster, smarter healthcare.</p>
    <div class="hero-actions" style="justify-content:center">
      <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
      <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
    </div>
  </div>
</div>

<script>
// Scroll reveal
document.querySelectorAll('.sr').forEach(function(el){
  new IntersectionObserver(function(es,o){
    if(es[0].isIntersecting){es[0].target.classList.add('vis');o.unobserve(es[0].target)}
  },{threshold:.1,rootMargin:'0px 0px -30px 0px'}).observe(el);
});
// Mock chart bars
(function(){var c=document.getElementById('mockChart');if(!c)return;
  [45,65,40,80,55,70,90,60,75,50,85,65].forEach(function(h){
    var bar=document.createElement('div');bar.className='chart-bar-el';
    bar.style.background='linear-gradient(to top,var(--teal-core),var(--teal-bright))';
    bar.style.height='0';c.appendChild(bar);
    setTimeout(function(){bar.style.height=h+'%'},300);
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>