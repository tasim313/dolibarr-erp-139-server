<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $specimens = isset($_POST['specimen']) ? $_POST['specimen'] : [];
    $lab_numbers = isset($_POST['lab_number']) ? $_POST['lab_number'] : [];
    $fk_gross_ids = isset($_POST['fk_gross_id']) ? $_POST['fk_gross_id'] : [];
    $descriptions = isset($_POST['description']) ? $_POST['description'] : [];
    $created_users = isset($_POST['created_user']) ? $_POST['created_user'] : [];
    $statuses = isset($_POST['status']) ? $_POST['status'] : [];
    $row_ids = isset($_POST['row_id']) ? $_POST['row_id'] : [];

    // Prepare update statement (excluding lab_number update)
    $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_diagnosis SET fk_gross_id = $1, specimen = $2, description = $3, created_user = $4, status = $5 WHERE row_id = $6");

    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    $success = true;

    // Loop through each description and update database
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
            exit();
        }
    }
    
    if ($success) {
        // Redirect after successful update
        echo '<script>alert("Data updated successfully!");</script>';
        echo '<script>';
        echo 'window.location.href = "list.php";';
        echo '</script>';
        exit();
    }
    header("Location: transcriptionindex.php");
    exit();
} else {
    // Redirect if not a POST request
    header("Location: transcriptionindex.php");
    exit();
}
?>
