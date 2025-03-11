<?php

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number'] ?? null;
    $new_descriptions = $_POST['new_description'] ?? [];
    $specimen_rowids = $_POST['specimen_rowid'] ?? [];

    if (!$lab_number || empty($new_descriptions)) {
        die("Missing required fields.");
    }

    // Check if lab_number already exists
    $checkSql = "SELECT COUNT(*) FROM llx_other_report_site_Of_specimen WHERE lab_number = $1";
    $checkResult = pg_query_params($pg_con, $checkSql, [$lab_number]);

    if ($checkResult) {
        $row = pg_fetch_result($checkResult, 0, 0);
        pg_free_result($checkResult);

        if ($row > 0) {
            // If lab_number exists, update the records
            foreach ($new_descriptions as $index => $description) {
                $specimen_rowid = $specimen_rowids[$index] ?? null;

                if ($specimen_rowid) {
                    $updateSql = "UPDATE llx_other_report_site_Of_specimen 
                                  SET site_of_specimen = $1 
                                  WHERE rowid = $2 AND lab_number = $3";
                    pg_query_params($pg_con, $updateSql, [$description, $specimen_rowid, $lab_number]);
                }
            }
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            // If lab_number does not exist, insert new records
            foreach ($new_descriptions as $description) {
                $insertSql = "INSERT INTO llx_other_report_site_Of_specimen (lab_number, site_of_specimen) 
                              VALUES ($1, $2)";
                pg_query_params($pg_con, $insertSql, [$lab_number, $description]);
            }
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }
    } else {
        echo "Error checking existing records: " . pg_last_error($pg_con);
    }
}

?>