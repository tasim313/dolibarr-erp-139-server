<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();

include("../../connection.php");

if (!$pg_con) {
    error_log("Database connection failed: " . pg_last_error());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

$lab_number = $data['lab_number'];
$username = $data['username'];
$timestamp = $data['timestamp']; // Get the timestamp from the request data


// Log received data for debugging
// error_log("Received data: " . print_r($data, true));

try {
    // Check if lab_number exists in the database
    $query = "SELECT * FROM llx_cyto_doctor_case_info WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, array($lab_number));

    if (!$result) {
        error_log("Database query failed (select): " . pg_last_error($pg_con));
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        exit();
    }

    $row = pg_fetch_assoc($result);

    if ($row) {
        // Check if the finalization flag is false
        if ($row['finalization'] == 'f') {
            // When finalization = false, set it to true and set finalization_doctor_name
            $finalization_count_data = [$username => [$timestamp]];
            $finalization_doctor_name = $username;

            // Update query for starting finalization
            $updateQuery = "
            UPDATE llx_cyto_doctor_case_info
            SET finalization = TRUE,
                finalization_datetime = CURRENT_TIMESTAMP,
                finalization_count_data = $1,
                finalization_doctor_name = $2
            WHERE lab_number = $3
            ";

            // Log the SQL query before executing it
            // error_log("Update query (finalization=false): " . $updateQuery);

            // Perform the update
            $updateResult = pg_query_params($pg_con, $updateQuery, array(json_encode($finalization_count_data), $finalization_doctor_name, $lab_number));

            if (!$updateResult) {
                error_log("Update query failed: " . pg_last_error($pg_con));
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Failed to start finalization']);
                exit();
            }

            // Log success message for debugging
            // error_log("Successfully started finalization for lab number: $lab_number");

            ob_end_clean();
            echo json_encode(['status' => 'success', 'message' => 'Finalization started']);
        } else {
            // When finalization = true, only update the finalization_count_data
            $current_finalization_count_data = json_decode($row['finalization_count_data'], true);
            if (!$current_finalization_count_data) {
                $current_finalization_count_data = []; // Initialize if null
            }

            // If no data exists for the username, initialize it
            if (!isset($current_finalization_count_data[$username])) {
                $current_finalization_count_data[$username] = [];
            }

            // Append the new timestamp to the user's data
            $current_finalization_count_data[$username][] = $timestamp;

            // Log the updated finalization_count_data for debugging
            // error_log("Updated finalization_count_data: " . json_encode($current_finalization_count_data));

            // Update query to update only finalization_count_data
            $updateQuery = "
                UPDATE llx_cyto_doctor_case_info
                SET finalization_count_data = $1
                WHERE lab_number = $2
            ";

            // Log the SQL query before executing it
            // error_log("Update query (finalization=true): " . $updateQuery);

            // Perform the update
            $updateResult = pg_query_params($pg_con, $updateQuery, array(json_encode($current_finalization_count_data), $lab_number));

            if (!$updateResult) {
                error_log("Update query failed: " . pg_last_error($pg_con));
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization count data']);
                exit();
            }

            // Log success message for debugging
            error_log("Successfully updated finalization count data for lab number: $lab_number");

            ob_end_clean();
            echo json_encode(['status' => 'success', 'message' => 'Finalization count data updated']);
        }
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Lab number not found in the database']);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

exit();
?>