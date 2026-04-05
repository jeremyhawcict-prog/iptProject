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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css" />
<style>
.landing-nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:16px 40px;backdrop-filter:blur(12px);background:rgba(255,255,255,.08);border-bottom:1px solid rgba(255,255,255,.12)}
.landing-logo{display:flex;align-items:center;gap:10px;color:#fff;font-family:'Inter',sans-serif;font-weight:800;font-size:1.3rem;text-decoration:none}
.landing-logo svg{width:28px;height:28px}
.landing-nav-links{display:flex;gap:24px;align-items:center}
.landing-nav-links a{color:rgba(255,255,255,.8);text-decoration:none;font-size:.9rem;font-weight:600;transition:color .2s}
.landing-nav-links a:hover{color:#fff}
.nav-cta{padding:8px 20px;border-radius:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25)}
.landing-section{padding:100px 40px 60px;max-width:1200px;margin:0 auto;position:relative;z-index:1}
.hero{display:flex;align-items:center;gap:60px;min-height:85vh;padding-top:80px}
.hero-text{flex:1}
.hero-text h1{font-family:'Inter',sans-serif;font-size:3.2rem;font-weight:800;color:#fff;line-height:1.15;margin-bottom:20px}
.hero-text h1 span{color:#7CB8D4}
.hero-text p{font-size:1.15rem;color:rgba(255,255,255,.82);max-width:480px;line-height:1.7;margin-bottom:32px}
.hero-btns{display:flex;gap:16px;flex-wrap:wrap}
.hero-btn{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border-radius:12px;font-family:'Inter',sans-serif;font-weight:700;font-size:.95rem;border:none;cursor:pointer;text-decoration:none;transition:all .3s ease}
.hero-btn-primary{color:#fff;background:linear-gradient(135deg,#3D6A8A,#2E5575);box-shadow:0 6px 24px rgba(61,106,138,.35)}
.hero-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 36px rgba(61,106,138,.5)}
.hero-btn-secondary{color:#fff;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25)}
.hero-illustration{flex:1;display:flex;justify-content:center}
.hero-illustration svg{width:100%;max-width:440px;height:auto;filter:drop-shadow(0 10px 30px rgba(0,0,0,.15))}
.stats-bar{display:flex;justify-content:center;gap:60px;padding:40px 0;flex-wrap:wrap}
.stat-item{text-align:center}
.stat-number{font-family:'Inter',sans-serif;font-size:2.5rem;font-weight:800;color:#fff}
.stat-label{font-size:.9rem;color:rgba(255,255,255,.7);margin-top:4px}
.section-heading{text-align:center;margin-bottom:48px}
.section-heading h2{font-family:'Inter',sans-serif;font-size:2rem;font-weight:800;color:#fff;margin-bottom:12px}
.section-heading p{color:rgba(255,255,255,.7);max-width:560px;margin:0 auto;font-size:1.05rem}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
.feature-card{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:32px 28px;transition:all .3s ease}
.feature-card:hover{background:rgba(255,255,255,.14);transform:translateY(-4px)}
.feature-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;margin-bottom:16px;background:linear-gradient(135deg,#3D6A8A,#2E5575)}
.feature-card h3{font-family:'Inter',sans-serif;font-size:1.15rem;font-weight:700;color:#fff;margin-bottom:8px}
.feature-card p{color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.6}
.steps{display:flex;gap:40px;justify-content:center;flex-wrap:wrap;counter-reset:step}
.step{flex:1;min-width:240px;max-width:320px;text-align:center}
.step::before{counter-increment:step;content:counter(step);width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-family:'Inter',sans-serif;font-weight:800;font-size:1.2rem;color:#fff;background:linear-gradient(135deg,#3D6A8A,#1a3a52)}
.step h3{font-family:'Inter',sans-serif;font-size:1.05rem;font-weight:700;color:#fff;margin-bottom:8px}
.step p{color:rgba(255,255,255,.7);font-size:.88rem;line-height:1.6}
.doctors-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.doctor-card{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:24px;display:flex;align-items:center;gap:16px;transition:all .3s ease}
.doctor-card:hover{background:rgba(255,255,255,.14);transform:translateY(-3px)}
.doctor-photo{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.2);background:#1a3a52}
.doctor-info h4{font-family:'Inter',sans-serif;font-size:1rem;font-weight:700;color:#fff;margin:0 0 4px}
.doctor-info .spec{font-size:.82rem;color:rgba(255,255,255,.6);margin-bottom:6px}
.doctor-info .stars{color:#F59E0B;font-size:.85rem}
.doctor-info .stars .dim{color:rgba(255,255,255,.25)}
.testimonials-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px}
.testimonial{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:28px}
.testimonial .stars{color:#F59E0B;font-size:.9rem;margin-bottom:12px}
.testimonial blockquote{color:rgba(255,255,255,.82);font-size:.92rem;line-height:1.65;margin:0 0 16px;font-style:italic}
.testimonial cite{font-style:normal;font-weight:700;color:#fff;font-size:.88rem}
.cta-section{text-align:center;padding:80px 40px}
.cta-section h2{font-family:'Inter',sans-serif;font-size:2.2rem;font-weight:800;color:#fff;margin-bottom:16px}
.cta-section p{color:rgba(255,255,255,.75);font-size:1.1rem;max-width:500px;margin:0 auto 32px}
.landing-footer{text-align:center;padding:32px;color:rgba(255,255,255,.45);font-size:.82rem}
@media(max-width:768px){.hero{flex-direction:column;text-align:center;gap:40px;padding-top:100px}.hero-text h1{font-size:2rem}.hero-btns{justify-content:center}.hero-illustration{order:-1}.stats-bar{gap:32px}.landing-nav{padding:12px 20px}.landing-section{padding:60px 20px 40px}}
</style>
</head>
<body>
<canvas id="particleCanvas"></canvas>
<div class="bg-mesh"></div>
<div class="bg-blob blob-1"></div><div class="bg-blob blob-2"></div><div class="bg-blob blob-3"></div><div class="bg-blob blob-4"></div><div class="bg-blob blob-5"></div>

<nav class="landing-nav">
  <a href="<?= BASE_URL ?>" class="landing-logo">
    <svg viewBox="0 0 28 28" fill="none"><rect x="11" y="3" width="6" height="22" rx="3" fill="white"/><rect x="3" y="11" width="22" height="6" rx="3" fill="white"/></svg>
    MediQueue
  </a>
  <div class="landing-nav-links">
    <a href="#features">Features</a>
    <a href="#doctors">Doctors</a>
    <a href="<?= BASE_URL ?>/pages/login.php" class="nav-cta"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
  </div>
</nav>

<section class="landing-section hero">
  <div class="hero-text">
    <h1>Smart <span>Healthcare</span> Queue System</h1>
    <p>Book appointments, manage queues, and connect with doctors � all in one seamless platform built for modern clinics.</p>
    <div class="hero-btns">
      <a href="<?= BASE_URL ?>/pages/register.php" class="hero-btn hero-btn-primary"><i class="fa-solid fa-user-plus"></i> Get Started Free</a>
      <a href="#how-it-works" class="hero-btn hero-btn-secondary"><i class="fa-solid fa-play"></i> How It Works</a>
    </div>
  </div>
  <div class="hero-illustration">
    <svg viewBox="0 0 500 420" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="40" y="60" width="420" height="300" rx="24" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.15)" stroke-width="2"/>
      <rect x="70" y="100" width="160" height="40" rx="10" fill="rgba(61,106,138,.5)"/>
      <rect x="70" y="160" width="360" height="16" rx="8" fill="rgba(255,255,255,.1)"/>
      <rect x="70" y="190" width="280" height="16" rx="8" fill="rgba(255,255,255,.08)"/>
      <rect x="70" y="220" width="320" height="16" rx="8" fill="rgba(255,255,255,.06)"/>
      <circle cx="400" cy="120" r="40" fill="rgba(61,106,138,.3)" stroke="rgba(61,106,138,.6)" stroke-width="2"/>
      <rect x="387" y="108" width="4" height="20" rx="2" fill="white"/><rect x="391" y="116" width="16" height="4" rx="2" fill="white"/>
      <circle cx="120" cy="310" r="28" fill="rgba(245,158,11,.2)" stroke="rgba(245,158,11,.5)" stroke-width="2"/>
      <path d="M112 310l6 6 12-12" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</section>

<div class="landing-section stats-bar">
  <div class="stat-item"><div class="stat-number" data-target="<?= $totalDoctors ?>">0</div><div class="stat-label">Licensed Doctors</div></div>
  <div class="stat-item"><div class="stat-number" data-target="<?= $totalPatients ?>">0</div><div class="stat-label">Happy Patients</div></div>
  <div class="stat-item"><div class="stat-number" data-target="<?= $totalAppts ?>">0</div><div class="stat-label">Appointments Managed</div></div>
  <div class="stat-item"><div class="stat-number" data-target="98">0</div><div class="stat-label">% Satisfaction Rate</div></div>
</div>

<section id="features" class="landing-section">
  <div class="section-heading"><h2>Everything Your Clinic Needs</h2><p>Powerful features to streamline healthcare operations.</p></div>
  <div class="features-grid">
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div><h3>Smart Booking</h3><p>3-step appointment wizard with real-time slot availability and instant confirmation.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-user-doctor"></i></div><h3>Doctor Profiles</h3><p>Browse doctors by specialization, view ratings and reviews.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div><h3>Analytics Dashboard</h3><p>Real-time KPIs, appointment trends, doctor performance reports.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-bell"></i></div><h3>Notifications</h3><p>Email confirmations, reminders, and in-app notifications.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-file-medical"></i></div><h3>Patient Records</h3><p>Diagnosis, prescriptions, and notes � patients view securely.</p></div>
    <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure by Design</h3><p>CSRF protection, rate limiting, encrypted passwords, RBAC.</p></div>
  </div>
</section>

<section id="how-it-works" class="landing-section">
  <div class="section-heading"><h2>How It Works</h2><p>Get started in three simple steps.</p></div>
  <div class="steps">
    <div class="step"><h3>Create Account</h3><p>Register for free as a patient. You'll be ready in seconds.</p></div>
    <div class="step"><h3>Pick a Doctor &amp; Time</h3><p>Search by specialization, pick an available slot.</p></div>
    <div class="step"><h3>Confirm &amp; Visit</h3><p>Get instant email confirmation. Show up � no long waits.</p></div>
  </div>
</section>

<section id="doctors" class="landing-section">
  <div class="section-heading"><h2>Our Top Doctors</h2><p>Trusted professionals ready to serve you.</p></div>
  <div class="doctors-grid">
    <?php foreach ($doctors as $doc): ?>
    <div class="doctor-card">
      <img class="doctor-photo" src="<?= BASE_URL ?>/assets/uploads/photos/<?= htmlspecialchars($doc['profile_photo'] ?? 'default.svg') ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <div class="doctor-info">
        <h4>Dr. <?= htmlspecialchars($doc['full_name']) ?></h4>
        <div class="spec"><?= htmlspecialchars($doc['specialization'] ?? 'General') ?></div>
        <div class="stars"><?php $r=round($doc['avg_rating'],1); for($i=1;$i<=5;$i++) echo $i<=$r?'<i class="fa-solid fa-star"></i>':'<i class="fa-solid fa-star dim"></i>'; ?> <span style="color:rgba(255,255,255,.5);font-size:.8rem">(<?= (int)$doc['review_count'] ?>)</span></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($doctors)): ?><p style="color:rgba(255,255,255,.6);text-align:center;grid-column:1/-1">Doctors will appear here soon.</p><?php endif; ?>
  </div>
</section>

<?php if (!empty($testimonials)): ?>
<section class="landing-section">
  <div class="section-heading"><h2>What Patients Say</h2></div>
  <div class="testimonials-grid">
    <?php foreach ($testimonials as $t): ?>
    <div class="testimonial">
      <div class="stars"><?php for($i=1;$i<=5;$i++) echo '<i class="fa-solid fa-star'.($i<=$t['rating']?'':' dim').'"></i>'; ?></div>
      <blockquote>&ldquo;<?= htmlspecialchars($t['comments']) ?>&rdquo;</blockquote>
      <cite><?= htmlspecialchars($t['patient_name']) ?></cite>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="cta-section">
  <h2>Ready to Get Started?</h2>
  <p>Join patients and doctors using MediQueue for smarter healthcare.</p>
  <div class="hero-btns" style="justify-content:center">
    <a href="<?= BASE_URL ?>/pages/register.php" class="hero-btn hero-btn-primary"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
    <a href="<?= BASE_URL ?>/pages/login.php" class="hero-btn hero-btn-secondary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</a>
  </div>
</section>

<footer class="landing-footer"><p>&copy; <?= date('Y') ?> MediQueue. All rights reserved.</p></footer>

<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
document.querySelectorAll('.stat-number[data-target]').forEach(function(el){
  var target=parseInt(el.dataset.target,10),startTime=null;
  function animate(ts){if(!startTime)startTime=ts;var p=Math.min((ts-startTime)/2000,1);el.textContent=Math.floor(p*target);if(p<1)requestAnimationFrame(animate);else el.textContent=target;}
  new IntersectionObserver(function(e,o){if(e[0].isIntersecting){requestAnimationFrame(animate);o.disconnect();}}).observe(el);
});
</script>
</body>
</html>