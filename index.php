<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Location.php';
require_once 'includes/maintenance_check.php';

$database = new Database();
$db = $database->getConnection();
$location = new Location($db);

if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

// Get featured cities
$featured_cities = [];
try {
    $query = "SELECT c.*, s.abbreviation as state_abbr,
              (SELECT COUNT(*) FROM listings WHERE city_id = c.id AND status = 'active') as listing_count
              FROM cities c
              LEFT JOIN states s ON c.state_id = s.id
              ORDER BY listing_count DESC
              LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_cities = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

// Get recent listings
$recent_listings = [];
try {
    $query = "SELECT l.*, c.name as city_name, s.abbreviation as state_abbr,
              cat.name as category_name
              FROM listings l
              LEFT JOIN cities c ON l.city_id = c.id
              LEFT JOIN states s ON c.state_id = s.id
              LEFT JOIN categories cat ON l.category_id = cat.id
              WHERE l.status = 'active'
              ORDER BY l.created_at DESC
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_listings = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

// Get stats
try {
    $stats_query = "SELECT 
                    (SELECT COUNT(*) FROM listings WHERE status = 'active') as total_listings,
                    (SELECT COUNT(DISTINCT city_id) FROM listings WHERE status = 'active') as active_cities,
                    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_users_today";
    $stats_stmt = $db->query($stats_query);
    $stats = $stats_stmt->fetch();
} catch(PDOException $e) {
    $stats = ['total_listings' => 0, 'active_cities' => 0, 'new_users_today' => 0];
}

include 'views/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Pacifico&display=swap');

/* --- State cards on choose-location page --- */
.state-card-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.state-card-grid a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 14px;
  border: 1px solid #444;
  border-radius: 4px; /* 0 if you want perfectly sharp corners */
  background-color: #111;
  color: #fff;
  text-decoration: none;
  font-size: 13px;
  min-width: 48px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.4);
}

.state-card-grid a:hover {
  background-color: #e11d48;
  border-color: #fb7185;
  color: #fff;
}
</style>

