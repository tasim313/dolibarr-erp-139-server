<?php
include('connection.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Collect form data safely
    $previousLabNumber = $_POST['previous_lab_number'] ?? '';
    $reportType = $_POST['reportType'] ?? '';
    $newLabNumber = $_POST['newLabNumber'] ?? '';
    $createdUser = $_POST['created_user'] ?? '';
    $LabNumber = $_POST['lab_number'] ?? '';
    $loggedInUserId = $_POST['user_id'] ?? '';
    $fk_status_id = $_POST['fk_status_id'] ?? ''; 
    $description = $_POST['description'] ?? ''; 

    $updateSuccess = false;
    $insertSuccess = false;
    $successMessage = '';

    // Validate required fields
    if (!$previousLabNumber || !$reportType || !$newLabNumber || !$createdUser) {
        die("❌ Error: Missing or invalid data in llx_other_report fields.");
    }
    if (!$LabNumber || !$loggedInUserId || !is_numeric($loggedInUserId)) {
        die("❌ Error: Missing LabNumber or User ID.");
    }

    // Check if previous_lab_number exists
    $stmt_check = pg_prepare($pg_con, "check_previous_lab", 
        "SELECT previous_report_type, report_type, new_lab_number, created_user, created_date 
         FROM llx_other_report WHERE previous_lab_number = $1"
    );
    $result_check = pg_execute($pg_con, "check_previous_lab", [$previousLabNumber]);

    if ($row = pg_fetch_assoc($result_check)) {
        // Fetch and decode existing previous report types
        $previousReportType = json_decode($row['previous_report_type'], true) ?? [];

        // Store previous values before updating
        $previousReportType[] = [
            "report_type" => $row['report_type'],  
            "lab_number" => $row['new_lab_number'],  
            "created_user" => $row['created_user'], 
            "created_date" => $row['created_date']  
        ];

        // Convert back to JSON format
        $previousReportTypeJson = json_encode($previousReportType);

        // Update query with new values
        $stmt_update = pg_prepare($pg_con, "update_other_report", 
            "UPDATE llx_other_report 
            SET previous_report_type = $1, 
                report_type = $2, 
                new_lab_number = $3, 
                created_user = $4, 
                created_date = CURRENT_TIMESTAMP 
            WHERE previous_lab_number = $5"
        );

        $result_update = pg_execute($pg_con, "update_other_report", [
            $previousReportTypeJson, $reportType, $newLabNumber, $createdUser, $previousLabNumber
        ]);

        if ($result_update) {
            $updateSuccess = true;
            $successMessage = "Data updated successfully!";
        }
    } else {
        
        
        $stmt_insert = pg_prepare($pg_con, "insert_other_report", 
            "INSERT INTO llx_other_report (previous_lab_number, report_type, new_lab_number, created_user, created_date) 
             VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP)"
        );
        $result_insert = pg_execute($pg_con, "insert_other_report", [
            $previousLabNumber, $reportType, $newLabNumber, $createdUser
        ]);

        if ($result_insert) {
            $insertSuccess = true;
            $successMessage = "Data inserted successfully!";
        }
    }

    // Insert into llx_commande_trackws if applicable
    if ($LabNumber && $loggedInUserId && $fk_status_id && $description) {
        $stmt = pg_prepare($pg_con, "insert_query", 
            "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) 
             VALUES ($1, $2, $3, $4)"
        );
        $result = pg_execute($pg_con, "insert_query", [
            $LabNumber, $loggedInUserId, $fk_status_id, $description
        ]);

        if ($result) {
            $insertSuccess = true;
            $successMessage = "Data inserted successfully!";
        }
    } 

    // Show success message only if an update or insert was successful
    if ($updateSuccess || $insertSuccess) {
        echo json_encode([
            'status' => 'success',
            'message' => $successMessage
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '❌ Error: Operation failed.'
        ]);
    }
    
    exit;
} 
?>

