<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ===== ABOUT PAGE — Uses global design system ===== */
.about-wrap {
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 40px 0 60px;
}

/* Hero */
.about-hero {
  text-align: center;
  margin-bottom: 56px;
  padding: 52px 24px;
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-xl);
  box-shadow: var(--card-shadow);
  position: relative;
  overflow: hidden;
}
.about-hero::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #22d3ee, var(--teal-core), #a78bfa);
}
.about-hero .hero-icon {
  width: 68px; height: 68px; border-radius: 18px;
  background: linear-gradient(135deg, var(--teal-core), var(--teal-bright));
  display: grid; place-items: center;
  font-size: 1.5rem; color: #fff;
  margin: 0 auto 18px;
  box-shadow: 0 6px 24px rgba(61,106,138,0.3);
}
.about-hero .tag {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(61,106,138,0.08);
  border: 1px solid rgba(61,106,138,0.12);
  border-radius: var(--radius-pill);
  padding: 5px 14px;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .8px;
  color: var(--teal-core); margin-bottom: 14px;
}
.about-hero h1 {
  font-family: var(--font-heading);
  font-size: var(--fs-4xl); font-weight: 900;
  color: var(--text-primary);
  letter-spacing: -0.8px; margin-bottom: 12px;
}
.about-hero > p {
  color: var(--text-secondary);
  font-size: var(--fs-md); line-height: 1.75;
  max-width: 540px; margin: 0 auto;
}

/* Section titles */
.about-sec-head { text-align: center; margin-bottom: 36px; }
.about-sec-head .tag {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(61,106,138,0.08);
  border: 1px solid rgba(61,106,138,0.12);
  border-radius: var(--radius-pill);
  padding: 5px 14px;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .8px;
  color: var(--teal-core); margin-bottom: 10px;
}
.about-sec-head h2 {
  font-family: var(--font-heading);
  font-size: var(--fs-3xl); font-weight: 900;
  color: var(--text-primary);
  letter-spacing: -0.5px; margin-bottom: 8px;
}
.about-sec-head p { color: var(--text-muted); font-size: var(--fs-base); line-height: 1.7; max-width: 440px; margin: 0 auto; }

/* Mission / Vision cards */
.mv-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 64px; }
.mv-card {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-lg);
  padding: 32px 24px;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
  position: relative; overflow: hidden;
}
.mv-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--accent-start, var(--teal-core)), var(--accent-end, var(--teal-bright)));
}
.mv-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); }
.mv-card .mv-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: grid; place-items: center; font-size: 1rem; color: #fff;
  margin-bottom: 14px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.mv-card h3 { font-size: var(--fs-lg); font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
.mv-card p { font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.7; margin: 0; }

/* What we offer grid */
.offer-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 64px; }
.offer-card {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-lg);
  padding: 28px 20px; text-align: center;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
}
.offer-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); }
.offer-card .o-icon {
  width: 48px; height: 48px; border-radius: 13px;
  display: grid; place-items: center; font-size: 1.1rem; color: #fff;
  margin: 0 auto 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.offer-card h4 { font-size: var(--fs-base); font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.offer-card p { font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.6; margin: 0; }

/* Values grid */
.values-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 64px; }
.value-card {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-md);
  padding: 22px 14px; text-align: center;
  box-shadow: var(--card-shadow);
  transition: var(--transition);
}
.value-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); }
.value-card .v-emoji { font-size: 1.8rem; margin-bottom: 8px; display: block; }
.value-card h4 { font-size: var(--fs-sm); font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.value-card p { font-size: var(--fs-xs); color: var(--text-secondary); line-height: 1.55; margin: 0; }

/* Team band */
.team-band {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-xl);
  padding: 40px 28px; text-align: center;
  box-shadow: var(--card-shadow);
  margin-bottom: 64px;
  position: relative; overflow: hidden;
}
.team-band::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #22d3ee, var(--teal-core), #a78bfa);
}
.team-band h3 { font-family: var(--font-heading); font-size: var(--fs-2xl); font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
.team-band .sub { color: var(--text-secondary); font-size: var(--fs-base); margin-bottom: 20px; line-height: 1.6; }
.team-badge {
  display: inline-flex; align-items: center; gap: 10px;
  background: rgba(61,106,138,0.06);
  border: 1px solid rgba(61,106,138,0.12);
  border-radius: var(--radius-md);
  padding: 10px 18px;
}
.team-badge i { color: var(--teal-core); font-size: 1rem; }
.team-badge span { color: var(--text-primary); font-size: var(--fs-sm); font-weight: 600; }

/* CTA */
.about-cta {
  background: var(--card-bg-solid);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: var(--radius-xl);
  padding: 48px 28px; text-align: center;
  box-shadow: var(--card-shadow);
  position: relative; overflow: hidden;
}
.about-cta::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #22d3ee, var(--teal-core), #a78bfa);
}
.about-cta h2 { font-family: var(--font-heading); font-size: var(--fs-2xl); font-weight: 900; color: var(--text-primary); margin-bottom: 10px; }
.about-cta p { color: var(--text-muted); margin-bottom: 24px; font-size: var(--fs-base); line-height: 1.7; max-width: 420px; margin-left: auto; margin-right: auto; }

/* Scroll reveal */
.sr { opacity: 0; transform: translateY(24px); transition: all .65s cubic-bezier(.22,1,.36,1); }
.sr.vis { opacity: 1; transform: translateY(0); }
.sr-d1{transition-delay:.08s}.sr-d2{transition-delay:.16s}.sr-d3{transition-delay:.24s}
.sr-d4{transition-delay:.32s}.sr-d5{transition-delay:.4s}

