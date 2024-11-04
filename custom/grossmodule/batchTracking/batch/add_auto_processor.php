<?php
include('../../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $batch_name = isset($_POST['batch_name']) ? trim($_POST['batch_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Prepare the SQL query for inserting data
    $insert_query = "INSERT INTO llx_auto_processor (user_name, batch_name, description) VALUES ($1, $2, $3)";
    $insert_result = pg_prepare($pg_con, "insert_auto_processor", $insert_query);

    if ($insert_result) {
        // Execute the prepared statement
        $insert_result = pg_execute($pg_con, "insert_auto_processor", array($user_name, $batch_name, $description));

        if ($insert_result) {
            // Redirect to the previous page after successful insertion
            echo '<script>';
            echo 'window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";'; 
            echo '</script>';
        } else {
            // Debug query execution error
            echo 'Error saving data. ' . pg_last_error($pg_con);
        }
    } else {
        // Debug query preparation error
        echo 'Error preparing insert statement. ' . pg_last_error($pg_con);
    }
} else {
    // Redirect if not a POST request
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>