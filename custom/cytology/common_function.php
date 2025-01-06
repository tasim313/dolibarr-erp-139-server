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
        aspiration_note 
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
            'aspiration_note' => $row['aspiration_note']
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


?>