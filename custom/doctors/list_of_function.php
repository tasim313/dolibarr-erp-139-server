<?php 
include('connection.php');

function get_done_gross_list_for_doctor() {
    global $pg_con;

    $sql = "SELECT 
                g.gross_id,
                g.lab_number,
                SUBSTRING(g.lab_number, 4) AS lab_number_without_prefix,
                g.patient_code,
                g.gross_assistant_name, 
                g.gross_doctor_name,
                c.date_commande AS date,
                c.date_livraison AS delivery_date
            FROM 
                llx_gross g
            LEFT JOIN 
                llx_commande c ON c.ref = SUBSTRING(g.lab_number, 4)
            LEFT JOIN 
                llx_commande_extrafields e ON e.fk_object = c.rowid
            WHERE 
                g.gross_status = 'Done' 
                AND g.gross_is_completed = 'true'
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_micro m
                    WHERE g.gross_id = CAST(m.fk_gross_id AS INTEGER)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_diagnosis d
                    WHERE g.gross_id = CAST(d.fk_gross_id AS INTEGER)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_micro m
                    WHERE g.lab_number = m.lab_number
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM llx_diagnosis d
                    WHERE g.lab_number = d.lab_number
                );
    ";

    $result = pg_query($pg_con, $sql);

    $done_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $done_list[] = [
                'gross_id' => $row['gross_id'],
                'lab_number' => $row['lab_number'],
                'patient_code' => $row['patient_code'],
                'gross_assistant_name' => $row['gross_assistant_name'],
                'gross_doctor_name' => $row['gross_doctor_name'],
                'date' => $row['date'],
                'delivery_date' => $row['delivery_date']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $done_list;
}


