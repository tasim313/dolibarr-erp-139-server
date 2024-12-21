<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../../connection.php");

if (!$pg_con) {
    error_log("Database connection failed: " . pg_last_error());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

$lab_number = $data['lab_number'];
$username = $data['username'];
$timestamp = $data['timestamp'];

try {
    // Fetch the current row for the provided lab_number
    $query = "SELECT * FROM llx_cyto_doctor_complete_case WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);

    if (!$result) {
        error_log("Database query failed: " . pg_last_error($pg_con));
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        exit();
    }

    $row = pg_fetch_assoc($result);

    if ($row) {
        // Case 1: finalization_done is false
        if ($row['finalization_done'] === 'f') {
            $finalization_done_count = 1;
            $finalization_done_count_data = [
                $username => [$timestamp]
            ];

            $updateQuery = "
                UPDATE llx_cyto_doctor_complete_case
                SET 
                    finalization_done = TRUE,
                    finalization_done_count = $1,
                    finalization_done_count_data = $2
                WHERE lab_number = $3
            ";
            $updateResult = pg_query_params($pg_con, $updateQuery, [
                $finalization_done_count,
                json_encode($finalization_done_count_data),
                $lab_number
            ]);

            if (!$updateResult) {
                error_log("Update query failed: " . pg_last_error($pg_con));
                echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization data']);
                exit();
            }

            echo json_encode(['status' => 'success', 'message' => 'Finalization marked as done successfully']);
        } else {
            // Case 2: finalization_done is true
            $currentData = json_decode($row['finalization_done_count_data'], true);
            if (!$currentData) {
                $currentData = [];
            }

            if (!isset($currentData[$username])) {
                $currentData[$username] = [];
            }

            $currentData[$username][] = $timestamp;

            // Increment the count
            $finalization_done_count = $row['finalization_done_count'] + 1;

            $updateQuery = "
                UPDATE llx_cyto_doctor_complete_case
                SET 
                    finalization_done_count = $1,
                    finalization_done_count_data = $2
                WHERE lab_number = $3
            ";

            $updateResult = pg_query_params($pg_con, $updateQuery, [
                $finalization_done_count,
                json_encode($currentData),
                $lab_number
            ]);

            if (!$updateResult) {
                error_log("Update query failed: " . pg_last_error($pg_con));
                echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization history']);
                exit();
            }

            echo json_encode(['status' => 'success', 'message' => 'Finalization history updated successfully']);
        }
    } else {
        // Lab number not found, insert new data
        $finalization_done_count = 1;
        $finalization_done_count_data = [
            $username => [$timestamp]
        ];

        $insertQuery = "
            INSERT INTO llx_cyto_doctor_complete_case (
                lab_number,
                finalization_done,
                finalization_done_count,
                finalization_done_count_data
            ) VALUES ($1, $2, $3, $4)
        ";

        $insertResult = pg_query_params($pg_con, $insertQuery, [
            $lab_number,
            TRUE, // Set finalization_done to true
            $finalization_done_count,
            json_encode($finalization_done_count_data)
        ]);

        if (!$insertResult) {
            error_log("Insert query failed: " . pg_last_error($pg_con));
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert new finalization data']);
            exit();
        }

        echo json_encode(['status' => 'success', 'message' => 'New finalization data inserted successfully']);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
?>