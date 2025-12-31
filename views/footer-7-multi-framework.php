</main>

<!-- Multi-Framework Footer -->
<footer class="bg-dark text-white mt-5 pt-5 border-top">
  <div class="container">

    <!-- Newsletter Section (Bootstrap Card) -->
    <div class="card bg-secondary mb-5">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-lg-6 mb-3 mb-lg-0">
            <h3 class="card-title h5 mb-2">Stay Updated</h3>
            <p class="card-text text-muted mb-0">Get the latest personals delivered to your inbox</p>
          </div>
          <div class="col-lg-6">
            <form class="row g-2">
              <div class="col-sm-8">
                <input type="email" class="form-control" placeholder="Enter your email" required>
              </div>
              <div class="col-sm-4">
                <button type="submit" class="btn btn-success w-100">Subscribe</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Grid (Bootstrap Grid) -->
    <div class="row g-4 mb-4">

      <!-- Brand Column -->
      <div class="col-md-6 col-lg-3">
        <h2 class="h4 mb-3" style="font-family: 'Brush Script MT', cursive; color: #FFD700;">
          Basehit.io
        </h2>
        <p class="text-muted small mb-3">
          Your trusted platform for authentic personals. Find what you're looking for.
        </p>

        <!-- Social Icons (Tabler Icons) -->
        <div class="d-flex gap-2">
          <a href="#" class="btn btn-icon btn-sm btn-outline-light" data-bs-toggle="tooltip" title="Facebook">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="#" class="btn btn-icon btn-sm btn-outline-light" data-bs-toggle="tooltip" title="Twitter">
            <i class="bi bi-twitter"></i>
          </a>
          <a href="#" class="btn btn-icon btn-sm btn-outline-light" data-bs-toggle="tooltip" title="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="#" class="btn btn-icon btn-sm btn-outline-light" data-bs-toggle="tooltip" title="YouTube">
            <i class="bi bi-youtube"></i>
          </a>
          <a href="#" class="btn btn-icon btn-sm btn-outline-light" data-bs-toggle="tooltip" title="Discord">
            <i class="bi bi-discord"></i>
          </a>
        </div>
      </div>

      <!-- Resources Column -->
      <div class="col-md-6 col-lg-3">
        <h6 class="text-uppercase fw-bold mb-3">Resources</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="how-it-works.php" class="text-decoration-none text-muted"><i class="bi bi-info-circle me-2"></i>How Basehit Works</a></li>
          <li class="mb-2"><a href="safety.php" class="text-decoration-none text-success"><i class="bi bi-shield-check me-2"></i>Safety Tips</a></li>
          <li class="mb-2"><a href="blog.php" class="text-decoration-none text-muted"><i class="bi bi-journal-text me-2"></i>Blog</a></li>
        </ul>
      </div>

      <!-- Support Column -->
      <div class="col-md-6 col-lg-3">
        <h6 class="text-uppercase fw-bold mb-3">Support Center</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="contact.php" class="text-decoration-none text-muted"><i class="bi bi-envelope me-2"></i>Contact Us</a></li>
          <li class="mb-2"><a href="report.php" class="text-decoration-none text-danger"><i class="bi bi-flag-fill me-2"></i>Report Content</a></li>
          <li class="mb-2"><a href="faq.php" class="text-decoration-none text-muted"><i class="bi bi-question-circle me-2"></i>FAQ</a></li>
          <li class="mb-2"><a href="terms.php" class="text-decoration-none text-muted"><i class="bi bi-file-text me-2"></i>Terms of Service</a></li>
          <li class="mb-2"><a href="privacy.php" class="text-decoration-none text-muted"><i class="bi bi-lock me-2"></i>Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Community Column -->
      <div class="col-md-6 col-lg-3">
        <h6 class="text-uppercase fw-bold mb-3">Community</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="forum.php" class="text-decoration-none text-primary"><i class="bi bi-chat-square-text-fill me-2"></i>Forum</a></li>
          <li class="mb-2"><a href="guidelines.php" class="text-decoration-none text-muted"><i class="bi bi-book me-2"></i>Guidelines</a></li>
          <li class="mb-2"><a href="membership.php" class="text-decoration-none text-muted"><i class="bi bi-gem me-2 text-warning"></i>Premium Membership</a></li>
          <li class="mb-2"><a href="bitcoin-wallet.php" class="text-decoration-none text-muted"><i class="bi bi-currency-bitcoin me-2 text-warning"></i>Bitcoin Wallet</a></li>
        </ul>
      </div>
    </div>

    <hr class="border-secondary">

    <!-- Bottom Bar -->
    <div class="row align-items-center py-3">
      <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
        <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> Basehit. All rights reserved.</p>
      </div>
      <div class="col-md-6">
        <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-3 small">
          <a href="sitemap.php" class="text-decoration-none text-muted"><i class="bi bi-diagram-3 me-1"></i>Sitemap</a>
          <span class="text-muted">|</span>
          <a href="choose-location.php" class="text-decoration-none text-muted"><i class="bi bi-geo-alt me-1"></i>Browse Locations</a>
          <span class="text-muted">|</span>
          <a href="api-docs.php" class="text-decoration-none text-muted"><i class="bi bi-code-square me-1"></i>API Docs</a>
        </div>
      </div>
    </div>

    <!-- Trust Badge -->
    <div class="text-center pb-4">
      <span class="badge bg-success-subtle text-success border border-success-subtle">
        <i class="bi bi-shield-check me-1"></i>
        Encrypted & Secure Platform
      </span>
    </div>
  </div>

  <!-- Scroll to Top Button (Flowbite + Bootstrap) -->
  <button 
    id="scrollToTop" 
    class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4 shadow-lg d-none"
    style="bottom: 80px !important; width: 48px; height: 48px;"
    aria-label="Scroll to top"
  >
    <i class="bi bi-arrow-up"></i>
  </button>
</footer>

<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Tabler JS -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>

<!-- Flowbite JS -->
<script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>

<script>
  // Initialize Bootstrap tooltips
  document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });

  // Scroll to Top Button
  const scrollToTopBtn = document.getElementById('scrollToTop');
  if(scrollToTopBtn) {
    window.addEventListener('scroll', () => {
      if(window.scrollY > 300) {
        scrollToTopBtn.classList.remove('d-none');
      } else {
        scrollToTopBtn.classList.add('d-none');
      }
    });

    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // Enable location helper
  function enableLocationFromBanner() {
    if(!navigator.geolocation) {
      alert('Geolocation is not supported by your browser');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        updateLocationSilent(position.coords.latitude, position.coords.longitude, () => {
          location.reload();
        });
      },
      (error) => {
        console.error('Geolocation error:', error);
        alert('Unable to get your location. Please enable location access.');
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  }

  function updateLocationSilent(latitude, longitude, callback) {
    const formData = new FormData();
    formData.append('action', 'update_location');
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    formData.append('autodetected', 'true');

    fetch('api/location.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if(data.success && callback) callback();
    })
    .catch(error => console.error('Error updating location:', error));
  }

  // Theme toggle
  function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    html.setAttribute('data-bs-theme', newTheme);

    fetch('api/update-theme.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ theme: newTheme })
    });
  }
</script>
</body>
</html>