<?php
session_start();
require_once 'config/database.php';
require_once 'classes/FeaturedAd.php';

if(!isset($_SESSION['user_id']) || !isset($_GET['listing_id'])) {
    header('Location: my-listings.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$featuredAd = new FeaturedAd($db);

$listing_id = $_GET['listing_id'];

// Verify ownership
$query = "SELECT * FROM listings WHERE id = :id AND user_id = :user_id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $listing_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$listing = $stmt->fetch();

if(!$listing) {
    header('Location: my-listings.php');
    exit();
}

$pricing_options = $featuredAd->getPricingOptions();

include 'views/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn-secondary">← Back to Listing</a>
    
    <div class="feature-ad-container">
        <div class="feature-ad-header">
            <h2>🌟 Feature Your Ad</h2>
            <p>Get more visibility and responses by featuring your listing at the top of search results</p>
        </div>

        <div class="listing-preview">
            <h3>Your Listing:</h3>
            <p><strong><?php echo htmlspecialchars($listing['title']); ?></strong></p>
            <p><?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?>...</p>
        </div>

        <div class="benefits-section">
            <h3>Featured Ad Benefits:</h3>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <span class="benefit-icon">⭐</span>
                    <strong>Top Placement</strong>
                    <p>Your ad appears first in search results</p>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">👀</span>
                    <strong>More Views</strong>
                    <p>Get up to 10x more visibility</p>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📈</span>
                    <strong>Better Results</strong>
                    <p>Receive more messages and responses</p>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">🎯</span>
                    <strong>Highlighted</strong>
                    <p>Special badge and styling</p>
                </div>
            </div>
        </div>

        <div class="pricing-section">
            <h3>Choose Your Duration:</h3>
            <div class="pricing-options">
                <?php foreach($pricing_options as $option): ?>
                <div class="pricing-option">
                    <div class="pricing-option-header">
                        <h4><?php echo $option['duration_days']; ?> Day<?php echo $option['duration_days'] > 1 ? 's' : ''; ?></h4>
                        <?php if($option['discount_percent'] > 0): ?>
                        <span class="discount-badge">Save <?php echo $option['discount_percent']; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="pricing-option-price">
                        $<?php echo number_format($option['price'], 2); ?>
                    </div>
                    <p class="pricing-option-desc"><?php echo htmlspecialchars($option['description']); ?></p>
                    <form method="POST" action="process-featured-payment.php">
                        <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                        <input type="hidden" name="duration_days" value="<?php echo $option['duration_days']; ?>">
                        <button type="submit" class="btn-primary btn-block">Select This Plan</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>How it works:</strong>
            <ol style="margin: 0.5rem 0 0 1.5rem;">
                <li>Choose your duration and complete payment</li>
                <li>Your request will be reviewed by our moderation team (usually within 24 hours)</li>
                <li>Once approved, your ad will be featured for the selected duration</li>
                <li>Track your ad's performance from your dashboard</li>
            </ol>
        </div>
    </div>
</div>

<style>
.feature-ad-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 2rem 0;
}

.feature-ad-header {
    text-align: center;
    margin-bottom: 2rem;
}

.feature-ad-header h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.listing-preview {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.benefits-section {
    margin-bottom: 2rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.benefit-item {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.benefit-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
}

.benefit-item strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.benefit-item p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.pricing-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.pricing-option {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.3s;
}

.pricing-option:hover {
    border-color: #3498db;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.pricing-option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.pricing-option-header h4 {
    margin: 0;
    color: #2c3e50;
}

.discount-badge {
    background: #e74c3c;
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
}

.pricing-option-price {
    font-size: 2rem;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 0.5rem;
}

.pricing-option-desc {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
</style>

<?php include 'views/footer.php'; ?>