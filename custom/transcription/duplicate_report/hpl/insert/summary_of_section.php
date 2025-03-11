<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form values
    $gross_summary_id = $_POST['gross_summary_id'];
    $fk_gross_id = $_POST['fk_gross_id'];
    $summary = $_POST['summary'];
    $ink_code = $_POST['ink_code'];
    $lab_number = $_POST['lab_number'];

    // Check if record with the same lab_number exists
    $checkQuery = "SELECT rowid FROM llx_other_report_gross_summary_of_section WHERE lab_number = $1";
    $checkResult = pg_query_params($pg_con, $checkQuery, [$lab_number]);

    if (pg_num_rows($checkResult) > 0) {
        // Record exists, update specific fields if needed
        $updateFields = [];
        $updateValues = [];

        // Check if the values have changed and prepare update SQL
        if ($summary !== '') {
            $updateFields[] = "summary = $1";
            $updateValues[] = $summary;
        }
        if ($ink_code !== '') {
            $updateFields[] = "ink_code = $2";
            $updateValues[] = $ink_code;
        }

        if (!empty($updateFields)) {
            // Add the rowid condition to the update query
            $updateQuery = "UPDATE llx_other_report_gross_summary_of_section 
                            SET " . implode(', ', $updateFields) . "
                            WHERE lab_number = $3";
            // Combine all the values
            $updateValues[] = $lab_number;
            pg_query_params($pg_con, $updateQuery, $updateValues);
        }
    } else {
        // No record with the same lab_number exists, insert a new record
        $insertQuery = "INSERT INTO llx_other_report_gross_summary_of_section 
                        (gross_summary_id, fk_gross_id, summary, ink_code, lab_number) 
                        VALUES ($1, $2, $3, $4, $5)";
        pg_query_params($pg_con, $insertQuery, [
            $gross_summary_id, 
            $fk_gross_id, 
            $summary, 
            $ink_code, 
            $lab_number
        ]);
    }

    // Redirect to avoid re-posting data on refresh
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>