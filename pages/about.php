<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ---- ABOUT PAGE STYLES ---- */
.about-hero{padding:60px 0 40px;text-align:center;position:relative}
.about-hero::before{content:'';position:absolute;top:-100px;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(61,106,138,.08) 0%,transparent 70%);pointer-events:none;z-index:0}
.about-hero .hero-icon{width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.6rem;color:#fff;box-shadow:0 8px 30px rgba(61,106,138,.25);position:relative}
.about-hero .hero-icon::after{content:'';position:absolute;inset:-6px;border-radius:24px;border:2px dashed rgba(61,106,138,.15);animation:spin 15s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.about-hero h1{font-size:2.4rem;font-weight:900;color:var(--text-primary);margin-bottom:12px;letter-spacing:-.8px;position:relative;z-index:1}
.about-hero h1 .highlight{color:var(--teal-core)}
.about-hero .subtitle{color:var(--text-muted);max-width:560px;margin:0 auto;font-size:1rem;line-height:1.7;position:relative;z-index:1}

/* Mission/Vision */
.mv-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:48px}
.mv-card{background:var(--card-bg);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.5);border-radius:20px;padding:36px 32px;text-align:center;transition:all .35s cubic-bezier(.22,1,.36,1);position:relative;overflow:hidden}
.mv-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;border-radius:20px 20px 0 0;opacity:0;transition:opacity .35s ease}
.mv-card:hover{transform:translateY(-6px);box-shadow:0 16px 48px rgba(15,35,55,.1)}
.mv-card:hover::before{opacity:1}
.mv-card.mission::before{background:linear-gradient(90deg,#5BA3C9,#3D6A8A)}
.mv-card.vision::before{background:linear-gradient(90deg,#10B981,#059669)}
.mv-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.4rem;color:#fff}
.mv-card h3{font-size:1.15rem;font-weight:800;color:var(--text-primary);margin-bottom:12px}
.mv-card p{color:var(--text-secondary);font-size:.88rem;line-height:1.7;margin:0}

/* Offers Grid */
.offers-section{margin-bottom:48px}
.offers-section .section-title{text-align:center;font-size:1.3rem;font-weight:800;color:var(--text-primary);margin-bottom:8px}
.offers-section .section-sub{text-align:center;color:var(--text-muted);font-size:.88rem;margin-bottom:32px;max-width:460px;margin-left:auto;margin-right:auto}
.offers-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.offer-card{background:var(--card-bg);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.45);border-radius:18px;padding:28px 24px;text-align:center;transition:all .35s cubic-bezier(.22,1,.36,1)}
.offer-card:hover{transform:translateY(-5px);box-shadow:0 12px 36px rgba(15,35,55,.08)}
.offer-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.2rem;color:#fff}
.offer-card h4{font-size:.92rem;font-weight:700;color:var(--text-primary);margin-bottom:6px}
.offer-card p{color:var(--text-muted);font-size:.8rem;line-height:1.6;margin:0}

/* Values Section */
.values-section{margin-bottom:48px}
.values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.value-card{background:var(--card-bg);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.45);border-radius:18px;padding:28px 20px;text-align:center;transition:all .35s ease}
.value-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(15,35,55,.08)}
.value-emoji{font-size:2rem;margin-bottom:12px;display:block}
.value-card h4{font-size:.9rem;font-weight:700;color:var(--text-primary);margin-bottom:6px}
.value-card p{color:var(--text-muted);font-size:.78rem;line-height:1.6;margin:0}

/* Team Section */
.team-section{margin-bottom:48px}
.team-section .section-title{text-align:center;font-size:1.3rem;font-weight:800;color:var(--text-primary);margin-bottom:4px}
.team-section .section-sub{text-align:center;color:var(--text-muted);font-size:.85rem;margin-bottom:28px}
.team-card{background:var(--card-bg);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.5);border-radius:24px;padding:40px 32px;text-align:center;position:relative;overflow:hidden}
.team-card::before{content:'';position:absolute;top:0;left:0;right:0;height:120px;background:linear-gradient(135deg,rgba(61,106,138,.08),rgba(91,163,201,.06));border-radius:24px 24px 0 0}
.team-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border-radius:50px;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);color:#fff;font-size:.82rem;font-weight:700;margin-bottom:20px;position:relative;z-index:1;box-shadow:0 4px 16px rgba(61,106,138,.3)}
.team-members{display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;position:relative;z-index:1}
.team-member{display:flex;flex-direction:column;align-items:center;gap:8px}
.member-avatar{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;color:#fff;border:2px solid rgba(255,255,255,.6);box-shadow:0 4px 12px rgba(15,35,55,.1);transition:all .3s ease}
.member-avatar:hover{transform:translateY(-3px) scale(1.05)}
.member-name{font-size:.72rem;font-weight:600;color:var(--text-secondary);max-width:70px;text-align:center;line-height:1.3}
.team-info{position:relative;z-index:1}
.team-info h3{font-size:1.1rem;font-weight:800;color:var(--text-primary);margin-bottom:6px}
.team-info p{color:var(--text-muted);font-size:.82rem;margin:0 0 4px;line-height:1.5}
.team-course{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:50px;background:rgba(61,106,138,.08);color:var(--teal-core);font-size:.75rem;font-weight:600;margin-top:12px}

/* CTA */
.about-cta{background:var(--card-bg);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.5);border-radius:24px;padding:48px 40px;text-align:center;position:relative;overflow:hidden}
.about-cta::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center bottom,rgba(61,106,138,.06),transparent 70%);pointer-events:none}
.about-cta h2{font-size:1.5rem;font-weight:900;color:var(--text-primary);margin-bottom:10px;position:relative}
.about-cta p{color:var(--text-muted);font-size:.95rem;max-width:420px;margin:0 auto 28px;position:relative}
.about-cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative}
.about-cta-btns .btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:12px;font-weight:700;font-size:.9rem;border:none;cursor:pointer;text-decoration:none;transition:all .3s cubic-bezier(.22,1,.36,1)}
.about-cta-btns .btn-primary{color:#fff;background:linear-gradient(135deg,#5BA3C9,#3D6A8A);box-shadow:0 6px 24px rgba(61,106,138,.3)}
.about-cta-btns .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 36px rgba(61,106,138,.45)}
.about-cta-btns .btn-outline{color:var(--teal-core);background:transparent;border:1.5px solid rgba(61,106,138,.2)}
.about-cta-btns .btn-outline:hover{background:rgba(61,106,138,.06);border-color:rgba(61,106,138,.35)}

