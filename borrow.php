<?php
session_start();
include 'db.php';

// Initialize variables
$error = '';
$success = '';
$available_books = [];

// Check if user is logged in
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user ID from session - Using null coalescing operator to handle both session structures
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php?error=invalid_session");
    exit;
}

// Update any overdue books before retrieving current borrowings
updateOverdueBooks();

// Get list of available books
try {
    $query = "SELECT * FROM books WHERE copies > 0 ORDER BY title";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $available_books[] = $row;
        }
    } else {
        $error = "Failed to retrieve available books: " . $conn->error;
    }
} catch (Exception $e) {
    $error = "Error getting available books: " . $e->getMessage();
}

// Handle book borrowing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['book_id']) && isset($_POST['due_date'])) {
        $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
        
        if ($book_id === false || $book_id === null) {
            $error = "Invalid book selection.";
        } else {
            // Validate due date (must be in the future)
            $today = date('Y-m-d');
            $min_date = date('Y-m-d', strtotime('+1 day'));
            $max_date = date('Y-m-d', strtotime('+30 days')); // Limit to 30 days in the future
            
            if (!$due_date || $due_date <= $today) {
                $error = "Due date must be in the future.";
            } else if ($due_date > $max_date) {
                $error = "Due date cannot be more than 30 days in the future.";
            } else {
                // Check borrowing limit (optional - you can add a limit)
                $current_borrowings = getUserBorrowings($user_id);
                $active_borrowings = array_filter($current_borrowings, function($b) {
                    return isset($b['status']) && ($b['status'] == 'borrowed' || $b['status'] == 'overdue');
                });
                
                $borrowing_limit = 5; // Example limit
                if (count($active_borrowings) >= $borrowing_limit) {
                    $error = "You have reached your borrowing limit ($borrowing_limit books).";
                } else {
                    // Process borrowing
                    $borrowing_id = addBorrowing($user_id, $book_id, $due_date);
                    
                    if ($borrowing_id) {
                        $success = "Book borrowed successfully! Due date: " . $due_date;
                        
                        // Refresh available books list
                        $result = $conn->query("SELECT * FROM books WHERE copies > 0 ORDER BY title");
                        $available_books = [];
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $available_books[] = $row;
                            }
                        }
                    } else {
                        $error = "Error: Could not process the borrowing. The book may not be available.";
                    }
                }
            }
        }
    } else {
        $error = "Missing required information.";
    }
}

// Get user's current borrowings
try {
    $current_borrowings = getUserBorrowings($user_id);
    if (!is_array($current_borrowings)) {
        $current_borrowings = [];
    }
} catch (Exception $e) {
    $current_borrowings = [];
    $error = "Could not retrieve current borrowings: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Books - Cavina Library Portal</title>
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* Custom styles for borrow page */
    body {
        background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../images/school-background.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: #fff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        flex: 1;
        width: 100%;
    }
    

/* Add or update these styles in your CSS section */

/* Fix for tight card body */
.card {
    background-color: rgba(48, 48, 48, 0.85);
    color: white;
    border-radius: 10px;
    margin-bottom: 20px;
    backdrop-filter: blur(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    border: none;
    height: auto !important; /* Override any fixed height */
    display: flex;
    flex-direction: column;
}

.card-body {
    flex: 1;
    overflow: visible !important; /* Ensure content doesn't get cut off */
    max-height: none !important; /* Remove any max height restrictions */
    padding: 0; /* Remove padding for table to use full width */
}

/* For the table inside card body */
.table-responsive {
    width: 100%;
    overflow: visible; /* Don't constrain table */
}

.table {
    width: 100%;
    margin-bottom: 0;
}

/* For tables that are truly wider than viewport */
@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto; /* Only allow horizontal scrolling on small devices */
    }
}