<!-- Hero Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-purple-950 via-pink-950 to-red-950 py-12">
  <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDEzNGg3djdjMCAxLjEwNS0uODk1IDItMiAyaC0zYy0xLjEwNSAwLTItLjg5NS0yLTJ2LTd6bTAtMTRoN3Y3YzAgMS4xMDUtLjg5NSAyLTIgMmgtM2MtMS4xMDUgMC0yLS44OTUtMi0ydi03eiIvPjwvZz48L2c+PC9zdmc+')] opacity-10"></div>

  <div class="relative mx-auto max-w-6xl px-4">
    <!-- Logo -->
    <div class="mb-6 text center">
      <img src="assets/images/basehit_logo.png" alt="Basehit" class="mx-auto mb-3 h-68 md:h-84">
      <p class="text-base font-semibold text-white-200 sm:text-lg">#1 Hookup Personals & Adult Content Marketplace</p>
    </div>

    <!-- Quick Stats -->
    <div class="mb-6 grid grid-cols-3 gap-3 text-center">
      <div class="rounded-xl bg-white/10 p-3 backdrop-blur-sm">
        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['total_listings']); ?></div>
        <div class="text-xs text-pink-200">Active Ads</div>
      </div>
      <div class="rounded-xl bg-white/10 p-3 backdrop-blur-sm">
        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['active_cities']); ?>+</div>
        <div class="text-xs text-pink-200">Cities</div>
      </div>
      <div class="rounded-xl bg-white/10 p-3 backdrop-blur-sm">
        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['new_users_today']); ?></div>
        <div class="text-xs text-pink-200">New Today</div>
      </div>
    </div>

    <!-- CTA Buttons -->
    <div class="flex flex-col gap-2 sm:flex-row sm:justify-center">
      <a href="choose-location.php" 
         class="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110">
        <i class="bi bi-geo-alt-fill"></i>
        Browse Personals
        <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
      </a>
      <a href="post-ad.php" 
         class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-white/30 bg-white/10 px-6 py-3 text-sm font-bold text-white backdrop-blur-sm transition-all hover:bg-white/20">
        <i class="bi bi-plus-circle-fill"></i>
        Post Free Ad
      </a>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="bg-gh-bg py-8">
  <div class="mx-auto max-w-6xl px-4">

    <!-- Feature Cards -->
    <div class="mb-8 grid gap-4 md:grid-cols-3">

      <!-- Stories Card -->
      <a href="story.php" 
         class="group relative overflow-hidden rounded-lg border border-pink-500/30 bg-gradient-to-br from-pink-600/20 to-purple-600/20 p-4 transition-all hover:border-pink-500 hover:shadow-lg hover:shadow-pink-500/30">
        <div class="absolute -right-4 -top-4 text-6xl opacity-10">📖</div>
        <div class="relative">
          <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-pink-500 text-lg text-white shadow-lg">
            <i class="bi bi-book-fill"></i>
          </div>
          <h3 class="mb-1 text-lg font-bold text-white">Lusterotic Stories</h3>
          <p class="mb-3 text-sm text-pink-200">Real hookup experiences</p>
          <div class="flex items-center gap-1 text-xs font-semibold text-pink-300">
            Read Stories
            <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
          </div>
        </div>
      </a>

      <!-- Marketplace Card -->
      <a href="marketplace.php" 
         class="group relative overflow-hidden rounded-lg border border-yellow-500/30 bg-gradient-to-br from-yellow-600/20 to-orange-600/20 p-4 transition-all hover:border-yellow-500 hover:shadow-lg hover:shadow-yellow-500/30">
        <div class="absolute -right-4 -top-4 text-6xl opacity-10">⭐</div>
        <div class="relative">
          <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500 text-lg text-white shadow-lg">
            <i class="bi bi-star-fill"></i>
          </div>
          <h3 class="mb-1 text-lg font-bold text-white">Creator Marketplace</h3>
          <p class="mb-3 text-sm text-yellow-200">Monetize your content</p>
          <div class="inline-flex items-center gap-1 rounded-full bg-yellow-500 px-2 py-0.5 text-xs font-bold text-black">
            NEW
          </div>
        </div>
      </a>

      <!-- Premium Card -->
      <a href="upgrade.php" 
         class="group relative overflow-hidden rounded-lg border border-purple-500/30 bg-gradient-to-br from-purple-600/20 to-blue-600/20 p-4 transition-all hover:border-purple-500 hover:shadow-lg hover:shadow-purple-500/30">
        <div class="absolute -right-4 -top-4 text-6xl opacity-10">💎</div>
        <div class="relative">
          <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500 text-lg text-white shadow-lg">
            <i class="bi bi-gem"></i>
          </div>
          <h3 class="mb-1 text-lg font-bold text-white">Go Premium</h3>
          <p class="mb-3 text-sm text-purple-200">Unlock exclusive features</p>
          <div class="flex items-center gap-1 text-xs font-semibold text-purple-300">
            Upgrade Now
            <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
          </div>
        </div>
      </a>
    </div>

    <!-- Featured Cities -->
    <div class="mb-8">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">
          <i class="bi bi-fire-fill mr-2 text-orange-500"></i>
          Hottest Cities
        </h2>
        <a href="choose-location.php" class="text-sm text-gh-accent transition-colors hover:text-gh-success">
          View All <i class="bi bi-arrow-right ml-1"></i>
        </a>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach($featured_cities as $city): ?>
          <a href="city.php?location=<?php echo htmlspecialchars($city['slug']); ?>"
             class="group rounded-lg border border-gh-border bg-gh-panel p-3 transition-all hover:border-gh-accent hover:shadow-lg hover:shadow-gh-accent/20">
            <div class="mb-2 flex items-center gap-2">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-gh-accent to-gh-success text-sm text-white">
                <i class="bi bi-geo-alt-fill"></i>
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-bold text-white group-hover:text-gh-accent">
                  <?php echo htmlspecialchars($city['name']); ?>
                </div>
                <div class="text-xs text-gh-muted"><?php echo htmlspecialchars($city['state_abbr']); ?></div>
              </div>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gh-muted">Active Ads</span>
              <span class="font-bold text-gh-accent"><?php echo number_format($city['listing_count']); ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Recent Listings -->
    <div class="mb-8">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">
          <i class="bi bi-clock-history mr-2 text-blue-500"></i>
          Recent Posts
        </h2>
        <a href="browse.php" class="text-sm text-gh-accent transition-colors hover:text-gh-success">
          View All <i class="bi bi-arrow-right ml-1"></i>
        </a>
      </div>

      <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach($recent_listings as $listing): ?>
          <a href="listing.php?id=<?php echo $listing['id']; ?>"
             class="group rounded-lg border border-gh-border bg-gh-panel p-3 transition-all hover:border-gh-accent hover:shadow-lg">
            <div class="mb-2 flex items-start justify-between gap-2">
              <h3 class="line-clamp-2 flex-1 text-sm font-bold text-white group-hover:text-gh-accent">
                <?php echo htmlspecialchars($listing['title']); ?>
              </h3>
              <button class="shrink-0 text-gh-muted transition-colors hover:text-yellow-500">
                <i class="bi bi-star text-sm"></i>
              </button>
            </div>

            <div class="mb-2 flex flex-wrap gap-2 text-xs text-gh-muted">
              <?php if($listing['city_name']): ?>
                <span class="inline-flex items-center gap-1">
                  <i class="bi bi-geo-alt-fill"></i>
                  <?php echo htmlspecialchars($listing['city_name']); ?>, <?php echo htmlspecialchars($listing['state_abbr']); ?>
                </span>
              <?php endif; ?>

              <?php if($listing['category_name']): ?>
                <span class="inline-flex items-center gap-1">
                  <i class="bi bi-tag-fill"></i>
                  <?php echo htmlspecialchars($listing['category_name']); ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-xs text-gh-muted">
                <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
              </span>
              <span class="inline-flex items-center gap-1 rounded-full bg-gh-accent/20 px-2 py-0.5 text-xs font-semibold text-gh-accent">
                View
                <i class="bi bi-arrow-right"></i>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- How It Works -->
    <div class="mb-8">
      <h2 class="mb-6 text-center text-2xl font-bold text-white">How It Works</h2>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="text-center">
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-xl font-bold text-white shadow-lg">
            1
          </div>
          <h3 class="mb-1 text-base font-bold text-white">Choose Location</h3>
          <p class="text-sm text-gh-muted">Select your city</p>
        </div>

        <div class="text-center">
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 text-xl font-bold text-white shadow-lg">
            2
          </div>
          <h3 class="mb-1 text-base font-bold text-white">Post or Browse</h3>
          <p class="text-sm text-gh-muted">Create ad or browse</p>
        </div>

        <div class="text-center">
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-600 text-xl font-bold text-white shadow-lg">
            3
          </div>
          <h3 class="mb-1 text-base font-bold text-white">Connect Safely</h3>
          <p class="text-sm text-gh-muted">Message members</p>
        </div>
      </div>
    </div>

    <!-- Browse by State (rectangular cards) -->
    <div class="mb-8">
      <h2 class="mb-4 text-xl font-bold text-white">
        <i class="bi bi-map-fill mr-2 text-green-500"></i>
        Browse by State
      </h2>

      <div class="state-card-grid">
        <?php
        // Example: if you already have an array of states, loop it here.
        // Replace this block with your actual state-output logic if different.
        $states = $db->query("SELECT id, abbreviation FROM states ORDER BY abbreviation ASC")->fetchAll();
        foreach ($states as $state):
        ?>
          <a href="?state=<?php echo (int)$state['id']; ?>">
            <?php echo htmlspecialchars($state['abbreviation']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="rounded-lg border border-gh-accent/30 bg-gradient-to-br from-gh-accent/10 to-gh-success/10 p-6 text-center">
      <h2 class="mb-2 text-2xl font-bold text-white">Ready to Get Started?</h2>
      <p class="mb-4 text-sm text-gh-muted">Join thousands of members connecting every day</p>
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-center">
        <a href="register.php" 
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110">
          <i class="bi bi-person-plus-fill"></i>
          Create Free Account
        </a>
        <a href="choose-location.php" 
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-gh-border bg-gh-panel px-6 py-3 text-sm font-bold text-white transition-all hover:border-gh-accent">
          <i class="bi bi-search"></i>
          Browse as Guest
        </a>
      </div>
    </div>

  </div>
</div>

<?php include 'views/footer.php'; ?>
