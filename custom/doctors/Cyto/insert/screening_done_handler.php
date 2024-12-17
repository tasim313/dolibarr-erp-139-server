<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include("../../connection.php"); // Database connection

// Retrieve JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

$lab_number = $data['lab_number'] ?? null;
$username = $data['username'] ?? null;
$timestamp = $data['timestamp'] ?? null;

if (!$lab_number || !$username || !$timestamp) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit();
}

try {
    // Fetch existing record
    $query = "SELECT screening_done_count_data, screening_done FROM llx_cyto_doctor_complete_case WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);
    $row = pg_fetch_assoc($result);

    if ($row) {
        // Decode existing JSON data
        $screening_done_data = json_decode($row['screening_done_count_data'], true) ?? [];

        // Append the new timestamp to the user's array
        $screening_done_data[$username][] = $timestamp;

        // Calculate total screening count
        $total_count = array_sum(array_map('count', $screening_done_data));

        // Update the record
        $updateQuery = "
            UPDATE llx_cyto_doctor_complete_case
            SET screening_done = TRUE,
                screening_done_date_time = NOW(),
                screening_done_count = $1,
                screening_done_count_data = $2
            WHERE lab_number = $3
        ";
        pg_query_params($pg_con, $updateQuery, [
            $total_count,
            json_encode($screening_done_data),
            $lab_number
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Screening data updated successfully',
            'updated_data' => $screening_done_data
        ]);
    } else {
        // Insert a new record
        $newData = [$username => [$timestamp]];
        $insertQuery = "
            INSERT INTO llx_cyto_doctor_complete_case 
                (lab_number, screening_done, screening_done_date_time, screening_done_count, screening_done_count_data)
            VALUES ($1, TRUE, NOW(), $2, $3)
        ";
        pg_query_params($pg_con, $insertQuery, [
            $lab_number,
            1,
            json_encode($newData)
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'New screening data inserted',
            'updated_data' => $newData
        ]);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
exit();
?>