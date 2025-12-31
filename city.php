<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$city_slug = isset($_GET['location']) ? $_GET['location'] : '';

if(!$city_slug) {
    header('Location: choose-location.php');
    exit();
}

// Get city info
try {
    $query = "SELECT c.*, s.name as state_name, s.abbreviation as state_abbr
              FROM cities c
              LEFT JOIN states s ON c.state_id = s.id
              WHERE c.slug = :slug";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':slug', $city_slug);
    $stmt->execute();
    $city = $stmt->fetch();

    if(!$city) {
        header('Location: choose-location.php');
        exit();
    }
} catch(PDOException $e) {
    header('Location: choose-location.php');
    exit();
}

// Get categories with counts
$categories = [];
try {
    $query = "SELECT cat.*, 
              (SELECT COUNT(*) FROM listings WHERE category_id = cat.id AND city_id = :city_id AND status = 'active') as listing_count
              FROM categories cat
              ORDER BY cat.name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':city_id', $city['id']);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Get listings
$listings = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

try {
    $where_clause = "WHERE l.city_id = :city_id AND l.status = 'active'";
    if($category_filter) {
        $where_clause .= " AND l.category_id = :category_id";
    }

    $query = "SELECT l.*, cat.name as category_name, u.username, u.is_verified, u.is_premium
              FROM listings l
              LEFT JOIN categories cat ON l.category_id = cat.id
              LEFT JOIN users u ON l.user_id = u.id
              $where_clause
              ORDER BY l.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':city_id', $city['id']);
    if($category_filter) {
        $stmt->bindParam(':category_id', $category_filter);
    }
    $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $listings = $stmt->fetchAll();

    // Get total
    $count_query = "SELECT COUNT(*) FROM listings l $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':city_id', $city['id']);
    if($category_filter) {
        $count_stmt->bindParam(':category_id', $category_filter);
    }
    $count_stmt->execute();
    $total_count = $count_stmt->fetchColumn();
    $total_pages = ceil($total_count / $per_page);

} catch(PDOException $e) {
    $listings = [];
    $total_pages = 0;
}

include 'views/header.php';
?>

