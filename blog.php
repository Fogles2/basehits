<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';

$database = new Database();
$db = $database->getConnection();

// Check for maintenance mode
if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

// Sample blog posts (you can later move these to a database)
$blog_posts = [
    [
        'id' => 1,
        'title' => 'Welcome to Basehit!',
        'excerpt' => 'Introducing the best local hookup classifieds platform. Learn how to get started and make the most of your experience.',
        'date' => '2025-01-10',
        'category' => 'Announcements',
        'icon' => '🎉'
    ],
    [
        'id' => 2,
        'title' => 'Safety Tips for Meeting New People',
        'excerpt' => 'Essential safety guidelines to follow when meeting someone from online classifieds. Your safety is our priority.',
        'date' => '2025-01-08',
        'category' => 'Safety',
        'icon' => '🛡️'
    ],
    [
        'id' => 3,
        'title' => 'How to Write a Great Personal Ad',
        'excerpt' => 'Tips and tricks for creating an eye-catching ad that gets responses. Stand out from the crowd!',
        'date' => '2025-01-05',
        'category' => 'Tips',
        'icon' => '✍️'
    ],
    [
        'id' => 4,
        'title' => 'New Features: Premium Membership Benefits',
        'excerpt' => 'Discover all the exclusive features available with premium membership. Upgrade your experience!',
        'date' => '2025-01-03',
        'category' => 'Features',
        'icon' => '💎'
    ]
];

include 'views/header.php';
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
  --gh-success-muted: rgba(46,160,67,0.4);
  --gh-success-subtle: rgba(46,160,67,0.15);

  --gh-attention-fg: #d29922;
  --gh-attention-emphasis: #9e6a03;
  --gh-attention-muted: rgba(187,128,9,0.4);
  --gh-attention-subtle: rgba(187,128,9,0.15);

  --gh-danger-fg: #f85149;
  --gh-danger-emphasis: #da3633;
  --gh-danger-muted: rgba(248,81,73,0.4);
  --gh-danger-subtle: rgba(248,81,73,0.15);

  --gh-done-fg: #a371f7;
  --gh-done-emphasis: #8957e5;

  --gh-sponsors-fg: #db61a2;
  --gh-sponsors-emphasis: #bf4b8a;

  /* Gold Colors */
  --gold-light: #ffd700;
  --gold-medium: #ffb700;
  --gold-dark: #ff8c00;
}

body {
  background: var(--gh-canvas-default);
  color: var(--gh-fg-default);
}

/* Apply GitHub theme colors to existing classes */
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
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
            <h1>Basehit Blog</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem; max-width: 600px; margin: 1rem auto;">
                Tips, updates, and news from the Basehit team
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
            <?php foreach($blog_posts as $post): ?>
            <div class="card" style="display: flex; flex-direction: column;">
                <div style="font-size: 3rem; margin-bottom: 1rem;"><?php echo $post['icon']; ?></div>
                
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                    <span style="background: rgba(66, 103, 245, 0.2); padding: 0.3rem 0.8rem; border-radius: 10px; font-size: 0.85rem; color: var(--primary-blue);">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </span>
                    <span style="color: var(--text-gray); font-size: 0.85rem;">
                        <?php echo date('M j, Y', strtotime($post['date'])); ?>
                    </span>
                </div>
                
                <h3 style="margin-bottom: 1rem; color: var(--primary-blue);">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h3>
                
                <p style="color: var(--text-gray); line-height: 1.8; margin-bottom: 1.5rem; flex-grow: 1;">
                    <?php echo htmlspecialchars($post['excerpt']); ?>
                </p>
                
                <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn-primary">
                    Read More →
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card" style="margin-top: 3rem; text-align: center; background: linear-gradient(135deg, rgba(66, 103, 245, 0.1), rgba(29, 155, 240, 0.1)); border: 2px solid var(--primary-blue);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💌</div>
            <h2 style="margin-bottom: 1rem;">Stay Updated</h2>
            <p style="color: var(--text-gray); max-width: 500px; margin: 0 auto 2rem;">
                Want to get the latest tips, updates, and news? Follow us on social media or check back here regularly!
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="#" class="btn-primary">📘 Facebook</a>
                <a href="#" class="btn-primary">🐦 Twitter</a>
                <a href="#" class="btn-primary">📸 Instagram</a>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>