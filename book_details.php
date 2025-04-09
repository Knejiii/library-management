<?php
session_start();
include 'db.php';
include_once 'auth_functions.php';
$is_admin = is_admin();

// Initialize variables
$book = null;
$error_message = null;

// Get book ID from URL parameter
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = $_GET['id'];
    
    try {
        // Prepare query to get book details
        $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }
        
        $stmt->bind_param("i", $book_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query");
        }
        
        $result = $stmt->get_result();
        
        if (!$result) {
            throw new Exception("Failed to get result set");
        }
        
        if ($result->num_rows === 0) {
            $error_message = "Book not found.";
        } else {
            $book = $result->fetch_assoc();
        }
        
    } catch (Exception $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
} else {
    $error_message = "Invalid book ID.";
}

// Check if user has borrowed this book
$user_has_borrowed = false;
if (isset($_SESSION['user_id']) && $book) {
    $user_id = $_SESSION['user_id'];
    $book_id = $book['id'];
    
    $borrow_stmt = $conn->prepare("SELECT * FROM borrowings 
                                 WHERE user_id = ? AND book_id = ? 
                                 AND (status = 'borrowed' OR status = 'overdue')");
    $borrow_stmt->bind_param("ii", $user_id, $book_id);
    $borrow_stmt->execute();
    $borrow_result = $borrow_stmt->get_result();
    
    $user_has_borrowed = ($borrow_result->num_rows > 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $book ? htmlspecialchars($book['title']) : 'Book Details' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/reset.css">
    <style>
        .book-cover {
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .book-info-card {
            height: 100%;
            border-left: 4px solid #0d6efd;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .description-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .action-buttons {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_message) ?>
            </div>
            <div class="text-center mt-4">
                <a href="books.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Books
                </a>
            </div>
        <?php elseif ($book): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="books.php">Books</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($book['title']) ?></li>
                </ol>
            </nav>
            
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <!-- Book Image Column -->
                        <div class="col-md-4 text-center mb-4 mb-md-0">
                            <img src="<?= !empty($book['image_path']) ? htmlspecialchars($book['image_path']) : 'uploads/books/default_book.jpg' ?>" 
                                class="book-cover" alt="<?= htmlspecialchars($book['title']) ?>">
                            
                            <div class="action-buttons">
                                <?php if (isset($_SESSION['user'])): ?>
                                    <?php $available = isset($book['copies']) ? $book['copies'] : 0; ?>
                                    <?php if ($available > 0 && !$user_has_borrowed): ?>
                                        <form method="post" action="borrow.php">
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-hand-holding me-1"></i> Borrow This Book
                                            </button>
                                        </form>
                                    <?php elseif ($user_has_borrowed): ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-check-circle me-1"></i> Already Borrowed
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-ban me-1"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($is_admin): ?>
                                    <div class="mt-3">
                                        <a href="add_edit_book.php?id=<?= $book['id'] ?>" class="btn btn-info me-2">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Book Details Column -->
                        <div class="col-md-8">
                            <div class="card book-info-card h-100">
                                <div class="card-body">
                                    <h2 class="card-title mb-4"><?= htmlspecialchars($book['title']) ?></h2>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Author:</div>
                                        <div class="col-md-9 col-sm-8"><?= htmlspecialchars($book['author']) ?></div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">ISBN:</div>
                                        <div class="col-md-9 col-sm-8"><?= htmlspecialchars($book['isbn']) ?></div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Publisher:</div>
                                        <div class="col-md-9 col-sm-8"><?= isset($book['publisher']) ? htmlspecialchars($book['publisher']) : '-' ?></div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Publication Year:</div>
                                        <div class="col-md-9 col-sm-8"><?= isset($book['publication_year']) ? htmlspecialchars($book['publication_year']) : '-' ?></div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Bookshelf:</div>
                                        <div class="col-md-9 col-sm-8"><?= isset($book['bookshelf']) ? htmlspecialchars($book['bookshelf']) : '-' ?></div>
                                    </div>
                                    
                                    <?php if (isset($book['bookshelf_location']) && !empty($book['bookshelf_location'])): ?>
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Location:</div>
                                        <div class="col-md-9 col-sm-8"><?= htmlspecialchars($book['bookshelf_location']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-3 col-sm-4 detail-label">Availability:</div>
                                        <div class="col-md-9 col-sm-8">
                                            <span class="badge <?= isset($book['copies']) && $book['copies'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?= isset($book['copies']) ? $book['copies'] : 0 ?> copies available
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="description-box">
                                        <h5 class="mb-3"><i class="fas fa-book-open me-2"></i>Book Description</h5>
                                        <?php if (isset($book['description']) && !empty($book['description'])): ?>
                                            <p><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                                        <?php else: ?>
                                            <p class="text-muted"><em>No description available for this book.</em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="books.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Books
                </a>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="borrowed_books.php" class="btn btn-info">
                        <i class="fas fa-book-reader me-1"></i> My Borrowed Books
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <?php if ($is_admin): ?>
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete the book "<?= htmlspecialchars($book['title']) ?>"? 
                            This action cannot be undone.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post" action="delete_book.php">
                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                <button type="submit" name="delete_book" class="btn btn-danger">Delete Book</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>