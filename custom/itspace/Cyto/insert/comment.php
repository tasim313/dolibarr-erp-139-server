<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    date_default_timezone_set("Asia/Dhaka"); // Set timezone
    $timestamp = date("j F, Y h:i A"); // Example: 15 January, 2025 12:56 PM

    // Collecting POST data
    $lab_number = trim($_POST['lab_number']);
    $username = trim($_POST['username']);
    $comment = trim($_POST['comment']);
    $status = trim($_POST['action']); // User-selected status
    $rowid = trim($_POST['rowid']);

    // Check database connection
    if (!$pg_con) {
        error_log("Database connection failed: " . pg_last_error());
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }

    try {
        // 🔍 Check if the given lab_number exists
        $query = "SELECT rowid, status_list FROM llx_cyto_study_patient_info_dispatch_center WHERE lab_number = $1";
        $result = pg_query_params($pg_con, $query, [$lab_number]);

        // ❌ If query execution fails, return an error
        if (!$result) {
            $error_message = pg_last_error($pg_con);
            error_log("Database query failed: " . $error_message);
            echo json_encode(['status' => 'error', 'message' => 'Database query failed', 'error' => $error_message]);
            exit();
        }

        $row = pg_fetch_assoc($result);

        if ($row) {
            // ✅ If lab_number exists → UPDATE the record
            $status_list = json_decode($row['status_list'], true) ?: [];

            // Append new status entry
            $status_list[] = [
                "status" => $status, // Use user-selected status
                "timestamp" => $timestamp,
                "user" => $username
            ];

            $updateQuery = "
                UPDATE llx_cyto_study_patient_info_dispatch_center
                SET 
                    status = $1,
                    comment = $2,
                    status_list = $3
                WHERE rowid = $4
            ";

            $updateResult = pg_query_params($pg_con, $updateQuery, [
                $status, // Use user-provided action
                $comment,
                json_encode($status_list),
                $rowid
            ]);

            if (!$updateResult) {
                $error_message = pg_last_error($pg_con);
                error_log("Update query failed: " . $error_message);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update data', 'error' => $error_message]);
                exit();
            }

            // ✅ Redirect back after update
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            // ❌ If lab_number does NOT exist → INSERT new record
            $status_list = [
                [
                    "status" => $status,
                    "timestamp" => $timestamp,
                    "user" => $username
                ]
            ];

            $insertQuery = "
                INSERT INTO llx_cyto_study_patient_info_dispatch_center (
                    lab_number,
                    status,
                    timestamp,
                    comment,
                    status_list
                ) VALUES ($1, $2, $3, $4, $5)
            ";

            $insertResult = pg_query_params($pg_con, $insertQuery, [
                $lab_number,
                $status,
                $timestamp,
                $comment,
                json_encode($status_list)
            ]);

            if (!$insertResult) {
                $error_message = pg_last_error($pg_con);
                error_log("Insert query failed: " . $error_message);
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert new lab data', 'error' => $error_message]);
                exit();
            }

            // ✅ Redirect back after insertion
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred']);
        exit();
    }
}
?>