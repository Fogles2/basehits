<?php
session_start();
require_once 'config/database.php';
require_once 'classes/AdvertisingManager.php';

$database = new Database();
$db = $database->getConnection();

$adManager = new AdvertisingManager($db);

$ad_type = $_GET['type'] ?? '';
$ad_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;

if($ad_type && $ad_id) {
    // Track the click
    $adManager->trackClick($ad_type, $ad_id, $user_id);
    
    // Get destination URL
    $destination = '';
    
    switch($ad_type) {
        case 'banner':
            $query = "SELECT destination_url FROM banner_ads WHERE id = :id LIMIT 1";
            break;
        case 'native':
            $query = "SELECT destination_url FROM native_ads WHERE id = :id LIMIT 1";
            break;
        case 'premium_listing':
            // Redirect to listing page
            header("Location: /listing.php?id={$ad_id}");
            exit();
        default:
            header("Location: /");
            exit();
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $ad_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if($result && !empty($result['destination_url'])) {
        header("Location: " . $result['destination_url']);
        exit();
    }
}

header("Location: /");
exit();
?>