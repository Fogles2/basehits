<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$current_user = $_SESSION['user_id'];

if($user_id == $current_user) {
    echo json_encode(['success' => false, 'message' => 'Cannot favorite yourself']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if already favorited
$query = "SELECT id FROM user_favorites WHERE user_id = :current_user AND favorite_user_id = :fav_user";
$stmt = $db->prepare($query);
$stmt->bindParam(':current_user', $current_user);
$stmt->bindParam(':fav_user', $user_id);
$stmt->execute();

if($stmt->fetch()) {
    // Remove favorite
    $query = "DELETE FROM user_favorites WHERE user_id = :current_user AND favorite_user_id = :fav_user";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':current_user', $current_user);
    $stmt->bindParam(':fav_user', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Removed from favorites', 'action' => 'removed']);
} else {
    // Add favorite
    $query = "INSERT INTO user_favorites (user_id, favorite_user_id) VALUES (:current_user, :fav_user)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':current_user', $current_user);
    $stmt->bindParam(':fav_user', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Added to favorites!', 'action' => 'added']);
}
