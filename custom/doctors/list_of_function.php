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

?>