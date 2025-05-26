<?php
include("../connection.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify database connection
    if (!$pg_con) {
        die("Database connection failed: " . pg_last_error());
    }

    // Extract and sanitize input data
    $fk_gross_id = isset($_POST['fk_gross_id'][0]) ? pg_escape_string(trim($_POST['fk_gross_id'][0])) : null;
    $lab_number = isset($_POST['lab_number'][0]) ? pg_escape_string(trim($_POST['lab_number'][0])) : null;
    $created_user = isset($_POST['created_user'][0]) ? pg_escape_string(trim($_POST['created_user'][0])) : null;
    $row_ids = isset($_POST['row_id']) ? $_POST['row_id'] : [];

    // Validate required fields
    if (empty($fk_gross_id) || empty($lab_number) || empty($created_user)) {
        die("Error: Required fields are missing");
    }

    // Process specimen and description arrays
    $specimens = isset($_POST['specimen']) ? $_POST['specimen'] : array();
    $descriptions = isset($_POST['description']) ? $_POST['description'] : array();

    // Begin transaction
    pg_query($pg_con, "BEGIN");

    try {
        // Update llx_preliminary_report_microscopic table by row_id
        for ($i = 0; $i < count($specimens); $i++) {
            $specimen = pg_escape_string(trim($specimens[$i]));
            $description = !empty(trim($descriptions[$i])) ? pg_escape_string(trim($descriptions[$i])) : 'Sections show';
            $row_id = isset($row_ids[$i]) ? pg_escape_string($row_ids[$i]) : null;

            // STEP 1: Fetch old description and previous_description from microscopic table
            $sql_old_desc = "SELECT description, previous_description FROM llx_preliminary_report_microscopic WHERE row_id = $1";
            $result_old_desc = pg_query_params($pg_con, $sql_old_desc, array($row_id));

            if (!$result_old_desc || pg_num_rows($result_old_desc) == 0) {
                throw new Exception("Row not found for old description");
            }

            $old_desc_data = pg_fetch_assoc($result_old_desc);
            $old_description = $old_desc_data['description'];
            $previous_description_json = $old_desc_data['previous_description'];

            // STEP 2: Decode previous_description JSON
            $previous_description_data = $previous_description_json ? json_decode($previous_description_json, true) : [];

            // STEP 3: Append current description with user and date
            if (!empty($old_description)) {
                $description_entry = [
                    'user' => $created_user,
                    'description' => $old_description,
                    'date' => date("j F, Y g:i A")
                ];
                $previous_description_data[] = $description_entry;
            }

            // STEP 4: Re-encode the updated array
            $updated_previous_description = json_encode($previous_description_data);

            // STEP 5: Update the microscopic report table
            $sql_micro_update = "UPDATE llx_preliminary_report_microscopic
                                 SET specimen = $1, description = $2, updated_user = $3, status = $4, previous_description = $5
                                 WHERE row_id = $6";

            $params_micro = array($specimen, $description, $created_user, 'Done', $updated_previous_description, $row_id);
            $result_micro = pg_query_params($pg_con, $sql_micro_update, $params_micro);

            if (!$result_micro) {
                throw new Exception("Microscopic report update failed: " . pg_last_error($pg_con));
            }

            // STEP 6: Also update preliminary_report table for historical tracking (optional)
            $sql_old_data = "SELECT created_user, created_date, previous_preliminary_report FROM llx_preliminary_report WHERE lab_number = $1";
            $result_old_data = pg_query_params($pg_con, $sql_old_data, array($lab_number));

            if (!$result_old_data || pg_num_rows($result_old_data) == 0) {
                throw new Exception("Row not found for update in llx_preliminary_report");
            }

            $old_data = pg_fetch_assoc($result_old_data);
            $old_created_user = $old_data['created_user'];
            $old_created_date = $old_data['created_date'];
            $previous_report = $old_data['previous_preliminary_report'];

            $previous_report_data = $previous_report ? json_decode($previous_report, true) : [];
            $new_report_entry = [
                'user' => $old_created_user,
                'date' => date("Y-m-d H:i:s")
            ];
            $previous_report_data[] = $new_report_entry;

            $updated_previous_report = json_encode($previous_report_data);

            $sql_report_update = "UPDATE llx_preliminary_report
                                  SET previous_preliminary_report = $1
                                  WHERE lab_number = $2";

            $params_report = array($updated_previous_report, $lab_number);
            $result_report = pg_query_params($pg_con, $sql_report_update, $params_report);

            if (!$result_report) {
                throw new Exception("Preliminary report update failed: " . pg_last_error($pg_con));
            }
        }

        // Commit transaction
        pg_query($pg_con, "COMMIT");

        // Redirect on success
        header("Location: " . filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL));
        exit();

    } catch (Exception $e) {
        // Rollback on error
        pg_query($pg_con, "ROLLBACK");
        die("Database error: " . $e->getMessage());
    }
}

// Close connection (though PHP will auto-close at script end)
if ($pg_con) {
    pg_close($pg_con);
}
?>