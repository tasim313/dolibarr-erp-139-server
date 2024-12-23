<?php
include("../../connection.php");
$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/transcription/FNA/index.php";

// Check if all required fields are present
$required_fields = ['row_id', 'doctor_username', 'lab_number',  'created_user'];
$missing_fields = array_diff_key(array_flip($required_fields), $_POST);
if (!empty($missing_fields)) {
    echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
    exit();
}

// Extract data from the POST request
$row_id = $_POST['row_id'];
$doctor_username = $_POST['doctor_username'];
$lab_number = $_POST['lab_number'];
$created_user = $_POST['created_user'];

// Update the doctor signature
$update_query = "UPDATE llx_doctor_assisted_by_signature SET doctor_username = $2, lab_number = $3, created_user = $4 WHERE row_id = $1";
$update_result = pg_query_params($pg_con, $update_query, array($row_id, $doctor_username, $lab_number, $created_user));

if (!$update_result) {
    error_log("Error updating doctor details: " . pg_last_error($pg_con));
    echo "Error updating doctor details.";
    exit();
}

echo "Doctor signature updated successfully.";
header("Location: " . $_SERVER['HTTP_REFERER']);

// Close the database connection
pg_close($pg_con);
?>
