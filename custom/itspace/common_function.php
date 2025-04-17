<?php 
include('connection.php');

function get_summary_list($labnumber) {
    global $pg_con;

    // Prepare SQL query with placeholders to prevent SQL injection
    $sql = "SELECT 
                ct.id,
                ct.labno, 
                u.login AS user_name,  
                ws.name AS status_name
            FROM 
                llx_commande_trackws ct
            JOIN 
                llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
            JOIN 
                llx_user u ON ct.user_id = u.rowid
            WHERE 
                ct.labno = $1";  // Using $1 as a placeholder for lab number

    // Use a prepared statement for safety and efficiency
    $result = pg_prepare($pg_con, "get_summary_query", $sql);

    if ($result === false) {
        echo 'Error preparing query: ' . pg_last_error($pg_con);
        return [];
    }

    // Execute the query with the actual parameter
    $result = pg_execute($pg_con, "get_summary_query", [$labnumber]);

    if ($result === false) {
        echo 'Error executing query: ' . pg_last_error($pg_con);
        return [];
    }

    // Fetch all rows
    $existingdata = pg_fetch_all($result) ?: [];

    // Free the result resource
    pg_free_result($result);

    return $existingdata;
}

function preliminary_report_release($lab_number) {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // Ensure the lab number is not empty
    if (empty($lab_number)) {
        return ['error' => 'Lab number is required.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT * 
        FROM llx_commande_trackws 
        WHERE fk_status_id = '69' 
          AND labno = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_preliminary_report_release_by_lab_number";

    // Check if the statement has already been prepared
    static $prepared_statements = [];

    if (!isset($prepared_statements[$stmt_name])) {
        $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

        if (!$prepare_result) {
            error_log('Query preparation error: ' . pg_last_error($pg_con));
            return ['error' => 'An error occurred while preparing the query.'];
        }

        $prepared_statements[$stmt_name] = true;
    }

    // Execute the prepared query with the lab number as a parameter
    $result = pg_execute($pg_con, $stmt_name, [$lab_number]);

    // Check if the query execution was successful
    if ($result) {
        $rows = pg_fetch_all($result);
        pg_free_result($result);
        return $rows ?: []; // Return empty array if no rows found
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}

function preliminary_report_ready_in_dispatch_centers($lab_number) {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // Ensure the lab number is not empty
    if (empty($lab_number)) {
        return ['error' => 'Lab number is required.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT * 
        FROM llx_commande_trackws 
        WHERE fk_status_id = '70' 
          AND labno = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_preliminary_report_ready_by_lab_number";

    // Check if the statement has already been prepared
    static $prepared_statements = [];

    if (!isset($prepared_statements[$stmt_name])) {
        $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

        if (!$prepare_result) {
            error_log('Query preparation error: ' . pg_last_error($pg_con));
            return ['error' => 'An error occurred while preparing the query.'];
        }

        $prepared_statements[$stmt_name] = true;
    }

    // Execute the prepared query with the lab number as a parameter
    $result = pg_execute($pg_con, $stmt_name, [$lab_number]);

    // Check if the query execution was successful
    if ($result) {
        $rows = pg_fetch_all($result);
        pg_free_result($result);
        return $rows ?: []; // Return empty array if no rows found
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
    
}

?>