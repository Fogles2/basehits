<?php
// Authentication & User Data
$unread_messages = 0;
$unread_notifications = 0;
$incognito_active = false;
$user_location_set = false;
$current_theme = 'dark';
$profile_incomplete = false;
$is_premium_user = false;
$current_username = 'User';

if(isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/Message.php';
    require_once __DIR__ . '/../classes/SmartNotifications.php';
    require_once __DIR__ . '/../classes/IncognitoMode.php';
    
    $db_header = new Database();
    $conn_header = $db_header->getConnection();
    
 try {
        $msg_header = new Message($conn_header);
        $unread_messages = $msg_header->getTotalUnreadCount($_SESSION['user_id']);
    } catch(Exception $e) {
        $unread_messages = 0;
    }
    
    try {
        $notif_header = new SmartNotifications($conn_header);
        $unread_notifications = $notif_header->getUnreadCount($_SESSION['user_id']);
    } catch(Exception $e) {
        $unread_notifications = 0;
    }
    
    try {
        $incognito_header = new IncognitoMode($conn_header);
        $incognito_active = $incognito_header->isActive($_SESSION['user_id']);
    } catch(Exception $e) {
        $incognito_active = false;
    }
    
    try {
        $columns_query = "SHOW COLUMNS FROM users";
        $columns_stmt = $conn_header->query($columns_query);
        $existing_columns = [];
        while($col = $columns_stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $col['Field'];
        }
        
        $select_fields = ['id', 'username', 'email', 'created_at'];
        if(in_array('current_latitude', $existing_columns)) $select_fields[] = 'current_latitude';
        if(in_array('auto_location', $existing_columns)) $select_fields[] = 'auto_location';
        if(in_array('theme_preference', $existing_columns)) $select_fields[] = 'theme_preference';
        if(in_array('age', $existing_columns)) $select_fields[] = 'age';
        if(in_array('gender', $existing_columns)) $select_fields[] = 'gender';
        if(in_array('location', $existing_columns)) $select_fields[] = 'location';
        if(in_array('bio', $existing_columns)) $select_fields[] = 'bio';
        if(in_array('is_premium', $existing_columns)) $select_fields[] = 'is_premium';
        
        $query = "SELECT " . implode(', ', $select_fields) . " FROM users WHERE id = :user_id LIMIT 1";
        $stmt = $conn_header->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user_data = $stmt->fetch();
        
        if($user_data) {
            $user_location_set = isset($user_data['current_latitude']) && !empty($user_data['current_latitude']);
            $current_theme = $user_data['theme_preference'] ?? 'dark';
            $is_premium_user = $user_data['is_premium'] ?? false;
            $current_username = $user_data['username'] ?? 'User';
            
            if(in_array('age', $existing_columns) && in_array('gender', $existing_columns) && 
               in_array('location', $existing_columns) && in_array('bio', $existing_columns)) {
                $account_age = time() - strtotime($user_data['created_at']);
                $is_new = $account_age < 86400;
                if($is_new) {
                    $profile_incomplete = empty($user_data['age']) || empty($user_data['gender']) || 
                                        empty($user_data['location']) || empty($user_data['bio']) || 
                                        strlen($user_data['bio']) < 20;
                }
            }
        }
    } catch(PDOException $e) {
        error_log("Header query error: " . $e->getMessage());
    }
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $current_theme === 'dark' ? 'dark' : ''; ?>" data-theme="<?php echo htmlspecialchars($current_theme); ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
  <meta name="description" content="Turnpage - Local hookup classifieds. Post and browse personal ads in your area." />
  <meta name="theme-color" content="#0d1117" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  
  <title>Turnpage - Local Hookup Classifieds</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            gh: {
              bg: '#0d1117',
              panel: '#161b22',
              panel2: '#0b1220',
              border: '#30363d',
              fg: '#c9d1d9',
              muted: '#8b949e',
              accent: '#2f81f7',
              danger: '#da3633',
              warning: '#d29922',
              success: '#238636'
            }
          }
        }
      }
    }
  </script>

	
  <link rel="stylesheet" href="assets/css/bottom-nav.css">
  <link rel="icon" type="image/png" href="logo.png">
  <link rel="apple-touch-icon" href="logo.png">
</head>

<body class="bg-gh-bg text-gh-fg <?php echo isset($_SESSION['user_id']) ? 'has-bottom-nav' : ''; ?>">
  
  <header class="sticky top-0 z-50 border-b border-gh-border bg-gh-bg/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3">
             
		<a class="flex items-center gap-3 font-extrabold tracking-tight text-gh-fg hover:no-underline"
   href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . urlencode($_SESSION['current_city']) : 'choose-location.php'; ?>">
  <span class="text-2xl sm:text-3xl font-bold" style="font-family: 'Brush Script MT', cursive; color: #FFD700;">Lustifieds</span>
