<?php

include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = $_POST['rowid'];
    $notified_method = isset($_POST['notified_method']) ? implode(", ", $_POST['notified_method']) : '';
    $follow_up_date = $_POST['follow_up_date'];
    $status = $_POST['status'];
    $notified_user = $_POST['notified_user'];  // Get notified user from form

    // Prepare the SQL query with placeholders
    $query = "UPDATE llx_cyto_recall_management 
              SET notified_method = $1, 
                  follow_up_date = $2, 
                  status = $3,
                  updated_date = NOW(),  -- Set the current timestamp
                  notified_user = $4  -- Set the notified user
              WHERE rowid = $5";

    // Execute the query with parameters
    $result = pg_query_params(
        $pg_con, 
        $query, 
        [$notified_method, $follow_up_date, $status, $notified_user, $rowid]
    );

    // Check the result
    if ($result) {
        // Redirect to the page the user came from
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;  // Ensure no further code is executed
    } else {
        echo "Error: " . pg_last_error($pg_con);
    }
}

?>