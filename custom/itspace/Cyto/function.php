<?php 
include("../connection.php");

function cyto_study_patient_info() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT 
            d.rowid,
            d.lab_number,
            d.screening_study,
            d.screening_patient_history,
            d.screening_study_count,
            d.screening_study_count_data,
            d.finalization_study,
            d.finalization_patient_history,
            d.screening_doctor_name,
            d.finalization_doctor_name,
            d.finalization_study_count,
            d.finalization_study_count_data,
            s.status,
            s.status_list,
            s.comment
        FROM 
            llx_cyto_doctor_study_patient_info AS d
        LEFT JOIN 
            llx_cyto_study_patient_info_dispatch_center AS s
            ON d.lab_number = s.lab_number
        WHERE 
            s.status IS NULL 
            OR s.status = '' 
            OR s.status = 'incomplete'
    ";

    // Prepare the SQL statement
    $stmt_name = "get_study_patient_info"; // Unique name for the prepared statement
    if (!pg_prepare($pg_con, $stmt_name, $sql)) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared statement (no parameters in this case)
    $result = pg_execute($pg_con, $stmt_name, []); // Provide an empty array for parameters

    // Check if the query execution was successful
    if ($result) {
        // Fetch all rows of the result
        $rows = pg_fetch_all($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched rows or an empty array if no data found
        return $rows ?: [];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}

?>