<?php
session_start();
include 'views/header-dark.php';
?>

<style>
:root {
  /* GitHub Dark Theme Colors */
  --gh-canvas-default: #0d1117;
  --gh-canvas-overlay: #161b22;
  --gh-canvas-inset: #010409;
  --gh-canvas-subtle: #161b22;

  --gh-fg-default: #e6edf3;
  --gh-fg-muted: #7d8590;
  --gh-fg-subtle: #6e7681;
  --gh-fg-on-emphasis: #ffffff;

  --gh-border-default: #30363d;
  --gh-border-muted: #21262d;
  --gh-border-subtle: #21262d;

  --gh-accent-fg: #2f81f7;
  --gh-accent-emphasis: #1f6feb;
  --gh-accent-muted: rgba(56,139,253,0.4);
  --gh-accent-subtle: rgba(56,139,253,0.15);

  --gh-success-fg: #3fb950;
  --gh-success-emphasis: #238636;

  --gh-attention-fg: #d29922;
  --gh-attention-emphasis: #9e6a03;

  --gh-danger-fg: #f85149;
  --gh-danger-emphasis: #da3633;

  --gh-done-fg: #a371f7;
  --gh-done-emphasis: #8957e5;

  --gh-sponsors-fg: #db61a2;
  --gh-sponsors-emphasis: #bf4b8a;
}

body {
  background: var(--gh-canvas-default);
  color: var(--gh-fg-default);
}

.bg-gh-panel { background-color: var(--gh-canvas-overlay); }
.bg-gh-panel2 { background-color: var(--gh-canvas-subtle); }
.border-gh-border { border-color: var(--gh-border-default); }
.text-gh-fg { color: var(--gh-fg-default); }
.text-gh-muted { color: var(--gh-fg-muted); }
.text-gh-accent { color: var(--gh-accent-fg); }
.text-gh-success { color: var(--gh-success-fg); }
.text-gh-danger { color: var(--gh-danger-fg); }
.text-gh-warning { color: var(--gh-attention-fg); }
.bg-gh-accent { background-color: var(--gh-accent-emphasis); }
.bg-gh-success { background-color: var(--gh-success-emphasis); }
.bg-gh-danger { background-color: var(--gh-danger-emphasis); }
.hover\:text-gh-accent:hover { color: var(--gh-accent-fg); }
.hover\:bg-gh-panel2:hover { background-color: var(--gh-canvas-subtle); }
.divide-gh-border > :not([hidden]) ~ :not([hidden]) { border-color: var(--gh-border-default); }
</style>




<div class="page-content">
    <div class="container-narrow">
        <div class="card">
            <h1>Support Center</h1>
            <p>Welcome to our Support Center. Find answers to common questions or contact us for help.</p>

            <h2>📚 Quick Links</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
                <a href="faq.php" class="card" style="text-decoration: none; text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">❓</div>
                    <h3>FAQ</h3>
                    <p style="color: var(--text-gray);">Frequently Asked Questions</p>
                </a>
                <a href="contact.php" class="card" style="text-decoration: none; text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✉️</div>
                    <h3>Contact Us</h3>
                    <p style="color: var(--text-gray);">Send us a message</p>
                </a>
                <a href="report-abuse.php" class="card" style="text-decoration: none; text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🚨</div>
                    <h3>Report Abuse</h3>
                    <p style="color: var(--text-gray);">Report violations</p>
                </a>
                <a href="safety.php" class="card" style="text-decoration: none; text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🛡️</div>
                    <h3>Safety Tips</h3>
                    <p style="color: var(--text-gray);">Stay safe online</p>
                </a>
            </div>

            <h2>🔧 Common Issues</h2>
            
            <h3>Account Issues</h3>
            <ul>
                <li><a href="faq.php#reset-password">I forgot my password</a></li>
                <li><a href="faq.php#verify-email">How do I verify my email?</a></li>
                <li><a href="faq.php#delete-account">How do I delete my account?</a></li>
                <li><a href="faq.php#update-profile">How do I update my profile?</a></li>
            </ul>

            <h3>Posting & Listings</h3>
            <ul>
                <li><a href="faq.php#create-listing">How do I create a listing?</a></li>
                <li><a href="faq.php#upload-images">How do I upload images?</a></li>
                <li><a href="faq.php#edit-listing">How do I edit my listing?</a></li>
                <li><a href="faq.php#delete-listing">How do I delete a listing?</a></li>
            </ul>

            <h3>Messaging</h3>
            <ul>
                <li><a href="faq.php#send-message">How do I send a message?</a></li>
                <li><a href="faq.php#read-messages">How do I read my messages?</a></li>
                <li><a href="faq.php#block-user">How do I block a user?</a></li>
            </ul>

            <h3>Payments & Subscriptions</h3>
            <ul>
                <li><a href="faq.php#upgrade-account">How do I upgrade my account?</a></li>
                <li><a href="faq.php#cancel-subscription">How do I cancel my subscription?</a></li>
                <li><a href="faq.php#refund-policy">What is your refund policy?</a></li>
                <li><a href="faq.php#featured-ads">How do featured ads work?</a></li>
            </ul>

            <h2>📞 Contact Information</h2>
            <div class="alert alert-info">
                <p><strong>Email:</strong> support@lustifieds.com</p>
                <p><strong>Response Time:</strong> We typically respond within 24-48 hours</p>
                <p><strong>Hours:</strong> Monday - Friday, 9AM - 5PM PST</p>
            </div>

            <h2>🌐 Additional Resources</h2>
            <ul>
                <li><a href="terms.php">Terms of Service</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="safety.php">Safety Guidelines</a></li>
            </ul>

            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <p style="text-align: center;">
                    <a href="contact.php" class="btn-primary" style="margin: 0 0.5rem;">Contact Support</a>
                    <a href="choose-location.php" class="btn-secondary" style="margin: 0 0.5rem;">Back to Home</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>