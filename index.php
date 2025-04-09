<?php
session_start();
include_once 'auth_functions.php';
$is_admin = is_admin(); // Use the consistent is_admin() function
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- BOILERPLATE -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- PAGE -->
    <title>Library Management System</title>

    <!-- LINKS -->
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/index.css">

    <!-- FONT / INTER -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include('navbar.php'); ?>

    <div class="container">
        <h1 class="text-center">Welcome to the Cavina Library Portal!</h1>

        <div class="card-container">
            <!-- Books Card -->
            <div class="card">
                <h2>Books</h2>
                <p>See all the available books</p>
                <a href="books.php" class="btn">View</a>
            </div>

            <?php if (isset($_SESSION['user'])): ?>
            <!-- My Books Card -->
            <div class="card">
                <h2>My Books</h2>
                <p>Borrow or Return books</p>
                <a href="borrow.php" class="btn">Borrow</a>
                <a href="return.php" class="btn">Return</a>
            </div>
            <?php else: ?>
            <!-- Login Card -->
            <div class="card">
                <h2>My Books</h2>
                <p>Log in to borrow or return books</p>
                <a href="login.php" class="btn">Login</a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['user']) && $is_admin): ?>
        <div class="admin-section">
            <div class="card">
                <h2>Admin Tools</h2>
                <p>Manage library resources</p>
                <a href="add_edit_book.php" class="btn">Add Book</a>
                <a href="manage_users.php" class="btn">Manage Users</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div>
                <h2>Contact Us</h2>
                <p>Phone: (02) 805-0319</p>
                <p>Mobile: 09617485254</p>
            </div>
            
            <div class="social-links">
                <h2>Follow Us</h2>
                <div class="social-icons">
                    <a href="https://www.facebook.com/doncarlocavinaschool1" target="_blank" class="social-icon">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://www.instagram.com/dccs.official.ph" target="_blank" class="social-icon">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                    <a href="https://www.doncarlocavinaschool.com/" target="_blank" class="social-icon">
                        <i class="fas fa-globe"></i> Website
                    </a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 Don Carlo Cavina School. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>