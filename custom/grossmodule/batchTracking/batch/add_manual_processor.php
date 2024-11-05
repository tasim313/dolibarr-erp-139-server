<?php
include('../../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $batch_names = isset($_POST['batch_name']) ? $_POST['batch_name'] : []; // Retrieve the array of batch names
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if (!empty($batch_names) && is_array($batch_names)) {
        // Prepare the SQL query for inserting data
        $insert_query = "INSERT INTO llx_manual_processor (user_name, batch_name, description) VALUES ($1, $2, $3)";
        $insert_result = pg_prepare($pg_con, "insert_manual_processor", $insert_query);

        if ($insert_result) {
            foreach ($batch_names as $batch_name) {
                // Execute the prepared statement for each batch name
                $sanitized_batch_name = trim($batch_name); // Sanitize each batch name
                $insert_result = pg_execute($pg_con, "insert_manual_processor", array($user_name, $sanitized_batch_name, $description));

                if (!$insert_result) {
                    // Debug query execution error
                    echo 'Error saving data for batch: ' . htmlspecialchars($sanitized_batch_name) . '. ' . pg_last_error($pg_con);
                }
            }

            // Redirect to the previous page after successful insertion
            echo '<script>';
            echo 'window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";'; 
            echo '</script>';
        } else {
            // Debug query preparation error
            echo 'Error preparing insert statement. ' . pg_last_error($pg_con);
        }
    } else {
        echo 'No batch names selected.';
    }
} else {
    // Redirect if not a POST request
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>