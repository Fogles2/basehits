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
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
  <meta name="description" content="Turnpage - Local hookup classifieds. Post and browse personal ads in your area." />
  <meta name="theme-color" content="#0d1117" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

  <title>Turnpage - Local Hookup Classifieds</title>

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Tailwind CSS -->
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
              tertiary: '#21262d',
              border: '#30363d',
              'border-hover': '#8b949e',
              fg: '#c9d1d9',
              muted: '#8b949e',
              link: '#58a6ff',
              green: '#238636',
              'green-hover': '#2ea043',
              'green-emphasis': '#0f5323',
              red: '#da3633',
              'red-hover': '#f85149',
              blue: '#1f6feb'
            }
          }
        }
      }
    }
  </script>

  <!-- Preline UI -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/preline@2.0.3/dist/preline.min.css">

  <style>
    /* GitHub Theme Base */
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
    }

    /* Bottom Navigation Styles */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #161b22;
      border-top: 1px solid #30363d;
      z-index: 40;
      padding-bottom: env(safe-area-inset-bottom);
    }

    .bottom-nav-container {
      display: flex;
      justify-content: space-around;
      align-items: center;
      max-width: 100%;
      margin: 0 auto;
    }

    .bottom-nav-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0.5rem 0.25rem;
      color: #8b949e;
      text-decoration: none;
      position: relative;
      transition: all 0.2s;
    }

    .bottom-nav-item.active {
      color: #238636;
    }

    .bottom-nav-item:hover {
      color: #c9d1d9;
    }

    .bottom-nav-icon {
      font-size: 1.25rem;
      margin-bottom: 0.25rem;
    }

    .bottom-nav-label {
      font-size: 0.625rem;
      font-weight: 500;
    }

    .bottom-nav-badge {
      position: absolute;
      top: 0.25rem;
      right: 50%;
      transform: translateX(0.75rem);
      min-width: 1.125rem;
      height: 1.125rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9999px;
      font-size: 0.625rem;
      font-weight: 700;
      color: white;
      background-color: #da3633;
    }

    .has-bottom-nav {
      padding-bottom: 70px;
    }

    @media (min-width: 1024px) {
      .bottom-nav {
        display: none;
      }
      .has-bottom-nav {
        padding-bottom: 0;
      }
    }
  </style>

  <link rel="icon" type="image/png" href="logo.png">
  <link rel="apple-touch-icon" href="logo.png">
</head>