/* Make sure rows are fully visible */
.row {
    margin-right: 0;
    margin-left: 0;
}
    
    .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: bold;
        padding: 15px;
    }
    
    .table {
        color: #A9A9A9;
    }
    
    .table thead th {
        border-color: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .table tbody td {
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    .list-group-item {
        background-color: rgba(48, 48, 48, 0.85);
        color: white;
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    .btn-primary {
        background-color: #303030;
        border-color: #303030;
    }
    
    .btn-primary:hover {
        background-color: #444;
        border-color: #444;
    }
    
    .btn-secondary {
        background-color: #505050;
        border-color: #505050;
    }
    
    .btn-secondary:hover {
        background-color: #666;
        border-color: #666;
    }
    
    .modal-content {
        background-color: #303030;
        color: white;
    }
    
    .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .modal-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .form-control {
        background-color: #444;
        border-color: #555;
        color: white;
    }
    
    .form-control:focus {
        background-color: #444;
        border-color: #666;
        color: white;
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
    }
    
    .form-text {
        color: #A9A9A9;
    }
    
    .alert {
        background-color: rgba(48, 48, 48, 0.85);
        border: none;
        backdrop-filter: blur(5px);
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.3);
        color: #a3e4b7;
    }
    
    .alert-danger {
        background-color: rgba(220, 53, 69, 0.3);
        color: #f5c6cb;
    }
    
    .alert-info {
        background-color: rgba(23, 162, 184, 0.3);
        color: #a8e0e9;
    }
    
    /* Page title */
    .page-title {
        font-size: 32px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 30px;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .navbar_items {
            flex-direction: column;
            gap: 20px;
        }
        
        .navbar_ul, .navbar_ul_auth {
            flex-direction: column;
            align-items: center;
        }
        
        .footer-content {
            flex-direction: column;
        }

    /* Footer styles to match the wider layout */
.footer {
    background-color: rgba(48, 48, 48, 0.9);
    color: white;
    padding: 30px 0;
    margin-top: 60px;
    backdrop-filter: blur(5px);
    width: 100%;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 30px;
    max-width: 1400px; /* Match the wider container size */
    margin: 0 auto;
    padding: 0 30px;
}

.social-links {
    display: flex;
    flex-direction: column;
}

.social-icons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.social-icon {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #A9A9A9;
    text-decoration: none;
    transition: color 0.2s ease;
}

.social-icon:hover {
    color: #fff;
}

.copyright {
    max-width: 1400px; /* Match the wider container size */
    margin: 30px auto 0;
    padding: 20px 30px 0;
    text-align: center;
    border-top: 1px solid rgba(169, 169, 169, 0.3);
}

.footer h2 {
    font-size: 24px;
    margin-bottom: 15px;
    font-weight: bold;
}

.footer p {
    margin: 5px 0;
    color: #A9A9A9;
    font-weight: 100;
}

/* Ensure the footer stays at the bottom */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.container-fluid {
    flex: 1;
}

.footer {
    margin-top: auto;
}

/* Responsive adjustments for the footer */
@media (max-width: 992px) {
    .footer-content {
        padding: 0 20px;
    }
    
    .copyright {
        padding: 20px 20px 0;
    }
}

@media (max-width: 576px) {
    .footer-content {
        padding: 0 15px;
    }
    
    .copyright {
        padding: 15px 15px 0;
    }
}
    }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="main-content">
        <div class="container mt-5">
            <h1 class="page-title">Borrow Books</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Available Books -->
<!-- Replace the existing table structure with this responsive one -->
<div class="card">
    <div class="card-header">
        <h4>Available Books</h4>
    </div>
    <div class="card-body p-0"> <!-- Zero padding for more space -->
        <?php if (empty($available_books)): ?>
            <div class="alert alert-info m-3">No books available for borrowing.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 35%">Title</th>
                            <th style="width: 30%">Author</th>
                            <th style="width: 15%">Copies</th>
                            <th style="width: 20%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_books as $book): ?>
                            <tr>
                                <td class="text-truncate" title="<?= htmlspecialchars($book['title']) ?>"><?= htmlspecialchars($book['title']) ?></td>
                                <td class="text-truncate" title="<?= htmlspecialchars($book['author']) ?>"><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['copies']) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#borrowModal" 
                                            data-book-id="<?= $book['id'] ?>"
                                            data-book-title="<?= htmlspecialchars($book['title']) ?>">
                                        Borrow
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
                
                <!-- Your Current Borrowings -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Your Current Borrowings</h4>
                        </div>
                        <div class="card-body">
                            <?php 
                            $active_borrowings = array_filter($current_borrowings, function($b) { 
                                return isset($b['status']) && ($b['status'] == 'borrowed' || $b['status'] == 'overdue'); 
                            });
                            
                            if (empty($active_borrowings)): 
                            ?>
                                <div class="alert alert-info">You don't have any borrowed books.</div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($active_borrowings as $borrowing): ?>
                                        <li class="list-group-item <?= ($borrowing['status'] == 'overdue') ? 'border-danger' : '' ?>">
                                            <div><strong><?= htmlspecialchars($borrowing['title']) ?></strong></div>
                                            <div class="small text-muted">by <?= htmlspecialchars($borrowing['author']) ?></div>
                                            <div>Borrowed: <?= htmlspecialchars($borrowing['borrow_date']) ?></div>
                                            <div>Due: <?= htmlspecialchars($borrowing['due_date']) ?>
                                                <?php if ($borrowing['status'] == 'overdue'): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="return.php" class="btn btn-secondary btn-sm">Return Books</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
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
    
    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1" aria-labelledby="borrowModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="borrowModalLabel">Borrow Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="borrowForm">
                    <div class="modal-body">
                        <input type="hidden" id="book_id" name="book_id">
                        <p>You are borrowing: <strong id="book_title"></strong></p>
                        
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                            <div class="form-text">Choose a date within the next 30 days.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Borrowing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dueDateInput = document.getElementById('due_date');
            const borrowForm = document.getElementById('borrowForm');
            
            if (dueDateInput) {
                // Set minimum date for due date calendar to tomorrow
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowStr = tomorrow.toISOString().split('T')[0];
                dueDateInput.min = tomorrowStr;
                
                // Set maximum date to 30 days from today
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 30);
                const maxDateStr = maxDate.toISOString().split('T')[0];
                dueDateInput.max = maxDateStr;
                
                // Set default due date to 2 weeks from now
                const twoWeeks = new Date();
                twoWeeks.setDate(twoWeeks.getDate() + 14);
                const twoWeeksStr = twoWeeks.toISOString().split('T')[0];
                dueDateInput.value = twoWeeksStr;
            }
            
            // Form validation
            if (borrowForm) {
                borrowForm.addEventListener('submit', function(event) {
                    const bookId = document.getElementById('book_id').value;
                    const dueDate = dueDateInput.value;
                    
                    if (!bookId || !dueDate) {
                        event.preventDefault();
                        alert('Please select a book and due date.');
                    }
                });
            }
        });
        
        // Set book details in modal
        const borrowModal = document.getElementById('borrowModal');
        if (borrowModal) {
            borrowModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const bookId = button.getAttribute('data-book-id');
                const bookTitle = button.getAttribute('data-book-title');
                
                this.querySelector('#book_id').value = bookId;
                this.querySelector('#book_title').textContent = bookTitle;
            });
        }
    </script>
</body>
</html>