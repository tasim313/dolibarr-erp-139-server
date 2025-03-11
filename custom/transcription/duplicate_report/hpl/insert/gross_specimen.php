<?php 

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lab_number = $_POST['lab_number'];
    $specimen_ids = $_POST['specimen_id'];
    $specimens = $_POST['specimen'];
    $gross_descriptions = $_POST['gross_description'];
    $fk_gross_ids = $_POST['fk_gross_id'];

    // Check if lab_number already exists in the database
    $check_lab_sql = "SELECT COUNT(*) FROM llx_other_report_gross_specimen WHERE lab_number = $1";
    $check_lab_result = pg_query_params($pg_con, $check_lab_sql, [$lab_number]);
    $lab_exists = (pg_fetch_result($check_lab_result, 0, 0) > 0);

    foreach ($specimen_ids as $index => $specimen_id) {
        $specimen = trim($specimens[$index]);
        $gross_description = trim($gross_descriptions[$index]);
        $fk_gross_id = $fk_gross_ids[$index];

        if ($lab_exists) {
            // Retrieve existing data for this specimen
            $check_sql = "
                SELECT specimen, gross_description 
                FROM llx_other_report_gross_specimen 
                WHERE lab_number = $1 AND specimen_id = $2";
            $check_result = pg_query_params($pg_con, $check_sql, [$lab_number, $specimen_id]);

            if (pg_num_rows($check_result) > 0) {
                $row = pg_fetch_assoc($check_result);
                $existing_specimen = trim($row['specimen']);
                $existing_gross_description = trim($row['gross_description']);

                // Update only if values have changed
                if ($existing_specimen !== $specimen || $existing_gross_description !== $gross_description) {
                    $update_sql = "
                        UPDATE llx_other_report_gross_specimen 
                        SET specimen = $1, gross_description = $2, fk_gross_id = $3 
                        WHERE lab_number = $4 AND specimen_id = $5";
                    pg_query_params($pg_con, $update_sql, [$specimen, $gross_description, $fk_gross_id, $lab_number, $specimen_id]);
                }
            } else {
                // Insert new specimen record if not found
                $insert_sql = "
                    INSERT INTO llx_other_report_gross_specimen (specimen_id, specimen, gross_description, fk_gross_id, lab_number)
                    VALUES ($1, $2, $3, $4, $5)";
                pg_query_params($pg_con, $insert_sql, [$specimen_id, $specimen, $gross_description, $fk_gross_id, $lab_number]);
            }
        } else {
            // First-time insertion: Insert all records for the new lab_number
            $insert_sql = "
                INSERT INTO llx_other_report_gross_specimen (specimen_id, specimen, gross_description, fk_gross_id, lab_number)
                VALUES ($1, $2, $3, $4, $5)";
            pg_query_params($pg_con, $insert_sql, [$specimen_id, $specimen, $gross_description, $fk_gross_id, $lab_number]);
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
}
?>