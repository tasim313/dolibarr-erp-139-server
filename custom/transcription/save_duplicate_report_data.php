<?php
include('connection.php'); // Ensure correct path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $previousLabNumber = isset($_POST['previous_lab_number']) ? $_POST['previous_lab_number'] : '';
    $reportType = isset($_POST['reportType']) ? $_POST['reportType'] : '';
    $newLabNumber = isset($_POST['newLabNumber']) ? $_POST['newLabNumber'] : '';
    $createdUser = isset($_POST['created_user']) ? $_POST['created_user'] : '';
    $LabNumber = isset($_POST['lab_number']) ? $_POST['lab_number'] : '';
    $loggedInUserId = isset($_POST['user_id']) ? $_POST['user_id'] : '';

    // Debugging: Check received data
    if (empty($previousLabNumber) || empty($reportType) || empty($newLabNumber) || empty($createdUser)) {
        die("Error: Missing or invalid data in llx_other_report fields.");
    }
    if (empty($LabNumber) || empty($loggedInUserId)) {
        die("Error: Missing LabNumber or User ID for llx_commande_trackws.");
    }

    // Insert into llx_other_report
    $stmt1 = pg_prepare($pg_con, "insert_other_report", 
        "INSERT INTO llx_other_report (previous_lab_number, report_type, new_lab_number, created_user, created_date) 
         VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP)");
    $result1 = pg_execute($pg_con, "insert_other_report", array($previousLabNumber, $reportType, $newLabNumber, $createdUser));

    if (!$result1) {
        die("Error inserting into llx_other_report: " . pg_last_error($pg_con));
    }

    // Define status IDs and descriptions based on report type
    $statusMap = [
        "duplicate" => ["Duplicate Report issued", 53],
        "correction" => ["Correction of Report issued", 65],
        "review" => ["Internal Histopathology Report issued", 66],
        "corrigendum" => ["Corrigendum Report issued", 67],
        "addendum" => ["Addendum Report issued", 68],
    ];

    if (isset($statusMap[$reportType])) {
        $description = $statusMap[$reportType][0];
        $fk_status_id = $statusMap[$reportType][1];

        // Debugging: Print values before inserting
        error_log("Attempting to insert into llx_commande_trackws: LabNumber=$LabNumber, UserID=$loggedInUserId, StatusID=$fk_status_id, Description=$description");

        // Insert into llx_commande_trackws
        $stmt2 = pg_prepare($pg_con, "insert_commande_trackws", 
            "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) 
             VALUES ($1, $2, $3, $4)");
        $result2 = pg_execute($pg_con, "insert_commande_trackws", array($LabNumber, $loggedInUserId, $fk_status_id, $description));

        if (!$result2) {
            die("Error inserting into llx_commande_trackws: " . pg_last_error($pg_con));
        } else {
            error_log("✅ Successfully inserted into llx_commande_trackws.");
        }
    } else {
        die("Error: Invalid report type.");
    }

    // Redirect back
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;

} else {
    die("Invalid request method.");
}
?>