<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* About page — dark overlay approach over global bg */
.about-wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:40px 24px 60px}

/* Hero section */
.about-hero{text-align:center;margin-bottom:60px;padding:60px 20px;background:rgba(15,33,50,.65);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:24px;position:relative;overflow:hidden}
.about-hero::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 50% 50%,rgba(91,163,201,.08),transparent 60%);pointer-events:none}
.about-hero .hero-icon{width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);display:grid;place-items:center;font-size:1.6rem;color:#fff;margin:0 auto 20px;box-shadow:0 8px 28px rgba(61,106,138,.35)}
.about-hero .tag{display:inline-flex;align-items:center;gap:6px;background:rgba(91,163,201,.15);border:1px solid rgba(91,163,201,.25);border-radius:50px;padding:5px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#7DD3FC;margin-bottom:16px}
.about-hero h1{font-family:'Inter',sans-serif;font-size:2.4rem;font-weight:900;color:#fff;letter-spacing:-.8px;margin-bottom:14px}
.about-hero>p{color:#a3c4d9;font-size:1rem;line-height:1.75;max-width:560px;margin:0 auto}

/* Section titles */
.sec-head{text-align:center;margin-bottom:40px}
.sec-head .tag{display:inline-flex;align-items:center;gap:6px;background:rgba(91,163,201,.12);border:1px solid rgba(91,163,201,.2);border-radius:50px;padding:5px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#7DD3FC;margin-bottom:12px}
.sec-head h2{font-family:'Inter',sans-serif;font-size:1.8rem;font-weight:900;color:#fff;letter-spacing:-.5px;margin-bottom:10px}
.sec-head p{color:#8badc4;font-size:.92rem;line-height:1.7;max-width:460px;margin:0 auto}

/* Mission / Vision */
.mv-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:70px}
.mv-card{background:rgba(15,33,50,.6);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:36px 28px;position:relative;overflow:hidden;transition:all .35s ease}
.mv-card:hover{background:rgba(15,33,50,.75);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.2)}
.mv-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent,#5BA3C9)}
.mv-card .mv-icon{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;font-size:1.1rem;color:#fff;margin-bottom:16px}
.mv-card h3{font-size:1.15rem;font-weight:700;color:#e8f0f6;margin-bottom:10px}
.mv-card p{font-size:.88rem;color:#a3c4d9;line-height:1.7}

/* What we offer */
.offer-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:70px}
.offer{background:rgba(15,33,50,.55);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:30px 22px;text-align:center;transition:all .35s cubic-bezier(.22,1,.36,1)}
.offer:hover{background:rgba(15,33,50,.7);transform:translateY(-4px);box-shadow:0 10px 30px rgba(0,0,0,.15);border-color:rgba(91,163,201,.2)}
.offer .o-icon{width:50px;height:50px;border-radius:14px;display:grid;place-items:center;font-size:1.1rem;color:#fff;margin:0 auto 14px}
.offer h4{font-size:.95rem;font-weight:700;color:#e8f0f6;margin-bottom:6px}
.offer p{font-size:.82rem;color:#8badc4;line-height:1.6}

/* Values */
.values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:70px}
.value-item{background:rgba(15,33,50,.5);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:24px 16px;text-align:center;transition:all .3s ease}
.value-item:hover{background:rgba(15,33,50,.65);transform:translateY(-3px)}
.value-item .v-emoji{font-size:2rem;margin-bottom:10px;display:block}
.value-item h4{font-size:.88rem;font-weight:700;color:#e8f0f6;margin-bottom:6px}
.value-item p{font-size:.78rem;color:#8badc4;line-height:1.55}

/* Team stripe */
.team-band{background:rgba(15,33,50,.6);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:42px 32px;text-align:center;margin-bottom:70px;position:relative;overflow:hidden}
.team-band::before{content:'';position:absolute;top:-80%;left:-40%;width:180%;height:180%;background:radial-gradient(circle at 40% 40%,rgba(91,163,201,.06),transparent 55%);pointer-events:none}
.team-band h3{font-family:'Inter',sans-serif;font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:8px;position:relative}
.team-band .sub{color:#8badc4;font-size:.9rem;margin-bottom:24px;line-height:1.6;position:relative}
.team-badge{display:inline-flex;align-items:center;gap:10px;background:rgba(91,163,201,.1);border:1px solid rgba(91,163,201,.15);border-radius:12px;padding:12px 20px;position:relative}
.team-badge i{color:#5BA3C9;font-size:1.1rem}
.team-badge span{color:#c5dce9;font-size:.88rem;font-weight:600}

/* CTA */
.about-cta{background:linear-gradient(135deg,rgba(61,106,138,.2),rgba(91,163,201,.1));border:1px solid rgba(91,163,201,.15);border-radius:20px;padding:52px 32px;text-align:center;position:relative;overflow:hidden}
.about-cta::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 50% 50%,rgba(91,163,201,.06),transparent 55%);pointer-events:none}
.about-cta h2{font-family:'Inter',sans-serif;font-size:1.7rem;font-weight:900;color:#fff;margin-bottom:10px;position:relative}
.about-cta p{color:#8badc4;margin-bottom:28px;font-size:.95rem;line-height:1.7;max-width:440px;margin-left:auto;margin-right:auto;position:relative}
.about-cta .bcta{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:12px;font-weight:700;font-size:.9rem;text-decoration:none;color:#fff;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);box-shadow:0 6px 24px rgba(61,106,138,.4);transition:all .3s;position:relative}
.about-cta .bcta:hover{transform:translateY(-2px);box-shadow:0 10px 36px rgba(61,106,138,.55)}

/* Scroll reveal */
.sr{opacity:0;transform:translateY(24px);transition:all .65s cubic-bezier(.22,1,.36,1)}
.sr.vis{opacity:1;transform:translateY(0)}
.sr-d1{transition-delay:.08s}.sr-d2{transition-delay:.16s}.sr-d3{transition-delay:.24s}
.sr-d4{transition-delay:.32s}.sr-d5{transition-delay:.4s}

/* Responsive */
@media(max-width:768px){
  .mv-row{grid-template-columns:1fr}
  .offer-grid{grid-template-columns:1fr}
  .values-grid{grid-template-columns:1fr 1fr}
  .about-hero h1{font-size:1.8rem}
  .about-hero{padding:40px 16px}
}
@media(max-width:480px){
  .values-grid{grid-template-columns:1fr}
  .about-hero h1{font-size:1.5rem}
}
</style>

<div class="about-wrap">
  <!-- Hero -->
  <div class="about-hero sr">
    <div class="hero-icon"><i class="fa-solid fa-heart-pulse"></i></div>
    <div class="tag"><i class="fa-solid fa-sparkles"></i> About MediQueue</div>
    <h1>Healthcare. Simplified.</h1>
    <p>We're a team of developers and healthcare advocates building technology that puts patients first — shorter waits, smarter scheduling, better outcomes.</p>
  </div>

  <!-- Mission / Vision -->
  <div class="sec-head sr"><div class="tag"><i class="fa-solid fa-bullseye"></i> Our Purpose</div><h2>Mission &amp; Vision</h2></div>
  <div class="mv-row">
    <div class="mv-card sr" style="--accent:#10B981">
      <div class="mv-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-rocket"></i></div>
      <h3>Our Mission</h3>
      <p>To eliminate long queues in healthcare facilities by providing an intelligent, easy-to-use appointment management system that respects everyone's time.</p>
    </div>
    <div class="mv-card sr sr-d1" style="--accent:#8B5CF6">
      <div class="mv-icon" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-eye"></i></div>
      <h3>Our Vision</h3>
      <p>A world where visiting a doctor is stress-free — where patients, doctors, and clinics are seamlessly connected through smart, accessible technology.</p>
    </div>
  </div>

  <!-- What We Offer -->
  <div class="sec-head sr"><div class="tag"><i class="fa-solid fa-hand-holding-medical"></i> What We Offer</div><h2>Built for Everyone in Healthcare</h2><p>Tools designed for patients, doctors, and administrators alike.</p></div>
  <div class="offer-grid">
    <div class="offer sr">
      <div class="o-icon" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)"><i class="fa-solid fa-laptop-medical"></i></div>
      <h4>Online Booking</h4>
      <p>Book appointments 24/7 from anywhere — phone, tablet, or desktop.</p>
    </div>
    <div class="offer sr sr-d1">
      <div class="o-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-calendar-days"></i></div>
      <h4>Doctor Scheduling</h4>
      <p>Doctors manage slots, breaks, and availability with intuitive controls.</p>
    </div>
    <div class="offer sr sr-d2">
      <div class="o-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-chart-pie"></i></div>
      <h4>Admin Analytics</h4>
      <p>Track appointments, no-shows, trends, and clinic performance at a glance.</p>
    </div>
    <div class="offer sr sr-d3">
      <div class="o-icon" style="background:linear-gradient(135deg,#EF4444,#DC2626)"><i class="fa-solid fa-bell"></i></div>
      <h4>Reminders &amp; Alerts</h4>
      <p>Automated email notifications for confirmations, reminders, and updates.</p>
    </div>
    <div class="offer sr sr-d4">
      <div class="o-icon" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-clipboard-list"></i></div>
      <h4>Patient Records</h4>
      <p>Secure digital records with diagnoses, prescriptions, and histories.</p>
    </div>
    <div class="offer sr sr-d5">
      <div class="o-icon" style="background:linear-gradient(135deg,#06B6D4,#0891B2)"><i class="fa-solid fa-star"></i></div>
      <h4>Feedback System</h4>
      <p>Patients rate visits and leave reviews — driving quality improvements.</p>
    </div>
  </div>

  <!-- Values -->
  <div class="sec-head sr"><div class="tag"><i class="fa-solid fa-gem"></i> Core Values</div><h2>What Drives Us</h2></div>
  <div class="values-grid">
    <div class="value-item sr"><span class="v-emoji">🩺</span><h4>Patient First</h4><p>Every feature starts with the patient experience.</p></div>
    <div class="value-item sr sr-d1"><span class="v-emoji">⚡</span><h4>Speed</h4><p>Reduce wait times and streamline workflows.</p></div>
    <div class="value-item sr sr-d2"><span class="v-emoji">🔒</span><h4>Security</h4><p>Data protection is a core requirement, never an afterthought.</p></div>
    <div class="value-item sr sr-d3"><span class="v-emoji">♿</span><h4>Accessibility</h4><p>Healthcare tools everyone can use, on any device.</p></div>
  </div>

  <!-- Team -->
  <div class="team-band sr">
    <h3>Built by Group 7</h3>
    <p class="sub">BSIT 2H &middot; Bulacan State University<br>IT 211: Web Systems &amp; Technology</p>
    <div class="team-badge"><i class="fa-solid fa-graduation-cap"></i><span>Academic Year 2024 — 2025</span></div>
  </div>

  <!-- CTA -->
  <div class="about-cta sr">
    <h2>Experience MediQueue Today</h2>
    <p>Sign up in under a minute and discover how we're making healthcare more accessible.</p>
    <a href="<?= BASE_URL ?>/pages/register.php" class="bcta"><i class="fa-solid fa-arrow-right"></i> Create Your Account</a>
  </div>
</div>

<script>
document.querySelectorAll('.sr').forEach(function(el){
  new IntersectionObserver(function(es,o){if(es[0].isIntersecting){es[0].target.classList.add('vis');o.unobserve(es[0].target)}},{threshold:.1,rootMargin:'0px 0px -30px 0px'}).observe(el);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>