<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data from the form
    $specimens = $_POST['final_specimen'] ?? [];
    $lab_numbers = $_POST['lab_number'] ?? [];
    $fk_gross_ids = $_POST['fk_gross_id'] ?? [];
    $descriptions = $_POST['final_description'] ?? [];
    $created_users = $_POST['created_user'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $row_ids = $_POST['row_id'] ?? [];

    // Prepare the update statement
    $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_micro SET fk_gross_id = $1, specimen = $2, description = $3, created_user = $4, status = $5 WHERE row_id = $6");

    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    $success = true;

    // Loop and execute update for each row
    for ($i = 0; $i < count($row_ids); $i++) {
        $result = pg_execute($pg_con, "update_statement", array(
            $fk_gross_ids[$i],
            pg_escape_string($specimens[$i]),
            pg_escape_string($descriptions[$i]),
            pg_escape_string($created_users[$i]),
            pg_escape_string($statuses[$i]),
            $row_ids[$i]
        ));

        if (!$result) {
            echo "Error updating data: " . pg_last_error($pg_con);
            $success = false;
            break;
        }
    }

    // Redirect back
    if ($success) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

} else {
    // Redirect if accessed directly
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>