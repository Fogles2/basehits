<?php
session_start();
require_once 'config/database.php';
require_once 'classes/ContentFilter.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$filter = new ContentFilter();

$data = json_decode(file_get_contents('php://input'), true);

$story_id = isset($data['story_id']) ? (int)$data['story_id'] : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';
$user_id = $_SESSION['user_id'];

if(!$story_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Check for offensive content
if($filter->isOffensive($comment)) {
    error_log("Comment blocked - offensive content. User ID: {$user_id}, Story ID: {$story_id}");
    echo json_encode(['success' => false, 'message' => $filter->getBlockReason()]);
    exit;
}

try {
    $query = "INSERT INTO story_comments (story_id, user_id, comment, created_at)
              VALUES (:story_id, :user_id, :comment, NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':story_id', $story_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':comment', $comment);

    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment posted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
    }

} catch(PDOException $e) {
    error_log("Comment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
