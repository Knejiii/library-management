<?php
session_start();
include 'db.php';
require_once 'auth_functions.php';

// Only admin can access this script
require_admin();

$message = '';

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_permissions'])) {
    // Check if permissions table exists
    $result = $conn->query("SHOW TABLES LIKE 'permissions'");
    if ($result->num_rows == 0) {
        // Create permissions table
        $sql = "CREATE TABLE permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permission_name VARCHAR(50) NOT NULL,
            is_enabled BOOLEAN NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql) === TRUE) {
            $message .= "<div class='alert alert-success'>Permissions table created successfully!</div>";
            
            // Insert default permissions
            $permissions = [
                ['add_books', 0],    // Disabled by default
                ['edit_books', 1],
                ['delete_books', 1],
                ['borrow_books', 1],
                ['manage_users', 1]
            ];
            
            $stmt = $conn->prepare("INSERT INTO permissions (permission_name, is_enabled) VALUES (?, ?)");
            foreach ($permissions as $perm) {
                $stmt->bind_param("si", $perm[0], $perm[1]);
                if ($stmt->execute()) {
                    $message .= "<div class='alert alert-success'>Permission '{$perm[0]}' added!</div>";
                } else {
                    $message .= "<div class='alert alert-danger'>Error adding permission '{$perm[0]}': " . $conn->error . "</div>";
                }
            }
        } else {
            $message .= "<div class='alert alert-danger'>Error creating permissions table: " . $conn->error . "</div>";
        }
    } else {
        // Update the add_books permission to disabled
        $sql = "SELECT id FROM permissions WHERE permission_name = 'add_books'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            // Permission exists, update it
            $sql = "UPDATE permissions SET is_enabled = 0 WHERE permission_name = 'add_books'";
            if ($conn->query($sql) === TRUE) {
                $message .= "<div class='alert alert-success'>Book addition has been disabled!</div>";
            } else {
                $message .= "<div class='alert alert-danger'>Error updating permission: " . $conn->error . "</div>";
            }
        } else {
            // Permission doesn't exist, insert it
            $stmt = $conn->prepare("INSERT INTO permissions (permission_name, is_enabled) VALUES ('add_books', 0)");
            if ($stmt->execute()) {
                $message .= "<div class='alert alert-success'>Book addition has been disabled!</div>";
            } else {
                $message .= "<div class='alert alert-danger'>Error adding permission: " . $conn->error . "</div>";
            }
        }
    }
}

// Check current permissions status
$add_books_enabled = true; // Default assumption
$result = $conn->query("SHOW TABLES LIKE 'permissions'");
if ($result->num_rows > 0) {
    $sql = "SELECT is_enabled FROM permissions WHERE permission_name = 'add_books'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $add_books_enabled = (bool)$row['is_enabled'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Permissions - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2>Update System Permissions</h2>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <div class="alert alert-info">
                    <p>Current Status: Book addition is <?php echo $add_books_enabled ? 'ENABLED' : 'DISABLED'; ?></p>
                </div>
                
                <p>Update the system permissions to restrict book addition functionality:</p>
                
                <form method="POST" action="">
                    <button type="submit" name="update_permissions" class="btn btn-primary">
                        <?php echo $add_books_enabled ? 'Disable Book Addition' : 'Book Addition Already Disabled'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>