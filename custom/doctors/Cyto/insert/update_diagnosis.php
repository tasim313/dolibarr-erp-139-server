<?php
include("../../connection.php");

ob_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number'] ?? null;
    $new_diagnosis = trim($_POST['diagnosis'] ?? '');
    $loggedInUsername = $_POST['user'] ?? null;

    if (!$loggedInUsername) {
        die("Error: User information is missing. Please log in again.");
    }
    if (!$lab_number || !$new_diagnosis) {
        die("Error: Both Lab number and new diagnosis are required.");
    }

    try {
        // Fetch existing diagnosis data securely using prepared statements
        $query = "SELECT diagnosis, previous_diagnosis, created_user FROM llx_cyto_doctor_diagnosis WHERE lab_number = $1";
        $result = pg_query_params($pg_con, $query, [$lab_number]);

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $current_diagnosis = $row['diagnosis'];
            $previous_diagnosis = json_decode($row['previous_diagnosis'], true) ?? [];
            $created_user = $row['created_user'];

            if ($current_diagnosis) {
                $previous_diagnosis[] = [
                    'previous' => $current_diagnosis,
                    'Date' => date('j F, Y h:i A'),
                    'created_user' => $created_user,
                    'updated_user' => $loggedInUsername,
                ];
            }

            // Secure update using prepared statement
            $update_query = "
                UPDATE llx_cyto_doctor_diagnosis
                SET diagnosis = $1,
                    previous_diagnosis = $2,
                    updated_user = $3,
                    updated_date = NOW()
                WHERE lab_number = $4";
            
            $update_result = pg_query_params($pg_con, $update_query, [
                $new_diagnosis, json_encode($previous_diagnosis, JSON_THROW_ON_ERROR), $loggedInUsername, $lab_number
            ]);

            if ($update_result) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            } else {
                die("Error: Failed to update the diagnosis.");
            }
        } else {
            // Insert new record securely
            $previous_diagnosis = json_encode([[
                'previous' => $new_diagnosis,
                'Date' => date('j F, Y h:i A'),
                'created_user' => $loggedInUsername,
                'updated_user' => $loggedInUsername,
            ]], JSON_THROW_ON_ERROR);

            $insert_query = "
                INSERT INTO llx_cyto_doctor_diagnosis (lab_number, diagnosis, previous_diagnosis, created_user, updated_user, created_date)
                VALUES ($1, $2, $3, $4, $5, NOW())";
            
            $insert_result = pg_query_params($pg_con, $insert_query, [
                $lab_number, $new_diagnosis, $previous_diagnosis, $loggedInUsername, $loggedInUsername
            ]);

            if ($insert_result) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            } else {
                die("Error: Failed to insert the new diagnosis.");
            }
        }
    } catch (Exception $e) {
        die("An error occurred: " . htmlspecialchars($e->getMessage()));
    }
} else {
    die("Error: Invalid request method.");
}

ob_end_flush();
?>