<?php

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required fields are present
    $required_fields = ['lab_number', 'clinical_details', 'created_user'];
    $missing_fields = array_diff_key(array_flip($required_fields), $_POST);
    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
        exit();
    }

    // Extract data from the POST request
    $lab_number = $_POST['lab_number'];
    $clinical_details = $_POST['clinical_details'];
    $created_user = $_POST['created_user'];

    // Check if clinical details already exist for the provided lab number
    $existing_result = pg_query_params($pg_con, "SELECT clinical_details FROM llx_clinical_details WHERE lab_number = $1", [$lab_number]);
    if ($existing_result && pg_num_rows($existing_result) > 0) {
        // If clinical details exist, update them
        $update_sql = "UPDATE llx_clinical_details SET clinical_details = $1 WHERE lab_number = $2";
        $update_stmt = pg_prepare($pg_con, "update_clinical_details", $update_sql);

        if (!$update_stmt) {
            error_log("Error preparing update statement: " . pg_last_error($pg_con));
            echo "Error preparing update statement.";
            exit();
        }

        // Execute the update statement
        $update_result = pg_execute($pg_con, "update_clinical_details", [$clinical_details, $lab_number]);

        if (!$update_result) {
            error_log("Error updating clinical details: " . pg_last_error($pg_con));
            echo "Error updating clinical details.";
            exit();
        }

        echo "Clinical details updated successfully.";
        $update_lab_number = trim($lab_number, '.');
        echo '<script>';
        echo 'window.location.href = "hpl_transcription_list.php?lab_number=' . $update_lab_number . '"';
        echo '</script>';
    } else {
        // If clinical details do not exist, insert them
        $insert_sql = "INSERT INTO llx_clinical_details (lab_number, clinical_details, created_user) VALUES ($1, $2, $3)";
        $insert_stmt = pg_prepare($pg_con, "insert_clinical_details", $insert_sql);

        if (!$insert_stmt) {
            error_log("Error preparing insert statement: " . pg_last_error($pg_con));
            echo "Error preparing insert statement.";
            exit();
        }

        // Execute the insert statement
        $insert_result = pg_execute($pg_con, "insert_clinical_details", [$lab_number, $clinical_details, $created_user]);

        if (!$insert_result) {
            error_log("Error inserting clinical details: " . pg_last_error($pg_con));
            echo "Error inserting clinical details.";
            exit();
        }

        echo "Clinical details inserted successfully.";
        $update_lab_number = trim($lab_number, '.');
        echo '<script>';
        echo 'window.location.href = "hpl_transcription_list.php?lab_number=' . $update_lab_number . '"';
        echo '</script>';
        
    }

    // Close the database connection
    pg_close($pg_con);
} else {
    // If the request method is not POST, redirect to another page
    header("Location: transcriptionindex.php"); 
    exit();
}

?>