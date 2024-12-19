<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);  // Disable direct error output
error_reporting(E_ALL);        // Log all errors

ob_start();  // Buffer output to prevent any early output

include("../../connection.php");

// Retrieve the input data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

$lab_number = $data['lab_number'];
$username = $data['username'];
$timestamp = $data['timestamp'];

try {
    // Query the database for the lab number
    $query = "SELECT * FROM llx_cyto_doctor_case_info WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, array($lab_number));
    $row = pg_fetch_assoc($result);

    if ($row) {
        if ($row['screening'] === 't') {
            // Decode the JSON of screening_count_data
            $screening_count_data = json_decode($row['screening_count_data'], true);

            // Debug: Log what $screening_count_data is before proceeding
            error_log("screening_count_data before processing: " . print_r($screening_count_data, true));

            // Ensure it's an array (in case the data was corrupted)
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($screening_count_data)) {
                error_log("Invalid JSON or Not an array: " . json_last_error_msg());
                $screening_count_data = [];  // Reset to an empty array if invalid
            }

            // Ensure $screening_count_data[$username] is an array
            if (isset($screening_count_data[$username])) {
                // Make sure it's an array before using the [] operator
                if (!is_array($screening_count_data[$username])) {
                    $screening_count_data[$username] = [];  // Reset to an empty array if it's not
                }
                // Append the timestamp
                $screening_count_data[$username][] = $timestamp;
            } else {
                // If no data for this username, create an array with the timestamp
                $screening_count_data[$username] = [$timestamp];
            }

            // Calculate the new screening count (sum of all timestamp arrays)
            $screening_count = array_sum(array_map('count', $screening_count_data));

            // Debug: Log the updated data
            error_log("Updated screening_count_data: " . print_r($screening_count_data, true));
            error_log("Total screening_count: " . $screening_count);

            // Update the database
            $updateQuery = "
                UPDATE llx_cyto_doctor_case_info
                SET screening_count = $1, screening_count_data = $2
                WHERE lab_number = $3
            ";
            pg_query_params($pg_con, $updateQuery, array($screening_count, json_encode($screening_count_data), $lab_number));

            // Output success response
            ob_end_clean();
            echo json_encode(['status' => 'success', 'message' => 'Screening data updated']);
        } else {
            // If screening is false, initialize it
            $insertQuery = "
                UPDATE llx_cyto_doctor_case_info
                SET screening = TRUE, screening_count = 1, screening_count_data = $1
                WHERE lab_number = $2
            ";
            pg_query_params($pg_con, $insertQuery, array(json_encode([$username => [$timestamp]]), $lab_number));

            // Output success response
            ob_end_clean();
            echo json_encode(['status' => 'success', 'message' => 'Screening data inserted']);
        }
    } else {
        // If the lab_number is not found, insert new data
        $insertQuery = "
            INSERT INTO llx_cyto_doctor_case_info (lab_number, screening, screening_dateTime, screening_count, screening_count_data, screening_doctor_name)
            VALUES ($1, TRUE, CURRENT_TIMESTAMP, 1, $2, $3)
        ";
        pg_query_params($pg_con, $insertQuery, array($lab_number, json_encode([$username => [$timestamp]]), $username));

        // Output success response
        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'Screening data inserted']);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
exit();

?>