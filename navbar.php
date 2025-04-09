<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once 'auth_functions.php';
?>

<nav class="nav_container">
    <!-- TITLE -->
    <div class="title_container">
        <p class="title_school">Don Carlo Cavina School</p>
        <a class="title_page" href="index.php">Cavina Library Portal</a>
    </div>

    <!-- NAVBAR ITEMS CONTAINER -->
    <div class="navbar_items">
        <!-- UNIVERSAL ITEMS-->
        <ul class="navbar_ul">
            <li>
                <a class="nav_link" href="index.php">Home</a>
            </li>

            <li>
                <a class="nav_link" href="books.php">Browse Books</a>
            </li>

            <?php if (is_logged_in()): ?>
                <li>
                    <a class="nav_link" href="borrowed_books.php">My Books</a>
                </li>

                <!-- ADMIN ONLY ITEMS-->
                <?php if (is_admin()): ?>
                    <li>
                        <a class="nav_link" href="manage_books.php">Manage Books</a>
                    </li>

                    <li>
                        <a class="nav_link" href="users.php">Manage Users</a>
                    </li>

                    <li>
                        <a class="nav_link" href="admin_borrowings.php">All Borrowings</a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <!-- AUTHENTICATION ITEMS -->
        <ul class="navbar_ul_auth">
            <?php if (is_logged_in()): ?>
                <li>
                    <span class="nav_user">
                        <?php echo is_admin() ? "(Admin) " : "(Student) "; ?>
                        <?php echo htmlspecialchars($_SESSION['user'] ?? 'User'); ?>
                    </span>
                </li>

                <li>
                    <a class="nav_link" href="logout.php">Logout</a>
                </li>
            <?php else: ?>
                <li>
                    <a class="nav_link" href="login.php">Login</a>
                </li>
                
                <li>
                    <a class="nav_link" href="register.php">Register</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>