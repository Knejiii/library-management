<?php
session_start();
include 'db.php';

// Initialize variables
$error = '';
$success = '';

// Check if user is logged in
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session - Using null coalescing operator to handle both session structures
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php?error=invalid_session");
    exit;
}

// Set timezone to Philippines - ESSENTIAL for recording accurate local time
date_default_timezone_set('Asia/Manila');

// Update any overdue books
updateOverdueBooks();

// Handle return action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return_book'])) {
    $borrowing_id = filter_input(INPUT_POST, 'borrowing_id', FILTER_VALIDATE_INT);
    
    if ($borrowing_id) {
        try {
            // Get current timestamp for return date
            $return_date = date('Y-m-d H:i:s');
            
            // Use the returnBook function from db.php with additional timestamp parameter
            $return_successful = returnBook($borrowing_id, $return_date);
            
            if ($return_successful) {
                $success = "Book returned successfully on " . date('M j, Y \a\t g:i A') . " (PHT)!";
            } else {
                $error = "Failed to return the book. Please try again.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid borrowing record.";
    }
}

// Get user's borrowings
try {
    $borrowings = getUserBorrowings($user_id);
    
    // Filter to only show active borrowings (not already returned)
    $active_borrowings = array_filter($borrowings, function($borrowing) {
        return empty($borrowing['return_date']);
    });
    
    // Filter to show returned books
    $returned_borrowings = array_filter($borrowings, function($borrowing) {
        return !empty($borrowing['return_date']);
    });
    
    // Sort returned borrowings by return date (most recent first)
    usort($returned_borrowings, function($a, $b) {
        return strtotime($b['return_date']) - strtotime($a['return_date']);
    });
} catch (Exception $e) {
    $error = "Error retrieving borrowings: " . $e->getMessage();
    $active_borrowings = [];
    $returned_borrowings = [];
}

// Function to verify timestamp accuracy and format
function formatTimestamp($timestamp) {
    if (empty($timestamp) || strtotime($timestamp) === false) {
        return 'Invalid timestamp';
    }
    return date('M j, Y \a\t g:i:s A', strtotime($timestamp)) . ' (PHT)';
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
    <title>My Borrowed Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <h2 class="text-center mb-4">My Borrowed Books</h2>
        
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
        
        <?php if (!empty($active_borrowings)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Borrow Date & Time</th>
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
                                <td><?= date('M j, Y', strtotime($borrowing['due_date'])) ?></td>
                                <td>
                                    <?php if ($borrowing['status'] == 'overdue'): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php elseif ($days_remaining <= 2 && $days_remaining >= 0): ?>
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">On Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to return this book?');">
                                        <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                                        <button type="submit" name="return_book" class="btn btn-sm btn-warning">Return Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                You haven't borrowed any books yet.
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <h4>Borrowing History</h4>
            <?php if (!empty($returned_borrowings)): ?>
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
                            <?php foreach ($returned_borrowings as $borrowing): ?>
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
            <?php else: ?>
                <div class="alert alert-light text-center">
                    No borrowing history yet.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
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