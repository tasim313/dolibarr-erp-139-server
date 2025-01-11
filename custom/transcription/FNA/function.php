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
               gross_note, 
               recall
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


function cyto_recall_lab_number($lab_number) {
    global $pg_con;

    // SQL query to fetch the required data
    $sql = "
        select rowid, lab_number, fna_station_type, doctor, 
        assistant, status, created_user, created_date, updated_user, updated_date from llx_cyto_recall 
        WHERE lab_number = $1 
        ";

    // Prepare the SQL query
    $stmt_name = "get_cyto_recall";
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


function cyto_recall_clinical_information($cyto_id) {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return 'Database connection error.';
    }

    // Validate the input parameter
    if (!is_int($cyto_id) && !ctype_digit($cyto_id)) {
        return 'Invalid cyto_id parameter.';
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT rowid, cyto_id, chief_complain, additional_relevant_clinical_history,
               additional_findings_on_examination, additional_clinical_impression
        FROM llx_cyto_recall_clinical_information 
        WHERE cyto_id = $1
    ";

    // Prepare the SQL query
    $stmt_name = "get_cyto_recall_clinical_information";
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return 'An error occurred while preparing the query.';
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($cyto_id));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row or a message if no data found
        return $row ?: 'No data found for the given cyto_id.';
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return 'An error occurred while executing the query.';
    }
}


function cyto_recall_fixation_additional_details($cyto_id) {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // Validate the input parameter
    if (!is_int($cyto_id) && !ctype_digit($cyto_id)) {
        return ['error' => 'Invalid cyto_id parameter.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT rowid, cyto_id, dry_slides_description, 
               additional_notes_on_fixation, number_of_needle_used, 
               number_of_syringe_used 
        FROM llx_cyto_recall_fixation_additional_details 
        WHERE cyto_id = $1
    ";

    // Generate a unique statement name
    $stmt_name = "get_fixation_additional_details_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($cyto_id));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row or a message if no data found
        return $row ?: ['message' => 'No data found for the given cyto_id.'];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}


function cyto_recall_fixation_details($cyto_id) {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // Validate the input parameter
    if (!is_int($cyto_id) && !ctype_digit($cyto_id)) {
        return ['error' => 'Invalid cyto_id parameter.'];
    }

    // SQL query to fetch the required data
    $sql = "
        select rowid, cyto_id, 
        slide_number, location, fixation_method, dry, aspiration_materials, 
        special_instructions from llx_cyto_recall_fixation_details 
        WHERE cyto_id = $1
    ";

    // Generate a unique statement name
    $stmt_name = "get_fixation_details_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array($cyto_id));

    // Check if the query execution was successful
    if ($result) {
        // Fetch the first row of the result
        $row = pg_fetch_assoc($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched row or a message if no data found
        return $row ?: ['message' => 'No data found for the given cyto_id.'];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}

?>