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
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
  <meta name="description" content="Turnpage - Local hookup classifieds. Post and browse personal ads in your area." />
  <meta name="theme-color" content="#1d232a" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

  <title>Turnpage - Local Hookup Classifieds</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Tabler CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Flowbite CSS -->
  <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />

  <link rel="icon" type="image/png" href="logo.png">
  <link rel="apple-touch-icon" href="logo.png">

  <style>
    /* Prevent framework conflicts */
    * {
      box-sizing: border-box;
    }

    /* Bottom Navigation */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #1a1d21;
      border-top: 1px solid rgba(255,255,255,0.1);
      z-index: 1050;
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
      color: rgba(255,255,255,0.5);
      text-decoration: none;
      position: relative;
      transition: all 0.2s;
    }

    .bottom-nav-item.active {
      color: #0d6efd;
    }

    .bottom-nav-item:hover {
      background: rgba(255,255,255,0.05);
      color: rgba(255,255,255,0.8);
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
      background-color: #dc3545;
    }

    .has-bottom-nav {
      padding-bottom: 70px;
    }

    @media (min-width: 992px) {
      .bottom-nav {
        display: none;
      }
      .has-bottom-nav {
        padding-bottom: 0;
      }
    }

    /* Framework harmony */
    .navbar {
      backdrop-filter: blur(10px);
    }
  </style>
</head>

<body class="<?php echo isset($_SESSION['user_id']) ? 'has-bottom-nav' : ''; ?>">

  <!-- Bootstrap + Tabler Navbar -->
  <nav class="navbar navbar-dark bg-dark sticky-top border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand fs-3 fw-bold" href="<?php echo isset($_SESSION['current_city']) ? 'city.php?location=' . urlencode($_SESSION['current_city']) : 'choose-location.php'; ?>" style="font-family: 'Brush Script MT', cursive; color: #FFD700;">
        Lustifieds
      </a>

      <!-- Desktop Navigation (Bootstrap) -->
      <ul class="navbar-nav d-none d-lg-flex flex-row gap-2 align-items-center">
        <?php if(isset($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'forum' ? 'active' : ''; ?>" href="forum.php">
              <i class="bi bi-chat-square-text-fill me-1"></i>Forum
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link position-relative <?php echo $current_page === 'nearby-users' ? 'active' : ''; ?>" href="nearby-users.php">
              <i class="bi bi-geo-alt-fill me-1"></i>Nearby
              <?php if(!$user_location_set): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">!</span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'bitcoin-wallet' ? 'active' : ''; ?>" href="bitcoin-wallet.php">
              <i class="bi bi-currency-bitcoin me-1"></i>Wallet
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link position-relative" href="messages-chat-simple.php">
              <i class="bi bi-chat-dots-fill"></i>
              <?php if($unread_messages > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unread_messages; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link position-relative" href="notifications.php">
              <i class="bi bi-bell-fill"></i>
              <?php if($unread_notifications > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unread_notifications; ?></span>
              <?php endif; ?>
            </a>
          </li>

          <!-- Bootstrap Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="avatar avatar-sm rounded-circle bg-secondary">
                <?php echo strtoupper(substr($current_username, 0, 1)); ?>
              </span>
              <span class="d-none d-xl-inline"><?php echo htmlspecialchars($current_username); ?></span>
              <?php if($is_premium_user): ?>
                <span class="badge bg-warning text-dark">PRO</span>
              <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><h6 class="dropdown-header"><?php echo htmlspecialchars($current_username); ?><br><small class="text-muted"><?php echo $is_premium_user ? 'Premium member' : 'Free member'; ?></small></h6></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="profile.php?id=<?php echo $_SESSION['user_id']; ?>"><i class="bi bi-person-circle me-2"></i>My profile</a></li>
              <li><a class="dropdown-item" href="my-listings.php"><i class="bi bi-file-text me-2"></i>My ads</a></li>
              <li><a class="dropdown-item" href="favorites.php"><i class="bi bi-star-fill me-2"></i>Favorites</a></li>
              <li><a class="dropdown-item" href="my-forum-activity.php"><i class="bi bi-chat-left-dots me-2"></i>Forum activity</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Account settings</a></li>
              <li><a class="dropdown-item" href="location-settings.php"><i class="bi bi-geo-alt-fill me-2"></i>Location settings</a></li>
              <li><a class="dropdown-item" href="privacy-settings.php"><i class="bi bi-shield-lock-fill me-2"></i>Privacy</a></li>
              <?php if(!$is_premium_user): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-warning" href="subscription-bitcoin.php"><i class="bi bi-gem me-2"></i>Upgrade to Premium</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
          <li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
          <li class="nav-item"><a class="nav-link" href="membership.php"><i class="bi bi-gem me-1"></i>Premium</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-primary" href="register.php">Sign up free</a></li>
        <?php endif; ?>
      </ul>

      <!-- Mobile Menu Toggle -->
      <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
  </nav>

  <!-- Bootstrap Offcanvas (Mobile Menu) -->
  <div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="mobileMenuLabel">Menu</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="nav nav-pills flex-column">
        <?php if(isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="forum.php"><i class="bi bi-chat-square-text-fill me-2"></i>Forum</a></li>
          <li class="nav-item"><a class="nav-link" href="nearby-users.php"><i class="bi bi-geo-alt-fill me-2"></i>Nearby</a></li>
          <li class="nav-item"><a class="nav-link" href="my-listings.php"><i class="bi bi-file-text-fill me-2"></i>My Listings</a></li>
          <li class="nav-item"><a class="nav-link" href="messages-chat-simple.php"><i class="bi bi-chat-dots-fill me-2"></i>Inbox <?php if($unread_messages > 0) echo "($unread_messages)"; ?></a></li>
          <li class="nav-item"><a class="nav-link" href="marketplace.php"><i class="bi bi-bell-fill me-2"></i>Marketplace <?php if($unread_notifications > 0) echo "($unread_notifications)"; ?></a></li>
          <li class="nav-item"><hr class="text-muted"></li>
          <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
          <li class="nav-item"><a class="nav-link" href="forum.php">Forum</a></li>
          <li class="nav-item"><a class="nav-link" href="membership.php"><i class="bi bi-gem me-2"></i>Premium</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-primary w-100 mt-2" href="register.php">Sign up free</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <?php if($incognito_active && isset($_SESSION['user_id'])): ?>
    <!-- Tabler Alert -->
    <div class="alert alert-info mb-0 rounded-0 border-0 border-bottom" role="alert">
      <div class="container-fluid">
        <div class="d-flex align-items-center">
          <i class="bi bi-incognito me-2"></i>
          <div>
            <strong>Incognito mode active</strong> — your profile is hidden.
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if($profile_incomplete && isset($_SESSION['user_id']) && $current_page !== 'profile-setup'): ?>
    <!-- Bootstrap Alert -->
    <div class="alert alert-warning mb-0 rounded-0 border-0 border-bottom" role="alert">
      <div class="container-fluid">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Complete your profile</strong> — <a href="profile-setup.php" class="alert-link">Complete now</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Bottom Navigation -->
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

  <main class="min-vh-50">
