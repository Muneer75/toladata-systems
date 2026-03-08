<?php
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Safely get user information
$display_name = 'Guest';
if(isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $display_name = $_SESSION['full_name'];
} elseif(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $display_name = $_SESSION['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tolar System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <<img src="icon" href="assets/images/favicon.png">
    </head>
<body>
<header class="header">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Tola System</h1>
            
            <nav class="nav-menu">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="add_record.php"><i class="fas fa-plus-circle"></i> Add Record</a>
                    <a href="view_records.php"><i class="fas fa-table"></i> View Records</a>
                    <a href="manage_projects.php"><i class="fas fa-project-diagram"></i> Projects</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo htmlspecialchars($display_name); ?>)</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
<main>