/* Scroll reveal */
.about-reveal{opacity:0;transform:translateY(24px);transition:all .6s cubic-bezier(.22,1,.36,1)}
.about-reveal.visible{opacity:1;transform:translateY(0)}
.about-reveal-d1{transition-delay:.08s}.about-reveal-d2{transition-delay:.16s}.about-reveal-d3{transition-delay:.24s}
.about-reveal-d4{transition-delay:.32s}.about-reveal-d5{transition-delay:.4s}

@media(max-width:768px){
  .mv-grid{grid-template-columns:1fr}
  .offers-grid{grid-template-columns:1fr 1fr}
  .values-grid{grid-template-columns:1fr 1fr}
  .about-hero h1{font-size:1.8rem}
  .team-members{gap:12px}
}
@media(max-width:480px){
  .offers-grid{grid-template-columns:1fr}
  .values-grid{grid-template-columns:1fr}
}
</style>

<section style="padding:60px 20px;">
  <div style="max-width:960px;margin:0 auto;">

    <!-- Hero -->
    <div class="about-hero about-reveal">
      <div class="hero-icon"><i class="fa-solid fa-heart-pulse"></i></div>
      <h1>About <span class="highlight">MediQueue</span></h1>
      <p class="subtitle">We're on a mission to eliminate long clinic queues. Modern appointment scheduling that puts patients first and empowers doctors to deliver better care.</p>
    </div>

    <!-- Mission & Vision -->
    <div class="mv-grid">
      <div class="mv-card mission about-reveal">
        <div class="mv-icon" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)"><i class="fa-solid fa-bullseye"></i></div>
        <h3>Our Mission</h3>
        <p>To streamline clinic appointment scheduling by providing a user-friendly platform that reduces wait times, minimizes no-shows, and enhances the overall patient-doctor experience for Philippine clinics.</p>
      </div>
      <div class="mv-card vision about-reveal about-reveal-d1">
        <div class="mv-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-eye"></i></div>
        <h3>Our Vision</h3>
        <p>To become the leading digital solution for Philippine clinics, enabling healthcare providers to deliver efficient, technology-driven services while keeping patients at the center of care.</p>
      </div>
    </div>

    <!-- What We Offer -->
    <div class="offers-section">
      <h2 class="section-title about-reveal">What MediQueue Offers</h2>
      <p class="section-sub about-reveal">Everything you need for modern clinic management, all in one place.</p>
      <div class="offers-grid">
        <div class="offer-card about-reveal">
          <div class="offer-icon" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)"><i class="fa-solid fa-calendar-check"></i></div>
          <h4>Easy Booking</h4>
          <p>Book appointments in just a few clicks with real-time slot availability and instant confirmation emails.</p>
        </div>
        <div class="offer-card about-reveal about-reveal-d1">
          <div class="offer-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706)"><i class="fa-solid fa-bell"></i></div>
          <h4>Smart Notifications</h4>
          <p>Email and in-app notifications keep both patients and doctors informed at every step.</p>
        </div>
        <div class="offer-card about-reveal about-reveal-d2">
          <div class="offer-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="fa-solid fa-shield-halved"></i></div>
          <h4>Secure &amp; Private</h4>
          <p>Built with CSRF protection, bcrypt hashing, and role-based access control from the ground up.</p>
        </div>
        <div class="offer-card about-reveal about-reveal-d3">
          <div class="offer-icon" style="background:linear-gradient(135deg,#3B82F6,#2563EB)"><i class="fa-solid fa-chart-line"></i></div>
          <h4>Admin Analytics</h4>
          <p>Rich dashboard with interactive charts, KPIs, trends, and exportable reports for data-driven decisions.</p>
        </div>
        <div class="offer-card about-reveal about-reveal-d4">
          <div class="offer-icon" style="background:linear-gradient(135deg,#8B5CF6,#7C3AED)"><i class="fa-solid fa-user-doctor"></i></div>
          <h4>Doctor Profiles</h4>
          <p>View specializations, ratings, consultation fees, and available schedule at a glance.</p>
        </div>
        <div class="offer-card about-reveal about-reveal-d5">
          <div class="offer-icon" style="background:linear-gradient(135deg,#EF4444,#DC2626)"><i class="fa-solid fa-file-medical"></i></div>
          <h4>Patient Records</h4>
          <p>Digitized records with diagnosis, prescription, and complete visit history accessible anytime.</p>
        </div>
      </div>
    </div>

    <!-- Core Values -->
    <div class="values-section">
      <h2 class="section-title about-reveal" style="text-align:center">Our Core Values</h2>
      <p class="section-sub about-reveal" style="text-align:center;color:var(--text-muted);font-size:.88rem;margin-bottom:28px;max-width:420px;margin-left:auto;margin-right:auto">The principles that guide everything we build.</p>
      <div class="values-grid">
        <div class="value-card about-reveal">
          <span class="value-emoji">&#x1F3AF;</span>
          <h4>Patient First</h4>
          <p>Every feature is designed to improve the patient experience and reduce friction.</p>
        </div>
        <div class="value-card about-reveal about-reveal-d1">
          <span class="value-emoji">&#x1F50D;</span>
          <h4>Transparency</h4>
          <p>Clear scheduling, honest wait times, and open communication between all parties.</p>
        </div>
        <div class="value-card about-reveal about-reveal-d2">
          <span class="value-emoji">&#x1F512;</span>
          <h4>Security</h4>
          <p>Your health data is protected with enterprise-grade security practices at every layer.</p>
        </div>
        <div class="value-card about-reveal about-reveal-d3">
          <span class="value-emoji">&#x26A1;</span>
          <h4>Efficiency</h4>
          <p>We eliminate unnecessary steps so clinics can focus on what matters: great care.</p>
        </div>
      </div>
    </div>

    <!-- Team Section -->
    <div class="team-section">
      <h2 class="section-title about-reveal">Meet the Team</h2>
      <p class="section-sub about-reveal">Bulacan State University &mdash; BSIT 2H &bull; IT 211: Web Systems &amp; Technology</p>

      <div class="team-card about-reveal">
        <span class="team-badge"><i class="fa-solid fa-users"></i> Group 7</span>

        <div class="team-members">
          <div class="team-member">
            <div class="member-avatar" style="background:linear-gradient(135deg,#5BA3C9,#3D6A8A)">G7</div>
            <span class="member-name">MediQueue Team</span>
          </div>
        </div>

        <div class="team-info">
          <h3>MediQueue Project</h3>
          <p>An academic capstone project for IT 211 &mdash; Web Systems &amp; Technology, built to demonstrate real-world full-stack development skills.</p>
          <div class="team-course"><i class="fa-solid fa-graduation-cap"></i> BSIT 2H &bull; AY 2025&ndash;2026</div>
        </div>
      </div>
    </div>

    <!-- CTA -->
    <div class="about-cta about-reveal">
      <h2>Want to Experience MediQueue?</h2>
      <p>Create a free account and see how modern clinic scheduling should feel.</p>
      <div class="about-cta-btns">
        <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary"><i class="fa-solid fa-arrow-right"></i> Get Started Free</a>
        <a href="<?= BASE_URL ?>/pages/index.php" class="btn btn-outline"><i class="fa-solid fa-house"></i> Back to Home</a>
      </div>
    </div>

    <p style="text-align:center;color:var(--text-muted);font-size:.75rem;margin-top:32px">&copy; <?= date('Y') ?> MediQueue. Built as an academic project for IT 211.</p>

  </div>
</section>

<script>
(function(){
  var els=document.querySelectorAll('.about-reveal');
  var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});},{threshold:0.1,rootMargin:'0px 0px -30px 0px'});
  els.forEach(function(el){obs.observe(el);});
})();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
