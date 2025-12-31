<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Forum.php';
require_once 'includes/ContentModerator.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=forum-create-thread.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$forum = new Forum($db);
$categories = $forum->getCategories();
$preselected_category = (int)($_GET['category'] ?? 0);

$error = '';
$success = '';

// Initialize AI Content Moderator
$moderationConfig = require 'config/moderation.php';
$moderator = new ContentModerator($moderationConfig['api_key'], $db);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    if(empty($title) || empty($content) || empty($category_id)) {
        $error = 'Please fill in all fields';
    } elseif(strlen($title) < 5) {
        $error = 'Title must be at least 5 characters';
    } elseif(strlen($content) < 20) {
        $error = 'Content must be at least 20 characters';
    } else {
        $result = $forum->createThread($_SESSION['user_id'], $category_id, $title, $content);
        if($result['success']) {
            header('Location: forum-thread.php?slug=' . $result['slug']);
            exit();
        } else {
            $error = $result['error'] ?? 'Failed to create thread';
        }
    }
}

include 'views/header.php';
?>

<!-- Hero Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 py-12">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDEzNGg3djdjMCAxLjEwNS0uODk1IDItMiAyaC0zYy0xLjEwNSAwLTItLjg5NS0yLTJ2LTd6bTAtMTRoN3Y3YzAgMS4xMDUtLjg5NSAyLTIgMmgtM2MtMS4xMDUgMC0yLS44OTUtMi0ydi03eiIvPjwvZz48L2c+PC9zdmc+')] opacity-10"></div>
    
    <div class="relative mx-auto max-w-6xl px-4">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-pink-200">
            <a href="forum.php" class="hover:text-white">
                <i class="bi bi-house-fill"></i> Forum
            </a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-white">Create Thread</span>
        </div>

        <div class="text-center">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/10 text-3xl backdrop-blur-sm">
                <i class="bi bi-pencil-square text-pink-300"></i>
            </div>
            <h1 class="mb-2 text-4xl font-bold text-white md:text-5xl">
                Start a New Discussion
            </h1>
            <p class="text-pink-200">Share your thoughts with the community</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="bg-gh-bg py-12">
    <div class="mx-auto max-w-4xl px-4">

        <?php if($error): ?>
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-500/30 bg-red-500/10 p-4">
            <i class="bi bi-exclamation-circle-fill mt-0.5 text-red-500"></i>
            <div>
                <p class="font-semibold text-red-400">Error</p>
                <p class="text-sm text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="rounded-lg border border-gh-border bg-gh-panel p-8">
            <form method="POST" class="space-y-6">
                
                <!-- Category Selection -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        <i class="bi bi-grid-fill mr-1 text-gh-accent"></i>
                        Category
                    </label>
                    <select 
                        name="category_id" 
                        required
                        class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-white transition-colors focus:border-gh-accent focus:outline-none focus:ring-2 focus:ring-gh-accent/20"
                    >
                        <option value="">Select a category...</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $preselected_category == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Title -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        <i class="bi bi-chat-left-text-fill mr-1 text-gh-accent"></i>
                        Thread Title
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        required
                        maxlength="200"
                        value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                        class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-white transition-colors focus:border-gh-accent focus:outline-none focus:ring-2 focus:ring-gh-accent/20"
                        placeholder="What's your discussion about?"
                    >
                    <p class="mt-1 text-xs text-gh-muted">Make it descriptive and engaging (minimum 5 characters)</p>
                </div>

                <!-- Content -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        <i class="bi bi-pen-fill mr-1 text-gh-accent"></i>
                        Content
                    </label>
                    <textarea 
                        name="content" 
                        rows="12" 
                        required
                        class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-white transition-colors focus:border-gh-accent focus:outline-none focus:ring-2 focus:ring-gh-accent/20"
                        placeholder="Share your thoughts, ask a question, or start a discussion..."
                    ><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <p class="mt-1 text-xs text-gh-muted">Minimum 20 characters. Be clear and respectful.</p>
                </div>

                <!-- Forum Guidelines -->
                <div class="rounded-lg border border-blue-500/30 bg-blue-500/10 p-4">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-300">
                        <i class="bi bi-info-circle-fill"></i>
                        Posting Guidelines
                    </h3>
                    <ul class="space-y-1 text-xs text-blue-200">
                        <li>• Be respectful to all community members</li>
                        <li>• Stay on topic and choose the right category</li>
                        <li>• No spam, self-promotion, or inappropriate content</li>
                        <li>• Use clear language and proper formatting</li>
                    </ul>
                </div>

                <!-- Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button 
                        type="submit"
                        class="group flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-8 py-3 font-bold text-white shadow-lg transition-all hover:brightness-110"
                    >
                        <i class="bi bi-send-fill"></i>
                        Create Thread
                        <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
                    </button>
                    <a href="forum.php" class="inline-flex items-center gap-2 rounded-lg border border-gh-border bg-gh-panel px-8 py-3 font-bold text-white transition-all hover:border-gh-accent">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gh-border bg-gh-panel p-4">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <h3 class="mb-1 text-sm font-bold text-white">Engage</h3>
                <p class="text-xs text-gh-muted">Start meaningful conversations</p>
            </div>
            <div class="rounded-lg border border-gh-border bg-gh-panel p-4">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 text-white">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h3 class="mb-1 text-sm font-bold text-white">Respectful</h3>
                <p class="text-xs text-gh-muted">Treat everyone with kindness</p>
            </div>
            <div class="rounded-lg border border-gh-border bg-gh-panel p-4">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 text-white">
                    <i class="bi bi-star-fill"></i>
                </div>
                <h3 class="mb-1 text-sm font-bold text-white">Quality</h3>
                <p class="text-xs text-gh-muted">Post valuable content</p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
