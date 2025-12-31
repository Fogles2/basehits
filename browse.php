<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$city_filter = isset($_GET['city']) ? (int)$_GET['city'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get categories
$categories = [];
try {
    $query = "SELECT * FROM categories ORDER BY name ASC";
    $stmt = $db->query($query);
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

// Build query
$where_clauses = ["l.status = 'active'"];
$params = [];

if($category_filter) {
    $where_clauses[] = "l.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}

if($city_filter) {
    $where_clauses[] = "l.city_id = :city_id";
    $params[':city_id'] = $city_filter;
}

if($search) {
    $where_clauses[] = "(l.title LIKE :search OR l.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch listings
try {
    $query = "SELECT l.*, c.name as city_name, s.abbreviation as state_abbr,
              cat.name as category_name, u.username, u.is_verified, u.is_premium
              FROM listings l
              LEFT JOIN cities c ON l.city_id = c.id
              LEFT JOIN states s ON c.state_id = s.id
              LEFT JOIN categories cat ON l.category_id = cat.id
              LEFT JOIN users u ON l.user_id = u.id
              WHERE $where_sql
              ORDER BY l.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll();

    $count_query = "SELECT COUNT(*) FROM listings l WHERE $where_sql";
    $count_stmt = $db->prepare($count_query);
    foreach($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_count = $count_stmt->fetchColumn();
    $total_pages = ceil($total_count / $per_page);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $listings = [];
    $total_pages = 0;
}

include 'views/header.php';
?>

<div class="min-h-screen bg-gh-bg py-4">
  <div class="mx-auto max-w-6xl px-4">

    <!-- Header -->
    <div class="mb-6">
      <a href="index.php" class="mb-3 inline-flex items-center gap-1 text-xs text-gh-muted transition-colors hover:text-gh-fg">
        <i class="bi bi-arrow-left"></i>
        Back to Home
      </a>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-white">Browse Personals</h1>
          <p class="mt-1 text-sm text-gh-muted">
            <span class="font-semibold text-gh-accent"><?php echo number_format($total_count); ?></span> 
            listings found
          </p>
        </div>

        <a href="post-ad.php" 
           class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-bold text-white transition-all hover:brightness-110">
          <i class="bi bi-plus-circle-fill"></i>
          Post Ad
        </a>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="mb-6 rounded-lg border border-gh-border bg-gh-panel p-4">
      <form method="GET" class="space-y-3">

        <!-- Search Bar -->
        <div class="relative">
          <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-gh-muted"></i>
          <input type="text" 
                 name="search" 
                 value="<?php echo htmlspecialchars($search); ?>"
                 placeholder="Search listings..."
                 class="w-full rounded-lg border border-gh-border bg-gh-panel2 py-2 pl-10 pr-3 text-sm text-gh-fg placeholder-gh-muted transition-all focus:border-gh-accent focus:outline-none">
        </div>

        <!-- Filter Chips -->
        <div class="flex flex-wrap gap-2">
          <select name="category" 
                  onchange="this.form.submit()"
                  class="rounded-lg border border-gh-border bg-gh-panel2 px-3 py-1.5 text-sm text-gh-fg transition-all focus:border-gh-accent focus:outline-none">
            <option value="">All Categories</option>
            <?php foreach($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <?php if($category_filter || $search): ?>
            <a href="browse.php" 
               class="rounded-lg border border-red-500/50 bg-red-500/10 px-3 py-1.5 text-sm font-semibold text-red-500 transition-all hover:bg-red-500 hover:text-white">
              <i class="bi bi-x-circle mr-1"></i>
              Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Listings Grid -->
    <?php if(count($listings) > 0): ?>
      <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach($listings as $listing): ?>
          <article class="group relative overflow-hidden rounded-lg border border-gh-border bg-gh-panel transition-all hover:border-gh-accent hover:shadow-lg">

            <?php if($listing['is_premium']): ?>
              <div class="absolute right-2 top-2 z-10 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 px-2 py-0.5 text-xs font-bold text-white">
                PREMIUM
              </div>
            <?php endif; ?>

            <div class="p-4">
              <div class="mb-3 flex items-start justify-between gap-2">
                <button onclick="openListingModal(<?php echo $listing['id']; ?>)" class="flex-1 text-left">
                  <h3 class="mb-1 line-clamp-2 text-base font-bold text-white transition-colors group-hover:text-gh-accent">
                    <?php echo htmlspecialchars($listing['title']); ?>
                  </h3>
                </button>

                <button onclick="toggleFavorite(<?php echo $listing['id']; ?>)" class="shrink-0 text-lg text-gh-muted transition-all hover:text-yellow-500">
                  <i class="bi bi-star"></i>
                </button>
              </div>

              <div class="mb-2 flex flex-wrap gap-2 text-xs">
                <span class="inline-flex items-center gap-1 rounded-full bg-gh-panel2 px-2 py-0.5 text-gh-muted">
                  <i class="bi bi-geo-alt-fill text-gh-accent"></i>
                  <?php echo htmlspecialchars($listing['city_name']); ?>, <?php echo htmlspecialchars($listing['state_abbr']); ?>
                </span>

                <?php if($listing['age']): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-gh-panel2 px-2 py-0.5 text-gh-muted">
                    <i class="bi bi-calendar"></i>
                    <?php echo $listing['age']; ?>
                  </span>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <span class="inline-flex items-center gap-1 rounded bg-gh-accent/20 px-2 py-0.5 text-xs font-semibold text-gh-accent">
                  <i class="bi bi-tag-fill"></i>
                  <?php echo htmlspecialchars($listing['category_name']); ?>
                </span>
              </div>

              <p class="mb-3 line-clamp-2 text-sm text-gh-muted">
                <?php echo htmlspecialchars(substr($listing['description'], 0, 100)); ?>...
              </p>

              <div class="flex items-center justify-between border-t border-gh-border pt-3">
                <div class="flex items-center gap-2 text-xs text-gh-muted">
                  <?php if($listing['is_verified']): ?>
                    <span class="inline-flex items-center gap-1 text-green-500">
                      <i class="bi bi-patch-check-fill"></i>
                      Verified
                    </span>
                  <?php endif; ?>

                  <span>
                    <i class="bi bi-clock"></i>
                    <?php echo date('M d', strtotime($listing['created_at'])); ?>
                  </span>
                </div>

                <button onclick="openListingModal(<?php echo $listing['id']; ?>)" 
                   class="inline-flex items-center gap-1 text-xs font-semibold text-gh-accent transition-all hover:gap-2">
                  View
                  <i class="bi bi-arrow-right"></i>
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
        <div class="flex items-center justify-center gap-2">
          <?php if($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="rounded-lg border border-gh-border bg-gh-panel px-3 py-1.5 text-sm font-semibold text-gh-fg transition-all hover:border-gh-accent">
              <i class="bi bi-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="<?php echo $i == $page ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white' : 'border border-gh-border bg-gh-panel text-gh-fg hover:border-gh-accent'; ?> rounded-lg px-3 py-1.5 text-sm font-semibold transition-all">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>

          <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="rounded-lg border border-gh-border bg-gh-panel px-3 py-1.5 text-sm font-semibold text-gh-fg transition-all hover:border-gh-accent">
              <i class="bi bi-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Empty State -->
      <div class="rounded-lg border border-gh-border bg-gh-panel p-10 text-center">
        <i class="bi bi-inbox text-5xl text-gh-muted opacity-20"></i>
        <h3 class="mt-3 text-lg font-bold text-white">No Listings Found</h3>
        <p class="mt-1 text-sm text-gh-muted">Try adjusting your filters</p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
          <a href="browse.php" 
             class="rounded-lg border border-gh-border bg-gh-panel2 px-4 py-2 text-sm font-semibold text-gh-fg transition-all hover:border-gh-accent">
            Clear Filters
          </a>
          <a href="post-ad.php" 
             class="rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:brightness-110">
            Post First Ad
          </a>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Listing Modal -->
<div id="listingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
  <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-gh-border bg-gh-bg shadow-2xl">
    <!-- Close Button -->
    <button onclick="closeListingModal()" class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-gh-panel text-gh-muted transition-all hover:bg-red-500 hover:text-white">
      <i class="bi bi-x-lg"></i>
    </button>

    <!-- Loading State -->
    <div id="modalLoading" class="flex items-center justify-center p-12">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gh-accent border-t-transparent"></div>
        <p class="mt-4 text-sm text-gh-muted">Loading...</p>
      </div>
    </div>

    <!-- Content -->
    <div id="modalContent" class="hidden"></div>
  </div>
</div>

<script>
function openListingModal(listingId) {
  const modal = document.getElementById('listingModal');
  const loading = document.getElementById('modalLoading');
  const content = document.getElementById('modalContent');

  modal.classList.remove('hidden');
  modal.classList.add('flex');
  loading.classList.remove('hidden');
  content.classList.add('hidden');
  document.body.style.overflow = 'hidden';

  fetch(`get-listing.php?id=${listingId}`)
    .then(response => response.json())
    .then(data => {
      if(data.error) {
        throw new Error(data.error);
      }

      loading.classList.add('hidden');
      content.classList.remove('hidden');
      content.innerHTML = generateListingHTML(data);
    })
    .catch(error => {
      console.error('Error:', error);
      content.innerHTML = '<div class="p-8 text-center"><p class="text-red-500">Failed to load listing</p></div>';
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    });
}

function closeListingModal() {
  const modal = document.getElementById('listingModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.style.overflow = '';
}

// Close on escape key
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closeListingModal();
});

// Close on backdrop click
document.getElementById('listingModal').addEventListener('click', (e) => {
  if(e.target.id === 'listingModal') closeListingModal();
});

function generateListingHTML(data) {
  const premiumBadge = data.is_premium ? '<span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 px-3 py-1 text-xs font-bold text-white"><i class="bi bi-gem"></i>PREMIUM</span>' : '';

  const verifiedBadge = data.is_verified ? '<span class="inline-flex items-center gap-1 rounded bg-green-500/20 px-3 py-1 text-xs font-semibold text-green-500"><i class="bi bi-patch-check-fill"></i>Verified</span>' : '';

  const ageMeta = data.age ? `<span class="inline-flex items-center gap-1"><i class="bi bi-calendar"></i>${data.age} years old</span>` : '';

  let contactSection = '';
  if(data.is_own_listing) {
    contactSection = `
      <div class="rounded-lg border border-gh-border bg-gh-panel p-3">
        <p class="text-sm text-gh-muted"><i class="bi bi-info-circle mr-1"></i>This is your listing</p>
      </div>`;
  } else if(data.is_logged_in) {
    contactSection = `
      <a href="message.php?to=${data.poster_id}&listing=${data.id}" 
         class="group inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-2.5 text-sm font-bold text-white transition-all hover:brightness-110">
        <i class="bi bi-send-fill"></i>
        Send Message
        <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
      </a>`;
  } else {
    contactSection = `
      <div class="rounded-lg border border-gh-border bg-gh-panel p-3">
        <p class="mb-2 text-xs text-gh-muted">Login to contact this member</p>
        <div class="flex gap-2">
          <a href="login.php" class="rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-semibold text-white hover:brightness-110">Login</a>
          <a href="register.php" class="rounded-lg border border-gh-border bg-gh-panel2 px-4 py-2 text-sm font-semibold text-gh-fg hover:border-gh-accent">Sign Up</a>
        </div>
      </div>`;
  }

  return `
    <div class="p-6">
      <!-- Header -->
      <div class="mb-4 border-b border-gh-border pb-4">
        <div class="mb-3 flex items-start justify-between gap-3">
          <h2 class="flex-1 text-2xl font-bold text-white">${escapeHtml(data.title)}</h2>
          ${premiumBadge}
        </div>

        <div class="flex flex-wrap gap-3 text-xs text-gh-muted">
          <span class="inline-flex items-center gap-1">
            <i class="bi bi-geo-alt-fill text-gh-accent"></i>
            <span class="font-semibold text-white">${escapeHtml(data.city_name)}, ${escapeHtml(data.state_abbr)}</span>
          </span>
          <span class="inline-flex items-center gap-1">
            <i class="bi bi-tag-fill text-gh-accent"></i>
            ${escapeHtml(data.category_name)}
          </span>
          ${ageMeta}
          <span class="inline-flex items-center gap-1">
            <i class="bi bi-clock"></i>
            ${formatDate(data.created_at)}
          </span>
          <span class="inline-flex items-center gap-1">
            <i class="bi bi-eye"></i>
            ${data.views || 0} views
          </span>
        </div>

        ${verifiedBadge ? '<div class="mt-3">' + verifiedBadge + '</div>' : ''}
      </div>

      <!-- Description -->
      <div class="mb-6">
        <h3 class="mb-3 text-lg font-bold text-white">About</h3>
        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gh-fg">${escapeHtml(data.description)}</p>
      </div>

      <!-- Contact Section -->
      <div class="mb-4 rounded-lg border border-gh-accent/30 bg-gh-accent/10 p-4">
        <h3 class="mb-3 text-base font-bold text-white">
          <i class="bi bi-chat-dots-fill mr-2 text-gh-accent"></i>
          Contact This Member
        </h3>
        ${contactSection}
      </div>

      <!-- Safety Tips -->
      <div class="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4">
        <h3 class="mb-2 flex items-center gap-2 text-sm font-bold text-yellow-500">
          <i class="bi bi-shield-check"></i>
          Safety Tips
        </h3>
        <ul class="space-y-1 text-xs text-gh-muted">
          <li class="flex items-start gap-1">
            <i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i>
            <span>Meet in public places first</span>
          </li>
          <li class="flex items-start gap-1">
            <i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i>
            <span>Tell someone where you're going</span>
          </li>
          <li class="flex items-start gap-1">
            <i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i>
            <span>Trust your instincts</span>
          </li>
        </ul>
      </div>
    </div>
  `;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function toggleFavorite(listingId) {
  fetch('favorite.php?id=' + listingId, { method: 'POST' })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        const btn = event.target.closest('button');
        const icon = btn.querySelector('i');
        icon.classList.toggle('bi-star');
        icon.classList.toggle('bi-star-fill');
      }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include 'views/footer.php'; ?>
