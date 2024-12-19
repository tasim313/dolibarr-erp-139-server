<?php
// header('Content-Type: application/json');
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// include("../../connection.php");

// if (!$pg_con) {
//     error_log("Database connection failed: " . pg_last_error());
//     echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
//     exit();
// }

// $data = json_decode(file_get_contents('php://input'), true);
// if (!$data) {
//     echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
//     exit();
// }

// $lab_number = $data['lab_number'];
// $username = $data['username'];
// $timestamp = $data['timestamp'];
// $finalization_patient_history = $data['finalization_patient_history'];

// try {
//     // Fetch the current row for the provided lab_number
//     $query = "SELECT * FROM llx_cyto_doctor_study_patient_info WHERE lab_number = $1";
//     $result = pg_query_params($pg_con, $query, [$lab_number]);

//     if (!$result) {
//         error_log("Database query failed: " . pg_last_error($pg_con));
//         echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
//         exit();
//     }

//     $row = pg_fetch_assoc($result);

//     if ($row) {
//         // Decode the existing history
//         $currentHistory = json_decode($row['finalization_patient_history'], true);
//         if (!$currentHistory) {
//             $currentHistory = [];
//         }

//         // Add or update the user's history
//         if (!isset($currentHistory[$username])) {
//             $currentHistory[$username] = [];
//         }

//         // Combine the new data with the timestamp
//         $newEntry = array_merge($finalization_patient_history, [$timestamp]);

//         // Append the new entry
//         $currentHistory[$username][] = $newEntry;

//         if ($row['finalization_study'] === 'f') {
//             // Case 1: finalization_study is false
//             $finalization_study_count = 1;
//             $finalization_study_count_data = [
//                 $username => [$timestamp]
//             ];

//             $updateQuery = "
//                 UPDATE llx_cyto_doctor_study_patient_info
//                 SET 
//                     finalization_study = TRUE,
//                     finalization_patient_history = $1,
//                     finalization_doctor_name = $2,
//                     finalization_study_count = $3,
//                     finalization_study_count_data = $4
//                 WHERE lab_number = $5
//             ";
//             $updateResult = pg_query_params($pg_con, $updateQuery, [
//                 json_encode($currentHistory), // Save updated history
//                 $username,
//                 $finalization_study_count,
//                 json_encode($finalization_study_count_data),
//                 $lab_number
//             ]);

//             if (!$updateResult) {
//                 error_log("Update query failed: " . pg_last_error($pg_con));
//                 echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization data']);
//                 exit();
//             }

//             echo json_encode(['status' => 'success', 'message' => 'Finalization started successfully']);
//         } else {
//             // Case 2: finalization_study is true
//             $currentData = json_decode($row['finalization_study_count_data'], true);
//             if (!$currentData) {
//                 $currentData = [];
//             }

//             if (!isset($currentData[$username])) {
//                 $currentData[$username] = [];
//             }

//             $currentData[$username][] = $timestamp;

//             // Increment the count
//             $finalization_study_count = $row['finalization_study_count'] + 1;

//             $updateQuery = "
//                 UPDATE llx_cyto_doctor_study_patient_info
//                 SET 
//                     finalization_patient_history = $1,
//                     finalization_study_count = $2,
//                     finalization_study_count_data = $3
//                 WHERE lab_number = $4
//             ";

//             $updateResult = pg_query_params($pg_con, $updateQuery, [
//                 json_encode($currentHistory), // Save updated history
//                 $finalization_study_count, // Incremented count
//                 json_encode($currentData),
//                 $lab_number
//             ]);

//             if (!$updateResult) {
//                 error_log("Update query failed: " . pg_last_error($pg_con));
//                 echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization history']);
//                 exit();
//             }

//             echo json_encode(['status' => 'success', 'message' => 'Finalization history updated successfully']);
//         }
//     } else {
//         echo json_encode(['status' => 'error', 'message' => 'Lab number not found']);
//     }
// } catch (Exception $e) {
//     error_log("Exception: " . $e->getMessage());
//     echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
// }
?>


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
$finalization_patient_history = $data['finalization_patient_history'];

try {
    // Fetch the current row for the provided lab_number
    $query = "SELECT * FROM llx_cyto_doctor_study_patient_info WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);

    if (!$result) {
        error_log("Database query failed: " . pg_last_error($pg_con));
        echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        exit();
    }

    $row = pg_fetch_assoc($result);

    if ($row) {
        // Decode the existing history
        $currentHistory = json_decode($row['finalization_patient_history'], true);
        if (!$currentHistory) {
            $currentHistory = [];
        }

        // Add or update the user's history
        if (!isset($currentHistory[$username])) {
            $currentHistory[$username] = [];
        }

        // Combine the new data with the timestamp
        $newEntry = array_merge($finalization_patient_history, [$timestamp]);

        // Append the new entry
        $currentHistory[$username][] = $newEntry;

        if ($row['finalization_study'] === 'f') {
            // Case 1: finalization_study is false
            $finalization_study_count = 1;
            $finalization_study_count_data = [
                $username => [$timestamp]
            ];

            $updateQuery = "
                UPDATE llx_cyto_doctor_study_patient_info
                SET 
                    finalization_study = TRUE,
                    finalization_patient_history = $1,
                    finalization_doctor_name = $2,
                    finalization_study_count = $3,
                    finalization_study_count_data = $4
                WHERE lab_number = $5
            ";
            $updateResult = pg_query_params($pg_con, $updateQuery, [
                json_encode($currentHistory), // Save updated history
                $username,
                $finalization_study_count,
                json_encode($finalization_study_count_data),
                $lab_number
            ]);

            if (!$updateResult) {
                error_log("Update query failed: " . pg_last_error($pg_con));
                echo json_encode(['status' => 'error', 'message' => 'Failed to update finalization data']);
                exit();
            }

            echo json_encode(['status' => 'success', 'message' => 'Finalization started successfully']);
        } else {
            // Case 2: finalization_study is true
            $currentData = json_decode($row['finalization_study_count_data'], true);
            if (!$currentData) {
                $currentData = [];
            }

            if (!isset($currentData[$username])) {
                $currentData[$username] = [];
            }

            $currentData[$username][] = $timestamp;

            // Increment the count
            $finalization_study_count = $row['finalization_study_count'] + 1;

            $updateQuery = "
                UPDATE llx_cyto_doctor_study_patient_info
                SET 
                    finalization_patient_history = $1,
                    finalization_study_count = $2,
                    finalization_study_count_data = $3
                WHERE lab_number = $4
            ";

            $updateResult = pg_query_params($pg_con, $updateQuery, [
                json_encode($currentHistory), // Save updated history
                $finalization_study_count, // Incremented count
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
        $finalization_study_count = 1;
        $finalization_study_count_data = [
            $username => [$timestamp]
        ];

        $currentHistory = [
            $username => [[
                "history" => $finalization_patient_history,
                "timestamp" => $timestamp
            ]]
        ];

        // Insert the new data
        $insertQuery = "
            INSERT INTO llx_cyto_doctor_study_patient_info (
                lab_number,
                finalization_patient_history,
                finalization_study,
                finalization_doctor_name,
                finalization_study_count,
                finalization_study_count_data
            ) VALUES ($1, $2, $3, $4, $5, $6)
        ";

        $insertResult = pg_query_params($pg_con, $insertQuery, [
            $lab_number,
            json_encode($currentHistory),
            TRUE, // Set finalization_study to true
            $username,
            $finalization_study_count,
            json_encode($finalization_study_count_data)
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