function get_histo_doctor_today_history_list($user_id) {
    global $pg_con;

    $sql = "SELECT 
                ct.id,
                ct.create_time, 
                ct.labno, 
                u.login AS user_name,  
                ws.name AS status_name, 
                ws.section, 
                ct.description,
                ct.lab_room_status
            FROM 
                llx_commande_trackws ct
            JOIN 
                llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
            JOIN 
                llx_user u ON ct.user_id = u.rowid
            WHERE 
                ct.create_time >= CURRENT_DATE 
                AND ct.create_time < CURRENT_DATE + INTERVAL '1 day'
                AND ct.user_id = '$user_id'
                AND ws.name NOT IN ('Start Screening', 'Final Screening Start', 'Diagnosis Completed')
                AND ct.lab_room_status <> 'delete'
            ORDER BY 
                ct.id DESC
            ";

    $result = pg_query($pg_con, $sql);

    $existingdata = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = [
                'TrackCreateTime' => $row['create_time'], 
                'Lab Number' => $row['labno'],
                'User Name' => $row['user_name'], 
                'Status Name' => $row['status_name'], 
                'Section' => $row['section'], 
                'Description' => $row['description'],
                'track_id' => $row['id'],
                'LabRoomStatus' => $row['lab_room_status']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

function get_histo_doctor_yesterday_history_list($user_id) {
    global $pg_con;

    $sql = "SELECT 
            ct.id,
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description,
            ct.lab_room_status
        FROM 
            llx_commande_trackws ct
        JOIN 
            llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
        JOIN 
            llx_user u ON ct.user_id = u.rowid
        WHERE 
            ct.create_time >= CURRENT_DATE - INTERVAL '1 day'
            AND ct.create_time < CURRENT_DATE
            AND ct.user_id = '$user_id'
            AND ws.name NOT IN ('Start Screening', 'Final Screening Start', 'Diagnosis Completed')
            AND ct.lab_room_status <> 'delete'
        ORDER BY 
            ct.id DESC
            ";

    $result = pg_query($pg_con, $sql);

    $existingdata = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = [
                'TrackCreateTime' => $row['create_time'], 
                'Lab Number' => $row['labno'],
                'User Name' => $row['user_name'], 
                'Status Name' => $row['status_name'], 
                'Section' => $row['section'], 
                'Description' => $row['description'],
                'track_id' => $row['id'],
                'LabRoomStatus' => $row['lab_room_status']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}



function get_histo_doctor_instruction_history_list($user_id) {
    global $pg_con;

    $sql = "SELECT 
            ct.id,
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description,
            ct.lab_room_status
        FROM 
            llx_commande_trackws ct
        JOIN 
            llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
        JOIN 
            llx_user u ON ct.user_id = u.rowid
        WHERE 
             ct.user_id = '$user_id'
            AND ws.name NOT IN ('Start Screening', 'Final Screening Start',
								'Diagnosis Completed', 'Finalized', 'Screening Done',
								'Waiting - Patient History / Investigation', 'Waiting - Study')
            AND ct.lab_room_status <> 'delete'
        ORDER BY 
            ct.id DESC
            ";

    $result = pg_query($pg_con, $sql);

    $existingdata = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = [
                'TrackCreateTime' => $row['create_time'], 
                'Lab Number' => $row['labno'],
                'User Name' => $row['user_name'], 
                'Status Name' => $row['status_name'], 
                'Section' => $row['section'], 
                'Description' => $row['description'],
                'track_id' => $row['id'],
                'LabRoomStatus' => $row['lab_room_status']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_cyto_labnumber_list_doctor_module() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            c.ref AS lab_number, 
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


function cyto_special_instructions_list_for_doctor_module($lab_number) {
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
                LEFT JOIN llx_cyto_special_instructions_complete s
                    ON f.rowid = s.fixation_details
                INNER JOIN llx_cyto c
                    ON f.cyto_id::INTEGER = c.rowid -- Cast f.cyto_id to INTEGER
                WHERE c.lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_special_instructions_by_lab_number";

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


function cyto_doctor_case_info_doctor_module($lab_number) {
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
        select rowid,lab_number,screening,screening_datetime,screening_count,
        screening_count_data, finalization, finalization_datetime, finalization_count_data,
        screening_doctor_name, finalization_doctor_name from llx_cyto_doctor_case_info where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_doctor_case_info_by_lab_number";

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


function cyto_doctor_study_patient_info_doctor_module($lab_number) {
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
        select rowid, lab_number, screening_study, screening_patient_history, screening_study_count, screening_study_count_data,
        finalization_study, finalization_patient_history, screening_doctor_name, finalization_doctor_name, finalization_study_count,
        finalization_study_count_data from llx_cyto_doctor_study_patient_info where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_doctor_study_patient_info_by_lab_number";

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

function cyto_doctor_complete_case_doctor_module($lab_number) {
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
        select rowid, lab_number, screening_done, screening_done_date_time, screening_done_count, screening_done_count_data,
        finalization_done, finalization_done_date_time, finalization_done_count, finalization_done_count_data 
        from llx_cyto_doctor_complete_case  where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_doctor_complete_case_by_lab_number";

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

function cyto_doctor_lab_instruction_doctor_module($lab_number) {
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
        select rowid, lab_number, screening_stain_name, 
        screening_doctor_name, screening_stain_again, finalization_stain_name, 
        finalization_doctor_name, finalization_stain_again from llx_cyto_doctor_lab_instruction  where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_doctor_lab_instruction_by_lab_number";

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


function cyto_slide_prepared_doctor_module($lab_number) {
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
        SELECT 
            rowid, lab_number, created_user, created_date
                from llx_cyto_slide_prepared where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_slide_prepared_by_lab_number";

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

function cyto_slide_centrifuge_doctor_module($lab_number) {
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
        select rowid,lab_number, slide_number, pipette_tips, 
        filter_paper, created_user, created_date from llx_cyto_slide_centrifuge where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_slide_centrifuge_by_lab_number";

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

function cyto_diagnosis_doctor_module($lab_number) {
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
        select rowid,lab_number,diagnosis,previous_diagnosis,created_user,
        created_date, updated_user, updated_date from llx_cyto_doctor_diagnosis where lab_number = $1;
    ";

    // Statement name (unique within the connection session)
    $stmt_name = "get_diagnosis_by_lab_number";

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

?>