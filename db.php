<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "library_db";

// Set timezone for all database operations
date_default_timezone_set('Asia/Manila');

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database exists, if not create it
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Function to initialize database schema if needed
function initializeDatabase() {
    global $conn;
    
    // Check if tables exist already
    $result = $conn->query("SHOW TABLES LIKE 'books'");
    if ($result->num_rows == 0) {
        // Tables don't exist, so create them
        
        // Create books table
        $sql = "CREATE TABLE `books` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `author` varchar(255) NOT NULL,
          `copies` int(11) NOT NULL,
          `available` tinyint(1) DEFAULT 1,
          `image_path` varchar(255) DEFAULT 'default_book.jpg',
          `bookshelf` varchar(100) DEFAULT NULL,
          `bookshelf_location` varchar(100) DEFAULT NULL,
          `isbn` varchar(20) DEFAULT NULL,
          `publisher` varchar(255) DEFAULT NULL,
          `publication_year` int(11) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `quantity` int(11) NOT NULL DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_isbn` (`isbn`),
          KEY `idx_title` (`title`),
          KEY `idx_author` (`author`),
          KEY `idx_bookshelf` (`bookshelf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error creating books table: " . $conn->error);
        }
        
        // Create users table
        $sql = "CREATE TABLE `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(255) NOT NULL,
          `password` varchar(255) NOT NULL,
          `is_admin` tinyint(1) DEFAULT 0,
          `role` varchar(20) DEFAULT 'student',
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error creating users table: " . $conn->error);
        }
        
        // Create borrowings table
        $sql = "CREATE TABLE `borrowings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `book_id` int(11) NOT NULL,
          `borrow_date` datetime DEFAULT NULL,
          `due_date` date NOT NULL,
          `return_date` datetime DEFAULT NULL,
          `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `book_id` (`book_id`),
          CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
          CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error creating borrowings table: " . $conn->error);
        }
        
        // Create borrowed_books table (legacy table?)
        $sql = "CREATE TABLE `borrowed_books` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL,
          `book_id` int(11) DEFAULT NULL,
          `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
          `return_date` timestamp NULL DEFAULT NULL,
          `status` enum('borrowed','returned') DEFAULT 'borrowed',
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `book_id` (`book_id`),
          CONSTRAINT `borrowed_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `borrowed_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error creating borrowed_books table: " . $conn->error);
        }
        
        // Create categories table
        $sql = "CREATE TABLE `categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `description` text DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error creating categories table: " . $conn->error);
        }
        
        // Insert default categories
        $sql = "INSERT INTO `categories` (`name`, `description`) VALUES
        ('Fiction', 'Novels and made-up stories'),
        ('Non-fiction', 'Factual books'),
        ('Science', 'Books about scientific subjects'),
        ('History', 'Historical books and references'),
        ('Technology', 'Books about technology and computing')";
        
        if ($conn->query($sql) !== TRUE) {
            die("Error inserting default categories: " . $conn->error);
        }
        
        echo "Database initialized successfully!";
    }
}

// Call the initialization function to ensure database is set up
initializeDatabase();

