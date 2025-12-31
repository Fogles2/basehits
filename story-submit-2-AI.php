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

$success_message = '';
$error_message = '';

// Initialize AI Content Moderator
$moderationConfig = require 'config/moderation.php';
$moderator = new ContentModerator($moderationConfig['api_key'], $db);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author_name = trim($_POST['author_name'] ?? 'Anonymous');
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? '';
    
    // Validation
    if(empty($title) || empty($category) || empty($content)) {
        $error_message = 'Please fill in all required fields.';
    } elseif(strlen($content) < 100) {
        $error_message = 'Story must be at least 100 characters long.';
    } elseif(strlen($content) > 10000) {
        $error_message = 'Story must be less than 10,000 characters.';
    } else {
        try {
            // AUTO-APPROVE: Status set to 'approved' instead of 'pending'
            $query = "INSERT INTO stories 
                      (title, category, location, content, author_name, age, gender, status, ip_address, created_at) 
                      VALUES 
                      (:title, :category, :location, :content, :author_name, :age, :gender, 'approved', :ip, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':author_name', $author_name);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':gender', $gender);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bindParam(':ip', $ip);
            
            if($stmt->execute()) {
                $success_message = 'Thank you! Your story has been published successfully and is now live!';
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Failed to submit story. Please try again.';
            }
            
        } catch(PDOException $e) {
            error_log("Error submitting story: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again later.';
        }
    }
}

$categories = [
    'hookup' => 'Hookup Stories',
    'first-time' => 'First Time',
    'encounter' => 'Random Encounter',
    'dating' => 'Dating Experience',
    'threesome' => 'Group Experience',
    'casual' => 'Casual Meet',
    'app' => 'App Hookup',
    'other' => 'Other'
];

include 'views/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Pacifico&display=swap');
.char-counter { font-size: 0.875rem; color: var(--gh-muted); margin-top: 0.5rem; }
</style>

<div class="min-h-screen bg-gh-bg py-6">
  <div class="mx-auto max-w-3xl px-4">

    <!-- Header -->
    <div class="mb-8 text-center">
      <div class="mb-3">
        <a href="index.php" class="inline-block">
          <h1 class="bg-gradient-to-r from-gh-accent via-gh-success to-gh-accent bg-clip-text text-4xl font-bold text-transparent sm:text-5xl" style="font-family: 'Pacifico', cursive;">
            Basehit
          </h1>
        </a>
      </div>
      <div class="mb-4 flex items-center justify-center gap-2">
        <i class="bi bi-pencil-square text-2xl text-pink-500"></i>
        <h2 class="text-2xl font-bold text-gh-fg">Share Your Story</h2>
      </div>
      <p class="text-gh-muted">Anonymous submissions welcome. All stories are published instantly!</p>
    </div>

    <?php if($success_message): ?>
      <div class="mb-6 rounded-xl border border-green-500 bg-green-500/10 p-4">
        <div class="flex items-center gap-2 text-green-500">
          <i class="bi bi-check-circle-fill text-xl"></i>
          <span class="font-semibold"><?php echo $success_message; ?></span>
        </div>
        <a href="story.php" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-gh-accent hover:underline">
          View All Stories <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>

    <?php if($error_message): ?>
      <div class="mb-6 rounded-xl border border-red-500 bg-red-500/10 p-4">
        <div class="flex items-center gap-2 text-red-500">
          <i class="bi bi-exclamation-circle-fill text-xl"></i>
          <span class="font-semibold"><?php echo $error_message; ?></span>
        </div>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="" class="space-y-6">
      <div class="rounded-xl border border-gh-border bg-gh-panel p-6">
        
        <div class="mb-4">
          <label for="title" class="mb-2 block font-semibold text-gh-fg">Story Title *</label>
          <input type="text" id="title" name="title" required 
                 value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                 placeholder="Give your story a catchy title"
                 class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label for="category" class="mb-2 block font-semibold text-gh-fg">Category *</label>
            <select id="category" name="category" required
                    class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
              <option value="">Select a category</option>
              <?php foreach($categories as $key => $name): ?>
                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $key) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="location" class="mb-2 block font-semibold text-gh-fg">Location</label>
            <input type="text" id="location" name="location" 
                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                   placeholder="City, State"
                   class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
          </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <div>
            <label for="author_name" class="mb-2 block font-semibold text-gh-fg">Your Name</label>
            <input type="text" id="author_name" name="author_name" 
                   value="<?php echo htmlspecialchars($_POST['author_name'] ?? 'Anonymous'); ?>"
                   placeholder="Anonymous"
                   class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
          </div>

          <div>
            <label for="age" class="mb-2 block font-semibold text-gh-fg">Age</label>
            <input type="number" id="age" name="age" min="18" max="99"
                   value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>"
                   class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
          </div>

          <div>
            <label for="gender" class="mb-2 block font-semibold text-gh-fg">Gender</label>
            <select id="gender" name="gender"
                    class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none">
              <option value="">Prefer not to say</option>
              <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
              <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
              <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <label for="content" class="mb-2 block font-semibold text-gh-fg">Your Story * (100-10,000 characters)</label>
          <textarea id="content" name="content" required rows="15" 
                    placeholder="Share your experience in detail..."
                    class="w-full rounded-lg border border-gh-border bg-gh-bg px-4 py-3 text-gh-fg focus:border-gh-accent focus:outline-none"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
          <div class="char-counter">
            <span id="charCount">0</span> / 10,000 characters
          </div>
        </div>

      </div>

      <div class="flex items-center justify-between gap-4">
        <a href="story.php" class="rounded-lg border border-gh-border bg-gh-panel px-6 py-3 font-semibold text-gh-fg transition-all hover:border-gh-accent">
          Cancel
        </a>
        <button type="submit" 
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-8 py-3 font-semibold text-white shadow-lg transition-all hover:brightness-110">
          <i class="bi bi-send-fill"></i>
          Publish Story
        </button>
      </div>

      <div class="rounded-xl border border-gh-border bg-gh-panel p-4 text-center">
        <i class="bi bi-info-circle text-gh-accent"></i>
        <span class="ml-2 text-sm text-gh-muted">
          Your story will be published instantly and visible to all users.
        </span>
      </div>
    </form>

  </div>
</div>

<script>
const contentField = document.getElementById('content');
const charCount = document.getElementById('charCount');

contentField.addEventListener('input', function() {
    charCount.textContent = this.value.length;
    
    if(this.value.length > 10000) {
        charCount.style.color = '#ef4444';
    } else if(this.value.length < 100) {
        charCount.style.color = '#f59e0b';
    } else {
        charCount.style.color = '#10b981';
    }
});

charCount.textContent = contentField.value.length;
</script>

<?php include 'views/footer.php'; ?>
