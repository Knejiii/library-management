<?php
session_start();
include 'db.php';
require_once 'auth_functions.php';

// Check if user is logged in
require_login();

// Check if the user is a student (not admin and doesn't have add_books permission)
$is_student = !is_admin() && !is_permission_enabled('add_books', false);

// If user is a student, redirect them silently to the books page
if ($is_student) {
    header('Location: books.php');
    exit;
}

// Proceed with normal functionality for admin/staff with proper permissions
// Determine if we're in edit mode or add mode
$is_edit_mode = isset($_GET['id']) && intval($_GET['id']) > 0;
$book_id = $is_edit_mode ? intval($_GET['id']) : 0;

// For editing existing books, check edit permission
if ($is_edit_mode) {
    $can_edit_books = is_permission_enabled('edit_books', true);
    if (!$can_edit_books && !is_admin()) {
        $_SESSION['error'] = "Book editing is currently restricted by the administrator.";
        header('Location: books.php');
        exit;
    }
    
    // Additional security: Verify the requested book exists
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = 'Book not found';
        header('Location: books.php');
        exit;
    }
    
    $book = $result->fetch_assoc();
}

// Initialize variables
$page_title = $book_id ? 'Edit Book' : 'Add New Book';
$error_message = '';
$success_message = '';

// If not in edit mode, initialize empty book data
if (!isset($book)) {
    $book = [
        'title' => '',
        'isbn' => '',
        'author' => '',
        'publisher' => '',
        'publication_year' => '',
        'bookshelf' => '',
        'bookshelf_location' => '',
        'description' => '',
        'copies' => 1,
        'quantity' => 1,
        'image_path' => 'uploads/books/default_book.jpg'
    ];
}

