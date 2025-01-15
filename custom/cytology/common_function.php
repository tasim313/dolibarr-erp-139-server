<?php 
include('connection.php');

function get_cyto_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            CONCAT(e.test_type, '', c.ref) AS lab_number, 
            c.rowid AS rowid
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
        LEFT JOIN 
            llx_cyto AS cy ON TRIM(LEADING 'FNA' FROM cy.lab_number) = c.ref
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            fk_statut = 1 
            AND date_commande BETWEEN '2024-05-03' AND CURRENT_DATE 
            AND e.test_type = 'FNA'
            AND (cy.status <> 'done' OR cy.status IS NULL)";
    $result = pg_query($pg_con, $sql);

    $labnumbers = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = ['patient_code' => $row['patient_code'], 'lab_number' => $row['lab_number'],
            'fk_commande'=>$row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}


function get_cyto_recall_list() {
    global $pg_con;

    $sql = "select rowid, lab_number, patient_code, 
            recall_reason, created_date, recalled_doctor, notified_user,
            notified_method, follow_up_date, status, updated_date from 
            llx_cyto_recall_management ";
    $result = pg_query($pg_con, $sql);

    $labnumbers = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = ['rowid' => $row['rowid'], 'lab_number' => $row['lab_number'],'patient_code' => $row['patient_code'],
            'recall_reason'=>$row['recall_reason'], 'created_date'=>$row['created_date'], 'recalled_doctor' =>$row['recalled_doctor'],
            'notified_user'=>$row['notified_user'], 'notified_method'=>$row['notified_method'], 'follow_up_date'=>$row['follow_up_date'],
            'status'=>$row['status'], 'updated_date'=>$row['updated_date']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}


function get_cyto_patient_history_list($labnumber) {
    global $pg_con;

    // Updated query with subqueries for referred_by_dr_lastname and referred_from_lastname
    $sql = "
        SELECT 
            e.rowid, 
            e.test_type, 
            e.prev_fnac, 
            e.prev_biopsy_date, 
            e.prev_biopsy_op, 
            e.informed, 
            e.given, 
            e.add_history, 
            e.other_labno, 
            e.referred_by_dr_text, 
            e.referredfrom_text, 
            -- Subquery for e.referred_by_dr
            (SELECT lastname 
            FROM llx_socpeople sp1
            WHERE sp1.rowid IN (
                SELECT fk_socpeople 
                FROM llx_categorie_contact 
                WHERE fk_categorie = 3
            ) 
            AND sp1.rowid = e.referred_by_dr::integer
            ) AS referred_by_dr_lastname,
            -- Subquery for e.referred_from
            (SELECT lastname 
            FROM llx_socpeople sp2
            WHERE sp2.rowid IN (
                SELECT fk_socpeople 
                FROM llx_categorie_contact 
                WHERE fk_categorie = 4
            ) 
            AND sp2.rowid = e.referred_from::integer
            ) AS referred_from_lastname
            FROM 
                llx_commande_extrafields e
            JOIN 
                llx_commande c
            ON 
                c.rowid = e.fk_object
            WHERE 
                c.ref = $1
    ";

    // Execute the query with parameterized values
    $result = pg_query_params($pg_con, $sql, [$labnumber]);

    $labnumbers = [];

    if ($result) {
        // Fetch results into the labnumbers array
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = [
                'rowid' => $row['rowid'],
                'test_type' => $row['test_type'],
                'prev_fnac' => $row['prev_fnac'],
                'given' => $row['given'],
                'prev_biopsy_op' => $row['prev_biopsy_op'],
                'informed' => $row['informed'],
                'add_history' => $row['add_history'],
                'other_labno' => $row['other_labno'],
                'referred_by_dr_text' => $row['referred_by_dr_text'],
                'referredfrom_text' => $row['referredfrom_text'],
                'referred_by_dr_lastname' => $row['referred_by_dr_lastname'], // Newly added field
                'referred_from_lastname' => $row['referred_from_lastname']  // Newly added field
            ];
        }

        pg_free_result($result);
    } else {
        // Log or handle the error
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}


function get_cyto_complete_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            CONCAT(e.test_type, '', c.ref) AS lab_number, 
            c.rowid AS rowid
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
        LEFT JOIN 
            llx_cyto AS cy ON TRIM(LEADING 'FNA' FROM cy.lab_number) = c.ref
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            fk_statut = 1 
            AND date_commande BETWEEN '2024-05-03' AND CURRENT_DATE 
            AND e.test_type = 'FNA'
            AND (cy.status = 'done')";
    $result = pg_query($pg_con, $sql);

    $labnumbers = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = ['patient_code' => $row['patient_code'], 'lab_number' => $row['lab_number'],
            'fk_commande'=>$row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}

function get_cyto_list($labnumber) {
    global $pg_con;

    $sql = "select 
        rowid,
        lab_number,
        patient_code,
        fna_station_type,
        doctor,
        assistant,
        status,
        created_user,
        created_date,
        updated_user,
        updated_date from llx_cyto where lab_number = $1";
    $result = pg_query_params($pg_con, $sql, [$labnumber]);

    $labnumbers = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = ['rowid' => $row['rowid'],
            'lab_number' => $row['lab_number'], 
            'patient_code' => $row['patient_code'],
            'fna_station_type' => $row['fna_station_type'],
            'doctor' => $row['doctor'],
            'assistant' => $row['assistant'],
            'status' => $row['status'],
            'created_user' => $row['created_user'],
            'created_date' => $row['created_date'],
            'updated_user' => $row['updated_user'],
            'updated_date'=>$row['updated_date']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}


function get_cyto_clinical_information($cyto_id) {
    global $pg_con;

    $sql = "select 
        rowid,
        cyto_id,
        chief_complain,
        relevant_clinical_history,
        on_examination,
        clinical_impression 
        from llx_cyto_clinical_information where cyto_id = $1 order by rowid ASC";

    $result = pg_query_params($pg_con, $sql, [$cyto_id]);

    $cyto_ids = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $cyto_ids[] = ['rowid' => $row['rowid'],
            'cyto_id' => $row['cyto_id'], 
            'chief_complain' => $row['chief_complain'],
            'relevant_clinical_history' => $row['relevant_clinical_history'],
            'on_examination' => $row['on_examination'],
            'clinical_impression' => $row['clinical_impression']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $cyto_ids;
}

function get_cyto_fixation_details($cyto_id) {
    global $pg_con;

    $sql = "select 
        rowid,
        cyto_id,
        slide_number,
        location,
        fixation_method,
        dry,
        aspiration_materials,
        special_instructions
        from llx_cyto_fixation_details where cyto_id = $1 order by rowid ASC";

    $result = pg_query_params($pg_con, $sql, [$cyto_id]);

    $cyto_ids = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $cyto_ids[] = ['rowid' => $row['rowid'],
            'cyto_id' => $row['cyto_id'], 
            'slide_number' => $row['slide_number'],
            'location' => $row['location'],
            'fixation_method' => $row['fixation_method'],
            'dry' => $row['dry'],
            'aspiration_materials' => $row['aspiration_materials'],
            'special_instructions' => $row['special_instructions']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $cyto_ids;
}

function get_cyto_fixation_additional_details($cyto_id) {
    global $pg_con;

    $sql = "select 
        rowid,
        cyto_id,
        dry_slides_description,
        additional_notes_on_fixation,
        special_instructions_or_tests_required,
        number_of_needle_used,
        number_of_syringe_used
        from llx_cyto_fixation_additional_details where cyto_id = $1 order by rowid ASC";

    $result = pg_query_params($pg_con, $sql, [$cyto_id]);

    $cyto_ids = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $cyto_ids[] = ['rowid' => $row['rowid'],
            'cyto_id' => $row['cyto_id'], 
            'dry_slides_description' => $row['dry_slides_description'],
            'additional_notes_on_fixation' => $row['additional_notes_on_fixation'],
            'fixation_method' => $row['fixation_method'],
            'special_instructions_or_tests_required' => $row['special_instructions_or_tests_required'],
            'number_of_needle_used' => $row['number_of_needle_used'],
            'number_of_syringe_used' => $row['number_of_syringe_used']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $cyto_ids;
}


function get_cyto_chief_complain_list() {
    global $pg_con;

    // Validate the database connection
    if (!$pg_con) {
        error_log('Database connection is not established.');
        return [];
    }

    $sql = "SELECT DISTINCT ON (LOWER(chief_complain)) chief_complain
            FROM llx_cyto_clinical_information
            ORDER BY LOWER(chief_complain) ASC
            LIMIT 100";

    $result = pg_query($pg_con, $sql);
    $chief_complains = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $chief_complains[] = ['chief_complain' => $row['chief_complain']];
        }

        pg_free_result($result);
    } else {
        // Log the error instead of echoing it
        error_log('SQL Error: ' . pg_last_error($pg_con));
    }

    return $chief_complains;
}



function get_cyto_recall_management($lab_number) {
    global $pg_con;

    $sql = "SELECT rowid, lab_number, recall_reason, notified_user, notified_method, follow_up_date 
            FROM llx_cyto_recall_management 
            WHERE lab_number = $1";

    $result = pg_query_params($pg_con, $sql, [$lab_number]);

    $lab_numbers = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $lab_numbers[] = [
                'rowid' => $row['rowid'],
                'lab_number' => $row['lab_number'],
                'recall_reason' => $row['recall_reason'],
                'notified_user' => $row['notified_user'],
                'notified_method' => $row['notified_method'],
                'follow_up_date' => $row['follow_up_date']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $lab_numbers;
} 


function cyto_recall_status_done_list() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data with the condition status = 'done'
    $sql = "
        SELECT rowid, lab_number, patient_code, 
               recall_reason, created_date, recalled_doctor, notified_user,
               notified_method, follow_up_date, status, updated_date 
        FROM llx_cyto_recall_management
        WHERE status = 'done'
    ";

    // Generate a unique statement name
    $stmt_name = "get_recall_status_done_list_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array());

    // Check if the query execution was successful
    if ($result) {
        // Fetch all rows of the result
        $rows = pg_fetch_all($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched rows or a message if no data found
        return $rows ?: ['message' => 'No data found for the given status.'];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}


function cyto_recall_status_not_done_list() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data with the condition status = 'not done' or empty
    $sql = "
        SELECT rowid, lab_number, patient_code, 
               recall_reason, created_date, recalled_doctor, notified_user,
               notified_method, follow_up_date, status, updated_date 
        FROM llx_cyto_recall_management
        WHERE status IS NULL OR status = '' OR status= ' '
    ";

    // Generate a unique statement name
    $stmt_name = "get_recall_status_not_done_list_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array());

    // Check if the query execution was successful
    if ($result) {
        // Fetch all rows of the result
        $rows = pg_fetch_all($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched rows or a message if no data found
        return $rows ?: ['message' => 'No data found for the given status.'];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}


function cyto_special_instructions_list() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data with the condition status = 'done'
    $sql = "
        SELECT 
        f.rowid,
        f.cyto_id,
        f.slide_number,
        f.location,
        f.fixation_method,
        f.dry,
        f.aspiration_materials,
        f.special_instructions
        FROM llx_cyto_fixation_details f
        LEFT JOIN llx_cyto_special_instructions_complete s
            ON f.rowid = s.fixation_details
        WHERE f.special_instructions IS NOT NULL 
        AND f.special_instructions <> ''
        AND s.fixation_details IS NULL
    ";

    // Generate a unique statement name
    $stmt_name = "get_special_instructions_list_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array());

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


function cyto_special_instructions_list_complete() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data where f.rowid matches s.fixation_details
    $sql = "
        SELECT 
        f.rowid,
        f.cyto_id,
        f.slide_number,
        f.location,
        f.fixation_method,
        f.dry,
        f.aspiration_materials,
        f.special_instructions,
        s.created_user,
		s.created_date
        FROM llx_cyto_fixation_details f
        INNER JOIN llx_cyto_special_instructions_complete s
            ON f.rowid = s.fixation_details
    ";

    // Generate a unique statement name
    $stmt_name = "get_special_instructions_list_complete_" . uniqid();

    // Prepare the SQL query
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, array());

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


function cyto_slide_prepared_list() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data from llx_cyto_slide_prepared
    $sql = "
        SELECT 
        lab_number, created_user, created_date
        FROM llx_cyto_slide_prepared
    ";

    // Prepare the SQL query with a fixed statement name
    $stmt_name = "get_slide_prepared_list";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

    // Check if the query execution was successful
    if ($result) {
        // Fetch all rows of the result
        $rows = pg_fetch_all($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched rows or an empty array if no data found
        if (empty($rows)) {
            error_log('No rows found for slide preparation.');
        }
        return $rows ?: [];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}

function cyto_slide_centrifuge_list() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data from llx_cyto_slide_prepared
    $sql = "
        SELECT 
        rowid, lab_number, slide_number, pipette_tips, filter_paper, created_user, created_date
        FROM llx_cyto_slide_centrifuge
    ";

    // Prepare the SQL query with a fixed statement name
    $stmt_name = "get_slide_centrifuge_list";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

    // Check if the query execution was successful
    if ($result) {
        // Fetch all rows of the result
        $rows = pg_fetch_all($result);

        // Free the result resource
        pg_free_result($result);

        // Return the fetched rows or an empty array if no data found
        if (empty($rows)) {
            error_log('No rows found for slide preparation.');
        }
        return $rows ?: [];
    } else {
        error_log('Query execution error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while executing the query.'];
    }
}

function cyto_recall_information_list_by_lab_number($lab_number) {
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
        SELECT rowid, lab_number, recall_reason, created_date, recalled_doctor, 
               notified_user, notified_method, follow_up_date 
        FROM llx_cyto_recall_management 
        WHERE lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_recall_info_by_lab_number";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query with the lab number as a parameter
    $result = pg_execute($pg_con, $stmt_name, [$lab_number]);

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



function cyto_doctor_lab_instruction() {
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
        d.screening_stain_name, 
        d.screening_doctor_name, 
        d.screening_stain_again, 
        d.finalization_stain_name, 
        d.finalization_doctor_name, 
        d.finalization_stain_again,
        s.status
    FROM 
        llx_cyto_doctor_lab_instruction d
    LEFT JOIN 
        llx_cyto_lab_instruction_status s
    ON 
        d.lab_number = s.lab_number
    WHERE 
        s.status IS NULL OR s.status = '' OR s.status = 'incomplete'
    ORDER BY 
        d.rowid DESC 
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_doctor_lab_instruction";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

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


function cyto_lab_instruction_status() {
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
        d.screening_stain_name, 
        d.screening_doctor_name, 
        d.screening_stain_again, 
        d.finalization_stain_name, 
        d.finalization_doctor_name, 
        d.finalization_stain_again,
        s.status_list
    FROM 
        llx_cyto_doctor_lab_instruction d
    LEFT JOIN 
        llx_cyto_lab_instruction_status s
    ON 
        d.lab_number = s.lab_number
    WHERE 
        s.status = 'complete'
    ORDER BY 
        d.rowid DESC 
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_cyto_lab_instruction_status";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

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


function cyto_cancel_status() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT c.ref, c.note_public
            FROM llx_commande c
            JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
            WHERE c.fk_statut = '-1'
            AND c.date_commande BETWEEN '2025-01-01' AND CURRENT_DATE
            AND e.test_type = 'FNA' 
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_cancel_status";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

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


function cyto_postpone_status() {
    global $pg_con;

    // Ensure the database connection is available
    if (!$pg_con) {
        return ['error' => 'Database connection error.'];
    }

    // SQL query to fetch the required data
    $sql = "
        SELECT c.ref, c.note_public
            FROM llx_commande c
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid
            WHERE c.fk_statut = '1'
            AND c.date_commande BETWEEN '2025-01-01' AND CURRENT_DATE
            AND e.test_type = 'FNA'
            AND c.note_public IS NOT NULL
            AND c.note_public != ''
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_postpone_status";

    // Prepare the SQL statement
    $prepare_result = pg_prepare($pg_con, $stmt_name, $sql);

    // Check if the preparation was successful
    if (!$prepare_result) {
        error_log('Query preparation error: ' . pg_last_error($pg_con));
        return ['error' => 'An error occurred while preparing the query.'];
    }

    // Execute the prepared query
    $result = pg_execute($pg_con, $stmt_name, []);

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