<?php if (isLoggedIn()): ?>
    </div><!-- /.dashboard-main -->
</div><!-- /.dashboard-wrapper -->
<?php else: ?>
      </div><!-- /.page-content -->
</main>
<?php endif; ?>

<!-- ========== FOOTER ========== -->
<footer class="footer" id="main-footer">
    <div class="footer-container">
        <div class="footer-grid">

            <!-- Brand Column -->
            <div class="footer-col footer-brand">
                <a href="<?= BASE_URL ?>/pages/index.php" class="footer-logo">
                    <span class="logo-icon"><i class="fa-solid fa-plus"></i></span>
                    <span class="logo-text">Medi<span class="logo-highlight">Queue</span></span>
                </a>
                <p class="footer-tagline">Your Health, Our Priority</p>
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-link" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/pages/index.php"><i class="fa-solid fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/patient/book-appointment.php"><i class="fa-solid fa-chevron-right"></i> Book Appointment</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/login.php"><i class="fa-solid fa-chevron-right"></i> Login</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="footer-col">
                <h4 class="footer-heading">Services</h4>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-solid fa-chevron-right"></i> General Medicine</a></li>
                    <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Pediatrics</a></li>
                    <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Dermatology</a></li>
                    <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Online Consultation</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-col">
                <h4 class="footer-heading">Contact Us</h4>
                <ul class="footer-contact">
                    <li><i class="fa-solid fa-location-dot"></i><span>MediQueue Clinic, Quezon City, Philippines</span></li>
                    <li><i class="fa-solid fa-phone"></i><span>(02) 8123-4567</span></li>
                    <li><i class="fa-solid fa-envelope"></i><span>info@mediqueue.com</span></li>
                    <li><i class="fa-solid fa-clock"></i><span>Mon – Sat: 9:00 AM – 5:00 PM</span></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MediQueue. Bulacan State University &ndash; BSIT 2H G2</p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Core JS loaded in header.php -->

<!-- Page-specific JS -->
<?php if (!empty($pageJS)): ?>
  <?php foreach ($pageJS as $js): ?>
  <script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Particle System -->
<script>
(function(){
  var canvas = document.getElementById('particleCanvas');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var particles = [];
  var PARTICLE_COUNT = 70;
  var CONNECTION_DIST = 120;

  function resizeCanvas(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }

  function Particle(){
    this.x = Math.random() * canvas.width;
    this.y = Math.random() * canvas.height;
    this.size = Math.random() * 1.6 + 0.4;
    this.vx = (Math.random() - 0.5) * 0.3;
    this.vy = (Math.random() - 0.5) * 0.3 - 0.06;
    this.opacity = Math.random() * 0.25 + 0.06;
    this.targetO = Math.random() * 0.25 + 0.06;
    this.fadeSpd = Math.random() * 0.005 + 0.002;
    var hue = 200 + Math.random() * 20;
    this.color = 'hsl(' + hue + ', 55%, 84%)';
  }

  Particle.prototype.update = function(){
    this.x += this.vx; this.y += this.vy;
    if (this.opacity < this.targetO) this.opacity += this.fadeSpd;
    else { this.opacity -= this.fadeSpd; if (this.opacity <= 0.04) this.targetO = Math.random() * 0.4 + 0.06; }
    if (this.y < -10) this.y = canvas.height + 10;
    if (this.x < -10) this.x = canvas.width + 10;
    if (this.x > canvas.width + 10) this.x = -10;
    if (this.y > canvas.height + 10) this.y = -10;
  };

  Particle.prototype.draw = function(){
    ctx.save();
    ctx.globalAlpha = Math.max(0, this.opacity);
    ctx.fillStyle = this.color;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  };

  function init(){ particles = []; for (var i = 0; i < PARTICLE_COUNT; i++) particles.push(new Particle()); }

  function drawConnections(){
    for (var i = 0; i < particles.length; i++){
      for (var j = i + 1; j < particles.length; j++){
        var dx = particles[i].x - particles[j].x, dy = particles[i].y - particles[j].y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < CONNECTION_DIST){
          var alpha = (1 - dist / CONNECTION_DIST) * 0.04;
          ctx.save(); ctx.globalAlpha = alpha;
          ctx.strokeStyle = 'rgba(210,235,250,0.6)'; ctx.lineWidth = 0.5;
          ctx.beginPath(); ctx.moveTo(particles[i].x, particles[i].y); ctx.lineTo(particles[j].x, particles[j].y); ctx.stroke();
          ctx.restore();
        }
      }
    }
  }

  function animate(){
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (var i = 0; i < particles.length; i++){ particles[i].update(); particles[i].draw(); }
    drawConnections();
    requestAnimationFrame(animate);
  }

  document.addEventListener('mousemove', function(e){
    for (var i = 0; i < particles.length; i++){
      var p = particles[i];
      var dx = p.x - e.clientX, dy = p.y - e.clientY;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < 80){
        var force = (80 - dist) / 80;
        p.vx += (dx / dist) * force * 0.05;
        p.vy += (dy / dist) * force * 0.05;
        var speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
        if (speed > 1) { p.vx = (p.vx / speed); p.vy = (p.vy / speed); }
      }
    }
  });

  window.addEventListener('resize', function(){ resizeCanvas(); init(); });
  resizeCanvas(); init(); animate();
})();
</script>

<!-- Dark Mode + Animated Counters + Ripple -->
<script>
(function(){
  var THEME_KEY = 'mediqueue-theme';
  var saved = localStorage.getItem(THEME_KEY);
  if (saved === 'dark') document.body.classList.add('dark');

  document.addEventListener('click', function(e){
    var btn = e.target.closest('#dark-mode-toggle, [data-toggle-dark]');
    if (!btn) return;
    document.body.classList.toggle('dark');
    localStorage.setItem(THEME_KEY, document.body.classList.contains('dark') ? 'dark' : 'light');
  });

  function animateCounters(){
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (!entry.isIntersecting) return;
        var el = entry.target;
        if (el.dataset.animated) return;
        el.dataset.animated = '1';
        var target = parseInt(el.dataset.count, 10);
        var duration = 1800, start = performance.now();
        function tick(now){
          var t = Math.min((now - start) / duration, 1);
          t = 1 - Math.pow(1 - t, 3);
          el.textContent = Math.round(target * t).toLocaleString();
          if (t < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
      });
    }, {threshold: 0.3});
    counters.forEach(function(c){ observer.observe(c); });
  }
  animateCounters();

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.btn, .login-btn, .social-btn, .nav-item, .sidebar-item a');
    if (!btn) return;
    var rect = btn.getBoundingClientRect();
    var ripple = document.createElement('span');
    ripple.className = 'ripple-effect';
    var size = Math.max(rect.width, rect.height) * 2;
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
    btn.style.position = btn.style.position || 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', function(){ ripple.remove(); });
  });
})();
</script>

</body>
</html>