<div class="min-h-screen bg-gh-bg">

  <!-- City Header -->
  <div class="relative overflow-hidden bg-gradient-to-br from-gh-accent/30 via-gh-success/30 to-gh-accent/30 py-8">
    <div class="relative mx-auto max-w-6xl px-4">
      <a href="choose-location.php" class="mb-3 inline-flex items-center gap-1 text-xs text-white/80 transition-colors hover:text-white">
        <i class="bi bi-arrow-left"></i>
        Change Location
      </a>

      <div class="flex items-center gap-3">
        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/10 text-2xl backdrop-blur-sm">
          <i class="bi bi-geo-alt-fill text-gh-accent"></i>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-white md:text-3xl">
            <?php echo htmlspecialchars($city['name']); ?>, <?php echo htmlspecialchars($city['state_abbr']); ?>
          </h1>
          <p class="mt-1 text-sm text-white/80">
            <span class="font-semibold"><?php echo number_format($total_count); ?></span> active personals
          </p>
        </div>
      </div>

      <div class="mt-4">
        <a href="post-ad.php" 
           class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-bold text-white transition-all hover:brightness-110">
          <i class="bi bi-plus-circle-fill"></i>
          Post in <?php echo htmlspecialchars($city['name']); ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="mx-auto max-w-6xl px-4 py-6">

    <!-- Category Pills -->
    <div class="mb-6">
      <h2 class="mb-3 text-base font-bold text-white">Browse by Category</h2>
      <div class="flex flex-wrap gap-2">
        <a href="city.php?location=<?php echo htmlspecialchars($city_slug); ?>" 
           class="<?php echo !$category_filter ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white' : 'border border-gh-border bg-gh-panel text-gh-fg hover:border-gh-accent'; ?> rounded-full px-3 py-1.5 text-xs font-semibold transition-all">
          All (<?php echo number_format($total_count); ?>)
        </a>

        <?php foreach($categories as $cat): ?>
          <?php if($cat['listing_count'] > 0): ?>
            <a href="city.php?location=<?php echo htmlspecialchars($city_slug); ?>&category=<?php echo $cat['id']; ?>" 
               class="<?php echo $category_filter == $cat['id'] ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white' : 'border border-gh-border bg-gh-panel text-gh-fg hover:border-gh-accent'; ?> rounded-full px-3 py-1.5 text-xs font-semibold transition-all">
              <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['listing_count']; ?>)
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Listings -->
    <?php if(count($listings) > 0): ?>
      <div class="space-y-3">
        <?php foreach($listings as $listing): ?>
          <article class="group rounded-lg border border-gh-border bg-gh-panel transition-all hover:border-gh-accent hover:shadow-lg">
            <div class="p-4">
              <div class="flex items-start gap-3">

                <button onclick="toggleFavorite(<?php echo $listing['id']; ?>)" class="shrink-0 pt-1 text-lg text-gh-muted transition-all hover:text-yellow-500">
                  <i class="bi bi-star"></i>
                </button>

                <div class="min-w-0 flex-1">
                  <button onclick="openListingModal(<?php echo $listing['id']; ?>)" class="w-full text-left">
                    <h3 class="mb-2 text-lg font-bold text-white transition-colors group-hover:text-gh-accent">
                      <?php echo htmlspecialchars($listing['title']); ?>
                    </h3>
                  </button>

                  <div class="mb-2 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-gh-muted">
                      <i class="bi bi-tag-fill text-gh-accent"></i>
                      <?php echo htmlspecialchars($listing['category_name']); ?>
                    </span>

                    <?php if($listing['age']): ?>
                      <span class="inline-flex items-center gap-1 text-gh-muted">
                        <i class="bi bi-calendar"></i>
                        <?php echo $listing['age']; ?> years old
                      </span>
                    <?php endif; ?>

                    <span class="inline-flex items-center gap-1 text-gh-muted">
                      <i class="bi bi-clock"></i>
                      <?php echo date('M d, g:i A', strtotime($listing['created_at'])); ?>
                    </span>

                    <?php if($listing['is_verified']): ?>
                      <span class="inline-flex items-center gap-1 text-green-500">
                        <i class="bi bi-patch-check-fill"></i>
                        Verified
                      </span>
                    <?php endif; ?>

                    <?php if($listing['is_premium']): ?>
                      <span class="inline-flex items-center gap-1 text-purple-500">
                        <i class="bi bi-gem"></i>
                        Premium
                      </span>
                    <?php endif; ?>
                  </div>

                  <p class="mb-3 line-clamp-2 text-sm text-gh-muted">
                    <?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?>...
                  </p>

                  <button onclick="openListingModal(<?php echo $listing['id']; ?>)" 
                     class="inline-flex items-center gap-1 text-xs font-semibold text-gh-accent transition-all hover:gap-2">
                    Read More
                    <i class="bi bi-arrow-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
        <div class="mt-6 flex items-center justify-center gap-2">
          <?php if($page > 1): ?>
            <a href="city.php?location=<?php echo htmlspecialchars($city_slug); ?>&page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?>" 
               class="rounded-lg border border-gh-border bg-gh-panel px-3 py-1.5 text-sm font-semibold text-gh-fg hover:border-gh-accent">
              <i class="bi bi-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="city.php?location=<?php echo htmlspecialchars($city_slug); ?>&page=<?php echo $i; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?>" 
               class="<?php echo $i == $page ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white' : 'border border-gh-border bg-gh-panel text-gh-fg hover:border-gh-accent'; ?> rounded-lg px-3 py-1.5 text-sm font-semibold">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>

          <?php if($page < $total_pages): ?>
            <a href="city.php?location=<?php echo htmlspecialchars($city_slug); ?>&page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category='.$category_filter : ''; ?>" 
               class="rounded-lg border border-gh-border bg-gh-panel px-3 py-1.5 text-sm font-semibold text-gh-fg hover:border-gh-accent">
              <i class="bi bi-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="rounded-lg border border-gh-border bg-gh-panel p-10 text-center">
        <i class="bi bi-inbox text-5xl text-gh-muted opacity-20"></i>
        <h3 class="mt-3 text-lg font-bold text-white">No Listings Yet</h3>
        <p class="mt-1 text-sm text-gh-muted">Be the first to post in <?php echo htmlspecialchars($city['name']); ?>!</p>
        <a href="post-ad.php" 
           class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-semibold text-white hover:brightness-110">
          <i class="bi bi-plus-circle-fill"></i>
          Post First Ad
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Listing Modal (Same as browse.php) -->
<div id="listingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
  <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-gh-border bg-gh-bg shadow-2xl">
    <button onclick="closeListingModal()" class="absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-gh-panel text-gh-muted transition-all hover:bg-red-500 hover:text-white">
      <i class="bi bi-x-lg"></i>
    </button>

    <div id="modalLoading" class="flex items-center justify-center p-12">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gh-accent border-t-transparent"></div>
        <p class="mt-4 text-sm text-gh-muted">Loading...</p>
      </div>
    </div>

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

