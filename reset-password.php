<?php
session_start();
require_once 'config/database.php';
require_once 'includes/maintenance_check.php';
require_once 'includes/session_security.php';
require_once 'includes/security_headers.php';
require_once 'classes/CSRF.php';
require_once 'classes/SecurityLogger.php';

// Set security headers
SecurityHeaders::set();

// Initialize secure session
SessionSecurity::init();

$database = new Database();
$db = $database->getConnection();

// Check for maintenance mode
if(checkMaintenanceMode($db)) {
    header('Location: maintenance.php');
    exit();
}

$securityLogger = new SecurityLogger($db);

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$tokenValid = false;
$user_data = null;

// Validate token
if(!empty($token)) {
    $query = "SELECT pr.*, u.id as user_id, u.username, u.email 
              FROM password_resets pr
              LEFT JOIN users u ON pr.user_id = u.id
              WHERE pr.token = :token 
              AND pr.used_at IS NULL 
              AND pr.expires_at > NOW()
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $user_data = $stmt->fetch();
    $tokenValid = ($user_data !== false);
}

// Handle password reset
if($_
