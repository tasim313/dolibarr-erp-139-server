<?php
header('Content-Type: application/json; charset=utf-8');

ob_start(); // Start output buffering

include("../connection.php"); // Include PostgreSQL connection file

try {
    // Retrieve JSON data safely
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
   
    // Extract and sanitize input data
    $lab_number = $data['lab_number'] ?? null;
    $recalled_doctor = $data['recalled_doctor'] ?? null;
    $recall_reasons = $data['recall_reason'] ?? [];
    $timestamp = $data['timestamp'] ?? null;
    $notified_method = $data['notified_method'] ?? []; // New field: Notified Method
    $follow_up_date = $data['follow_up_date'] ?? null; // New field: Follow-up Date

    // Validate required fields
    if (!$lab_number || !$recalled_doctor || empty($recall_reasons) || !$timestamp) {
        throw new Exception('Missing required fields');
    }

    // Check for existing lab_number in the database
    $query = "SELECT recall_reason FROM llx_cyto_recall_management WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);

    if ($result === false) {
        throw new Exception('Database query failed: ' . pg_last_error($pg_con));
    }

    $row = pg_fetch_assoc($result);

    // Structure the new data
    $newData = [
        "reason" => $recall_reasons,
        "timestamp" => $timestamp,
        "notified_method" => $notified_method, 
        "follow_up_date" => $follow_up_date
    ];

    if ($row) {
        // Update existing record
        $existingRecallReasons = json_decode($row['recall_reason'], true) ?? [];
        $existingRecallReasons[$recalled_doctor][] = $newData;

        $updateQuery = "UPDATE llx_cyto_recall_management 
                        SET recall_reason = $1, notified_method = $2, follow_up_date = $3, updated_date = NOW() 
                        WHERE lab_number = $4";
        $updateResult = pg_query_params($pg_con, $updateQuery, [
            json_encode($existingRecallReasons),
            json_encode($notified_method),
            $follow_up_date,
            $lab_number
        ]);

        if ($updateResult === false) {
            throw new Exception('Failed to update record: ' . pg_last_error($pg_con));
        }

        $response = [
            'status' => 'success',
            'message' => 'Recall instruction updated successfully',
            'lab_number' => $lab_number,
            'recalled_doctor' => $recalled_doctor,
            'recall_reason' => $existingRecallReasons,
            'notified_method' => $notified_method, // Return notified_method
            'follow_up_date' => $follow_up_date, // Return follow_up_date
            'timestamp' => $timestamp
        ];
    } else {
        // Insert new record
        $newRecallData = [
            $recalled_doctor => [$newData]
        ];

        $insertQuery = "INSERT INTO llx_cyto_recall_management 
                        (lab_number, recall_reason, recalled_doctor, notified_method, follow_up_date, notified_user, created_date) 
                        VALUES ($1, $2, $3, $4, $5, $6, NOW())";

        $insertResult = pg_query_params($pg_con, $insertQuery, [
            $lab_number,
            json_encode($newRecallData),
            $recalled_doctor,
            json_encode($notified_method),
            $follow_up_date,
            $recalled_doctor // Setting notified_user to recalled_doctor
        ]);

        if ($insertResult === false) {
            throw new Exception('Failed to insert record: ' . pg_last_error($pg_con));
        }

        $response = [
            'status' => 'success',
            'message' => 'Recall instruction saved successfully',
            'lab_number' => $lab_number,
            'recalled_doctor' => $recalled_doctor,
            'recall_reason' => $newRecallData,
            'notified_method' => $notified_method, // Return notified_method
            'follow_up_date' => $follow_up_date, // Return follow_up_date
            'notified_user' => $recalled_doctor, // Return notified_user
            'timestamp' => $timestamp
        ];
    }

    // Send clean JSON response
    ob_end_clean();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    // Handle errors gracefully
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}
?>
