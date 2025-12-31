<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Favorites.php';
require_once 'includes/maintenance_check.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

$favorites = new Favorites($db);

// Get user's favorites
$favorite_listings = $favorites->getUserFavorites($_SESSION['user_id']);

include 'views/header.php';
?>

<link rel="stylesheet" href="/assets/css/dark-blue-theme.css">
<link rel="stylesheet" href="/assets/css/light-theme.css">

<style>
.favorites-header {
    background: linear-gradient(135deg, #4267F5, #1D9BF0);
    padding: 3rem 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    text-align: center;
    color: white;
}

.favorites-stats {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.favorite-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    position: relative;
}

.favorite-card:hover {
    border-color: var(--primary-blue);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(66, 103, 245, 0.3);
}

.favorite-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.95);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 10;
}

.favorite-badge:hover {
    transform: scale(1.1);
}

.favorite-badge.active {
    background: var(--danger-red);
}

.favorite-content {
    padding: 1.5rem;
}

.favorite-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-white);
}

.favorite-title a {
    color: var(--text-white);
    text-decoration: none;
}

.favorite-title a:hover {
    color: var(--primary-blue);
}

.favorite-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: var(--text-gray);
    margin-top: 1rem;
}

.filter-bar {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .favorites-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-content">
    <div class="container">
        <div class="favorites-header">
            <h1 style="margin-bottom: 0.5rem;">⭐ My Favorites</h1>
            <p style="opacity: 0.95;">Listings you've saved for later</p>
            
            <div class="favorites-stats">
                <div class="stat-item">
                    <span style="font-size: 1.5rem;">📝</span>
                    <span><?php echo count($favorite_listings); ?> Saved</span>
                </div>
            </div>
        </div>

        <?php if(count($favorite_listings) > 0): ?>
        <!-- Filter Bar -->
        <div class="filter-bar">
            <label style="color: var(--text-white); display: flex; align-items: center; gap: 0.5rem;">
                <span>🔍</span>
                <input type="text" 
                       id="searchFavorites" 
                       placeholder="Search favorites..." 
                       style="min-width: 250px;"
                       onkeyup="filterFavorites()">
            </label>
            
            <select id="sortFavorites" onchange="sortFavorites()" style="min-width: 200px;">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="title">Title A-Z</option>
            </select>
            
            <button class="btn-danger btn-small" onclick="clearAllFavorites()" style="margin-left: auto;">
                🗑️ Clear All
            </button>
        </div>

        <!-- Favorites Grid -->
        <div class="favorites-grid" id="favoritesGrid">
            <?php foreach($favorite_listings as $listing): ?>
            <div class="favorite-card" data-title="<?php echo htmlspecialchars($listing['title']); ?>" data-date="<?php echo strtotime($listing['favorited_at']); ?>">
                <div class="favorite-badge active" onclick="removeFavorite(<?php echo $listing['listing_id']; ?>, this)">
                    <span style="font-size: 1.5rem;">❤️</span>
                </div>
                
                <?php if($listing['photo_url']): ?>
                <img src="<?php echo htmlspecialchars($listing['photo_url']); ?>" 
                     style="width: 100%; height: 200px; object-fit: cover;" 
                     alt="Listing image">
                <?php endif; ?>
                
                <div class="favorite-content">
                    <div class="favorite-title">
                        <a href="/listing.php?id=<?php echo $listing['listing_id']; ?>">
                            <?php echo htmlspecialchars($listing['title']); ?>
                        </a>
                    </div>
                    
                    <p style="color: var(--text-gray); font-size: 0.9rem;">
                        <?php echo htmlspecialchars(substr($listing['description'], 0, 100)); ?>...
                    </p>
                    
                    <div class="favorite-meta">
                        <span>📂 <?php echo htmlspecialchars($listing['category_name']); ?></span>
                        <span>📍 <?php echo htmlspecialchars($listing['city_name']); ?></span>
                        <span>⭐ Saved <?php echo date('M j', strtotime($listing['favorited_at'])); ?></span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="/listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn-primary btn-small" style="flex: 1;">
                            View Listing
                        </a>
                        <a href="/messages-chat.php?user=<?php echo $listing['user_id']; ?>" class="btn-secondary btn-small" style="flex: 1;">
                            💬 Message
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">⭐</div>
            <h2>No Favorites Yet</h2>
            <p style="color: var(--text-gray); margin: 1rem 0 2rem;">
                Start browsing and save listings you're interested in!
            </p>
            <a href="/choose-location.php" class="btn-primary">
                Browse Listings
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Theme Toggle -->
<?php include 'components/theme-toggle.php'; ?>

<script>
function removeFavorite(listingId, element) {
    if(!confirm('Remove this listing from favorites?')) return;
    
    fetch('/api/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove&listing_id=' + listingId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            element.closest('.favorite-card').remove();
            
            // Check if empty
            if(document.querySelectorAll('.favorite-card').length === 0) {
                location.reload();
            }
        } else {
            alert(data.error || 'Failed to remove favorite');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove favorite');
    });
}

function clearAllFavorites() {
    if(!confirm('Remove ALL favorites? This cannot be undone!')) return;
    
    fetch('/api/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear_all'
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to clear favorites');
        }
    });
}

function filterFavorites() {
    const search = document.getElementById('searchFavorites').value.toLowerCase();
    const cards = document.querySelectorAll('.favorite-card');
    
    cards.forEach(card => {
        const title = card.getAttribute('data-title').toLowerCase();
        if(title.includes(search)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function sortFavorites() {
    const grid = document.getElementById('favoritesGrid');
    const cards = Array.from(document.querySelectorAll('.favorite-card'));
    const sortBy = document.getElementById('sortFavorites').value;
    
    cards.sort((a, b) => {
        if(sortBy === 'newest') {
            return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
        } else if(sortBy === 'oldest') {
            return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
        } else if(sortBy === 'title') {
            return a.getAttribute('data-title').localeCompare(b.getAttribute('data-title'));
        }
    });
    
    cards.forEach(card => grid.appendChild(card));
}
</script>

<?php include 'views/footer.php'; ?>