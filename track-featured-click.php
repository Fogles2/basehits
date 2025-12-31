<?php
require_once 'config/database.php';
require_once 'classes/FeaturedAd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['listing_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $featuredAd = new FeaturedAd($db);
    
    $featuredAd->trackClick($_POST['listing_id']);
}
?>