</a>

      <nav class="hidden items-center gap-2 md:flex">
        <?php if(isset($_SESSION['user_id'])): ?>
          <a class="rounded-md px-3 py-2 text-sm font-semibold transition-colors <?php echo $current_page === 'forum' ? 'border border-gh-border bg-gh-panel text-gh-fg' : 'text-gh-muted hover:bg-white/5 hover:text-gh-fg'; ?>"
             href="forum.php">
            <i class="bi bi-chat-square-text-fill mr-1.5"></i>Forum
          </a>

          <a class="relative rounded-md px-3 py-2 text-sm font-semibold transition-colors <?php echo $current_page === 'nearby-users' ? 'border border-gh-border bg-gh-panel text-gh-fg' : 'text-gh-muted hover:bg-white/5 hover:text-gh-fg'; ?>"
             href="nearby-users.php">
            <i class="bi bi-geo-alt-fill mr-1.5"></i>Nearby
            <?php if(!$user_location_set): ?>
              <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gh-warning px-1 text-[11px] font-black text-black">!</span>
            <?php endif; ?>
          </a>

          <a class="rounded-md px-3 py-2 text-sm font-semibold transition-colors <?php echo $current_page === 'bitcoin-wallet' ? 'border border-gh-border bg-gh-panel text-gh-fg' : 'text-gh-muted hover:bg-white/5 hover:text-gh-fg'; ?>"
             href="bitcoin-wallet.php">
            <i class="bi bi-currency-bitcoin mr-1.5"></i>Wallet
          </a>

          <a class="relative rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5"
             href="messages-chat-simple.php" title="Messages">
            <i class="bi bi-chat-dots-fill"></i>
            <?php if($unread_messages > 0): ?>
              <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gh-danger px-1 text-[11px] font-black text-white">
                <?php echo $unread_messages; ?>
              </span>
            <?php endif; ?>
          </a>

          <a class="relative rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5"
             href="notifications.php" title="Notifications">
            <i class="bi bi-bell-fill"></i>
            <?php if($unread_notifications > 0): ?>
              <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gh-danger px-1 text-[11px] font-black text-white">
                <?php echo $unread_notifications; ?>
              </span>
            <?php endif; ?>
          </a>

          <div class="relative">
            <button id="userMenuBtn"
                    class="inline-flex items-center gap-2 rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5"
                    type="button">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/5 font-extrabold">
                <?php echo strtoupper(substr($current_username, 0, 1)); ?>
              </span>
              <span class="hidden max-w-[100px] truncate lg:inline"><?php echo htmlspecialchars($current_username); ?></span>
              <?php if($is_premium_user): ?>
                <span class="hidden rounded-full border border-gh-border bg-gh-panel2 px-2 py-0.5 text-[11px] font-black text-gh-warning lg:inline">PRO</span>
              <?php endif; ?>
              <i class="bi bi-chevron-down text-gh-muted"></i>
            </button>

            <div id="userMenu"
                 class="invisible absolute right-0 mt-2 w-72 translate-y-1 rounded-xl border border-gh-border bg-gh-panel opacity-0 shadow-2xl transition-all duration-200
                        data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100"
                 data-open="false">
              <div class="border-b border-gh-border px-4 py-3">
                <div class="font-bold"><?php echo htmlspecialchars($current_username); ?></div>
                <div class="text-xs text-gh-muted"><?php echo $is_premium_user ? 'Premium member' : 'Free member'; ?></div>
              </div>

              <div class="p-2">
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                  <i class="bi bi-person-circle w-5 text-gh-muted"></i> My profile
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="my-listings.php">
                  <i class="bi bi-file-text w-5 text-gh-muted"></i> My ads
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="favorites.php">
                  <i class="bi bi-star-fill w-5 text-gh-muted"></i> Favorites
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="my-forum-activity.php">
                  <i class="bi bi-chat-left-dots w-5 text-gh-muted"></i> Forum activity
                </a>

                <div class="my-2 border-t border-gh-border"></div>

                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="settings.php">
                  <i class="bi bi-gear-fill w-5 text-gh-muted"></i> Account settings
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="location-settings.php">
                  <i class="bi bi-geo-alt-fill w-5 text-gh-muted"></i> Location settings
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-fg transition-colors hover:bg-white/5" 
                   href="privacy-settings.php">
                  <i class="bi bi-shield-lock-fill w-5 text-gh-muted"></i> Privacy
                </a>

                <div class="my-2 border-t border-gh-border"></div>

                <?php if(!$is_premium_user): ?>
                  <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-warning transition-colors hover:bg-white/5" 
                     href="subscription-bitcoin.php">
                    <i class="bi bi-gem w-5"></i> Upgrade to Premium
                  </a>
                  <div class="my-2 border-t border-gh-border"></div>
                <?php endif; ?>

                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-gh-danger transition-colors hover:bg-white/5" 
                   href="logout.php">
                  <i class="bi bi-box-arrow-right w-5"></i> Logout
                </a>
              </div>
            </div>
          </div>

        <?php else: ?>
          <a class="rounded-md px-3 py-2 text-sm font-semibold text-gh-muted transition-colors hover:bg-white/5 hover:text-gh-fg" href="about.php">About</a>
          <a class="rounded-md px-3 py-2 text-sm font-semibold text-gh-muted transition-colors hover:bg-white/5 hover:text-gh-fg" href="forum.php">Forum</a>
          <a class="rounded-md px-3 py-2 text-sm font-semibold text-gh-muted transition-colors hover:bg-white/5 hover:text-gh-fg" href="membership.php">
            <i class="bi bi-gem mr-1.5"></i>Premium
          </a>
          <a class="rounded-md px-3 py-2 text-sm font-semibold text-gh-muted transition-colors hover:bg-white/5 hover:text-gh-fg" href="login.php">Login</a>
          <a class="rounded-md bg-gh-accent px-4 py-2 text-sm font-semibold text-white transition-all hover:brightness-110" href="register.php">Sign up free</a>
        <?php endif; ?>
      </nav>

      <button id="mobileBtn"
              class="inline-flex items-center justify-center rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5 md:hidden"
              type="button">
        <i class="bi bi-list text-xl"></i>
      </button>
    </div>

    <div id="mobileMenu" class="hidden border-t border-gh-border bg-gh-bg md:hidden">
      <div class="mx-auto max-w-7xl space-y-1 px-4 py-3">
        <?php if(isset($_SESSION['user_id'])): ?>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="forum.php">
            <i class="bi bi-chat-square-text-fill mr-2"></i>Forum
          </a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="nearby-users.php">
            <i class="bi bi-geo-alt-fill mr-2"></i>Nearby
          </a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="my-listings.php">
            <i class="bi bi-file-text-fill mr-2"></i>My Listings
          </a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="messages-chat-simple.php">
            <i class="bi bi-chat-dots-fill mr-2"></i>Inbox <?php if($unread_messages > 0) echo '(' . $unread_messages . ')'; ?>
          </a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="marketplace.php">
            <i class="bi bi-bell-fill mr-2"></i>Content Marketplace <?php if($unread_notifications > 0) echo '(' . $unread_notifications . ')'; ?>
          </a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold text-gh-danger transition-colors hover:bg-white/5" href="logout.php">
            <i class="bi bi-box-arrow-right mr-2"></i>Logout
          </a>
        <?php else: ?>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="about.php">About</a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="forum.php">Community Forum</a>
          <a class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-white/5" href="login.php">Login</a>
          <a class="block rounded-md bg-gh-accent px-3 py-2 text-sm font-semibold text-white transition-all hover:brightness-110" href="register.php">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if($incognito_active && isset($_SESSION['user_id'])): ?>
    <div class="border-b border-gh-border bg-gh-panel">
      <div class="mx-auto max-w-7xl px-4 py-2.5 text-sm">
        <i class="bi bi-incognito mr-2"></i>
        <span class="font-semibold text-gh-accent">Incognito mode active</span>
        <span class="text-gh-muted">— your profile is hidden.</span>
      </div>
    </div>
  <?php endif; ?>

  <?php if($profile_incomplete && isset($_SESSION['user_id']) && $current_page !== 'profile-setup'): ?>
    <div class="border-b border-gh-border bg-gh-panel">
      <div class="mx-auto max-w-7xl px-4 py-2.5 text-sm">
        <i class="bi bi-exclamation-triangle-fill mr-2 text-gh-warning"></i>
        <span class="font-semibold">Complete your profile</span>
        <span class="text-gh-muted"> — </span>
        <a class="font-semibold text-gh-accent hover:underline" href="profile-setup.php">Complete now</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if(isset($_SESSION['user_id'])): ?>
    <nav class="bottom-nav d-lg-none">
      <div class="bottom-nav-container">
        <a href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . urlencode($_SESSION['current_city']) : 'index.php'; ?>" 
           class="bottom-nav-item <?php echo in_array($current_page, ['index', 'city', 'choose-location']) ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-house-fill"></i></div>
          <span class="bottom-nav-label">Home</span>
        </a>
        <a href="marketplace.php" class="bottom-nav-item <?php echo $current_page === 'forum' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-chat-square-text-fill"></i></div>
          <span class="bottom-nav-label">Marketplace</span>
        </a>
        <a href="messages-inbox.php" class="bottom-nav-item <?php echo $current_page === 'messages-chat-simple' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <span class="bottom-nav-label">Messages</span>
          <?php if($unread_messages > 0): ?>
            <span class="bottom-nav-badge bg-danger"><?php echo $unread_messages; ?></span>
          <?php endif; ?>
        </a>
        <a href="my-listings.php" class="bottom-nav-item <?php echo $current_page === 'my-listings' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-file-text-fill"></i></div>
          <span class="bottom-nav-label">My Listings</span>
        </a>
        <a href="story.php"<?php echo $_SESSION['user_id']; ?>" class="bottom-nav-item <?php echo $current_page === 'story' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-person-fill"></i></div>
          <span class="bottom-nav-label">Stories</span>
        </a>
      </div>
    </nav>
  <?php endif; ?>

  <main class="min-h-[60vh]">
