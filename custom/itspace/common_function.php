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


?>