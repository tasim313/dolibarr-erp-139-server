<?php 
include('connection.php');

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
            AND ws.name = 'Waiting - Patient History / Investigation'
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



function get_histo_doctor_instruction_progress_list() {
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
            AND ws.name = 'Waiting - Patient History / Investigation'
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


function get_histo_doctor_instruction_done_list() {
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
            AND ct.lab_room_status = 'done'
            AND ws.name = 'Waiting - Patient History / Investigation'
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
            AND ws.name = 'Waiting - Patient History / Investigation'
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

function get_histo_case_summary_list() {
    global $pg_con;

    // Ensure indexes on columns used in WHERE, JOIN, ORDER BY
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
                ct.create_time BETWEEN '2024-08-15' AND CURRENT_DATE + INTERVAL '1 day' - INTERVAL '1 second'
            ORDER BY 
                ct.id DESC";

    // Use a prepared statement if running this query multiple times
    $result = pg_query($pg_con, $sql);

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