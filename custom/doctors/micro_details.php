<?php

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required fields are present
    $required_fields = ['specimen', 'description', 'created_user', 'status', 'lab_number', 'fk_gross_id'];
    $missing_fields = array_diff_key(array_flip($required_fields), $_POST);
    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
        exit();
    }

    // Extract data from the POST request
    $specimens = $_POST['specimen'];
    $descriptions = $_POST['description'];
    $created_users = $_POST['created_user'];
    $statuses = $_POST['status'];
    $lab_numbers = $_POST['lab_number'];
    $fk_gross_ids = $_POST['fk_gross_id'];

    // Prepare and execute the INSERT or UPDATE statement for each set of data
    for ($i = 0; $i < count($specimens); $i++) {
        $result = pg_query_params($pg_con, "SELECT * FROM llx_micro WHERE lab_number = $1", [$lab_numbers[$i]]);
        if ($result && pg_num_rows($result) > 0) {
            // If data already exists, update it
            $update_sql = "UPDATE llx_micro SET specimen = $1, description = $2, created_user = $3, status = $4, fk_gross_id = $5 WHERE lab_number = $6";
            $update_stmt = pg_prepare($pg_con, "update_statement", $update_sql);

            if (!$update_stmt) {
                error_log("Error preparing update statement: " . pg_last_error($pg_con));
                echo "Error preparing update statement.";
                exit();
            }

            // Execute the update statement
            $update_result = pg_execute($pg_con, "update_statement", [
                $specimens[$i],
                $descriptions[$i],
                $created_users[$i],
                $statuses[$i],
                $fk_gross_ids[$i],
                $lab_numbers[$i]
            ]);

            if (!$update_result) {
                error_log("Error updating data: " . pg_last_error($pg_con));
                echo "Error updating data.";
                exit();
            }

            echo "Data updated successfully.";
        } else {
            // If data does not exist, insert it
            $insert_sql = "INSERT INTO llx_micro (specimen, description, created_user, status, fk_gross_id, lab_number) VALUES ($1, $2, $3, $4, $5, $6)";
            $insert_stmt = pg_prepare($pg_con, "insert_statement", $insert_sql);

            if (!$insert_stmt) {
                error_log("Error preparing insert statement: " . pg_last_error($pg_con));
                echo "Error preparing insert statement.";
                exit();
            }

            // Execute the insert statement
            $insert_result = pg_execute($pg_con, "insert_statement", [
                $specimens[$i],
                $descriptions[$i],
                $created_users[$i],
                $statuses[$i],
                $fk_gross_ids[$i],
                $lab_numbers[$i]
            ]);

            if (!$insert_result) {
                error_log("Error inserting data: " . pg_last_error($pg_con));
                echo "Error inserting data.";
                exit();
            }

            echo "Data inserted successfully.";
        }
    }

    // Close the database connection
    pg_close($pg_con);
} else {
    // If the request method is not POST, redirect to another page
    header("Location: transcriptionindex.php"); 
    exit();
}

?>
