<?php
session_start();
include 'db.php';


// Initialize variables
$error = '';
$success = '';
$borrowings = [];

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

// Set timezone to Philippines - ESSENTIAL for recording accurate local time
date_default_timezone_set('Asia/Manila');

// Get active borrowings for the user
$borrowings = getUserBorrowings($user_id);

// Handle book return
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrowing_id'])) {
    $borrowing_id = filter_input(INPUT_POST, 'borrowing_id', FILTER_VALIDATE_INT);
    
    if ($borrowing_id) {
        try {
            // Use current timestamp for return date
            $return_date = date('Y-m-d H:i:s');
            
            if (returnBook($borrowing_id, $return_date)) {
                $success = "Book returned successfully on " . date('M j, Y \a\t g:i A') . " (PHT)!";
                // Refresh borrowings list
                $borrowings = getUserBorrowings($user_id);
            } else {
                $error = "Error: Could not process the return.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid borrowing record.";
    }
}

// Function to format timestamps consistently
function formatTimestamp($timestamp) {
    if (empty($timestamp) || strtotime($timestamp) === false) {
        return 'Invalid timestamp';
    }
    return date('M j, Y \a\t g:i:s A', strtotime($timestamp)) . ' (PHT)';
}

// Update any overdue books
updateOverdueBooks();
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
    <title>Return Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

    <style>
    /* Make text white and more visible on dark background */
    body {
        color: #fff !important;
    }
    
    /* Make table text white */
    .table {
        color: #fff !important;
    }
    
    /* Make alerts more visible */
    .alert {
        color: #000;
        font-weight: bold;
    }
    
    /* Make time displays more visible */
    #current-time, .text-muted {
        color: #fff !important;
        font-weight: bold;
    }
    
    /* Improve visibility of table headers */
    .table-dark th {
        background-color: #2a2a2a !important;
    }
    
    /* Keep the table row highlighting but improve contrast */
    .table-danger {
        background-color: rgba(220, 53, 69, 0.7) !important;
        color: #fff !important;
    }
    
    .table-warning {
        background-color: rgba(255, 193, 7, 0.7) !important;
        color: #000 !important;
        font-weight: bold;
    }
    
    /* Secondary table styling */
    .table-secondary, .table-secondary th {
        background-color: #4a4a4a !important;
        color: #fff !important;
    }
</style>

