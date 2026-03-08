<?php
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Store the requested page for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Optional: Check if session is valid and recover missing data
if(!isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
        } else {
            // Invalid user, destroy session
            session_destroy();
            header("Location: login.php");
            exit();
        }
    } catch(Exception $e) {
        // If database error, redirect to login
        session_destroy();
        header("Location: login.php");
        exit();
    }
}
?>