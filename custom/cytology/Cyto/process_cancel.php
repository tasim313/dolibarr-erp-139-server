<?php
// Include the database connection file
require_once '../connection.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $note_public = trim($_POST['note_public']);
    $fk_status = intval($_POST['fk_status']);  // Ensure this is an integer
    $username = trim($_POST['username']);      // Ensure this is a string
    $ref = trim($_POST['ref']);                // Ensure this is a string

    // Initialize an array to track missing fields
    $missingFields = [];

    // Validate each field and add to the missingFields array if necessary
    if (empty($note_public)) {
        $missingFields[] = 'Note';
    }
    if (is_null($fk_status)) {
        $missingFields[] = 'Status';
    }
    if (empty($username)) {
        $missingFields[] = 'Username';
    }
    if (empty($ref)) {
        $missingFields[] = 'Reference (ref)';
    }

    // Check if there are any missing fields
    if (!empty($missingFields)) {
        echo "Required fields are missing: " . implode(', ', $missingFields);
        exit;
    }

    // Update query (concatenate note_public with the new text)
    $updateQuery = "
        UPDATE llx_commande
        SET 
            note_public = CONCAT(COALESCE(note_public, ''), ' ', $1::text, ' Added by user: ', $2::text),
            fk_statut = $3::int
        WHERE ref = $4::text
    ";

    // Prepare and execute query with explicit types for the parameters
    $result = pg_query_params($pg_con, $updateQuery, array($note_public, $username, $fk_status, $ref));

    if ($result) {
        // Redirect to the referring page after successful update
        header("Location: /custom/cytology/cytologyindex.php");
        exit;
    } else {
        echo "Error updating record: " . pg_last_error($pg_con);
    }
}
?>