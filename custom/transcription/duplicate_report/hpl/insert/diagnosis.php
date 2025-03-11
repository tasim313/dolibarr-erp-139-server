<?php
include("connection.php");

// Log received POST data
error_log("🟢 Received POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['specimen']) && is_array($_POST['specimen'])) {
        foreach ($_POST['specimen'] as $index => $specimen) {
            $title = $_POST['title'][$index] ?? '';
            $description = $_POST['description'][$index] ?? '';
            $comment = $_POST['comment'][$index] ?? '';
            $fk_gross_id = $_POST['fk_gross_id'][$index] ?? '';
            $lab_number = $_POST['lab_number'][$index] ?? '';
            $row_id = $_POST['row_id'][$index] ?? '';

            if (empty($lab_number)) {
                error_log("⚠️ Skipping entry: Missing Lab Number");
                continue;
            }

            // Debug: Log the data being processed
            error_log("🟢 Processing entry: " . json_encode([
                'specimen' => $specimen,
                'title' => $title,
                'description' => $description,
                'comment' => $comment,
                'fk_gross_id' => $fk_gross_id,
                'lab_number' => $lab_number,
                'row_id' => $row_id,
            ]));

            try {
                // Check if the row_id already exists (to prevent duplicates)
                $check_sql = "SELECT rowid FROM llx_other_report_diagnosis WHERE rowid = $1";
                error_log("🟢 Executing SQL: $check_sql with rowid = $row_id");
                $check_result = pg_query_params($pg_con, $check_sql, array($row_id));

                if ($check_result && pg_num_rows($check_result) > 0) {
                    // Update existing record if rowid exists
                    $update_sql = "UPDATE llx_other_report_diagnosis 
                                   SET specimen = $1, title = $2, description = $3, comment = $4, fk_gross_id = $5 
                                   WHERE rowid = $6";
                    error_log("🟢 Executing SQL: $update_sql");
                    pg_query_params($pg_con, $update_sql, array(
                        $specimen, $title, $description, $comment, $fk_gross_id, $row_id
                    ));
                    error_log("✅ Updated rowid: $row_id");
                } else {
                    // Insert new record if rowid does not exist
                    $insert_sql = "INSERT INTO llx_other_report_diagnosis (specimen, title, description, comment, fk_gross_id, lab_number, rowid) 
                                   VALUES ($1, $2, $3, $4, $5, $6, $7)";
                    error_log("🟢 Executing SQL: $insert_sql");
                    pg_query_params($pg_con, $insert_sql, array(
                        $specimen, $title, $description, $comment, $fk_gross_id, $lab_number, $row_id
                    ));
                    error_log("✅ Inserted new record for Lab Number: $lab_number");
                }
            } catch (Exception $e) {
                error_log("❌ Error: " . $e->getMessage());
            }
        }
    } else {
        error_log("❌ No valid specimen data found in POST.");
    }
} else {
    error_log("❌ Invalid request method. Expected POST.");
}

?>