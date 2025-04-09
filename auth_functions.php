<?php
/**
 * Authentication and authorization helper functions
 */

/**
 * Check if a user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 * @return bool True if user is an admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to login page if user is not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = "Please log in to access this page.";
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to home page if user is not an admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header('Location: index.php');
        exit;
    }
}

/**
 * Check if a specific permission is enabled
 * @param string $permission_name The name of the permission to check
 * @param bool $default Default value if permission not found
 * @return bool True if permission is enabled, false otherwise
 */
function is_permission_enabled($permission_name, $default = true) {
    global $conn;
    if (!$conn) {
        include_once 'db.php';  // Ensure database connection is established
    }
    
    // Check if permissions table exists
    $result = $conn->query("SHOW TABLES LIKE 'permissions'");
    if ($result->num_rows == 0) {
        return $default; // Return default if table doesn't exist
    }
    
    // Check permission status
    $stmt = $conn->prepare("SELECT is_enabled FROM permissions WHERE permission_name = ?");
    $stmt->bind_param("s", $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (bool)$row['is_enabled'];
    }
    
    return $default; // Return default if permission not found
}

/**
 * Check if the current user can add books
 * Redirects to books.php with error message if not allowed
 */
function require_add_books_permission() {
    require_login();
    
    // Admins can always add books
    if (is_admin()) {
        return;
    }
    
    // Check permission for regular users
    if (!is_permission_enabled('add_books', false)) {
        $_SESSION['error'] = "Book addition is currently disabled by the administrator.";
        header('Location: books.php');
        exit;
    }
}
?>