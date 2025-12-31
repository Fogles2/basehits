<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/CSRF.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        $email = trim($_POST['email']);
        
        if(empty($email)) {
            $error = 'Please enter your email address';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } else {
            $result = $user->sendPasswordReset($email);
            if($result['success']) {
                $success = 'Password reset instructions have been sent to your email';
            } else {
                $error = $result['message'];
            }
        }
    }
}

include 'views/header.php';
?>

<div class="flex min-h-[70vh] items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">
    
    <!-- Logo -->
    <div class="mb-8 text-center">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl border-2 border-gh-border bg-gradient-to-br from-gh-panel to-gh-panel2 text-xl font-black shadow-lg">
        <i class="bi bi-key-fill text-gh-accent"></i>
      </div>
      <h1 class="text-2xl font-extrabold tracking-tight">Reset your password</h1>
      <p class="mt-2 text-sm text-gh-muted">Enter your email to receive reset instructions</p>
    </div>

    <!-- Reset Card -->
    <div class="rounded-xl border border-gh-border bg-gh-panel shadow-lg">
      <div class="p-6">
        
        <?php if(!empty($success)): ?>
          <div class="mb-5 rounded-lg border border-gh-border bg-gh-panel2 p-4">
            <div class="flex items-start gap-3">
              <i class="bi bi-check-circle-fill text-lg text-gh-success"></i>
              <div class="flex-1 text-sm">
                <span class="font-semibold text-gh-success">Success!</span>
                <span class="text-gh-fg"> <?php echo htmlspecialchars($success); ?></span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
          <div class="mb-5 rounded-lg border border-gh-border bg-gh-panel2 p-4">
            <div class="flex items-start gap-3">
              <i class="bi bi-exclamation-triangle-fill text-lg text-gh-danger"></i>
              <div class="flex-1 text-sm">
                <span class="font-semibold text-gh-danger">Error:</span>
                <span class="text-gh-fg"> <?php echo htmlspecialchars($error); ?></span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if(empty($success)): ?>
          <form method="POST" action="forgot-password.php" class="space-y-4">
            <?php echo CSRF::getHiddenInput(); ?>
            
            <div>
              <label class="mb-2 block text-sm font-semibold text-gh-fg">Email address</label>
              <input type="email" 
                     name="email" 
                     required 
                     autofocus
                     placeholder="you@example.com"
                     class="w-full rounded-lg border border-gh-border bg-gh-panel2 px-4 py-2.5 text-gh-fg placeholder-gh-muted transition-all focus:border-gh-accent focus:outline-none focus:ring-2 focus:ring-gh-accent/50" />
            </div>

            <button type="submit" 
                    class="w-full rounded-lg bg-gh-accent px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition-all hover:brightness-110">
              Send reset instructions
            </button>
          </form>
        <?php else: ?>
          <a href="login.php" 
             class="block w-full rounded-lg border border-gh-border bg-gh-panel2 px-4 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-white/5">
            Back to login
          </a>
        <?php endif; ?>
      </div>

      <div class="border-t border-gh-border bg-gh-panel2 px-6 py-4 text-center text-sm">
        <span class="text-gh-muted">Remember your password?</span>
        <a href="login.php" class="ml-1 font-semibold text-gh-accent hover:underline">Sign in</a>
      </div>
    </div>
  </div>
</div>

<?php include 'views/footer.php'; ?>
