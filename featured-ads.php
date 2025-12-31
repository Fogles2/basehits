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

// Ensure is_featured column exists
try {
    $db->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE AFTER status");
    $db->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS featured_at TIMESTAMP NULL AFTER is_featured");
} catch(PDOException $e) {
    // Columns might already exist
}

// Get featured listings
$featured_listings = [];
try {
    $query = "SELECT l.*, u.username, c.name as category_name, ct.name as city_name, s.abbreviation as state_abbr
              FROM listings l
              LEFT JOIN users u ON l.user_id = u.id
              LEFT JOIN categories c ON l.category_id = c.id
              LEFT JOIN cities ct ON l.city_id = ct.id
              LEFT JOIN states s ON ct.state_id = s.id
              WHERE l.is_featured = TRUE AND l.status = 'active'
              ORDER BY l.featured_at DESC, l.created_at DESC
              LIMIT 100";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_listings = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching featured listings: " . $e->getMessage());
    $featured_listings = [];
}

include 'views/header.php';
?>

<div class="page-content">
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⭐</div>
            <h1>Featured Ads</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem; max-width: 600px; margin: 1rem auto;">
                Premium featured listings that get maximum visibility and appear at the top of search results
            </p>
        </div>

        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; text-align: center;">
                <div>
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🚀</div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Top Placement</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem;">Your ad appears at the top of search results</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👀</div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">More Views</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem;">Get 10x more views than regular ads</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚡</div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Fast Results</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem;">Get responses within hours, not days</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🏆</div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Stand Out</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem;">Special badge makes your ad stand out</p>
                </div>
            </div>
        </div>

        <?php if(count($featured_listings) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <?php foreach($featured_listings as $listing): ?>
            <div class="card" style="position: relative; overflow: hidden;">
                <div style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                    <span class="category-badge" style="background: linear-gradient(135deg, var(--featured-gold), var(--warning-orange));">
                        ⭐ Featured
                    </span>
                </div>
                
                <div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <span style="font-size: 4rem;">📋</span>
                </div>
                
                <h3 style="margin-bottom: 0.5rem; color: var(--text-white);">
                    <?php echo htmlspecialchars($listing['title']); ?>
                </h3>
                
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <?php if($listing['category_name']): ?>
                    <span style="background: rgba(66, 103, 245, 0.2); padding: 0.3rem 0.7rem; border-radius: 10px; font-size: 0.8rem; color: var(--primary-blue);">
                        <?php echo htmlspecialchars($listing['category_name']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if($listing['city_name']): ?>
                    <span style="background: rgba(29, 155, 240, 0.2); padding: 0.3rem 0.7rem; border-radius: 10px; font-size: 0.8rem; color: var(--info-cyan);">
                        📍 <?php echo htmlspecialchars($listing['city_name']); ?>, <?php echo $listing['state_abbr']; ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <p style="color: var(--text-gray); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?><?php echo strlen($listing['description']) > 150 ? '...' : ''; ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <small style="color: var(--text-gray);">
                        Posted by <?php echo htmlspecialchars($listing['username']); ?>
                    </small>
                    <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn-primary btn-small">
                        View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 5rem; margin-bottom: 1rem;">⭐</div>
            <h2 style="margin-bottom: 1rem;">No Featured Ads Yet</h2>
            <p style="color: var(--text-gray); margin-bottom: 2rem;">
                Be the first to feature your ad and get maximum visibility!
            </p>
            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="my-listings.php" class="btn-primary">View My Ads</a>
            <?php else: ?>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="register.php" class="btn-primary">Sign Up to Post</a>
                <a href="login.php" class="btn-secondary">Login</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card" style="background: linear-gradient(135deg, rgba(66, 103, 245, 0.1), rgba(29, 155, 240, 0.1)); border: 2px solid var(--primary-blue);">
            <h2 style="text-align: center; margin-bottom: 2rem;">Want to Feature Your Ad?</h2>
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="background: var(--card-bg); padding: 2rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--featured-gold); margin-bottom: 1rem;">💎 Premium Featured Ad</h3>
                    <p style="color: var(--text-gray); margin-bottom: 1rem;">
                        Make your ad stand out with premium featured placement:
                    </p>
                    <ul style="color: var(--text-gray); line-height: 2; margin-left: 1.5rem;">
                        <li>Top position in all search results</li>
                        <li>Special featured badge and highlighting</li>
                        <li>Appear on featured ads page</li>
                        <li>10x more visibility than regular ads</li>
                        <li>Get responses faster</li>
                        <li>30 days of premium placement</li>
                    </ul>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(251, 191, 36, 0.1); border-radius: 8px; text-align: center;">
                        <span style="font-size: 2rem; font-weight: bold; color: var(--featured-gold);">$9.99</span>
                        <span style="color: var(--text-gray);"> / 30 days</span>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="membership.php" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        Upgrade to Featured
                    </a>
                    <?php else: ?>
                    <a href="register.php" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        Sign Up to Get Started
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 2rem; text-align: center;">How Featured Ads Work</h2>
            <div style="display: grid; gap: 2rem;">
                <div style="display: flex; gap: 1.5rem; align-items: start;">
                    <div style="font-size: 2.5rem; flex-shrink: 0;">1️⃣</div>
                    <div>
                        <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Choose Your Ad</h3>
                        <p style="color: var(--text-gray); line-height: 1.8;">
                            Select any of your existing ads or create a new one that you want to feature.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; align-items: start;">
                    <div style="font-size: 2.5rem; flex-shrink: 0;">2️⃣</div>
                    <div>
                        <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Upgrade to Featured</h3>
                        <p style="color: var(--text-gray); line-height: 1.8;">
                            Purchase the featured ad upgrade for just $9.99 for 30 days of premium placement.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; align-items: start;">
                    <div style="font-size: 2.5rem; flex-shrink: 0;">3️⃣</div>
                    <div>
                        <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Get More Views</h3>
                        <p style="color: var(--text-gray); line-height: 1.8;">
                            Your ad appears at the top of search results with a special featured badge, getting 10x more views!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>