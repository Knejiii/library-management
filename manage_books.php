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
$book_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$book_data = [
    'title' => '',
    'isbn' => '',
    'author' => '',
    'publisher' => '',
    'publication_year' => '',
    'bookshelf' => '',
    'quantity' => 1,
    'description' => '',
    'bookshelf_location' => '',
    'image_path' => ''
];

// Handle book deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->begin_transaction();
    
    try {
        // First check if book is borrowed
        $check_sql = "SELECT COUNT(*) as borrowed FROM borrowings WHERE book_id = ? AND return_date IS NULL";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['borrowed'] > 0) {
            throw new Exception("Cannot delete book because it is currently borrowed.");
        }
        
        // Delete the book
        $delete_sql = "DELETE FROM books WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $delete_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to delete book: " . $delete_stmt->error);
        }
        
        $conn->commit();
        $success_message = "Book successfully deleted.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Load book data if editing
if ($book_id > 0) {
    $sql = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $book_data = [
            'title' => $book['title'],
            'isbn' => $book['isbn'],
            'author' => $book['author'],
            'publisher' => $book['publisher'],
            'publication_year' => $book['publication_year'],
            'bookshelf' => $book['bookshelf'],
            'quantity' => $book['copies'],
            'description' => $book['description'],
            'bookshelf_location' => $book['bookshelf_location'],
            'image_path' => $book['image_path']
        ];
    } else {
        $error_message = "Book not found.";
        $book_id = 0;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $book_data = [
        'title' => $_POST['title'] ?? '',
        'isbn' => $_POST['isbn'] ?? '',
        'author' => $_POST['author'] ?? '',
        'publisher' => $_POST['publisher'] ?? '',
        'publication_year' => $_POST['publication_year'] ?? '',
        'bookshelf' => $_POST['bookshelf'] ?? '',
        'quantity' => intval($_POST['quantity'] ?? 1),
        'description' => $_POST['description'] ?? '',
        'bookshelf_location' => $_POST['bookshelf_location'] ?? '',
        'image_path' => $_POST['image_path'] ?? ''
    ];
    
    // Handle image upload if present
    if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/books/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['book_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['book_image']['tmp_name'], $target_file)) {
            $book_data['image_path'] = $target_file;
        } else {
            $error_message = "Failed to upload image.";
        }
    }
    
    // Validate form data
    if (empty($book_data['title'])) {
        $error_message = "Title is required.";
    } elseif (empty($book_data['author'])) {
        $error_message = "Author is required.";
    } elseif ($book_data['quantity'] < 0) {
        $error_message = "Quantity cannot be negative.";
    }
    
    // Save book if no errors
    if (empty($error_message)) {
        try {
            if ($book_id > 0) {
                // Update existing book
                updateBook($book_id, $book_data);
                $success_message = "Book updated successfully.";
            } else {
                // Add new book
                $new_id = insertBook($book_data);
                $success_message = "Book added successfully.";
                
                // Clear form after successful add
                $book_data = [
                    'title' => '',
                    'isbn' => '',
                    'author' => '',
                    'publisher' => '',
                    'publication_year' => '',
                    'bookshelf' => '',
                    'quantity' => 1,
                    'description' => '',
                    'bookshelf_location' => '',
                    'image_path' => ''
                ];
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get all books for the table display
$sql = "SELECT * FROM books ORDER BY title";
$result = $conn->query($sql);
$books = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
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
    <title>Manage Books - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h1><?php echo $book_id > 0 ? 'Edit Book' : 'Add New Book'; ?></h1>
        
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
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo $book_id > 0 ? 'Edit Book' : 'Add New Book'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book_data['title']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book_data['isbn']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book_data['author']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="publisher" class="form-label">Publisher</label>
                                <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book_data['publisher']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="publication_year" class="form-label">Publication Year</label>
                                <input type="text" class="form-control" id="publication_year" name="publication_year" value="<?php echo htmlspecialchars($book_data['publication_year']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bookshelf" class="form-label">Bookshelf</label>
                                <input type="text" class="form-control" id="bookshelf" name="bookshelf" value="<?php echo htmlspecialchars($book_data['bookshelf']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bookshelf_location" class="form-label">Bookshelf Location</label>
                                <input type="text" class="form-control" id="bookshelf_location" name="bookshelf_location" value="<?php echo htmlspecialchars($book_data['bookshelf_location']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Copies Available</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($book_data['quantity']); ?>" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($book_data['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="book_image" class="form-label">Book Cover Image</label>
                                <input type="file" class="form-control" id="book_image" name="book_image">
                                <?php if (!empty($book_data['image_path'])): ?>
                                    <div class="mt-2">
                                        <p>Current image: <?php echo htmlspecialchars($book_data['image_path']); ?></p>
                                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($book_data['image_path']); ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary"><?php echo $book_id > 0 ? 'Update Book' : 'Add Book'; ?></button>
                                <?php if ($book_id > 0): ?>
                                    <a href="manage_books.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Book Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>ISBN</th>
                                        <th>Copies</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($books)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No books found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($books as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                                <td><?php echo htmlspecialchars($book['copies']); ?></td>
                                                <td>
                                                    <a href="manage_books.php?edit=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="manage_books.php?delete=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this book?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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