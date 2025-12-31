<?php
session_start();
require_once 'config/database.php';
require_once 'classes/SmartNotifications.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$notifications = new SmartNotifications($db);

// Handle mark as read
if(isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notifications->markAsRead($_GET['id'], $_SESSION['user_id']);
    header('Location: notifications.php');
    exit();
}

// Handle mark all as read
if(isset($_GET['mark_all_read'])) {
    $notifications->markAllAsRead($_SESSION['user_id']);
    header('Location: notifications.php');
    exit();
}

$all_notifications = $notifications->getUserNotifications($_SESSION['user_id'], 50);
$unread_count = $notifications->getUnreadCount($_SESSION['user_id']);

include 'views/header-pink.php';
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>🔔 Notifications</h1>
            <?php if($unread_count > 0): ?>
            <a href="notifications.php?mark_all_read=1" class="btn-secondary btn-small">
                Mark All as Read
            </a>
            <?php endif; ?>
        </div>
        
        <?php if(count($all_notifications) > 0): ?>
        <div class="card">
            <?php foreach($all_notifications as $notif): ?>
            <div class="notification-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); position: relative; <?php echo !$notif['is_read'] ? 'background: rgba(255, 107, 157, 0.1);' : ''; ?>">
                <?php if(!$notif['is_read']): ?>
                <div style="position: absolute; top: 1.5rem; right: 1.5rem; width: 10px; height: 10px; background: var(--accent-rose); border-radius: 50%; box-shadow: 0 0 10px var(--accent-rose);"></div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 1rem; align-items: start;">
                    <div style="font-size: 2.5rem; min-width: 60px; text-align: center;">
                        <?php 
                        $icons = [
                            'message' => '💬',
                            'match' => '💖',
                            'view' => '👁️',
                            'favorite' => '⭐',
                            'badge' => '🏆',
                            'daily_match' => '💕',
                            'nearby' => '📍',
                            'system' => '🔔'
                        ];
                        echo $icons[$notif['notification_type']] ?? '🔔';
                        ?>
                    </div>
                    
                    <div style="flex: 1;">
                        <strong style="color: var(--primary-pink); display: block; margin-bottom: 0.3rem;">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </strong>
                        <p style="color: var(--text-gray); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($notif['body']); ?>
                        </p>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="font-size: 0.85rem; color: var(--text-gray);">
                                <?php echo date('M j, Y g:i A', strtotime($notif['sent_at'])); ?>
                            </span>
                            
                            <?php if($notif['action_url']): ?>
                            <a href="<?php echo htmlspecialchars($notif['action_url']); ?>" class="btn-secondary btn-small" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">
                                View
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!$notif['is_read']): ?>
                            <a href="notifications.php?mark_read=1&id=<?php echo $notif['id']; ?>" style="font-size: 0.85rem; color: var(--primary-pink); text-decoration: none;">
                                Mark as Read
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 5rem; margin-bottom: 1rem;">🔔</div>
            <h2 style="margin-bottom: 1rem;">No Notifications Yet</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">
                When you get new messages, matches, or activity, they'll appear here!
            </p>
            <a href="browse-members.php" class="btn-primary">Start Browsing</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/footer.php'; ?>