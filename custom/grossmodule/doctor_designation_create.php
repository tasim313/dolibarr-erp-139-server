<?php

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required fields are present
    $required_fields = ['username', 'doctor_name', 'education', 'designation', 'created_user'];
    $missing_fields = array_diff_key(array_flip($required_fields), $_POST);
    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
        exit();
    }

    // Extract data from the POST request
    $username = $_POST['username'];
    $doctor_name = $_POST['doctor_name'];
    $education = $_POST['education'];
    $designation = $_POST['designation'];
    $created_user = $_POST['created_user'];

    // Use prepared statements to prevent SQL injection
    $query = "SELECT username FROM llx_doctor_degination WHERE username = $1";
    $result = pg_query_params($pg_con, $query, array($username));

    if (!$result) {
        error_log("Error executing query: " . pg_last_error($pg_con));
        echo "Error executing query.";
        exit();
    }

    if (pg_num_rows($result) > 0) {
        // If doctor details exist, update them
        $update_query = "UPDATE llx_doctor_degination SET education = $2, designation = $3 WHERE username = $1";
        $update_result = pg_query_params($pg_con, $update_query, array($username, $education, $designation));

        if (!$update_result) {
            error_log("Error updating doctor details: " . pg_last_error($pg_con));
            echo "Error updating doctor details.";
            exit();
        }

        echo "Doctor details updated successfully.";
        header("Location: gross_assign.php"); 
    } else {
        // If doctor details do not exist, insert them
        $insert_query = "INSERT INTO llx_doctor_degination (username, doctor_name, education, designation, created_user) VALUES ($1, $2, $3, $4, $5)";
        $insert_result = pg_query_params($pg_con, $insert_query, array($username, $doctor_name, $education, $designation, $created_user));

        if (!$insert_result) {
            error_log("Error inserting doctor details: " . pg_last_error($pg_con));
            echo "Error inserting doctor details.";
            exit();
        }

        echo "Doctor details inserted successfully.";
        header("Location: gross_assign.php"); 
    }

    // Close the database connection
    pg_close($pg_con);
} else {
    // If the request method is not POST, redirect to another page
    header("Location: gross_assign.php"); 
    exit();
}

?>