/* Page transition */
.page-content { animation: pageIn 0.45s var(--ease-out) both; }
@keyframes pageIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 768px) {
  .mv-row { grid-template-columns: 1fr; }
  .offer-grid { grid-template-columns: 1fr; }
  .values-grid { grid-template-columns: 1fr 1fr; }
  .about-hero h1 { font-size: var(--fs-3xl); }
  .about-hero { padding: 36px 16px; }
}
@media (max-width: 480px) {
  .values-grid { grid-template-columns: 1fr; }
  .about-hero h1 { font-size: var(--fs-2xl); }
}
</style>

<div class="about-wrap container">
  <!-- Hero -->
  <div class="about-hero sr">
    <div class="hero-icon"><i class="fa-solid fa-heart-pulse"></i></div>
    <div class="tag"><i class="fa-solid fa-sparkles"></i> About MediQueue</div>
    <h1>Healthcare. Simplified.</h1>
    <p>We're a team of developers and healthcare advocates building technology that puts patients first — shorter waits, smarter scheduling, better outcomes.</p>
  </div>

  <!-- Mission / Vision -->
  <div class="about-sec-head sr"><div class="tag"><i class="fa-solid fa-bullseye"></i> Our Purpose</div><h2>Mission &amp; Vision</h2></div>
  <div class="mv-row">
    <div class="mv-card sr" style="--accent-start:#059669;--accent-end:#34d399">
      <div class="mv-icon" style="background:linear-gradient(135deg,#059669,#34d399)"><i class="fa-solid fa-rocket"></i></div>
      <h3>Our Mission</h3>
      <p>To eliminate long queues in healthcare facilities by providing an intelligent, easy-to-use appointment management system that respects everyone's time.</p>
    </div>
    <div class="mv-card sr sr-d1" style="--accent-start:#7C3AED;--accent-end:#A78BFA">
      <div class="mv-icon" style="background:linear-gradient(135deg,#7C3AED,#A78BFA)"><i class="fa-solid fa-eye"></i></div>
      <h3>Our Vision</h3>
      <p>A world where visiting a doctor is stress-free — where patients, doctors, and clinics are seamlessly connected through smart, accessible technology.</p>
    </div>
  </div>

  <!-- What We Offer -->
  <div class="about-sec-head sr"><div class="tag"><i class="fa-solid fa-hand-holding-medical"></i> What We Offer</div><h2>Built for Everyone in Healthcare</h2><p>Tools designed for patients, doctors, and administrators alike.</p></div>
  <div class="offer-grid">
    <div class="offer-card sr"><div class="o-icon" style="background:linear-gradient(135deg,#3D6A8A,#5BA3C9)"><i class="fa-solid fa-laptop-medical"></i></div><h4>Online Booking</h4><p>Book appointments 24/7 from anywhere — phone, tablet, or desktop.</p></div>
    <div class="offer-card sr sr-d1"><div class="o-icon" style="background:linear-gradient(135deg,#059669,#34d399)"><i class="fa-solid fa-calendar-days"></i></div><h4>Doctor Scheduling</h4><p>Doctors manage slots, breaks, and availability with intuitive controls.</p></div>
    <div class="offer-card sr sr-d2"><div class="o-icon" style="background:linear-gradient(135deg,#D97706,#FBBF24)"><i class="fa-solid fa-chart-pie"></i></div><h4>Admin Analytics</h4><p>Track appointments, no-shows, trends, and clinic performance at a glance.</p></div>
    <div class="offer-card sr sr-d3"><div class="o-icon" style="background:linear-gradient(135deg,#DC2626,#F87171)"><i class="fa-solid fa-bell"></i></div><h4>Reminders &amp; Alerts</h4><p>Automated email notifications for confirmations, reminders, and updates.</p></div>
    <div class="offer-card sr sr-d4"><div class="o-icon" style="background:linear-gradient(135deg,#7C3AED,#A78BFA)"><i class="fa-solid fa-clipboard-list"></i></div><h4>Patient Records</h4><p>Secure digital records with diagnoses, prescriptions, and histories.</p></div>
    <div class="offer-card sr sr-d5"><div class="o-icon" style="background:linear-gradient(135deg,#0891B2,#22D3EE)"><i class="fa-solid fa-star"></i></div><h4>Feedback System</h4><p>Patients rate visits and leave reviews — driving quality improvements.</p></div>
  </div>

  <!-- Values -->
  <div class="about-sec-head sr"><div class="tag"><i class="fa-solid fa-gem"></i> Core Values</div><h2>What Drives Us</h2></div>
  <div class="values-grid">
    <div class="value-card sr"><span class="v-emoji">🩺</span><h4>Patient First</h4><p>Every feature starts with the patient experience.</p></div>
    <div class="value-card sr sr-d1"><span class="v-emoji">⚡</span><h4>Speed</h4><p>Reduce wait times and streamline workflows.</p></div>
    <div class="value-card sr sr-d2"><span class="v-emoji">🔒</span><h4>Security</h4><p>Data protection is a core requirement, not an afterthought.</p></div>
    <div class="value-card sr sr-d3"><span class="v-emoji">♿</span><h4>Accessibility</h4><p>Healthcare tools everyone can use, on any device.</p></div>
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
    <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-arrow-right"></i> Create Your Account</a>
  </div>
</div>

<script>
document.querySelectorAll('.sr').forEach(function(el){
  new IntersectionObserver(function(es,o){
    if(es[0].isIntersecting){es[0].target.classList.add('vis');o.unobserve(es[0].target)}
  },{threshold:.1,rootMargin:'0px 0px -30px 0px'}).observe(el);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>