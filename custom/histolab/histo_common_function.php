<?php 
include('connection.php');

function get_histo_gross_specimen_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id, g.lab_number, g.gross_create_date, g.gross_status,
            s.gross_specimen_section_id, s.section_code, s.cassettes_numbers, s.tissue
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]' AND s.bone = 'no'";
    $result = pg_query($pg_con, $sql);

    $gross_specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $gross_specimens[] = ['Lab Number' => $row['lab_number'], 'Gross Create Date' => $row['gross_create_date'],
            'Gross Status'=>$row['gross_status'], 'gross_specimen_section_id' => $row['gross_specimen_section_id'], 
            'section_code' => $row['section_code'], 'cassettes_numbers' => $row['cassettes_numbers'], 'tissue' => $row['tissue']];
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


function get_bones_not_ready_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id, g.lab_number, g.gross_create_date, g.gross_status, g.gross_assistant_name, g.gross_doctor_name,
            s.gross_specimen_section_id, s.section_code, s.cassettes_numbers, s.tissue, s.bone
            FROM llx_gross g
            INNER JOIN llx_gross_specimen_section s ON g.gross_id = CAST(s.fk_gross_id AS INTEGER)
            WHERE g.gross_status = 'Done'
            AND s.fk_gross_id !~ '[^\d]'
			AND s.bone = 'yes' order by s.gross_specimen_section_id ASC;
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
                'tissue'  => $row['tissue']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

?>