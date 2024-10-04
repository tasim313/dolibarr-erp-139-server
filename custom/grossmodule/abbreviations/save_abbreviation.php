<?php

// Include the connection file
include('../connection.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $abbreviationKey = $_POST['abbreviation_key'] ?? '';
    $abbreviationFullText = $_POST['abbreviation_full_text'] ?? '';
    $fkUserId = $_POST['fk_user_id'] ?? '';

    // Sanitize inputs
    $abbreviationKey = ($abbreviationKey);
    $abbreviationFullText = ($abbreviationFullText);
    $fkUserId = intval($fkUserId);

    // Prepare SQL statement
    $query = 'INSERT INTO llx_abbreviations (abbreviation_key, abbreviation_full_text, fk_user_id) VALUES ($1, $2, $3)';
    $result = pg_prepare($pg_con, "insert_abbreviation", $query);

    if ($result) {
        // Execute the prepared statement
        $result = pg_execute($pg_con, "insert_abbreviation", array($abbreviationKey, $abbreviationFullText, $fkUserId));

        if ($result) {
            echo '<script>';
            echo 'window.location.href = "index.php";'; 
            echo '</script>';
        } else {
            // Debug query execution
            echo 'Error saving abbreviation. ' . pg_last_error($pg_con);
        }
    } else {
        // Debug query preparation
        echo 'Error preparing statement. ' . pg_last_error($pg_con);
    }
}

?>   