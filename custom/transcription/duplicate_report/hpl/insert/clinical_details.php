<?php 

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number'] ?? null;
    $clinical_details = $_POST['clinical_details'] ?? null;
    $created_user = $_POST['created_user'] ?? null;

    if (!$lab_number || !$clinical_details || !$created_user) {
        die("Missing required fields.");
    }

    // Check if lab_number already exists
    $checkSql = "SELECT COUNT(*) FROM llx_other_report_clinical_details WHERE lab_number = $1";
    $checkResult = pg_query_params($pg_con, $checkSql, [$lab_number]);

    if ($checkResult) {
        $row = pg_fetch_result($checkResult, 0, 0);
        pg_free_result($checkResult);

        if ($row > 0) {
            // If lab_number exists, update the record
            $updateSql = "UPDATE llx_other_report_clinical_details 
                          SET clinical_details = $1, created_user = $2, created_date = NOW() 
                          WHERE lab_number = $3";
            $updateResult = pg_query_params($pg_con, $updateSql, [$clinical_details, $created_user, $lab_number]);

            if ($updateResult) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                echo "Update failed: " . pg_last_error($pg_con);
            }
        } else {
            // If lab_number does not exist, insert a new record
            $insertSql = "INSERT INTO llx_other_report_clinical_details (lab_number, clinical_details, created_user) 
                          VALUES ($1, $2, $3)";
            $insertResult = pg_query_params($pg_con, $insertSql, [$lab_number, $clinical_details, $created_user]);

            if ($insertResult) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                echo "Insert failed: " . pg_last_error($pg_con);
            }
        }
    } else {
        echo "Error checking existing records: " . pg_last_error($pg_con);
    }
}

?>