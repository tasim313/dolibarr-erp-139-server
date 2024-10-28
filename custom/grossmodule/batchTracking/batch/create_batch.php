<?php 
include('../../connection.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $created_user = isset($_POST['created_user']) ? $_POST['created_user'] : '';

    // Prepare the SQL query
    $query = "INSERT INTO llx_batch (name,  created_user) VALUES ($1, $2)";
    $result = pg_prepare($pg_con, "insert_batch", $query);

    if ($result) {
        // Execute the prepared statement
        $result = pg_execute($pg_con, "insert_batch", array($name,  $created_user));

        if ($result) {
            // Redirect to the previous page after successful insertion
            echo '<script>';
            echo 'window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";'; 
            echo '</script>';
        } else {
            // Debug query execution error
            echo 'Error creating batch. ' . pg_last_error($pg_con);
        }
    } else {
        // Debug query preparation error
        echo 'Error preparing statement. ' . pg_last_error($pg_con);
    }
} else {
    // Redirect if not a POST request
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

?>