<body>
    <?php include('navbar.php'); ?>
    
    <div class="container mt-5">
        <h2 class="text-center">Return Books</h2>
        
        <!-- Current PHT Time Display -->
        <div class="text-center mb-3">
            <p class="text-muted">Current Date & Time: <span id="current-time"><?= date('M j, Y \a\t g:i:s A') ?></span> (PHT)</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php 
        // Filter active borrowings (not returned)
        $active_borrowings = array_filter($borrowings, function($b) { 
            return empty($b['return_date']); 
        });
        
        if (empty($active_borrowings)): 
        ?>
            <div class="alert alert-info text-center">You don't have any books to return.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_borrowings as $borrowing): ?>
                            <?php 
                                // Calculate days until due
                                $today = new DateTime();
                                $due_date = new DateTime($borrowing['due_date']);
                                $days_remaining = $today->diff($due_date)->format('%R%a');
                                
                                // Determine row styling based on status
                                $row_class = '';
                                if ($borrowing['status'] == 'overdue') {
                                    $row_class = 'table-danger';
                                } elseif ($days_remaining <= 2 && $days_remaining >= 0) {
                                    $row_class = 'table-warning';
                                }
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td><?= htmlspecialchars($borrowing['title']) ?></td>
                                <td><?= htmlspecialchars($borrowing['author']) ?></td>
                                <td><?= htmlspecialchars($borrowing['isbn'] ?? 'N/A') ?></td>
                                <td><?= formatTimestamp($borrowing['borrow_date']) ?></td>
                                <td>
                                    <?= date('M j, Y', strtotime($borrowing['due_date'])) ?>
                                    <?php if ($borrowing['status'] == 'overdue'): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php elseif ($days_remaining <= 2 && $days_remaining >= 0): ?>
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(ucfirst($borrowing['status'])) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to return this book?');">
                                        <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">Return Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <h4>Recent Returns</h4>
            <?php 
            // Filter to show returned books, limit to most recent 5
            $returned_borrowings = array_filter($borrowings, function($b) {
                return !empty($b['return_date']);
            });
            
            // Sort by return date (most recent first)
            usort($returned_borrowings, function($a, $b) {
                return strtotime($b['return_date']) - strtotime($a['return_date']);
            });
            
            // Take only the 5 most recent returns
            $recent_returns = array_slice($returned_borrowings, 0, 5);
            
            if (!empty($recent_returns)): 
            ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-secondary">
                            <tr>
                                <th>Title</th>
                                <th>Borrowed</th>
                                <th>Returned</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_returns as $borrowing): ?>
                                <?php
                                    // Check if timestamps are valid
                                    if (empty($borrowing['borrow_date']) || empty($borrowing['return_date']) ||
                                        strtotime($borrowing['borrow_date']) === false || strtotime($borrowing['return_date']) === false) {
                                        continue; // Skip invalid records
                                    }
                                    
                                    // Calculate borrowing duration
                                    $borrow_datetime = new DateTime($borrowing['borrow_date']);
                                    $return_datetime = new DateTime($borrowing['return_date']);
                                    
                                    // Verify borrow time is before return time
                                    if ($borrow_datetime > $return_datetime) {
                                        // Log error or fix data - for display, adjust to avoid negative duration
                                        $duration_text = 'Invalid duration';
                                    } else {
                                        $duration = $borrow_datetime->diff($return_datetime);
                                        
                                        // Format duration as days, hours, minutes
                                        $duration_text = '';
                                        if ($duration->days > 0) {
                                            $duration_text .= $duration->days . ' days';
                                        }
                                        if ($duration->h > 0) {
                                            $duration_text .= ($duration_text ? ', ' : '') . $duration->h . ' hours';
                                        }
                                        if ($duration->i > 0 && $duration->days == 0) { // Only show minutes if less than a day
                                            $duration_text .= ($duration_text ? ', ' : '') . $duration->i . ' minutes';
                                        }
                                        if ($duration_text == '') {
                                            $duration_text = 'Less than a minute';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($borrowing['title']) ?></td>
                                    <td><?= formatTimestamp($borrowing['borrow_date']) ?></td>
                                    <td><?= formatTimestamp($borrowing['return_date']) ?></td>
                                    <td><?= $duration_text ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end">
                    <a href="borrowed_books.php" class="btn btn-sm btn-link">View full history</a>
                </div>
            <?php else: ?>
                <div class="alert alert-light text-center">
                    No recent returns to display.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-3 text-center">
            <a href="borrow.php" class="btn btn-primary">Borrow Books</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Improved real-time clock script -->
    <script>
    function updateTime() {
        // Create an AJAX request to get the current server time
        // This ensures time accuracy with the server's time
        fetch('get_server_time.php')
            .then(response => response.text())
            .then(time => {
                document.getElementById('current-time').textContent = time;
            })
            .catch(error => {
                // Fallback: update locally if server fetch fails
                let timeDisplay = document.getElementById('current-time');
                let currentTime = new Date();
                let hours = currentTime.getHours();
                let minutes = currentTime.getMinutes();
                let seconds = currentTime.getSeconds();
                let ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                
                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const month = monthNames[currentTime.getMonth()];
                const day = currentTime.getDate();
                const year = currentTime.getFullYear();
                
                const timeString = `${month} ${day}, ${year} at ${hours}:${minutes}:${seconds} ${ampm}`;
                timeDisplay.textContent = timeString;
            });
    }
    
    // Update the time every second
    setInterval(updateTime, 1000);
    
    // Initial update
    updateTime();
    </script>
</body>
</html>