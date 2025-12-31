<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';

$database = new Database();
$db = $database->getConnection();

if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle status post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_status') {
    $status_content = trim($_POST['status_content'] ?? '');

    if (!empty($status_content) && strlen($status_content) <= 500) {
        try {
            $stmt = $db->prepare("INSERT INTO status_updates (user_id, content, created_at) VALUES (:user_id, :content, NOW())");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':content', $status_content);
            $stmt->execute();

            header('Location: dashboard.php');
            exit();
        } catch(PDOException $e) {
            error_log("Status post error: " . $e->getMessage());
        }
    }
}

// Fetch user info
try {
    $stmt = $db->prepare("SELECT username, email, avatar, created_at FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $user = ['username' => $username, 'email' => '', 'avatar' => null];
}

// Fetch activity feed (combined from multiple sources)
$feed_items = [];

try {
    // Get status updates
    $stmt = $db->query("
        SELECT 
            'status' as type,
            s.id,
            s.content,
            s.created_at,
            u.username,
            u.avatar,
            u.id as user_id
        FROM status_updates s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 20
    ");
    $feed_items = array_merge($feed_items, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Get new listings
    $stmt = $db->query("
        SELECT 
            'listing' as type,
            l.id,
            l.title as content,
            l.description,
            l.created_at,
            u.username,
            u.avatar,
            u.id as user_id,
            l.category,
            l.location
        FROM listings l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE l.status = 'active'
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    $feed_items = array_merge($feed_items, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Get forum threads
    $stmt = $db->query("
        SELECT 
            'forum' as type,
            t.id,
            t.title as content,
            t.slug,
            t.created_at,
            u.username,
            u.avatar,
            u.id as user_id,
            c.name as category_name
        FROM forum_threads t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN forum_categories c ON t.category_id = c.id
        WHERE t.is_active = 1
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $feed_items = array_merge($feed_items, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Get marketplace items
    $stmt = $db->query("
        SELECT 
            'marketplace' as type,
            m.id,
            m.title as content,
            m.description,
            m.price,
            m.created_at,
            u.username,
            u.avatar,
            u.id as user_id
        FROM marketplace_items m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.status = 'active'
        ORDER BY m.created_at DESC
        LIMIT 10
    ");
    $feed_items = array_merge($feed_items, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Sort all items by created_at
    usort($feed_items, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Limit to 30 items
    $feed_items = array_slice($feed_items, 0, 30);

} catch(PDOException $e) {
    error_log("Feed fetch error: " . $e->getMessage());
    $feed_items = [];
}

// Helper function for time ago
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y';
    if ($diff->m > 0) return $diff->m . 'mo';
    if ($diff->d > 0) return $diff->d . 'd';
    if ($diff->h > 0) return $diff->h . 'h';
    if ($diff->i > 0) return $diff->i . 'm';
    return 'now';
}

$page_title = "Dashboard - Basehit";
include 'views/header.php';
?>

<!-- Main Content -->
<div class="bg-gh-bg min-h-screen py-6">
    <div class="mx-auto max-w-6xl px-4">

        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Left Sidebar - User Profile Card -->
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">

                    <!-- Profile Card -->
                    <div class="rounded-lg border border-gh-border bg-gh-panel p-6">
                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-2xl font-bold text-white">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-white text-lg"><?php echo htmlspecialchars($user['username']); ?></h3>
                                <p class="text-sm text-gh-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-gh-muted">
                                <i class="bi bi-calendar3"></i>
                                <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gh-border">
                            <a href="profile.php" class="flex items-center justify-center gap-2 rounded-lg border border-gh-border bg-gh-bg px-4 py-2 text-sm font-semibold text-white transition-all hover:border-gh-accent">
                                <i class="bi bi-person"></i>
                                View Profile
                            </a>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="rounded-lg border border-gh-border bg-gh-panel p-4">
                        <h4 class="mb-3 font-bold text-white">Quick Links</h4>
                        <div class="space-y-2">
                            <a href="post-ad.php" class="flex items-center gap-2 rounded-lg bg-gh-bg px-3 py-2 text-sm text-gh-muted transition-colors hover:bg-gh-panel2 hover:text-white">
                                <i class="bi bi-plus-circle text-green-500"></i>
                                Post Ad
                            </a>
                            <a href="browse.php" class="flex items-center gap-2 rounded-lg bg-gh-bg px-3 py-2 text-sm text-gh-muted transition-colors hover:bg-gh-panel2 hover:text-white">
                                <i class="bi bi-search text-blue-500"></i>
                                Browse Listings
                            </a>
                            <a href="forum.php" class="flex items-center gap-2 rounded-lg bg-gh-bg px-3 py-2 text-sm text-gh-muted transition-colors hover:bg-gh-panel2 hover:text-white">
                                <i class="bi bi-chat-dots text-purple-500"></i>
                                Forum
                            </a>
                            <a href="marketplace.php" class="flex items-center gap-2 rounded-lg bg-gh-bg px-3 py-2 text-sm text-gh-muted transition-colors hover:bg-gh-panel2 hover:text-white">
                                <i class="bi bi-shop text-yellow-500"></i>
                                Marketplace
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Main Feed -->
            <div class="lg:col-span-2">

                <!-- Status Post Box -->
                <div class="mb-6 rounded-lg border border-gh-border bg-gh-panel p-4">
                    <form method="POST" action="dashboard.php">
                        <input type="hidden" name="action" value="post_status">
                        <div class="flex gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <textarea 
                                    name="status_content"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="What's on your mind?"
                                    class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-white placeholder-gh-muted transition-colors focus:border-gh-accent focus:outline-none focus:ring-2 focus:ring-gh-accent/20 resize-none"
                                    required
                                ></textarea>
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex gap-2">
                                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-gh-muted transition-colors hover:bg-gh-bg hover:text-gh-accent" title="Add photo">
                                            <i class="bi bi-image"></i>
                                        </button>
                                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-gh-muted transition-colors hover:bg-gh-bg hover:text-gh-accent" title="Add emoji">
                                            <i class="bi bi-emoji-smile"></i>
                                        </button>
                                    </div>
                                    <button 
                                        type="submit"
                                        class="rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-2 text-sm font-bold text-white transition-all hover:brightness-110"
                                    >
                                        Post
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Feed Tabs -->
                <div class="mb-4 flex gap-2 border-b border-gh-border">
                    <button class="border-b-2 border-gh-accent px-4 py-3 text-sm font-semibold text-white">
                        For You
                    </button>
                    <button class="px-4 py-3 text-sm font-semibold text-gh-muted transition-colors hover:text-white">
                        Following
                    </button>
                </div>

                <!-- Activity Feed -->
                <div class="space-y-4">
                    <?php if (empty($feed_items)): ?>
                        <div class="rounded-lg border border-gh-border bg-gh-panel p-12 text-center">
                            <i class="bi bi-inbox text-5xl text-gh-muted mb-3"></i>
                            <h3 class="mb-2 text-lg font-bold text-white">No Activity Yet</h3>
                            <p class="text-gh-muted mb-4">Start by posting a status or browsing listings</p>
                            <a href="browse.php" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-2 text-sm font-bold text-white transition-all hover:brightness-110">
                                <i class="bi bi-search"></i>
                                Browse Listings
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feed_items as $item): ?>
                            <?php
                            $time_ago = time_elapsed_string($item['created_at']);
                            $item_username = htmlspecialchars($item['username']);
                            ?>

                            <!-- Feed Item -->
                            <div class="rounded-lg border border-gh-border bg-gh-panel p-4 transition-all hover:border-gh-accent">
                                <div class="flex gap-3">
                                    <!-- Avatar -->
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white">
                                        <?php echo strtoupper(substr($item_username, 0, 1)); ?>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <!-- Header -->
                                        <div class="mb-2 flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-white"><?php echo $item_username; ?></span>

                                            <?php if ($item['type'] === 'status'): ?>
                                                <span class="text-gh-muted">posted a status</span>
                                            <?php elseif ($item['type'] === 'listing'): ?>
                                                <span class="text-gh-muted">posted a listing</span>
                                                <span class="rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-semibold text-green-400">
                                                    <i class="bi bi-bookmark-fill"></i> Listing
                                                </span>
                                            <?php elseif ($item['type'] === 'forum'): ?>
                                                <span class="text-gh-muted">created a thread</span>
                                                <span class="rounded-full bg-blue-500/20 px-2 py-0.5 text-xs font-semibold text-blue-400">
                                                    <i class="bi bi-chat-dots-fill"></i> Forum
                                                </span>
                                            <?php elseif ($item['type'] === 'marketplace'): ?>
                                                <span class="text-gh-muted">listed an item</span>
                                                <span class="rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs font-semibold text-yellow-400">
                                                    <i class="bi bi-shop"></i> Marketplace
                                                </span>
                                            <?php endif; ?>

                                            <span class="text-gh-muted">· <?php echo $time_ago; ?></span>
                                        </div>

                                        <!-- Content -->
                                        <div class="mb-3">
                                            <?php if ($item['type'] === 'status'): ?>
                                                <p class="text-white leading-relaxed"><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>

                                            <?php elseif ($item['type'] === 'listing'): ?>
                                                <a href="listing.php?id=<?php echo $item['id']; ?>" class="block group">
                                                    <h4 class="font-bold text-white group-hover:text-gh-accent mb-1"><?php echo htmlspecialchars($item['content']); ?></h4>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <p class="text-sm text-gh-muted line-clamp-2"><?php echo htmlspecialchars(substr($item['description'], 0, 150)); ?>...</p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['location'])): ?>
                                                        <p class="mt-1 text-xs text-gh-muted">
                                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($item['location']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </a>

                                            <?php elseif ($item['type'] === 'forum'): ?>
                                                <a href="forum-thread.php?slug=<?php echo htmlspecialchars($item['slug']); ?>" class="block group">
                                                    <h4 class="font-bold text-white group-hover:text-gh-accent"><?php echo htmlspecialchars($item['content']); ?></h4>
                                                    <?php if (!empty($item['category_name'])): ?>
                                                        <p class="mt-1 text-xs text-gh-muted">in <?php echo htmlspecialchars($item['category_name']); ?></p>
                                                    <?php endif; ?>
                                                </a>

                                            <?php elseif ($item['type'] === 'marketplace'): ?>
                                                <a href="marketlisting.php?id=<?php echo $item['id']; ?>" class="block group">
                                                    <h4 class="font-bold text-white group-hover:text-gh-accent mb-1"><?php echo htmlspecialchars($item['content']); ?></h4>
                                                    <?php if (!empty($item['price'])): ?>
                                                        <p class="text-lg font-bold text-green-500"><?php echo htmlspecialchars($item['price']); ?></p>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex gap-4 text-gh-muted">
                                            <button class="flex items-center gap-1 text-sm transition-colors hover:text-blue-400">
                                                <i class="bi bi-chat"></i>
                                                <span>Reply</span>
                                            </button>
                                            <button class="flex items-center gap-1 text-sm transition-colors hover:text-green-400">
                                                <i class="bi bi-arrow-repeat"></i>
                                                <span>Share</span>
                                            </button>
                                            <button class="flex items-center gap-1 text-sm transition-colors hover:text-red-400">
                                                <i class="bi bi-heart"></i>
                                                <span>Like</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Load More -->
                <?php if (count($feed_items) >= 30): ?>
                    <div class="mt-6 text-center">
                        <button class="rounded-lg border border-gh-border bg-gh-panel px-6 py-3 text-sm font-semibold text-white transition-all hover:border-gh-accent hover:bg-gh-panel2">
                            Load More
                        </button>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php include 'views/footer.php'; ?>