// Define bookshelf options (in production, fetch from database)
$bookshelf_options = [
    'Fiction' => 'Main Floor',
    'Non-Fiction' => 'Second Floor',
    'Reference' => 'Study Room',
    'Children' => 'Kids Corner',
    'Sci-Fi' => 'Basement',
    'Biography' => 'Main Floor - Section B',
    'History' => 'Second Floor - East Wing'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation
    if (empty($_POST['title'])) {
        $error_message = 'Book title is required';
    } elseif (empty($_POST['author'])) {
        $error_message = 'Author name is required';
    } else {
        // Process bookshelf information
        $bookshelf = $_POST['bookshelf'] ?? '';
        $bookshelf_location = '';
        
        if ($bookshelf === 'custom') {
            $bookshelf = $_POST['custom_bookshelf'] ?? '';
            $bookshelf_location = $_POST['bookshelf_location'] ?? '';
        } else {
            $bookshelf_location = isset($bookshelf_options[$bookshelf]) ? $bookshelf_options[$bookshelf] : '';
        }
        
        // Handle image upload if present
        $image_path = $book['image_path']; // Default to current image
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/books/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['book_image']['name']);
            $target_path = $upload_dir . $file_name;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES['book_image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                $error_message = "Error uploading file.";
            }
        }
        
        if (empty($error_message)) {
            try {
                // Prepare data for database operation
                $title = $_POST['title'];
                $isbn = $_POST['isbn'] ?? '';
                $author = $_POST['author'];
                $publisher = $_POST['publisher'] ?? '';
                $publication_year = $_POST['publication_year'] ?? '';
                $description = $_POST['description'] ?? '';
                $copies = intval($_POST['quantity']);
                
                // Database operation (update or insert)
                if ($book_id) {
                    // Update existing book
                    $stmt = $conn->prepare("UPDATE books SET title = ?, isbn = ?, author = ?, publisher = ?, 
                            publication_year = ?, bookshelf = ?, bookshelf_location = ?, description = ?, 
                            copies = ?, image_path = ? WHERE id = ?");
                    
                    $stmt->bind_param("ssssssssssi", $title, $isbn, $author, $publisher, $publication_year, 
                            $bookshelf, $bookshelf_location, $description, $copies, $image_path, $book_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Book updated successfully!";
                        header('Location: books.php');
                        exit;
                    } else {
                        $error_message = "Error updating book: " . $conn->error;
                    }
                } else {
                    // Insert new book
                    $stmt = $conn->prepare("INSERT INTO books (title, isbn, author, publisher, publication_year, 
                            bookshelf, bookshelf_location, description, copies, image_path) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("ssssssssss", $title, $isbn, $author, $publisher, $publication_year, 
                            $bookshelf, $bookshelf_location, $description, $copies, $image_path);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Book added successfully!";
                        header('Location: books.php');
                        exit;
                    } else {
                        $error_message = "Error adding book: " . $conn->error;
                    }
                }
            } catch (Exception $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Check if navbar.php exists and include it
$navbar_file = 'navbar.php';
$include_navbar = file_exists($navbar_file);
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
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            max-width: 650px;
            margin: 2rem auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 1rem;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .preview-image {
            max-height: 120px;
            width: auto;
            margin: 0 auto;
            display: block;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: 500;
            color: #0d6efd;
        }
        .form-label {
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .tab-content {
            padding: 1.5rem 0;
        }
    </style>
</head>
<body>
    <?php if ($include_navbar): include $navbar_file; endif; ?>
    
    <div class="container py-4">
        <!-- Alert Messages -->
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <?php unset($_SESSION['message']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i><?= htmlspecialchars($page_title) ?></h5>
                <a href="books.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Books
                </a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" id="bookForm">
                    <input type="hidden" name="book_id" value="<?= $book_id ?>">
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="bookFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" 
                                    type="button" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fas fa-info-circle me-1"></i> General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bookshelf-tab" data-bs-toggle="tab" data-bs-target="#bookshelf" 
                                    type="button" role="tab" aria-controls="bookshelf" aria-selected="false">
                                <i class="fas fa-bookmark me-1"></i> Bookshelf
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" 
                                    type="button" role="tab" aria-controls="details" aria-selected="false">
                                <i class="fas fa-list me-1"></i> Details
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content">
                        <!-- General tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="mb-3">
                                <label for="title" class="form-label required-field">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       value="<?= htmlspecialchars($book['title']) ?>">
                                <div class="form-text">Enter the complete title of the book</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" 
                                       value="<?= htmlspecialchars($book['isbn'] ?? '') ?>">
                                <div class="form-text">10 or 13-digit International Standard Book Number</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="author" class="form-label required-field">Author</label>
                                <input type="text" class="form-control" id="author" name="author" required
                                       value="<?= htmlspecialchars($book['author']) ?>">
                                <div class="form-text">Primary author or multiple authors separated by commas</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publisher" class="form-label">Publisher</label>
                                        <input type="text" class="form-control" id="publisher" name="publisher"
                                               value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publication_year" class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" id="publication_year" name="publication_year"
                                               value="<?= htmlspecialchars($book['publication_year'] ?? '') ?>"
                                               min="1000" max="<?= date('Y') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bookshelf tab -->
                        <div class="tab-pane fade" id="bookshelf" role="tabpanel" aria-labelledby="bookshelf-tab">
                            <div class="mb-3">
                                <label for="bookshelf_select" class="form-label">Bookshelf</label>
                                <select class="form-select" id="bookshelf_select" name="bookshelf" onchange="toggleCustomBookshelf(this.value)">
                                    <option value="">-- Select Bookshelf --</option>
                                    <?php foreach ($bookshelf_options as $shelf => $location): ?>
                                        <option value="<?= htmlspecialchars($shelf) ?>" 
                                                <?= (isset($book['bookshelf']) && $book['bookshelf'] === $shelf) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($shelf) ?> (<?= htmlspecialchars($location) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom" 
                                            <?= (isset($book['bookshelf']) && !isset($bookshelf_options[$book['bookshelf']])) ? 'selected' : '' ?>>
                                        Custom Bookshelf
                                    </option>
                                </select>
                            </div>
                            
                            <div id="custom_bookshelf_div" 
                                 style="display: <?= (isset($book['bookshelf']) && !isset($bookshelf_options[$book['bookshelf']])) ? 'block' : 'none' ?>;">
                                <div class="mb-3">
                                    <label for="custom_bookshelf" class="form-label">Custom Bookshelf Name</label>
                                    <input type="text" class="form-control" id="custom_bookshelf" name="custom_bookshelf" 
                                           value="<?= (isset($book['bookshelf']) && !isset($bookshelf_options[$book['bookshelf']])) ? htmlspecialchars($book['bookshelf']) : '' ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bookshelf_location" class="form-label">Bookshelf Location</label>
                                    <input type="text" class="form-control" id="bookshelf_location" name="bookshelf_location"
                                           value="<?= htmlspecialchars($book['bookshelf_location'] ?? '') ?>">
                                    <div class="form-text">Where this bookshelf is located (e.g., "Third Floor - Corner")</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Details tab -->
                        <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
                                <div class="form-text">Book summary or synopsis</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label required-field">Copies</label>
                                        <?php 
                                            $copies = 1;
                                            if (isset($book['copies'])) {
                                                $copies = $book['copies'];
                                            } elseif (isset($book['quantity'])) {
                                                $copies = $book['quantity'];
                                            }
                                        ?>
                                        <input type="number" class="form-control" id="quantity" name="quantity" 
                                               value="<?= intval($copies) ?>" min="1" required>
                                        <div class="form-text">Total copies available</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="book_image" class="form-label">Book Cover</label>
                                        <?php if (isset($book['image_path']) && $book['image_path']): ?>
                                            <img src="<?= htmlspecialchars($book['image_path']) ?>" class="preview-image mb-2" alt="Book Cover" id="coverPreview">
                                        <?php else: ?>
                                            <img src="uploads/books/default_book.jpg" class="preview-image mb-2" alt="Default Cover" id="coverPreview">
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="book_image" name="book_image" accept="image/*" onchange="previewImage()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form actions -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="books.php" class="btn btn-light">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> <?= $book_id ? 'Update Book' : 'Save Book' ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle custom bookshelf inputs visibility
        function toggleCustomBookshelf(value) {
            const customDiv = document.getElementById('custom_bookshelf_div');
            if (value === 'custom') {
                customDiv.style.display = 'block';
                document.getElementById('custom_bookshelf').required = true;
            } else {
                customDiv.style.display = 'none';
                document.getElementById('custom_bookshelf').required = false;
            }
        }
        
        // Preview uploaded image
        function previewImage() {
            const preview = document.getElementById('coverPreview');
            const file = document.getElementById('book_image').files[0];
            const reader = new FileReader();
            
            reader.onloadend = function() {
                preview.src = reader.result;
            }
            
            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.src = "<?= htmlspecialchars($book['image_path'] ?? 'uploads/books/default_book.jpg') ?>";
            }
        }
        
        // Form validation
        document.getElementById('bookForm').addEventListener('submit', function(event) {
            const title = document.getElementById('title').value.trim();
            const author = document.getElementById('author').value.trim();
            const quantity = document.getElementById('quantity').value;
            const bookshelf = document.getElementById('bookshelf_select').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!title) {
                errorMessage = 'Book title is required';
                isValid = false;
            } else if (!author) {
                errorMessage = 'Author name is required';
                isValid = false;
            } else if (quantity < 1) {
                errorMessage = 'Number of copies must be at least 1';
                isValid = false;
            } else if (bookshelf === 'custom') {
                const customBookshelf = document.getElementById('custom_bookshelf').value.trim();
                if (!customBookshelf) {
                    errorMessage = 'Custom bookshelf name is required';
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
    </script>
</body>
</html>