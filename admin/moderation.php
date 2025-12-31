<?php
session_start();
require_once '../config/database.php';
require_once '../classes/ContentModerator.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$moderator = new ContentModerator($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $logId = $_POST['log_id'] ?? 0;
    $action = $_POST['action'];
    $notes = $_POST['admin_notes'] ?? '';

    $moderator->markAsReviewed($logId, $_SESSION['user_id'], $action, $notes);

    // Handle additional actions
    if ($action === 'banned_user' && isset($_POST['user_id'])) {
        $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = :id");
        $stmt->execute(['id' => $_POST['user_id']]);
    } elseif ($action === 'rejected' && isset($_POST['content_type']) && isset($_POST['content_id'])) {
        // Remove/hide the content
        $contentType = $_POST['content_type'];
        $contentId = $_POST['content_id'];

        if ($contentType === 'listing') {
            $stmt = $db->prepare("UPDATE listings SET status = 'rejected', is_deleted = 1 WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'marketplace_listing') {
            $stmt = $db->prepare("UPDATE creator_listings SET status = 'rejected' WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'forum_thread') {
            $stmt = $db->prepare("UPDATE forum_threads SET is_deleted = 1 WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'forum_post') {
            $stmt = $db->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        }
    } elseif ($action === 'approved' && isset($_POST['content_type']) && isset($_POST['content_id'])) {
        // Approve the content
        $contentType = $_POST['content_type'];
        $contentId = $_POST['content_id'];

        if ($contentType === 'listing') {
            $stmt = $db->prepare("UPDATE listings SET status = 'active', moderation_status = 'approved' WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'marketplace_listing') {
            $stmt = $db->prepare("UPDATE creator_listings SET status = 'active', moderation_status = 'approved' WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'forum_thread') {
            $stmt = $db->prepare("UPDATE forum_threads SET moderation_status = 'approved' WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        } elseif ($contentType === 'forum_post') {
            $stmt = $db->prepare("UPDATE forum_posts SET moderation_status = 'approved' WHERE id = :id");
            $stmt->execute(['id' => $contentId]);
        }
    }

    header('Location: moderation.php?success=1');
    exit;
}

// Get stats
$stats = $moderator->getStats();

// Get flagged content
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
$flaggedContent = $moderator->getFlaggedContent($perPage, $offset);

// Get total flagged count
$countQuery = "SELECT COUNT(*) FROM moderation_logs WHERE risk_level IN ('high', 'medium') AND reviewed_at IS NULL";
$totalFlagged = $db->query($countQuery)->fetchColumn();
$totalPages = ceil($totalFlagged / $perPage);

include '../views/header.php';
?>

<style>
    .risk-high { 
        background: rgba(239, 68, 68, 0.1); 
        border-color: rgba(239, 68, 68, 0.3);
    }
    .risk-medium { 
        background: rgba(251, 191, 36, 0.1); 
        border-color: rgba(251, 191, 36, 0.3);
    }
    .risk-low { 
        background: rgba(34, 197, 94, 0.1); 
        border-color: rgba(34, 197, 94, 0.3);
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div class="min-h-screen bg-gh-bg py-6">
    <div class="mx-auto max-w-7xl px-4">

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white">
                    <i class="bi bi-shield-check mr-2 text-gh-accent"></i>
                    AI Content Moderation
                </h1>
                <p class="mt-1 text-sm text-gh-muted">Powered by Perplexity AI</p>
            </div>
            <div class="flex gap-2">
                <a href="moderation-settings.php" class="inline-flex items-center gap-2 rounded-lg border border-gh-border bg-gh-panel px-4 py-2 font-semibold text-gh-fg transition-all hover:border-gh-accent">
                    <i class="bi bi-gear-fill"></i> Settings
                </a>
                <a href="dashboard.php" class="inline-flex items-center gap-2 rounded-lg border border-gh-border bg-gh-panel px-4 py-2 font-semibold text-gh-fg transition-all hover:border-gh-accent">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 rounded-lg border border-green-500/30 bg-green-500/10 p-4">
            <p class="text-sm text-green-500">
                <i class="bi bi-check-circle-fill mr-2"></i>
                Action completed successfully!
            </p>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-gh-border bg-gh-panel p-6">
                <div class="mb-2 text-sm font-semibold text-gh-muted">Total Scans (30d)</div>
                <div class="text-3xl font-bold text-gh-accent"><?php echo number_format($stats['total_scans']); ?></div>
            </div>
            <div class="rounded-lg border border-red-500/30 bg-red-500/10 p-6">
                <div class="mb-2 text-sm font-semibold text-red-500">High Risk</div>
                <div class="text-3xl font-bold text-red-500"><?php echo number_format($stats['high_risk']); ?></div>
            </div>
            <div class="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-6">
                <div class="mb-2 text-sm font-semibold text-yellow-500">Medium Risk</div>
                <div class="text-3xl font-bold text-yellow-500"><?php echo number_format($stats['medium_risk']); ?></div>
            </div>
            <div class="rounded-lg border border-green-500/30 bg-green-500/10 p-6">
                <div class="mb-2 text-sm font-semibold text-green-500">Low Risk</div>
                <div class="text-3xl font-bold text-green-500"><?php echo number_format($stats['low_risk']); ?></div>
            </div>
            <div class="rounded-lg border border-purple-500/30 bg-purple-500/10 p-6">
                <div class="mb-2 text-sm font-semibold text-purple-500">Pending Review</div>
                <div class="text-3xl font-bold text-purple-400"><?php echo number_format($stats['pending']); ?></div>
            </div>
        </div>

        <!-- Flagged Content List -->
        <div class="rounded-lg border border-gh-border bg-gh-panel">
            <div class="border-b border-gh-border p-6">
                <h2 class="text-xl font-bold text-white">
                    <i class="bi bi-flag-fill mr-2 text-red-500"></i>
                    Flagged Content Requiring Review (<?php echo number_format($totalFlagged); ?>)
                </h2>
            </div>

            <div class="p-6">
                <?php if (empty($flaggedContent)): ?>
                <div class="py-12 text-center">
                    <i class="bi bi-check-circle text-6xl text-green-500 opacity-20"></i>
                    <h3 class="mt-4 text-xl font-bold text-white">All Clear!</h3>
                    <p class="mt-2 text-gh-muted">No flagged content pending review.</p>
                </div>
                <?php else: ?>

                <div class="space-y-4">
                    <?php foreach ($flaggedContent as $item): ?>
                    <div class="risk-<?php echo $item['risk_level']; ?> rounded-lg border p-4">
                        <div class="mb-3 flex flex-wrap items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <!-- Risk Badge -->
                                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold
                                        <?php echo $item['risk_level'] === 'high' ? 'bg-red-500 text-white' : 
                                                   ($item['risk_level'] === 'medium' ? 'bg-yellow-500 text-black' : 'bg-green-500 text-white'); ?>">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <?php echo strtoupper($item['risk_level']); ?> RISK
                                    </span>

                                    <!-- Content Type -->
                                    <span class="inline-flex items-center gap-1 rounded-full border border-gh-border bg-gh-bg px-3 py-1 text-xs font-semibold text-gh-fg">
                                        <i class="bi bi-file-text"></i>
                                        <?php echo str_replace('_', ' ', ucwords($item['content_type'])); ?>
                                    </span>

                                    <!-- Confidence Score -->
                                    <span class="rounded-full border border-gh-border bg-gh-bg px-3 py-1 text-xs font-semibold text-gh-muted">
                                        Confidence: <?php echo number_format($item['confidence'] * 100, 1); ?>%
                                    </span>
                                </div>

                                <div class="mb-2 text-sm text-gh-muted">
                                    <strong>User:</strong> 
                                    <a href="../profile.php?id=<?php echo $item['user_id']; ?>" class="text-gh-accent hover:underline" target="_blank">
                                        <?php echo htmlspecialchars($item['username']); ?>
                                    </a>
                                    (ID: <?php echo $item['user_id']; ?>) | 
                                    <strong>Date:</strong> <?php echo date('M d, Y g:i A', strtotime($item['created_at'])); ?>
                                </div>

                                <!-- Violations -->
                                <?php 
                                $violations = json_decode($item['violations'], true);
                                if (!empty($violations) && is_array($violations)): 
                                ?>
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <?php foreach ($violations as $violation): ?>
                                    <span class="inline-flex items-center gap-1 rounded bg-red-500/20 px-2 py-0.5 text-xs text-red-400">
                                        <i class="bi bi-x-circle"></i>
                                        <?php echo htmlspecialchars($violation); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <!-- AI Reason -->
                                <div class="mb-3 rounded-lg bg-gh-bg p-3">
                                    <div class="mb-1 text-xs font-semibold text-gh-accent">
                                        <i class="bi bi-robot"></i> AI Analysis:
                                    </div>
                                    <p class="text-sm text-gh-fg"><?php echo htmlspecialchars($item['reason']); ?></p>
                                </div>

                                <!-- Content Preview -->
                                <div class="rounded-lg border border-gh-border bg-gh-bg p-3">
                                    <div class="mb-1 flex items-center justify-between">
                                        <div class="text-xs font-semibold text-gh-muted">Content Preview:</div>
                                        <button onclick="toggleContent(<?php echo $item['id']; ?>)" class="text-xs text-gh-accent hover:underline">
                                            Show Full <i class="bi bi-chevron-down" id="toggle-icon-<?php echo $item['id']; ?>"></i>
                                        </button>
                                    </div>
                                    <p class="text-sm text-gh-fg line-clamp-3" id="content-preview-<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['content']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <form method="POST" class="mt-4 border-t border-gh-border pt-4">
                            <input type="hidden" name="log_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $item['user_id']; ?>">
                            <input type="hidden" name="content_type" value="<?php echo $item['content_type']; ?>">
                            <input type="hidden" name="content_id" value="<?php echo $item['content_id']; ?>">

                            <div class="mb-3">
                                <label class="mb-2 block text-sm font-semibold text-gh-fg">
                                    <i class="bi bi-pencil-square"></i> Admin Notes:
                                </label>
                                <textarea name="admin_notes" rows="2" class="w-full rounded-lg border border-gh-border bg-gh-bg px-3 py-2 text-sm text-gh-fg focus:border-gh-accent focus:outline-none" placeholder="Add notes about your decision..."></textarea>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" name="action" value="approved" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:brightness-110">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button type="submit" name="action" value="rejected" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:brightness-110">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                                <button type="submit" name="action" value="banned_user" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:brightness-110" onclick="return confirm('Ban this user permanently?')">
                                    <i class="bi bi-person-x"></i> Ban User
                                </button>
                                <button type="submit" name="action" value="no_action" class="inline-flex items-center gap-2 rounded-lg border border-gh-border bg-gh-bg px-4 py-2 text-sm font-semibold text-gh-fg transition-all hover:border-gh-accent">
                                    <i class="bi bi-skip-forward"></i> No Action
                                </button>
                                <?php
                                $viewUrl = '#';
                                if ($item['content_type'] === 'listing') {
                                    $viewUrl = '../listing.php?id=' . $item['content_id'];
                                } elseif ($item['content_type'] === 'marketplace_listing') {
                                    $viewUrl = '../marketlisting.php?id=' . $item['content_id'];
                                } elseif ($item['content_type'] === 'forum_thread') {
                                    $viewUrl = '../forum-thread.php?id=' . $item['content_id'];
                                } elseif ($item['content_type'] === 'profile') {
                                    $viewUrl = '../profile.php?id=' . $item['user_id'];
                                }
                                ?>
                                <a href="<?php echo $viewUrl; ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gh-border bg-gh-panel px-4 py-2 text-sm font-semibold text-gh-fg transition-all hover:border-gh-accent">
                                    <i class="bi bi-box-arrow-up-right"></i> View
                                </a>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center gap-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="rounded-lg border border-gh-border bg-gh-panel px-4 py-2 font-semibold text-gh-fg hover:border-gh-accent">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="rounded-lg <?php echo $i === $page ? 'bg-gh-accent text-white' : 'border border-gh-border bg-gh-panel text-gh-fg hover:border-gh-accent'; ?> px-4 py-2 font-semibold">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="rounded-lg border border-gh-border bg-gh-panel px-4 py-2 font-semibold text-gh-fg hover:border-gh-accent">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function toggleContent(id) {
    const preview = document.getElementById('content-preview-' + id);
    const icon = document.getElementById('toggle-icon-' + id);

    if (preview.classList.contains('line-clamp-3')) {
        preview.classList.remove('line-clamp-3');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');
    } else {
        preview.classList.add('line-clamp-3');
        icon.classList.remove('bi-chevron-up');
        icon.classList.add('bi-chevron-down');
    }
}
</script>

<?php include '../views/footer.php'; ?>
