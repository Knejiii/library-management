<?php
session_start();
include 'db.php';
require_once 'auth_functions.php';

// Only allow admin access
require_admin();

// Handle role updates if form submitted
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    // Don't allow changing your own role (security measure)
    if ($user_id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger">You cannot change your own role.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User role updated successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating user role: ' . $conn->error . '</div>';
        }
    }
}

// Get all users
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
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
    <title>Manage Users - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>User Management</h2>
        
        <?php echo $message; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
                        <?php 
                        // Display role if exists, otherwise show "N/A"
                        echo isset($row['role']) ? ucfirst($row['role']) : "N/A"; 
                        ?>
                    </td>
                    <td>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <select name="role" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="student" <?php echo (isset($row['role']) && $row['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="admin" <?php echo (isset($row['role']) && $row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit" name="update_role" class="btn btn-sm btn-primary ms-2">Update</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>