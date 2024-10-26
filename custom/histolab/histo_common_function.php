<?php 
include('connection.php');

function get_histo_gross_specimen_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id, g.lab_number, g.gross_create_date, g.gross_status, g.gross_doctor_name as doctor, g.gross_assistant_name as assistant,
            g.gross_station_type, s.gross_specimen_section_id, s.section_code, s.cassettes_numbers, s.tissue, s.requires_slide_for_block
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]' AND s.bone = 'no' AND s.re_gross = ''";
    $result = pg_query($pg_con, $sql);

    $gross_specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $gross_specimens[] = ['Lab Number' => $row['lab_number'], 'Gross Create Date' => $row['gross_create_date'],
            'Gross Status'=>$row['gross_status'], 'gross_specimen_section_id' => $row['gross_specimen_section_id'], 
            'section_code' => $row['section_code'], 'cassettes_numbers' => $row['cassettes_numbers'], 'tissue' => $row['tissue'],
            'doctor' => $row['doctor'], 'assistant' => $row['assistant'], 'requires_slide_for_block' => $row['requires_slide_for_block'],
            'gross_station_type' => $row['gross_station_type'],
        ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $gross_specimens;
}


function get_histo_doctor_instruction_list() {
    global $pg_con;

    $sql = "SELECT 
            ct.id,
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description
        FROM 
            llx_commande_trackws ct
        JOIN 
            llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
        JOIN 
            llx_user u ON ct.user_id = u.rowid
        WHERE 
            ct.create_time BETWEEN '2024-07-27' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
            AND (ct.lab_room_status IS NULL OR ct.lab_room_status = '') 
        ORDER BY 
            ct.id ASC
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
                'track_id' => $row['id']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_histo_doctor_instruction_inprogress_list() {
    global $pg_con;

    $sql = "SELECT 
            ct.id,
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description
        FROM 
            llx_commande_trackws ct
        JOIN 
            llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
        JOIN 
            llx_user u ON ct.user_id = u.rowid
        WHERE 
            ct.create_time BETWEEN '2024-07-27' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
            AND ct.lab_room_status = 'in_progress'
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
                'track_id' => $row['id']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_histo_doctor_instruction_complete_list() {
    global $pg_con;

    $sql = "SELECT 
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description
            FROM 
                llx_commande_trackws ct
            JOIN 
                llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
            JOIN 
                llx_user u ON ct.user_id = u.rowid
            WHERE 
                ct.create_time BETWEEN '2024-07-27' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
            ORDER BY 
                ct.id DESC;
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
                'Description' => $row['description']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_histo_doctor_instruction_on_hold_list() {
    global $pg_con;

    $sql = "SELECT 
            ct.id,
            ct.create_time, 
            ct.labno, 
            u.login AS user_name,  
            ws.name AS status_name, 
            ws.section, 
            ct.description
        FROM 
            llx_commande_trackws ct
        JOIN 
            llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
        JOIN 
            llx_user u ON ct.user_id = u.rowid
        WHERE 
            ct.create_time BETWEEN '2024-07-27' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
            AND ct.lab_room_status = 'on-hold'
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
                'track_id' => $row['id']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_slide_block_order_list() {
    global $pg_con;
    $existingdata = array();

    // SQL query to retrieve the required fields
    $sql = "SELECT 
        c.rowid,
        c.ref, 
        c.date_commande, 
        c.date_livraison,
        e.test_type,
        d.description,
        (SELECT lastname FROM llx_socpeople WHERE rowid = CAST(e.courier AS INTEGER)) AS courier_name
    FROM 
        llx_commande c
    JOIN 
        llx_commande_extrafields e ON CAST(c.rowid AS INTEGER) = e.fk_object
    JOIN 
        llx_commandedet d ON c.rowid = d.fk_commande
    WHERE 
        c.date_livraison >= '2024-01-01'
        AND c.fk_statut != '0'
        AND c.fk_statut != '3'
        AND e.test_type = 'SBO'
        AND NOT EXISTS (
            SELECT 1
            FROM llx_commande_trackws t
            WHERE t.labno = c.ref
            AND t.fk_status_id = 51
        )
    ORDER BY 
        c.ref DESC";

    // Run the query and check for success
    $result = pg_query($pg_con, $sql);

    if ($result) {
        // Fetch rows and store them in the array
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'ref' => $row['ref'],
                'date_commande' => $row['date_commande'],
                'date_livraison' => $row['date_livraison'],
                'test_type' => $row['test_type'],
                'courier' => $row['courier_name'],
                'description' => $row['description']
            );
        }
        // Free the result set
        pg_free_result($result);
    } else {
        // Handle query error
        return 'Error: ' . pg_last_error($pg_con);
    }

    // Return the data array
    return $existingdata;
}


function get_slide_block_order_ready_list() {
    global $pg_con;
    $existingdata = array();

    // SQL query to retrieve the required fields
    $sql = "SELECT 
                ct.id,
                ct.create_time, 
                ct.labno, 
                u.login AS user_name,  
                ws.name AS status_name, 
                ws.section, 
                ct.description
            FROM 
                llx_commande_trackws ct
            JOIN 
                llx_commande_wsstatus ws ON ct.fk_status_id = ws.id
            JOIN 
                llx_user u ON ct.user_id = u.rowid
            WHERE 
                ct.create_time BETWEEN '2024-01-01' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
                AND ct.fk_status_id = '51'
            ORDER BY 
                ct.id ASC";

    // Run the query and check for success
    $result = pg_query($pg_con, $sql);

    if ($result) {
        // Fetch rows and store them in the array
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'id'           => $row['id'], // Include 'id' if needed
                'labno'        => $row['labno'],
                'create_time'  => $row['create_time'],
                'user_name'    => $row['user_name'],
                'status_name'  => $row['status_name'],
                'section'      => $row['section'], // Include 'section' if needed
                'description'  => $row['description'] // Include 'description' if needed
            );
        }
        // Free the result set
        pg_free_result($result);
    } else {
        // Handle query error
        error_log('Database query error: ' . pg_last_error($pg_con)); // Log the error
        return false; // Return false to indicate failure
    }

    // Return the data array
    return $existingdata;
}


