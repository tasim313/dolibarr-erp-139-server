<?php 
include('../connection.php');

function batch_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "select rowid,
            name,
            created_date,
            created_user,
            updated_user,
            created_time,
            updated_time from llx_batch
            ORDER BY rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch", $sql);
    $result = pg_execute($pg_con, "get_batch", array());

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function batch_details_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "SELECT bd.rowid,
                   b.name AS batch_name,
                   bd.lab_number,
                   bd.gross_station
            FROM llx_batch_details AS bd
            JOIN llx_batch AS b ON bd.batch_number = b.rowid
            ORDER BY bd.rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch_details", $sql);
    $result = pg_execute($pg_con, "get_batch_details", array());

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}



function batch_details_cassettes_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "SELECT 
                c.rowid, 
                b.name AS batch_name,
                c.cassettes_number, 
                c.created_date, 
                c.created_user, 
                c.updated_user, 
                c.created_time, 
                c.updated_time
                FROM 
                    llx_batch_details_cassettes AS c
                JOIN 
                    llx_batch_details AS bd ON c.batch_details = bd.rowid -- references the batch
                JOIN 
                    llx_batch AS b ON bd.batch_number = b.rowid -- Use 'rowid' from llx_batch
                ORDER BY 
                    c.rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch_details", $sql);
    $result = pg_execute($pg_con, "get_batch_details", array());

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function cassettes_count_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "SELECT 
            c.rowid, 
            b.name, 
            c.total_cassettes_count, 
            c.created_date, 
            c.description 
        FROM 
            llx_batch_cassette_counts AS c
        JOIN 
            llx_batch AS b ON c.batch_details_cassettes = b.rowid 
        ORDER BY 
            c.rowid ASC;";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch_count", $sql);
    $result = pg_execute($pg_con, "get_batch_count", array());

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}
?>