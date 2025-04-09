<?php
// Script to update database schema to support timestamps with time

require_once 'db.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

echo "Checking database schema for timestamp support...<br>";

try {
    // Check if the function exists
    if (function_exists('updateSchemaForTimestamps')) {
        $result = updateSchemaForTimestamps();
        
        if ($result) {
            echo "Success! Database updated to support timestamps with time.<br>";
        } else {
            echo "No changes needed. Schema already supports timestamps with time.<br>";
        }
    } else {
        // If function doesn't exist in db.php, implement it directly
        global $conn;
        
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
            
            echo "Success! Database manually updated to support timestamps with time.<br>";
        } else {
            echo "No changes needed. Schema already supports timestamps with time.<br>";
        }
    }
    
    echo "<br>Current server time: " . date('Y-m-d H:i:s') . " (PHT)<br>";
    echo "<br><a href='index.php'>Return to Home</a>";
    
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "<br>";
    echo "<br><a href='index.php'>Return to Home</a>";
}
?>