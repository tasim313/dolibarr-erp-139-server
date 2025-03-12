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

    // Validate required fields
    if (!$previousLabNumber || !$reportType || !$newLabNumber || !$createdUser) {
        die("❌ Error: Missing or invalid data in llx_other_report fields.");
    }
    if (!$LabNumber || !$loggedInUserId || !is_numeric($loggedInUserId)) {
        die("❌ Error: Missing LabNumber or User ID.");
    }

    // Insert into llx_other_report
    $stmt1 = pg_prepare($pg_con, "insert_other_report", 
        "INSERT INTO llx_other_report (previous_lab_number, report_type, new_lab_number, created_user, created_date) 
         VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP)");

    if (!$stmt1) {
        die("❌ Error preparing llx_other_report: " . pg_last_error($pg_con));
    }

    $result1 = pg_execute($pg_con, "insert_other_report", [$previousLabNumber, $reportType, $newLabNumber, $createdUser]);

    if (!$result1) {
        die("❌ Error inserting into llx_other_report: " . pg_last_error($pg_con));
    }

    if ($LabNumber && $loggedInUserId && $fk_status_id && $description) {
        $stmt = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
        $result = pg_execute($pg_con, "insert_query", array($LabNumber, $loggedInUserId, $fk_status_id, $description));

        if (!$result) {
            echo "Error inserting data: " . pg_last_error($pg_con);
        } 
    } 

    // Redirect back after success
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;

} 

?>