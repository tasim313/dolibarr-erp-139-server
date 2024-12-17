<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);  // Disable direct error output
error_reporting(E_ALL);        // Log all errors

include("../../connection.php");

// Retrieve JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

$lab_number = $data['lab_number'];
$username = $data['username'];
$timestamp = $data['timestamp'];
$screening_study = $data['screening_study'];
$new_patient_history = $data['screening_patient_history']; // Array of history options

try {
    // Check if the lab_number already exists
    $query = "SELECT * FROM llx_cyto_doctor_study_patient_info WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, array($lab_number));
    $row = pg_fetch_assoc($result);

    if ($row) {
        // Existing record: Decode the current screening_patient_history JSONB
        $screening_patient_history = json_decode($row['screening_patient_history'], true);
        if (!$screening_patient_history) $screening_patient_history = []; // Ensure it's an array

        // Append new history under the username key
        $entry = array_merge($new_patient_history, [$timestamp]); // Combine history options + timestamp
        $screening_patient_history[$username][] = $entry;

        // Update screening_study_count_data as before
        $screening_count_data = json_decode($row['screening_study_count_data'], true);
        if (!$screening_count_data) $screening_count_data = [];
        $screening_count_data[$username][] = $timestamp;

        $screening_count = array_sum(array_map('count', $screening_count_data));

        // Update query
        $updateQuery = "
            UPDATE llx_cyto_doctor_study_patient_info
            SET screening_study = $1,
                screening_patient_history = $2,
                screening_study_count = $3,
                screening_study_count_data = $4,
                screening_doctor_name = $5
            WHERE lab_number = $6
        ";
        pg_query_params($pg_con, $updateQuery, array(
            $screening_study, json_encode($screening_patient_history), 
            $screening_count, json_encode($screening_count_data), 
            $username, $lab_number
        ));

        echo json_encode(['status' => 'success', 'message' => 'Data updated successfully']);
    } else {
        // New record: Insert data
        $new_screening_patient_history = [
            $username => [array_merge($new_patient_history, [$timestamp])]
        ];

        $insertQuery = "
            INSERT INTO llx_cyto_doctor_study_patient_info 
            (lab_number, screening_study, screening_patient_history, screening_study_count, screening_study_count_data, screening_doctor_name) 
            VALUES ($1, $2, $3, $4, $5, $6)
        ";
        pg_query_params($pg_con, $insertQuery, array(
            $lab_number, $screening_study, json_encode($new_screening_patient_history), 
            1, json_encode([$username => [$timestamp]]), $username
        ));

        echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