<body class="bg-gh-bg text-gh-fg <?php echo isset($_SESSION['user_id']) ? 'has-bottom-nav' : ''; ?>">

  <!-- Preline Header -->
  <header class="sticky top-0 z-50 w-full bg-gh-panel border-b border-gh-border">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Global">
      <div class="flex items-center justify-between py-3">

        <!-- Logo -->
        <div class="flex lg:flex-1">
          <a href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . urlencode($_SESSION['current_city']) : 'choose-location.php'; ?>" 
             class="flex items-center">
            <span class="text-2xl sm:text-3xl font-bold text-gh-green">Lustifieds</span>
          </a>
        </div>

        <?php if(isset($_SESSION['user_id'])): ?>
          <!-- Desktop Navigation -->
          <div class="hidden lg:flex lg:gap-x-2">
            <a href="forum.php" 
               class="inline-flex items-center gap-x-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors
                      <?php echo $current_page === 'forum' ? 'bg-gh-tertiary text-gh-fg' : 'text-gh-muted hover:bg-gh-tertiary hover:text-gh-fg'; ?>">
              <i class="bi bi-chat-square-text-fill"></i>
              Forum
            </a>

            <a href="nearby-users.php" 
               class="relative inline-flex items-center gap-x-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors
                      <?php echo $current_page === 'nearby-users' ? 'bg-gh-tertiary text-gh-fg' : 'text-gh-muted hover:bg-gh-tertiary hover:text-gh-fg'; ?>">
              <i class="bi bi-geo-alt-fill"></i>
              Nearby
              <?php if(!$user_location_set): ?>
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-500 px-1 text-[10px] font-bold text-black">!</span>
              <?php endif; ?>
            </a>

            <a href="bitcoin-wallet.php" 
               class="inline-flex items-center gap-x-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors
                      <?php echo $current_page === 'bitcoin-wallet' ? 'bg-gh-tertiary text-gh-fg' : 'text-gh-muted hover:bg-gh-tertiary hover:text-gh-fg'; ?>">
              <i class="bi bi-currency-bitcoin"></i>
              Wallet
            </a>

            <!-- Messages -->
            <a href="messages-chat-simple.php" 
               class="relative inline-flex items-center rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary hover:border-gh-border-hover">
              <i class="bi bi-chat-dots-fill"></i>
              <?php if($unread_messages > 0): ?>
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gh-red px-1 text-[10px] font-bold text-white">
                  <?php echo $unread_messages; ?>
                </span>
              <?php endif; ?>
            </a>

            <!-- Notifications -->
            <a href="notifications.php" 
               class="relative inline-flex items-center rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary hover:border-gh-border-hover">
              <i class="bi bi-bell-fill"></i>
              <?php if($unread_notifications > 0): ?>
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gh-red px-1 text-[10px] font-bold text-white">
                  <?php echo $unread_notifications; ?>
                </span>
              <?php endif; ?>
            </a>
          </div>

          <!-- Profile Dropdown (Preline) -->
          <div class="flex lg:flex-1 lg:justify-end">
            <div class="hs-dropdown relative inline-flex">
              <button type="button" 
                      class="hs-dropdown-toggle inline-flex items-center gap-x-2 rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary hover:border-gh-border-hover">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gh-tertiary font-bold">
                  <?php echo strtoupper(substr($current_username, 0, 1)); ?>
                </span>
                <span class="hidden max-w-[100px] truncate lg:inline"><?php echo htmlspecialchars($current_username); ?></span>
                <?php if($is_premium_user): ?>
                  <span class="hidden rounded-full border border-gh-border bg-gh-green-emphasis px-2 py-0.5 text-[10px] font-bold text-gh-green lg:inline">PRO</span>
                <?php endif; ?>
                <i class="bi bi-chevron-down text-gh-muted text-xs"></i>
              </button>

              <div class="hs-dropdown-menu duration opacity-0 z-10 mt-2 hidden min-w-60 rounded-md border border-gh-border bg-gh-panel p-2 shadow-md transition-[opacity,margin]" 
                   aria-labelledby="hs-dropdown">
                <!-- Profile Header -->
                <div class="border-b border-gh-border px-3 py-3 mb-2">
                  <p class="text-sm font-semibold text-gh-fg"><?php echo htmlspecialchars($current_username); ?></p>
                  <p class="text-xs text-gh-muted"><?php echo $is_premium_user ? 'Premium member' : 'Free member'; ?></p>
                </div>

                <!-- Menu Items -->
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                  <i class="bi bi-person-circle w-4 text-gh-muted"></i>
                  My profile
                </a>
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="my-listings.php">
                  <i class="bi bi-file-text w-4 text-gh-muted"></i>
                  My ads
                </a>
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="favorites.php">
                  <i class="bi bi-star-fill w-4 text-gh-muted"></i>
                  Favorites
                </a>
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="my-forum-activity.php">
                  <i class="bi bi-chat-left-dots w-4 text-gh-muted"></i>
                  Forum activity
                </a>

                <div class="my-2 border-t border-gh-border"></div>

                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="settings.php">
                  <i class="bi bi-gear-fill w-4 text-gh-muted"></i>
                  Account settings
                </a>
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="location-settings.php">
                  <i class="bi bi-geo-alt-fill w-4 text-gh-muted"></i>
                  Location settings
                </a>
                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-fg transition-colors hover:bg-gh-tertiary" 
                   href="privacy-settings.php">
                  <i class="bi bi-shield-lock-fill w-4 text-gh-muted"></i>
                  Privacy
                </a>

                <?php if(!$is_premium_user): ?>
                  <div class="my-2 border-t border-gh-border"></div>
                  <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-green transition-colors hover:bg-gh-green-emphasis" 
                     href="subscription-bitcoin.php">
                    <i class="bi bi-gem w-4"></i>
                    Upgrade to Premium
                  </a>
                <?php endif; ?>

                <div class="my-2 border-t border-gh-border"></div>

                <a class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gh-red transition-colors hover:bg-gh-red/10" 
                   href="logout.php">
                  <i class="bi bi-box-arrow-right w-4"></i>
                  Logout
                </a>
              </div>
            </div>

            <!-- Mobile Menu Button -->
            <button type="button" 
                    class="ml-3 inline-flex items-center justify-center rounded-md border border-gh-border bg-gh-panel p-2 text-gh-fg transition-colors hover:bg-gh-tertiary lg:hidden"
                    data-hs-collapse="#mobile-menu">
              <i class="bi bi-list text-xl"></i>
            </button>
          </div>

        <?php else: ?>
          <!-- Guest Navigation -->
          <div class="hidden lg:flex lg:gap-x-4">
            <a href="about.php" class="text-sm font-semibold text-gh-muted transition-colors hover:text-gh-fg">About</a>
            <a href="forum.php" class="text-sm font-semibold text-gh-muted transition-colors hover:text-gh-fg">Forum</a>
            <a href="membership.php" class="text-sm font-semibold text-gh-muted transition-colors hover:text-gh-fg">
              <i class="bi bi-gem mr-1"></i>Premium
            </a>
          </div>

          <div class="flex flex-1 items-center justify-end gap-x-3">
            <a href="login.php" class="hidden lg:block text-sm font-semibold text-gh-muted transition-colors hover:text-gh-fg">Login</a>
            <a href="register.php" class="rounded-md bg-gh-green px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-gh-green-hover">Sign up free</a>

            <!-- Mobile Menu Button -->
            <button type="button" 
                    class="inline-flex items-center justify-center rounded-md border border-gh-border bg-gh-panel p-2 text-gh-fg transition-colors hover:bg-gh-tertiary lg:hidden"
                    data-hs-collapse="#mobile-menu">
              <i class="bi bi-list text-xl"></i>
            </button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu -->
      <div id="mobile-menu" class="hs-collapse hidden overflow-hidden transition-all duration-300 lg:hidden">
        <div class="space-y-1 border-t border-gh-border py-3">
          <?php if(isset($_SESSION['user_id'])): ?>
            <a href="forum.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">
              <i class="bi bi-chat-square-text-fill mr-2"></i>Forum
            </a>
            <a href="nearby-users.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">
              <i class="bi bi-geo-alt-fill mr-2"></i>Nearby
            </a>
            <a href="my-listings.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">
              <i class="bi bi-file-text-fill mr-2"></i>My Listings
            </a>
            <a href="messages-chat-simple.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">
              <i class="bi bi-chat-dots-fill mr-2"></i>Inbox <?php if($unread_messages > 0) echo '(' . $unread_messages . ')'; ?>
            </a>
            <a href="marketplace.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">
              <i class="bi bi-bell-fill mr-2"></i>Marketplace <?php if($unread_notifications > 0) echo '(' . $unread_notifications . ')'; ?>
            </a>
          <?php else: ?>
            <a href="about.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">About</a>
            <a href="forum.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">Community Forum</a>
            <a href="login.php" class="block rounded-md border border-gh-border bg-gh-panel px-3 py-2 text-sm font-semibold transition-colors hover:bg-gh-tertiary">Login</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
  </header>

  <?php if($incognito_active && isset($_SESSION['user_id'])): ?>
    <div class="border-b border-gh-border bg-gh-panel">
      <div class="mx-auto max-w-7xl px-4 py-2.5 text-sm">
        <i class="bi bi-incognito mr-2"></i>
        <span class="font-semibold text-gh-link">Incognito mode active</span>
        <span class="text-gh-muted">— your profile is hidden.</span>
      </div>
    </div>
  <?php endif; ?>

  <?php if($profile_incomplete && isset($_SESSION['user_id']) && $current_page !== 'profile-setup'): ?>
    <div class="border-b border-gh-border bg-gh-panel">
      <div class="mx-auto max-w-7xl px-4 py-2.5 text-sm">
        <i class="bi bi-exclamation-triangle-fill mr-2 text-yellow-500"></i>
        <span class="font-semibold">Complete your profile</span>
        <span class="text-gh-muted"> — </span>
        <a class="font-semibold text-gh-link hover:underline" href="profile-setup.php">Complete now</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav">
      <div class="bottom-nav-container">
        <a href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . urlencode($_SESSION['current_city']) : 'index.php'; ?>" 
           class="bottom-nav-item <?php echo in_array($current_page, ['index', 'city', 'choose-location']) ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-house-fill"></i></div>
          <span class="bottom-nav-label">Home</span>
        </a>
        <a href="marketplace.php" class="bottom-nav-item <?php echo $current_page === 'marketplace' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-chat-square-text-fill"></i></div>
          <span class="bottom-nav-label">Market</span>
        </a>
        <a href="messages-inbox.php" class="bottom-nav-item <?php echo $current_page === 'messages-inbox' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <span class="bottom-nav-label">Messages</span>
          <?php if($unread_messages > 0): ?>
            <span class="bottom-nav-badge"><?php echo $unread_messages; ?></span>
          <?php endif; ?>
        </a>
        <a href="my-listings.php" class="bottom-nav-item <?php echo $current_page === 'my-listings' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-file-text-fill"></i></div>
          <span class="bottom-nav-label">My Ads</span>
        </a>
        <a href="story.php" class="bottom-nav-item <?php echo $current_page === 'story' ? 'active' : ''; ?>">
          <div class="bottom-nav-icon"><i class="bi bi-person-fill"></i></div>
          <span class="bottom-nav-label">Stories</span>
        </a>
      </div>
    </nav>
  <?php endif; ?>

  <main class="min-h-[60vh]">
