<?php 

include ('../connection.php');


function cyto_microscopic_description_lab($lab_number) {
    global $pg_con;

    // SQL query to fetch the required data
    $sql = "
        SELECT rowid, lab_number, 
               microscopic_description, 
               conclusion, 
               comment,
               aspiration_notes,
               gross_note
        FROM llx_cyto_microscopic_description 
        WHERE lab_number = $1 
        ORDER BY rowid DESC";

    // Prepare the SQL query
    $stmt_name = "get_cyto_description";
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        return 'Error in query preparation: ' . pg_last_error($pg_con);
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($lab_number));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row
        return $row;
    } else {
        return 'Error in query execution: ' . pg_last_error($pg_con);
    }
}

?>