function get_bones_not_ready_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id, g.lab_number, g.gross_create_date, g.gross_status, 
                g.gross_assistant_name, g.gross_doctor_name,
                s.gross_specimen_section_id, s.section_code, s.cassettes_numbers, 
                s.tissue, s.bone, s.requires_slide_for_block
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]'
            AND s.bone = 'yes' 
            AND (s.boneslide IS NULL OR s.boneslide != 'Bones Slide Ready')
            ORDER BY s.gross_specimen_section_id ASC;
    ";

    $result = pg_query($pg_con, $sql);

    $existingdata = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = [
                'lab_number' => $row['lab_number'],
                'gross_doctor_name' => $row['gross_doctor_name'], 
                'gross_assistant_name' => $row['gross_assistant_name'], 
                'section_code' => $row['section_code'], 
                'bone' => $row['bone'],
                'cassettes_numbers' => $row['cassettes_numbers'],
                'tissue'  => $row['tissue'],
                'id' => $row['gross_specimen_section_id'],
                'requires_slide_for_block' => $row['requires_slide_for_block'],
                'gross_create_date' => $row['gross_create_date']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_bone_status_lab_number($LabNumber) {
    global $pg_con;

    $sql = "SELECT  s.bone, s.boneslide, g.lab_number, s.section_code
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]'
            AND s.bone = 'yes' 
            AND g.lab_number = '$LabNumber'
            ORDER BY s.gross_specimen_section_id ASC;
    ";

    $result = pg_query($pg_con, $sql);

    $existingdata = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = [
                'lab_number' => $row['lab_number'],
                'bones_status' => $row['bone'], 
                'status_name' => $row['boneslide'], 
                'block_number' => $row['section_code'], 
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}



function get_histo_techs_user_list() {
    global $pg_con;

    $sql = "SELECT u.rowid, u.firstname, u.lastname, u.login
            FROM llx_usergroup_user AS ugu
            JOIN llx_usergroup AS ug ON ugu.fk_usergroup = ug.rowid
            JOIN llx_user AS u ON ugu.fk_user = u.rowid
            WHERE ug.nom = 'Histo Techs'";
    $result = pg_query($pg_con, $sql);

    $assistants = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $assistants[] = ['assistants_name' =>$row['firstname'] . ' ' . $row['lastname'], 'username' => $row['login'], 'userId' => $row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $assistants;
}
?>