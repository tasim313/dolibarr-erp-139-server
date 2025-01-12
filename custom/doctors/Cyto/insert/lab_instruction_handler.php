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
$stainOptions = $data['screening_stain_name'];

try {
    // Fetch the current row for the provided lab_number
    $query = "SELECT * FROM llx_cyto_doctor_lab_instruction WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);

    if (!$result) {
        error_log("Database query failed: " . pg_last_error($pg_con));
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        exit();
    }

    $row = pg_fetch_assoc($result);

    if ($row) {
        $screeningStainName = json_decode($row['screening_stain_name'], true);
        $screeningStainAgain = json_decode($row['screening_stain_again'], true);

        // Prepare new data for screening_stain_again
        $newEntry = [
            "stains" => $stainOptions,
            "timestamp" => $timestamp
        ];

        if (!$screeningStainAgain) {
            $screeningStainAgain = [];
        }

        if (!isset($screeningStainAgain[$username])) {
            $screeningStainAgain[$username] = [];
        }
        $screeningStainAgain[$username][] = $newEntry;

        if (empty($screeningStainName)) {
            // Case 1: screening_stain_name is empty
            $updateQuery = "
                UPDATE llx_cyto_doctor_lab_instruction
                SET 
                    screening_stain_name = $1,
                    screening_doctor_name = $2,
                    screening_stain_again = $3
                WHERE lab_number = $4
            ";
            $updateResult = pg_query_params($pg_con, $updateQuery, [
                json_encode($stainOptions), // Save stain name
                $username,
                json_encode($screeningStainAgain), // Save updated stain again data
                $lab_number
            ]);

            if (!$updateResult) {
                $error_message = pg_last_error($pg_con); // Capture the error message
                error_log("Update query failed: " . $error_message);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update stain data', 'error' => $error_message]);
                exit();
            }

            echo json_encode(['status' => 'success', 'message' => 'Stain data initialized successfully']);
        } else {
            // Case 2: screening_stain_name is not empty
            $updateQuery = "
                UPDATE llx_cyto_doctor_lab_instruction
                SET 
                    screening_stain_again = $1
                WHERE lab_number = $2
            ";
            $updateResult = pg_query_params($pg_con, $updateQuery, [
                json_encode($screeningStainAgain), // Save updated stain again data
                $lab_number
            ]);

            if (!$updateResult) {
                $error_message = pg_last_error($pg_con); // Capture the error message
                error_log("Update query failed: " . $error_message);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update stain again data', 'error' => $error_message]);
                exit();
            }

            echo json_encode(['status' => 'success', 'message' => 'Stain data updated successfully']);
        }
    } else {
        // Lab Number not found, insert new data
        $screeningStainAgain = [
            $username => [[
                "stains" => $stainOptions,
                "timestamp" => $timestamp
            ]]
        ];

        $insertQuery = "
            INSERT INTO llx_cyto_doctor_lab_instruction (
                lab_number,
                screening_stain_name,
                screening_doctor_name,
                screening_stain_again
            ) VALUES ($1, $2, $3, $4)
        ";
        $insertResult = pg_query_params($pg_con, $insertQuery, [
            $lab_number,
            json_encode($stainOptions), // Save stain name
            $username,
            json_encode($screeningStainAgain) // Save stain again data
        ]);

        if (!$insertResult) {
            $error_message = pg_last_error($pg_con); // Capture the error message
            error_log("Insert query failed: " . $error_message);
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert new lab data', 'error' => $error_message]);
            exit();
        }

        echo json_encode(['status' => 'success', 'message' => 'New lab data inserted successfully']);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
}
?>