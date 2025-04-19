<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = isset($_POST['report_type']) ? strtolower(trim($_POST['report_type'])) : '';
    $labNumber = isset($_POST['lab_number']) ? trim($_POST['lab_number']) : '';
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

    // Check if required fields are present
    if (empty($reportType) || empty($labNumber)) {
        die(json_encode(['status' => 'error', 'message' => 'Required fields are missing']));
    }

    // For preliminary reports, check if report exists first
    if ($reportType === 'preliminary report') {
        // Prepare and execute query to check if preliminary report exists
        $query = "SELECT * FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 70";
        $result = pg_query_params($pg_con, $query, array($labNumber));

        if (!$result) {
            die("Error in SQL query: " . pg_last_error($pg_con));
        }

        if (pg_num_rows($result) === 0) {
            // No preliminary report found
            echo "<div class='alert alert-danger'>";
            echo "<h3>Report Not Ready</h3>";
            echo "<p>Lab Number: $labNumber</p>";
            echo "<p>User Id: $user_id</p>";
            echo "<p>Preliminary Report Not Ready</p>";
            echo "</div>";
            exit();
        }
        
        // If we get here, preliminary report exists
        echo "<div class='alert alert-info'>";
        echo "<h3>Preliminary Report Ready</h3>";
        echo "<p>Lab Number: $labNumber</p>";
        echo "<p>This is a preliminary report. The final report will be available later.</p>";
        echo "</div>";
    } 
    elseif ($reportType === 'final report') {
        // Handle final report (no check needed)
        echo "<div class='alert alert-success'>";
        echo "<h3>Final Report Ready to Deliver</h3>";
        echo "<p>Lab Number: $labNumber</p>";
        echo "<p>The final report is now complete and ready for delivery.</p>";
        echo "</div>";
    } 
    else {
        // Handle unknown report types
        echo "<div class='alert alert-warning'>";
        echo "<h3>Unknown Report Type</h3>";
        echo "<p>The specified report type '$reportType' is not recognized.</p>";
        echo "</div>";
    }
}
?>