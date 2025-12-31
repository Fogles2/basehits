<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $listing_id = isset($_POST['listing_id']) ? $_POST['listing_id'] : null;
    $reporter_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $reason = htmlspecialchars($_POST['reason']);
    $details = htmlspecialchars($_POST['details']);
    
    $query = "INSERT INTO reports (listing_id, reporter_id, reason, details, status, created_at) 
              VALUES (:listing_id, :reporter_id, :reason, :details, 'pending', NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':listing_id', $listing_id);
    $stmt->bindParam(':reporter_id', $reporter_id);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':details', $details);
    
    if($stmt->execute()) {
        $success = 'Thank you for your report. Our moderation team will review it within 24 hours.';
    } else {
        $error = 'Failed to submit report. Please try again.';
    }
}

include 'views/header.php';
?>

<!-- Hero Header Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-pink-500/10 via-purple-500/10 to-pink-600/10 py-12">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-red-500/10 border border-red-500/20 mb-4">
            <i class="bi bi-shield-exclamation text-3xl text-red-500"></i>
        </div>
        <h1 class="text-4xl font-bold text-gh-fg mb-3">Report Abuse</h1>
        <p class="text-gh-muted text-lg max-w-2xl mx-auto">
            Help us keep Basehit safe by reporting violations of our community guidelines
        </p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-3xl mx-auto px-4 py-8">
    
    <!-- Alert Messages -->
    <?php if($success): ?>
    <div class="mb-6 bg-gh-success/10 border border-gh-success rounded-lg p-4 flex items-start gap-3">
        <i class="bi bi-check-circle-fill text-xl text-gh-success flex-shrink-0"></i>
        <div>
            <h3 class="font-semibold text-gh-success mb-1">Report Submitted</h3>
            <p class="text-sm text-gh-muted"><?php echo $success; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if($error): ?>
    <div class="mb-6 bg-red-500/10 border border-red-500 rounded-lg p-4 flex items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill text-xl text-red-500 flex-shrink-0"></i>
        <div>
            <h3 class="font-semibold text-red-500 mb-1">Submission Failed</h3>
            <p class="text-sm text-gh-muted"><?php echo $error; ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Important Notice -->
    <div class="mb-6 bg-red-500/5 border border-red-500/20 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-xl text-red-500 flex-shrink-0"></i>
            <div>
                <h3 class="font-semibold text-red-500 mb-2">Important Guidelines</h3>
                <ul class="space-y-1.5 text-sm text-gh-muted">
                    <li class="flex items-start gap-2">
                        <span class="text-red-500">•</span>
                        <span>False reports may result in account suspension</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-500">•</span>
                        <span>Only report genuine violations of our <a href="terms.php" class="text-gh-accent hover:underline">Terms of Service</a></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-500">•</span>
                        <span>Provide as much detail as possible to help our review</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Report Form Card -->
    <div class="bg-gh-panel border border-gh-border rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gh-fg mb-6 flex items-center gap-2">
            <i class="bi bi-file-earmark-text text-gh-accent"></i>
            Submit Report
        </h2>
        
        <form method="POST" action="report-abuse.php" class="space-y-5">
            
            <!-- Listing ID Field -->
            <?php if(isset($_GET['listing_id'])): ?>
            <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($_GET['listing_id']); ?>">
            <div class="bg-gh-panel2 border border-gh-border rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <i class="bi bi-link-45deg text-xl text-gh-accent"></i>
                    <div>
                        <p class="text-xs text-gh-muted">Reporting Listing</p>
                        <p class="font-semibold text-gh-fg">#<?php echo htmlspecialchars($_GET['listing_id']); ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div>
                <label class="block text-sm font-medium text-gh-fg mb-2">
                    Listing ID <span class="text-gh-muted font-normal">(optional)</span>
                </label>
                <input 
                    type="number" 
                    name="listing_id" 
                    class="w-full bg-gh-bg border border-gh-border rounded-lg px-4 py-2.5 text-gh-fg placeholder-gh-muted focus:outline-none focus:ring-2 focus:ring-gh-accent focus:border-transparent"
                    placeholder="Enter listing ID if reporting specific content"
                >
            </div>
            <?php endif; ?>
            
            <!-- Reason Select -->
            <div>
                <label class="block text-sm font-medium text-gh-fg mb-2">
                    Reason for Report *
                </label>
                <select 
                    name="reason" 
                    required
                    class="w-full bg-gh-bg border border-gh-border rounded-lg px-4 py-2.5 text-gh-fg focus:outline-none focus:ring-2 focus:ring-gh-accent focus:border-transparent"
                >
                    <option value="">Select a reason</option>
                    <option value="Spam or Commercial">Spam or Commercial Solicitation</option>
                    <option value="Inappropriate Content">Inappropriate or Explicit Content</option>
                    <option value="Scam or Fraud">Scam or Fraud</option>
                    <option value="Harassment">Harassment or Threats</option>
                    <option value="Underage Content">Underage or Minor-Related Content</option>
                    <option value="Violence">Violence or Illegal Activity</option>
                    <option value="Impersonation">Impersonation or Fake Identity</option>
                    <option value="False Information">False or Misleading Information</option>
                    <option value="Privacy Violation">Privacy Violation</option>
                    <option value="Other">Other Violation</option>
                </select>
            </div>
            
            <!-- Details Textarea -->
            <div>
                <label class="block text-sm font-medium text-gh-fg mb-2">
                    Additional Details *
                </label>
                <textarea 
                    name="details" 
                    rows="6" 
                    required
                    class="w-full bg-gh-bg border border-gh-border rounded-lg px-4 py-2.5 text-gh-fg placeholder-gh-muted focus:outline-none focus:ring-2 focus:ring-gh-accent focus:border-transparent resize-none"
                    placeholder="Please provide as much detail as possible about the violation. Include dates, times, usernames, or specific content that violates our policies..."
                ></textarea>
                <p class="text-xs text-gh-muted mt-2">
                    <i class="bi bi-info-circle"></i>
                    The more information you provide, the faster we can review your report
                </p>
            </div>
            
            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <i class="bi bi-send-fill"></i>
                Submit Report
            </button>
            
        </form>
    </div>
    
    <!-- What Happens Next Section -->
    <div class="bg-gh-panel border border-gh-border rounded-lg p-6">
        <h3 class="text-lg font-bold text-gh-fg mb-4 flex items-center gap-2">
            <i class="bi bi-clock-history text-gh-accent"></i>
            What Happens Next?
        </h3>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-gh-accent/10 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-gh-accent">1</span>
                </div>
                <div>
                    <h4 class="font-semibold text-gh-fg text-sm mb-1">Review Process</h4>
                    <p class="text-sm text-gh-muted">Our moderation team will review your report within 24 hours</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-gh-accent/10 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-gh-accent">2</span>
                </div>
                <div>
                    <h4 class="font-semibold text-gh-fg text-sm mb-1">Action Taken</h4>
                    <p class="text-sm text-gh-muted">If the content violates our <a href="terms.php" class="text-gh-accent hover:underline">Terms of Service</a>, it will be removed promptly</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-gh-accent/10 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-gh-accent">3</span>
                </div>
                <div>
                    <h4 class="font-semibold text-gh-fg text-sm mb-1">Account Actions</h4>
                    <p class="text-sm text-gh-muted">Repeat offenders may have their accounts suspended or permanently banned</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-gh-accent/10 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-gh-accent">4</span>
                </div>
                <div>
                    <h4 class="font-semibold text-gh-fg text-sm mb-1">Follow Up</h4>
                    <p class="text-sm text-gh-muted">We may contact you if additional information is needed</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Resources -->
    <div class="grid grid-cols-3 gap-4 mt-6">
        <a href="terms.php" class="bg-gh-panel border border-gh-border rounded-lg p-4 hover:border-gh-accent transition-colors text-center group">
            <i class="bi bi-file-text text-2xl text-gh-accent mb-2 block"></i>
            <h4 class="font-semibold text-gh-fg text-sm mb-1 group-hover:text-gh-accent transition">Terms</h4>
            <p class="text-xs text-gh-muted">Guidelines</p>
        </a>
        
        <a href="safety.php" class="bg-gh-panel border border-gh-border rounded-lg p-4 hover:border-gh-accent transition-colors text-center group">
            <i class="bi bi-shield-check text-2xl text-gh-accent mb-2 block"></i>
            <h4 class="font-semibold text-gh-fg text-sm mb-1 group-hover:text-gh-accent transition">Safety</h4>
            <p class="text-xs text-gh-muted">Stay safe</p>
        </a>
        
        <a href="contact.php" class="bg-gh-panel border border-gh-border rounded-lg p-4 hover:border-gh-accent transition-colors text-center group">
            <i class="bi bi-envelope text-2xl text-gh-accent mb-2 block"></i>
            <h4 class="font-semibold text-gh-fg text-sm mb-1 group-hover:text-gh-accent transition">Contact</h4>
            <p class="text-xs text-gh-muted">Get help</p>
        </a>
    </div>
    
</div>

<?php include 'views/footer.php'; ?>