document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closeListingModal();
});

document.getElementById('listingModal').addEventListener('click', (e) => {
  if(e.target.id === 'listingModal') closeListingModal();
});

function generateListingHTML(data) {
  const premiumBadge = data.is_premium ? '<span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 px-3 py-1 text-xs font-bold text-white"><i class="bi bi-gem"></i>PREMIUM</span>' : '';
  const verifiedBadge = data.is_verified ? '<span class="inline-flex items-center gap-1 rounded bg-green-500/20 px-3 py-1 text-xs font-semibold text-green-500"><i class="bi bi-patch-check-fill"></i>Verified</span>' : '';
  const ageMeta = data.age ? `<span class="inline-flex items-center gap-1"><i class="bi bi-calendar"></i>${data.age} years old</span>` : '';

  let contactSection = '';
  if(data.is_own_listing) {
    contactSection = '<div class="rounded-lg border border-gh-border bg-gh-panel p-3"><p class="text-sm text-gh-muted"><i class="bi bi-info-circle mr-1"></i>This is your listing</p></div>';
  } else if(data.is_logged_in) {
    contactSection = `<a href="message.php?to=${data.poster_id}&listing=${data.id}" class="group inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-2.5 text-sm font-bold text-white transition-all hover:brightness-110"><i class="bi bi-send-fill"></i>Send Message<i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i></a>`;
  } else {
    contactSection = '<div class="rounded-lg border border-gh-border bg-gh-panel p-3"><p class="mb-2 text-xs text-gh-muted">Login to contact this member</p><div class="flex gap-2"><a href="login.php" class="rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-4 py-2 text-sm font-semibold text-white hover:brightness-110">Login</a><a href="register.php" class="rounded-lg border border-gh-border bg-gh-panel2 px-4 py-2 text-sm font-semibold text-gh-fg hover:border-gh-accent">Sign Up</a></div></div>';
  }

  return `
    <div class="p-6">
      <div class="mb-4 border-b border-gh-border pb-4">
        <div class="mb-3 flex items-start justify-between gap-3">
          <h2 class="flex-1 text-2xl font-bold text-white">${escapeHtml(data.title)}</h2>
          ${premiumBadge}
        </div>
        <div class="flex flex-wrap gap-3 text-xs text-gh-muted">
          <span class="inline-flex items-center gap-1"><i class="bi bi-geo-alt-fill text-gh-accent"></i><span class="font-semibold text-white">${escapeHtml(data.city_name)}, ${escapeHtml(data.state_abbr)}</span></span>
          <span class="inline-flex items-center gap-1"><i class="bi bi-tag-fill text-gh-accent"></i>${escapeHtml(data.category_name)}</span>
          ${ageMeta}
          <span class="inline-flex items-center gap-1"><i class="bi bi-clock"></i>${formatDate(data.created_at)}</span>
          <span class="inline-flex items-center gap-1"><i class="bi bi-eye"></i>${data.views || 0} views</span>
        </div>
        ${verifiedBadge ? '<div class="mt-3">' + verifiedBadge + '</div>' : ''}
      </div>
      <div class="mb-6">
        <h3 class="mb-3 text-lg font-bold text-white">About</h3>
        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gh-fg">${escapeHtml(data.description)}</p>
      </div>
      <div class="mb-4 rounded-lg border border-gh-accent/30 bg-gh-accent/10 p-4">
        <h3 class="mb-3 text-base font-bold text-white"><i class="bi bi-chat-dots-fill mr-2 text-gh-accent"></i>Contact This Member</h3>
        ${contactSection}
      </div>
      <div class="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4">
        <h3 class="mb-2 flex items-center gap-2 text-sm font-bold text-yellow-500"><i class="bi bi-shield-check"></i>Safety Tips</h3>
        <ul class="space-y-1 text-xs text-gh-muted">
          <li class="flex items-start gap-1"><i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i><span>Meet in public places first</span></li>
          <li class="flex items-start gap-1"><i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i><span>Tell someone where you're going</span></li>
          <li class="flex items-start gap-1"><i class="bi bi-check-circle-fill mt-0.5 text-green-500"></i><span>Trust your instincts</span></li>
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
