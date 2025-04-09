<?php
session_start();
include 'db.php';
include_once 'auth_functions.php';
$is_admin = is_admin(); // Use the consistent is_admin() function

// Initialize variables
$result = null;
$error_message = null;
$success_message = isset($_GET['success']) ? $_GET['success'] : null;

// Get search parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_field = isset($_GET['field']) ? $_GET['field'] : 'all';

try {
    // Prepare the base query
    $sql = "SELECT * FROM books";
    $params = [];
    $types = "";
    
    // Add search conditions if search term is provided
    if (!empty($search_term)) {
        // Different WHERE clauses based on selected field
        if ($search_field === 'title') {
            $sql .= " WHERE title LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $types .= "s";
        } elseif ($search_field === 'author') {
            $sql .= " WHERE author LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $types .= "s";
        } elseif ($search_field === 'isbn') {
            $sql .= " WHERE isbn LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $types .= "s";
        } elseif ($search_field === 'publisher') {
            $sql .= " WHERE publisher LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $types .= "s";
        } elseif ($search_field === 'bookshelf') {
            $sql .= " WHERE bookshelf LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $types .= "s";
        } else {
            // Search in all fields if 'all' is selected
            $sql .= " WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR publisher LIKE ? OR bookshelf LIKE ?";
            $search_param = "%" . $search_term . "%";
            $params = array_fill(0, 5, $search_param);
            $types .= "sssss";
        }
    }
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }
    
    // Bind parameters if there are any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query");
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Failed to get result set");
    }
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Display mode: table or grid
$display_mode = isset($_GET['display']) ? $_GET['display'] : 'table';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/reset.css">
    <style>
        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .book-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .book-card-image {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .book-details {
            min-height: 100px;
        }
        .display-toggle-btn.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-book me-2"></i>Book Collection</h2>
            </div>
            <?php if ($is_admin): ?>
            <div class="col-md-6 text-end">
                <a href="add_edit_book.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Add New Book
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Search Form -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="books.php" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search books..." value="<?= htmlspecialchars($search_term) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="field" class="form-select">
                            <option value="all" <?= $search_field === 'all' ? 'selected' : '' ?>>All Fields</option>
                            <option value="title" <?= $search_field === 'title' ? 'selected' : '' ?>>Title</option>
                            <option value="author" <?= $search_field === 'author' ? 'selected' : '' ?>>Author</option>
                            <option value="isbn" <?= $search_field === 'isbn' ? 'selected' : '' ?>>ISBN</option>
                            <option value="publisher" <?= $search_field === 'publisher' ? 'selected' : '' ?>>Publisher</option>
                            <option value="bookshelf" <?= $search_field === 'bookshelf' ? 'selected' : '' ?>>Bookshelf</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <?php if (!empty($search_term)): ?>
                            <a href="books.php" class="btn btn-secondary w-100">Clear</a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group w-100" role="group">
                            <a href="?<?= http_build_query(array_merge($_GET, ['display' => 'table'])) ?>" class="btn btn-outline-primary display-toggle-btn <?= $display_mode === 'table' ? 'active' : '' ?>">
                                <i class="fas fa-list"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['display' => 'grid'])) ?>" class="btn btn-outline-primary display-toggle-btn <?= $display_mode === 'grid' ? 'active' : '' ?>">
                                <i class="fas fa-th-large"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <!-- Show search result count when searching -->
                    <?php if (!empty($search_term)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Found <?= $result->num_rows ?> result(s) for: <strong><?= htmlspecialchars($search_term) ?></strong>
                            <?php if ($search_field !== 'all'): ?> 
                                in <strong><?= htmlspecialchars($search_field) ?></strong>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                
                    <?php if ($display_mode === 'table'): ?>
                        <!-- Table View -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Cover</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Publisher</th>
                                        <th>Bookshelf</th>
                                        <th>Copies</th>
                                        <?php if (isset($_SESSION['user'])): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                        <?php if ($is_admin): ?>
                                            <th>Admin Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'uploads/books/default_book.jpg' ?>" 
                                                     alt="<?= htmlspecialchars($row['title']) ?>" class="book-cover">
                                            </td>
                                            <td>
                                                <?php if ($is_admin): ?>
                                                    <a href="add_edit_book.php?id=<?= $row['id'] ?>" class="text-decoration-none fw-bold">
                                                        <?= htmlspecialchars($row['title']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="book_details.php?id=<?= $row['id'] ?>" class="text-decoration-none fw-bold">
                                                        <?= htmlspecialchars($row['title']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['author']) ?></td>
                                            <td><?= isset($row['publisher']) ? htmlspecialchars($row['publisher']) : '-' ?></td>
                                            <td><?= isset($row['bookshelf']) ? htmlspecialchars($row['bookshelf']) : '-' ?></td>
                                            <td>
                                                <span class="badge <?= (isset($row['copies']) && $row['copies'] > 0) || (isset($row['quantity']) && $row['quantity'] > 0) ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= isset($row['copies']) ? $row['copies'] : (isset($row['quantity']) ? $row['quantity'] : 0) ?> available
                                                </span>
                                            </td>
                                            <?php if (isset($_SESSION['user'])): ?>
                                                <td>
                                                    <?php $available = isset($row['copies']) ? $row['copies'] : (isset($row['quantity']) ? $row['quantity'] : 0); ?>
                                                    <?php if ($available > 0): ?>
                                                        <form method="post" action="borrow.php" style="display: inline;">
                                                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-hand-holding me-1"></i> Borrow
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                            <i class="fas fa-ban me-1"></i> Out of Stock
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($is_admin): ?>
                                                <td>
                                                    <a href="add_edit_book.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="post" action="delete_book.php" style="display: inline;" 
                                                        onsubmit="return confirm('Are you sure you want to delete this book?');">
                                                        <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                    <!-- Reset data pointer to beginning for grid view -->
                                    <?php $result->data_seek(0); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Grid View -->
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <div class="col">
                                    <div class="card h-100 book-card">
                                        <img src="<?= !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'uploads/books/default_book.jpg' ?>" 
                                             class="book-card-image" alt="<?= htmlspecialchars($row['title']) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php if ($is_admin): ?>
                                                    <a href="add_edit_book.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($row['title']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="book_details.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($row['title']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="card-text book-details">
                                                <strong>Author:</strong> <?= htmlspecialchars($row['author']) ?><br>
                                                <strong>Publisher:</strong> <?= isset($row['publisher']) ? htmlspecialchars($row['publisher']) : '-' ?><br>
                                                <strong>Bookshelf:</strong> <?= isset($row['bookshelf']) ? htmlspecialchars($row['bookshelf']) : '-' ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <?php $available = isset($row['copies']) ? $row['copies'] : (isset($row['quantity']) ? $row['quantity'] : 0); ?>
                                                <span class="badge <?= $available > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $available ?> available
                                                </span>
                                                
                                                <?php if (isset($_SESSION['user'])): ?>
                                                    <?php if ($available > 0): ?>
                                                        <form method="post" action="borrow.php">
                                                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-hand-holding me-1"></i> Borrow
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                            <i class="fas fa-ban me-1"></i> Out of Stock
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($is_admin): ?>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between">
                                                    <a href="add_edit_book.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info w-100 me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="post" action="delete_book.php" class="w-100 ms-1" 
                                                        onsubmit="return confirm('Are you sure you want to delete this book?');">
                                                        <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" name="delete_book" class="btn btn-sm btn-danger w-100">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php if (!empty($search_term)): ?>
                            No books found matching your search criteria.
                        <?php else: ?>
                            <?= $error_message ? 'Unable to retrieve books.' : 'No books available in the library.' ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_admin): ?>
                        <div class="text-center mt-4">
                            <a href="add_edit_book.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Your First Book
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home me-1"></i> Back to Home
            </a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="borrowed_books.php" class="btn btn-info">
                    <i class="fas fa-book-reader me-1"></i> My Borrowed Books
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>