// Function to insert a new book
function insertBook(&$book_data) {
    global $conn;
    
    $sql = "INSERT INTO books (title, isbn, author, publisher, publication_year, 
            bookshelf, copies, description, bookshelf_location, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $title = $book_data['title'];
    $isbn = $book_data['isbn'];
    $author = $book_data['author'];
    $publisher = $book_data['publisher'];
    $publication_year = $book_data['publication_year'];
    $bookshelf = $book_data['bookshelf'];
    $quantity = $book_data['quantity'];
    $description = $book_data['description'];
    $bookshelf_location = $book_data['bookshelf_location'] ?? '';
    $image_path = $book_data['image_path'] ?? '';
    
    $stmt->bind_param("ssssssdsss", 
        $title, 
        $isbn, 
        $author, 
        $publisher, 
        $publication_year, 
        $bookshelf, 
        $quantity, 
        $description,
        $bookshelf_location,
        $image_path
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $conn->insert_id;
}

// Include the rest of your existing functions here
// Function to update an existing book
function updateBook($book_id, &$book_data) {
    global $conn;
    
    $sql = "UPDATE books SET 
            title = ?, 
            isbn = ?, 
            author = ?, 
            publisher = ?, 
            publication_year = ?, 
            bookshelf = ?, 
            copies = ?, 
            description = ?,
            bookshelf_location = ?,
            image_path = ?
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $title = $book_data['title'];
    $isbn = $book_data['isbn'];
    $author = $book_data['author'];
    $publisher = $book_data['publisher'];
    $publication_year = $book_data['publication_year'];
    $bookshelf = $book_data['bookshelf'];
    $quantity = $book_data['quantity'];
    $description = $book_data['description'];
    $bookshelf_location = $book_data['bookshelf_location'] ?? '';
    $image_path = $book_data['image_path'] ?? '';
    
    $stmt->bind_param("ssssssdsssi", 
        $title, 
        $isbn, 
        $author, 
        $publisher, 
        $publication_year, 
        $bookshelf, 
        $quantity, 
        $description,
        $bookshelf_location,
        $image_path,
        $book_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return true;
}

// ROLE-BASED ACCESS CONTROL FUNCTIONS

/**
 * Get user role by ID
 * @param int $user_id The user ID
 * @return string|null The user's role or null if not found
 */
function getUserRole($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['role'];
    }
    
    return null;
}

/**
 * Update user role
 * @param int $user_id The user ID
 * @param string $role The new role ('admin' or 'student')
 * @return bool True if successful, false otherwise
 */
function updateUserRole($user_id, $role) {
    global $conn;
    
    if ($role !== 'admin' && $role !== 'student') {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("si", $role, $user_id);
    return $stmt->execute();
}

/**
 * Add a new borrowing record with real-time timestamp
 * @param int $user_id The user ID
 * @param int $book_id The book ID
 * @param string $due_date The due date (YYYY-MM-DD)
 * @return int|bool The new borrowing ID or false on failure
 */
function addBorrowing($user_id, $book_id, $due_date) {
    global $conn;
    
    // Ensure proper timezone is set
    date_default_timezone_set('Asia/Manila');
    
    // First check if the book is available
    $book_query = $conn->prepare("SELECT copies FROM books WHERE id = ?");
    $book_query->bind_param("i", $book_id);
    $book_query->execute();
    $book_result = $book_query->get_result();
    $book_data = $book_result->fetch_assoc();
    
    if (!$book_data || $book_data['copies'] <= 0) {
        return false; // Book not available
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Reduce book copies by 1
        $update_book = $conn->prepare("UPDATE books SET copies = copies - 1 WHERE id = ?");
        $update_book->bind_param("i", $book_id);
        $update_book->execute();
        
        // Add borrowing record with exact timestamp
        $borrow_date = date('Y-m-d H:i:s'); // Use current date and time with seconds
        $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
        $stmt->bind_param("iiss", $user_id, $book_id, $borrow_date, $due_date);
        $stmt->execute();
        $borrowing_id = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        return $borrowing_id;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

/**
 * Return a borrowed book with real-time timestamp
 * @param int $borrowing_id The borrowing record ID
 * @param string|null $return_timestamp The return timestamp (Y-m-d H:i:s format)
 * @return bool True if successful, false otherwise
 */
function returnBook($borrowing_id, $return_timestamp = null) {
    global $conn;
    
    // Ensure proper timezone is set
    date_default_timezone_set('Asia/Manila');
    
    // If no timestamp provided, use current date and time
    if ($return_timestamp === null) {
        $return_timestamp = date('Y-m-d H:i:s');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get the book ID from the borrowing record
        $get_book = $conn->prepare("SELECT book_id FROM borrowings WHERE id = ? AND return_date IS NULL");
        $get_book->bind_param("i", $borrowing_id);
        $get_book->execute();
        $result = $get_book->get_result();
        
        if ($result->num_rows === 0) {
            return false; // No active borrowing found
        }
        
        $book_data = $result->fetch_assoc();
        $book_id = $book_data['book_id'];
        
        // Update borrowing record with the exact timestamp
        $update_borrowing = $conn->prepare("UPDATE borrowings SET return_date = ?, status = 'returned' WHERE id = ?");
        $update_borrowing->bind_param("si", $return_timestamp, $borrowing_id);
        $update_borrowing->execute();
        
        // Increase book copies by 1
        $update_book = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE id = ?");
        $update_book->bind_param("i", $book_id);
        $update_book->execute();
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

/**
 * Get all borrowings for a specific user
 * @param int $user_id The user ID
 * @return array The borrowing records
 */
function getUserBorrowings($user_id) {
    global $conn;
    
    $sql = "SELECT b.*, books.title, books.author, books.isbn 
            FROM borrowings b
            JOIN books ON b.book_id = books.id
            WHERE b.user_id = ?
            ORDER BY b.borrow_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $borrowings = [];
    
    while ($row = $result->fetch_assoc()) {
        $borrowings[] = $row;
    }
    
    return $borrowings;
}

/**
 * Get all borrowings (admin function)
 * @param string $status Filter by status (optional)
 * @return array The borrowing records
 */
function getAllBorrowings($status = null) {
    global $conn;
    
    $sql = "SELECT b.*, books.title, books.author, users.username 
            FROM borrowings b
            JOIN books ON b.book_id = books.id
            JOIN users ON b.user_id = users.id";
    
    if ($status) {
        $sql .= " WHERE b.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowings = [];
    
    while ($row = $result->fetch_assoc()) {
        $borrowings[] = $row;
    }
    
    return $borrowings;
}

/**
 * Check for overdue books and update their status
 * @return int Number of books marked as overdue
 */
function updateOverdueBooks() {
    global $conn;
    
    $today = date('Y-m-d');
    $sql = "UPDATE borrowings 
            SET status = 'overdue' 
            WHERE status = 'borrowed' 
            AND return_date IS NULL 
            AND due_date < ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    
    return $stmt->affected_rows;
}

/**
 * Update database schema to support timestamps with time component
 * Run this once to modify the borrowings table if it only has date (no time)
 * @return bool True if successful, false otherwise
 */
function updateSchemaForTimestamps() {
    global $conn;
    
    try {
        // Check if the column needs to be modified
        $result = $conn->query("SHOW COLUMNS FROM borrowings LIKE 'borrow_date'");
        $row = $result->fetch_assoc();
        
        // Only update if the column is DATE format, not DATETIME
        if (strpos(strtolower($row['Type']), 'date') !== false && 
            strpos(strtolower($row['Type']), 'datetime') === false) {
            
            // Modify borrow_date column to DATETIME
            $conn->query("ALTER TABLE borrowings MODIFY COLUMN borrow_date DATETIME");
            
            // Modify return_date column to DATETIME
            $conn->query("ALTER TABLE borrowings MODIFY COLUMN return_date DATETIME NULL");
            
            return true;
        }
        
        return false; // No changes needed
        
    } catch (Exception $e) {
        return false;
    }
}
?>