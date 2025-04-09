
<?php
// This might be part of books.php or another page that lists books
// Add this where you want to show/hide the "Add Book" button

// Add Book button - Only visible to admins
if (is_admin()): ?>
    <a href="add_book.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add New Book
    </a>
<?php endif; ?>