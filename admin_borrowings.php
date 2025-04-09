<?php
// Start session and include necessary files
session_start();
require_once 'auth_functions.php';
require_once 'db.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    // Redirect to login page if not admin
    header("Location: login.php");
    exit();
}

// Initialize variables
$error_message = "";
$success_message = "";

// Update overdue books status if requested
if (isset($_GET['update_overdue'])) {
    $overdue_count = updateOverdueBooks();
    if ($overdue_count > 0) {
        $success_message = "$overdue_count books marked as overdue.";
    } else {
        $success_message = "No books are currently overdue.";
    }
}

// Handle status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$valid_statuses = ['borrowed', 'returned', 'overdue', ''];

if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = '';
}

// Handle return book action
if (isset($_GET['return']) && is_numeric($_GET['return'])) {
    $borrowing_id = intval($_GET['return']);
    
    // Use current timestamp for return date
    $return_timestamp = date('Y-m-d H:i:s');
    
    if (returnBook($borrowing_id, $return_timestamp)) {
        $success_message = "Book successfully returned.";
    } else {
        $error_message = "Failed to return book. It may already be returned or the record doesn't exist.";
    }
}

// Get all borrowings with optional status filter
$borrowings = $status_filter ? getAllBorrowings($status_filter) : getAllBorrowings();

// Calculate statistics
$total_borrowed = 0;
$total_returned = 0;
$total_overdue = 0;

foreach ($borrowings as $borrowing) {
    if ($borrowing['status'] === 'borrowed') {
        $total_borrowed++;
    } elseif ($borrowing['status'] === 'returned') {
        $total_returned++;
    } elseif ($borrowing['status'] === 'overdue') {
        $total_overdue++;
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
    <title>All Borrowings - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h1>All Borrowings</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Borrowing Records</h5>
                    <div>
                        <div class="btn-group" role="group">
                            <a href="admin_borrowings.php" class="btn btn-outline-primary <?php echo $status_filter === '' ? 'active' : ''; ?>">All</a>
                            <a href="admin_borrowings.php?status=borrowed" class="btn btn-outline-primary <?php echo $status_filter === 'borrowed' ? 'active' : ''; ?>">Borrowed</a>
                            <a href="admin_borrowings.php?status=returned" class="btn btn-outline-primary <?php echo $status_filter === 'returned' ? 'active' : ''; ?>">Returned</a>
                            <a href="admin_borrowings.php?status=overdue" class="btn btn-outline-primary <?php echo $status_filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
                        </div>
                        <a href="admin_borrowings.php?update_overdue=1" class="btn btn-warning ms-2">Update Overdue</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Borrowed By</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($borrowings)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No borrowing records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($borrowings as $borrowing): ?>
                                    <tr class="<?php echo $borrowing['status'] === 'overdue' ? 'table-danger' : ($borrowing['status'] === 'returned' ? 'table-success' : ''); ?>">
                                        <td><?php echo htmlspecialchars($borrowing['id']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['title']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['author']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['username']); ?></td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d g:i A', strtotime($borrowing['borrow_date']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($borrowing['due_date']))); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($borrowing['return_date'])) {
                                                echo htmlspecialchars(date('Y-m-d g:i A', strtotime($borrowing['return_date'])));
                                            } else {
                                                echo 'Not returned';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            switch ($borrowing['status']) {
                                                case 'borrowed':
                                                    echo '<span class="badge bg-primary">Borrowed</span>';
                                                    break;
                                                case 'returned':
                                                    echo '<span class="badge bg-success">Returned</span>';
                                                    break;
                                                case 'overdue':
                                                    echo '<span class="badge bg-danger">Overdue</span>';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($borrowing['status']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($borrowing['status'] !== 'returned'): ?>
                                                <a href="admin_borrowings.php?return=<?php echo $borrowing['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to mark this book as returned?')">Return</a>
                                            <?php else: ?>
                                                <span class="text-muted">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($borrowings)): ?>
                    <div class="mt-3">
                        <p>Total records: <?php echo count($borrowings); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Borrowing Statistics -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Borrowing Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <h3 class="text-primary"><?php echo $total_borrowed; ?></h3>
                                    <p>Currently Borrowed</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <h3 class="text-success"><?php echo $total_returned; ?></h3>
                                    <p>Total Returned</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <h3 class="text-danger"><?php echo $total_overdue; ?></h3>
                                    <p>Overdue Books</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Bulk Operations</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin_borrowings.php?update_overdue=1" class="btn btn-warning">Refresh Overdue Status</a>
                            <a href="#" class="btn btn-info" onclick="window.print()">Print Current View</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>