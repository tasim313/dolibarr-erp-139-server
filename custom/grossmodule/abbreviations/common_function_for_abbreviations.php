<?php 

include("../connection.php");

function abbreviations_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "SELECT rowid, abbreviation_key, abbreviation_full_text 
            FROM llx_abbreviations 
            -- WHERE fk_user_id = $1 
            ORDER BY abbreviation_key ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_abbreviations", $sql);
    // $result = pg_execute($pg_con, "get_abbreviations", array($user_id));
    $result = pg_execute($pg_con, "get_abbreviations", array());

    $existingdata = [];

    if ($result) {
        // Use pg_fetch_all to fetch all rows at once if the dataset is not too large
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


?>