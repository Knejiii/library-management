<?php
session_start();
include 'db.php';
include_once 'auth_functions.php';

$success_message = '';
$error_message = '';

// Check if the users table exists, create it if it doesn't
$check_table = "SHOW TABLES LIKE 'users'";
$result = $conn->query($check_table);
if ($result->num_rows == 0) {
    // Create users table with role field
    $create_table = "CREATE TABLE users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table) === FALSE) {
        $error_message = "Error creating users table: " . $conn->error;
    } else {
        // Create first admin account if this is initial setup
        $admin_username = "admin";
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Default password
        $admin_role = "admin";
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $admin_username, $admin_password, $admin_role);
        $stmt->execute();
        
        $success_message = "System initialized with default admin account (Username: admin, Password: admin123). Please change the password after logging in.";
    }
} else {
    // Check if the role column exists, add it if it doesn't
    $check_column = "SHOW COLUMNS FROM users LIKE 'role'";
    $result = $conn->query($check_column);
    if ($result->num_rows == 0) {
        $alter_table = "ALTER TABLE users ADD role ENUM('student', 'admin') NOT NULL DEFAULT 'student'";
        if ($conn->query($alter_table) === FALSE) {
            $error_message = "Error updating users table: " . $conn->error;
        } else {
            // Make the first user an admin if upgrading from old version
            $update_first_user = "UPDATE users ORDER BY id ASC LIMIT 1 SET role = 'admin'";
            $conn->query($update_first_user);
        }
    }
}

// Determine if this is an admin registration
$admin_registration = isset($_GET['admin']) && $_GET['admin'] == 'true' && is_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Role handling - only admins can create other admins
    if (isset($_POST['role']) && $_POST['role'] == 'admin' && is_admin()) {
        $role = 'admin';
    } else {
        $role = 'student'; // Default role
    }
    
    // Validate input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username already exists";
        } else {
            // Insert new user with role
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($stmt->execute()) {
                // If admin is creating a new user, stay on page with success message
                if (is_admin() && isset($_GET['admin'])) {
                    $success_message = "User '{$username}' successfully created with {$role} role.";
                } else {
                    // Set session variables to automatically log in the user
                    $_SESSION['user'] = $username;
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['role'] = $role; // Store role in session
                    
                    // Redirect to the homepage or dashboard
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- LINKS -->
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/index.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center">
                            <?php echo $admin_registration ? 'Create New User' : 'Register'; ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <?php if (is_admin()): ?>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="student" selected>Student</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $admin_registration ? 'Create User' : 'Register'; ?>
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!$admin_registration): ?>
                        <div class="mt-3 text-center">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                        <?php else: ?>
                        <div class="mt-3 text-center">
                            <p><a href="users.php">Back to User Management</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>