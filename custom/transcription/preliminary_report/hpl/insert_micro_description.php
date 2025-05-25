<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify database connection
    if (!$pg_con) {
        die("Database connection failed: " . pg_last_error());
    }

    // Extract and sanitize input data
    $fk_gross_id = isset($_POST['fk_gross_id'][0]) ? pg_escape_string(trim($_POST['fk_gross_id'][0])) : null;
    $lab_number = isset($_POST['lab_number'][0]) ? pg_escape_string(trim($_POST['lab_number'][0])) : null;
    $created_user = isset($_POST['created_user'][0]) ? pg_escape_string(trim($_POST['created_user'][0])) : null;
    $status = isset($_POST['status'][0]) ? pg_escape_string(trim($_POST['status'][0])) : null;

    // Validate required fields
    if (empty($fk_gross_id) || empty($lab_number) || empty($created_user)) {
        die("Error: Required fields are missing Please First Gross Complete then Enter Transcription Data");
    }

    // Process specimen and description arrays
    $specimens = isset($_POST['specimen']) ? $_POST['specimen'] : array();
    $descriptions = isset($_POST['description']) ? $_POST['description'] : array();

    // Begin transaction
    pg_query($pg_con, "BEGIN");

    try {
        // Insert into microscopic report table
        for ($i = 0; $i < count($specimens); $i++) {
            $specimen = pg_escape_string(trim($specimens[$i]));
            $description = !empty(trim($descriptions[$i])) ? pg_escape_string(trim($descriptions[$i])) : 'Sections show';

            $sql_micro = "INSERT INTO llx_preliminary_report_microscopic 
                         (fk_gross_id, specimen, lab_number, description, created_user, status)
                         VALUES ($1, $2, $3, $4, $5, $6)";
            
            $params = array($fk_gross_id, $specimen, $lab_number, $description, $created_user, $status);
            $result_micro = pg_query_params($pg_con, $sql_micro, $params);
            
            if (!$result_micro) {
                throw new Exception("Microscopic insert failed: " . pg_last_error($pg_con));
            }
        }

        // Insert into preliminary report table
        $sql_report = "INSERT INTO llx_preliminary_report 
                      (test_type, lab_number, created_user)
                      VALUES ($1, $2, $3)";
        
        $params = array('HPL', $lab_number, $created_user);
        $result_report = pg_query_params($pg_con, $sql_report, $params);

        if (!$result_report) {
            throw new Exception("Preliminary report insert failed: " . pg_last_error($pg_con));
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