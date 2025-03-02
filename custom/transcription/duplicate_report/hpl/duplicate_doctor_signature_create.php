<?php
include("connection.php");

// Check if all required fields are present
$required_fields = ['doctor_username', 'lab_number',  'created_user'];
$missing_fields = array_diff_key(array_flip($required_fields), $_POST);
if (!empty($missing_fields)) {
    echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
    exit();
}

// Extract data from the POST request
$doctor_username = $_POST['doctor_username'];
$lab_number = $_POST['lab_number'];
$created_user = $_POST['created_user'];

// Insert the doctor signature
$insert_query = "INSERT INTO llx_doctor_assisted_by_signature (doctor_username, lab_number, created_user) VALUES ($1, $2, $3)";
$insert_result = pg_query_params($pg_con, $insert_query, array($doctor_username, $lab_number, $created_user));

if (!$insert_result) {
    error_log("Error inserting doctor details: " . pg_last_error($pg_con));
    echo "Error inserting doctor details.";
    exit();
}

echo "Doctor signature inserted successfully.";
header("Location: transcription.php?lab_number=$lab_number");

// Close the database connection
pg_close($pg_con);
?>