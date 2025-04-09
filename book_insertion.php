<?php
function insertBook($conn, $book_data) {
    $sql = "INSERT INTO books 
            (title, isbn, author, publisher, publication_year, 
             bookshelf, bookshelf_location, description, copies, image_path) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "ssssssssss", 
        $book_data['title'], 
        $book_data['isbn'], 
        $book_data['author'], 
        $book_data['publisher'], 
        $book_data['publication_year'],
        $book_data['bookshelf'], 
        $book_data['bookshelf_location'], 
        $book_data['description'], 
        $book_data['quantity'], 
        $book_data['image_path']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt->insert_id;
}

function updateBook($conn, $book_id, $book_data) {
    $sql = "UPDATE books 
            SET title = ?, isbn = ?, author = ?, publisher = ?, 
                publication_year = ?, bookshelf = ?, bookshelf_location = ?, 
                description = ?, copies = ?, image_path = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "ssssssssssi", 
        $book_data['title'], 
        $book_data['isbn'], 
        $book_data['author'], 
        $book_data['publisher'], 
        $book_data['publication_year'],
        $book_data['bookshelf'], 
        $book_data['bookshelf_location'], 
        $book_data['description'], 
        $book_data['quantity'], 
        $book_data['image_path'],
        $book_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return true;
}