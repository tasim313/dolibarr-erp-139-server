<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Loop through each entry in the POST data
    foreach ($_POST['specimen'] as $index => $specimen) {
        // Ensure all required fields are present
        if (!isset($_POST['lab_number'][$index], $_POST['fk_gross_id'][$index], $_POST['description'][$index], $_POST['row_id'][$index])) {
            continue; // Skip if any required field is missing
        }

        // Trim and sanitize input data
        $lab_number = trim($_POST['lab_number'][$index]);
        $fk_gross_id = trim($_POST['fk_gross_id'][$index]);
        $description = trim($_POST['description'][$index]);
        $specimen = trim($_POST['specimen'][$index]);
        $row_id = trim($_POST['row_id'][$index]);

        // Check if the record exists based on row_id (unique identifier)
        $check_sql = "SELECT rowid FROM llx_other_report_micro WHERE rowid = $1";
        $check_result = pg_query_params($pg_con, $check_sql, array($row_id));

        if ($check_result && pg_num_rows($check_result) > 0) {
            // Update existing record
            $update_sql = "UPDATE llx_other_report_micro 
                          SET description = $1, specimen = $2, lab_number = $3, fk_gross_id = $4 
                          WHERE rowid = $5";
            pg_query_params($pg_con, $update_sql, array($description, $specimen, $lab_number, $fk_gross_id, $row_id));
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO llx_other_report_micro (rowid, lab_number, fk_gross_id, description, specimen)
                          VALUES ($1, $2, $3, $4, $5)";
            pg_query_params($pg_con, $insert_sql, array($row_id, $lab_number, $fk_gross_id, $description, $specimen));
        }
    }

    // Redirect back to the previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>