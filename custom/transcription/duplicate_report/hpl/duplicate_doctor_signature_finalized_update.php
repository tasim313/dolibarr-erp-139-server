<?php
include("../connection.php");

// Check if all required fields are present
$required_fields = ['rowid', 'doctor_username', 'lab_number', 'created_user'];
$missing_fields = array_diff_key(array_flip($required_fields), $_POST);
if (!empty($missing_fields)) {
    echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
    exit();
}

// Extract data from the POST request
$rowid = $_POST['rowid'];
$doctor_username = $_POST['doctor_username'];
$lab_number = $_POST['lab_number'];
$created_user = $_POST['created_user'];
$updated_date = date("Y-m-d H:i:s"); // Current timestamp

// Fetch the existing previous_signature field and current doctor_username, created_user
$fetch_query = "SELECT previous_signature, doctor_username, created_user 
                FROM llx_duplicate_report_doctor_finalized_by_signature
                WHERE rowid = $1";
$fetch_result = pg_query_params($pg_con, $fetch_query, array($rowid));

if (!$fetch_result) {
    error_log("Error fetching previous data: " . pg_last_error($pg_con));
    echo "Error fetching previous data.";
    exit();
}

$existing_data = pg_fetch_assoc($fetch_result);
$previous_signature = $existing_data['previous_signature'];
$old_doctor_username = $existing_data['doctor_username'];
$old_created_user = $existing_data['created_user'];

// Decode existing JSON data, if not empty
$previous_entries = (!empty($previous_signature)) ? json_decode($previous_signature, true) : [];

// Only append to previous_signature if doctor_username or created_user has changed
if ($doctor_username !== $old_doctor_username || $created_user !== $old_created_user) {
    $new_entry = [
        "doctor_username" => $old_doctor_username,
        "created_user" => $old_created_user,
        "updated_date" => $updated_date // Changed from created_date to updated_date
    ];
    $previous_entries[] = $new_entry;
}

// Convert back to JSON (only update if changes were made)
$updated_previous_signature = (!empty($previous_entries)) ? json_encode($previous_entries) : $previous_signature;

// Update the doctor signature and previous_signature if necessary
$update_query = "UPDATE llx_duplicate_report_doctor_finalized_by_signature
                 SET doctor_username = $2, 
                     lab_number = $3, 
                     created_user = $4, 
                     previous_signature = $5 
                 WHERE rowid = $1";

$update_result = pg_query_params($pg_con, $update_query, array($rowid, $doctor_username, $lab_number, $created_user, $updated_previous_signature));

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