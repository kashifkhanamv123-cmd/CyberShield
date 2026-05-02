<?php
require_once __DIR__ . '/../config/db.php';

$sql = file_get_contents(__DIR__ . '/../fix_database_schema.sql');

if ($conn->multi_query($sql)) {
    do {
        /* store first result set */
        if ($result = $conn->store_result()) {
            $result->free();
        }
        /* print divider */
        if ($conn->more_results()) {
            // echo "-----------------\n";
        }
    } while ($conn->next_result());
    echo "Database schema updated successfully.\n";
} else {
    echo "Error updating database: " . $conn->error . "\n";
}
?>
