<?php 
include('connection.php');


function get_done_gross_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id,
    g.lab_number,
    g.patient_code,
    g.gross_assistant_name, 
    g.gross_doctor_name
    FROM llx_gross g
        WHERE g.gross_status = 'Done' 
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
                'gross_doctor_name' => $row['gross_doctor_name']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $done_list;
}

function get_pending_transcription_value() {
    global $pg_con;

    $sql = "SELECT COUNT(*) AS total_count
    FROM (
        SELECT g.gross_id,
               g.lab_number,
               g.patient_code,
               g.gross_assistant_name, 
               g.gross_doctor_name
        FROM llx_gross g
        WHERE g.gross_status = 'Done' 
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
          )
    ) AS subquery;";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}

function get_complete_transcription_value() {
    global $pg_con;

    $sql = "SELECT COUNT(*) AS total_count
    FROM (
        SELECT g.gross_id,
               g.lab_number,
               g.patient_code,
               g.gross_assistant_name, 
               g.gross_doctor_name
        FROM llx_gross g
        WHERE g.gross_status = 'Done' 
          AND g.gross_is_completed = 'true'
          AND  EXISTS (
              SELECT 1
              FROM llx_micro m
              WHERE g.gross_id = CAST(m.fk_gross_id AS INTEGER)
          )
          AND  EXISTS (
              SELECT 1
              FROM llx_diagnosis d
              WHERE g.gross_id = CAST(d.fk_gross_id AS INTEGER)
          )
          AND  EXISTS (
              SELECT 1
              FROM llx_micro m
              WHERE g.lab_number = m.lab_number
          )
          AND  EXISTS (
              SELECT 1
              FROM llx_diagnosis d
              WHERE g.lab_number = d.lab_number
          )
    ) AS subquery;";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}


function get_complete_transcription_value_current_month() {
    global $pg_con;

    $sql = "SELECT COUNT(DISTINCT fk_gross_id) AS micro_count
    FROM llx_micro
    WHERE EXTRACT(MONTH FROM create_date) = EXTRACT(MONTH FROM CURRENT_DATE)";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['micro_count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}


function get_complete_transcription_value_current_year() {
    global $pg_con;

    $sql = "SELECT COUNT(DISTINCT fk_gross_id) AS micro_count
    FROM llx_micro
    WHERE EXTRACT(YEAR FROM create_date) = EXTRACT(YEAR FROM CURRENT_DATE);";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['micro_count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}

function get_micro_description($fk_gross_id, $lab_number) {
    global $pg_con;

    $sql = "select row_id, lab_number, fk_gross_id, description, status, specimen from llx_micro where fk_gross_id='$fk_gross_id' 
    AND lab_number = '$lab_number' ORDER BY row_id ASC";

    $result = pg_query($pg_con, $sql);

    $description_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $description_list[] = [
                'row_id' => $row['row_id'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
                'status' => $row['status'],
                'specimen' => $row['specimen']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $description_list;
}


function get_done_transcript_list() {
    global $pg_con;

    $sql = "SELECT g.gross_id,
    g.lab_number,
    g.patient_code,
    g.gross_assistant_name, 
    g.gross_doctor_name
    FROM llx_gross g
        WHERE g.gross_status = 'Done' 
        AND g.gross_is_completed = 'true'
        AND  EXISTS (
        SELECT 1
        FROM llx_micro m
        WHERE g.gross_id = CAST(m.fk_gross_id AS INTEGER)
			AND m.status = 'Done'
        )
        AND EXISTS (
        SELECT 1
        FROM llx_diagnosis d
        WHERE g.gross_id = CAST(d.fk_gross_id AS INTEGER)
			AND d.status = 'Done'
        )
        AND  EXISTS (
        SELECT 1
        FROM llx_micro m
        WHERE g.lab_number = m.lab_number
        )
        AND  EXISTS (
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
                'gross_doctor_name' => $row['gross_doctor_name']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $done_list;
}

function get_patient_details_information($lab_number) {

    global $pg_con;

    $sql = "SELECT s.rowid AS rowid,
        s.nom AS nom,
        s.code_client AS code_client,
        s.address AS address,
        s.phone AS phone,
        s.fax AS fax,
        e.date_of_birth,
        e.sex,
        e.ageyrs,
        e.att_name,
        e.att_relation
        FROM llx_commande AS c
        JOIN llx_societe AS s ON c.fk_soc = s.rowid
        JOIN llx_societe_extrafields AS e ON s.rowid = e.fk_object
        WHERE ref = '$lab_number'";

    $result = pg_query($pg_con, $sql);

   $patients = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
           $patients[] = [
            'rowid' => $row['rowid'],
			'name' => $row['nom'], 
		   'patient_code' => $row['code_client'], 
		   'address'=> $row['address'],
		   'phone' => $row['phone'],
		   'fax' => $row['fax'],
           'date_of_birth' => $row['date_of_birth'],
           'Gender' => $row['sex'],
           'Age' => $row['ageyrs'],
           'att_name' => $row['att_name'],
           'att_relation' => $row['att_relation']
		];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return$patients;
}


function getGrossIdByLabNumber($labNumber) {
    global $pg_con;
    $sql = "SELECT gross_id FROM llx_gross WHERE lab_number = '$labNumber'";
    $result = pg_query($pg_con, $sql);
    if ($result) {
        $row = pg_fetch_assoc($result);
        pg_free_result($result);
        if ($row) {
            return $row['gross_id'];
        } else {
            return null;
        }
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        return null;
    }
}

function getExistingMicroDescriptions($labNumber) {
    global $pg_con;

    $existingMicroDescriptions = array();

    $sql = "SELECT row_id,
    lab_number,
    fk_gross_id,
    description,
    created_user,
    status,
    specimen,
    histologic_type,
    hitologic_grade,
    pattern_of_growth, 
    stromal_reaction, 
    depth_of_invasion, 
    lymphovascular_invasion, 
    perineural_invasion,
    bone, lim_node, ptnm_title, pt2, pnx, pmx, resection_margin FROM llx_micro WHERE lab_number = '$labNumber' ORDER BY row_id ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingMicroDescriptions[] = array(
                'row_id' => $row['row_id'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
                'created_user' => $row['created_user'],
                'status' => $row['status'],
                'specimen' => $row['specimen'],
                'histologic_type' => $row['histologic_type'],
                'hitologic_grade' => $row['hitologic_grade'],
                'pattern_of_growth' => $row['pattern_of_growth'],
                'stromal_reaction' => $row['stromal_reaction'],
                'depth_of_invasion' => $row['depth_of_invasion'],
                'lymphovascular_invasion' => $row['lymphovascular_invasion'],
                'perineural_invasion' => $row['perineural_invasion'],
                'bone' => $row['bone'],
                'lim_node' => $row['lim_node'],
                'ptnm_title' => $row['ptnm_title'],
                'pt2' => $row['pt2'],
                'pnx' => $row['pnx'],
                'pmx' => $row['pmx'],
                'resection_margin' => $row['resection_margin']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingMicroDescriptions;
}

function getExistingDiagnosisDescriptions($labNumber) {
    global $pg_con;

    $existingDiagnosisDescriptions = array();

    $sql = "SELECT row_id, lab_number, fk_gross_id, description, created_user, status, specimen, title, comment 
    FROM llx_diagnosis WHERE lab_number = '$labNumber' order by row_id ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingDiagnosisDescriptions[] = array(
                'row_id' => $row['row_id'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
                'title' => $row['title'],
                'comment' => $row['comment'],
                'created_user' => $row['created_user'],
                'status' => $row['status'],
                'specimen' => $row['specimen']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingDiagnosisDescriptions;
}


function get_doctor_degination_details() {
    global $pg_con;

    $sql = "select row_id, username, doctor_name, education, designation from llx_doctor_degination";
    $result = pg_query($pg_con, $sql);

    $doctors = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $doctors[] = ['doctor_name' =>$row['doctor_name'], 'username' => $row['username'], 
            'education' => $row['education'], 'designation' => $row['designation'], 'row_id' => $row['row_id']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $doctors;
}


function get_doctor_assisted_by_signature_details($labNumber) {
    global $pg_con;

    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
    dd.designation as designation, ds.row_id as row_id
    FROM llx_doctor_degination AS dd
    INNER JOIN llx_doctor_assisted_by_signature AS ds ON dd.username = ds.doctor_username
    WHERE ds.lab_number = '$labNumber'";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'row_id' => $row['row_id'],
                'username' => $row['username'],
                'education' => $row['education'],
                'designation' => $row['designation']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_doctor_finalized_by_signature_details($labNumber) {
    global $pg_con;
    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
            dd.designation as designation, ds.row_id as row_id
            FROM llx_doctor_degination AS dd
            INNER JOIN llx_doctor_finalized_by_signature AS ds ON dd.username = ds.doctor_username
            WHERE ds.lab_number = '$labNumber'";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'row_id' => $row['row_id'],
                'username' => $row['username'],
                'education' => $row['education'],
                'designation' => $row['designation']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_report_delivery_date_list() {
    global $pg_con;
    $existingdata = array();

    $sql = "SELECT 
    c.rowid,
    c.ref, 
    c.date_commande, 
    c.date_livraison,
    e.test_type
FROM 
    llx_commande c
JOIN 
    llx_commande_extrafields e ON CAST(c.rowid AS INTEGER) = e.fk_object
WHERE 
    DATE(c.date_livraison) = CURRENT_DATE";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'ref' => $row['ref'],
                'date_commande' => $row['date_commande'],
                'date_livraison' => $row['date_livraison'],
                'test_type' => $row['test_type'],
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


// Function to get lab number for doctor tracking
function get_lab_number_status_for_doctor_tracking_by_lab_number($labNumber) {
    global $pg_con;
    $existingdata = array();
    
    // Escape lab number (if not using prepared statements)
    $labNumber = pg_escape_string($pg_con, $labNumber);
    
    // SQL query using a placeholder
    $sql = "SELECT 
                t.id,
                t.create_time AS TrackCreateTime, 
                t.labno, 
                t.description,
                t.lab_room_status,
                CONCAT(u1.firstname, ' ', u1.lastname) AS TrackUserName,
                ws.name AS WSStatusName, 
                ws.section,
                e.test_type,
                s.nom AS patient_name,
                ROW_NUMBER() OVER (PARTITION BY t.id ORDER BY t.id) AS RowNumber
            FROM 
                llx_commande AS c
            INNER JOIN 
                llx_commande_extrafields e 
                ON c.rowid = e.fk_object
            INNER JOIN 
                llx_commande_trackws AS t
                ON c.ref = t.labno
                AND t.lab_room_status <> 'delete'
            INNER JOIN 
                llx_user u1 
                ON t.user_id = u1.rowid
            INNER JOIN 
                llx_commande_wsstatus AS ws
                ON t.fk_status_id = ws.id
            LEFT JOIN 
                llx_facture AS f 
                ON c.fk_soc = f.fk_soc
            LEFT JOIN 
                llx_societe s 
                ON f.fk_soc = s.rowid
            WHERE 
                c.ref = $1";  // Placeholder for prepared statement
    
    // Execute the query with pg_query_params for safe input handling
    $result = pg_query_params($pg_con, $sql, array($labNumber));

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'TrackCreateTime' => $row['trackcreatetime'],
                'labno' => $row['labno'],
                'TrackUserName' => $row['trackusername'],
                'WSStatusName' => $row['wsstatusname'],
                'section' => $row['section'],
                'test_type' => $row['test_type'],
                'patient_name' => $row['patient_name'],
                'description' => $row['description'],
                'track_id' => $row['id']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


// // Function to get lab number for bone tracking
// function get_bone_status_lab_number($labNumber) {
//     global $pg_con;
//     $existingdata = array();

//     // SQL query with parameterized input to avoid SQL injection
//     $sql = "SELECT
//                 b.rowid,
//                 b.labnumber,
//                 b.doctor_name,
//                 b.assistant_name,
//                 b.station_type,
//                 b.bones_status,
//                 b.specimen_name,
//                 ws.name AS status_name
//             FROM llx_commande c
//             LEFT JOIN llx_commande_trackws t ON c.ref = t.labno
//             LEFT JOIN llx_bone b ON c.ref = b.labnumber OR b.labnumber = 'HPL' || c.ref
//             LEFT JOIN llx_commande_wsstatus ws ON t.fk_status_id = ws.id
//             WHERE b.bones_status = 'Yes'
//             AND c.ref = $1"; // $1 is a placeholder for parameter

//     // Execute the query with the provided lab number
//     $result = pg_query_params($pg_con, $sql, array($labNumber));

//     if ($result) {
//         while ($row = pg_fetch_assoc($result)) {
//             $existingdata[] = array(
//                 'rowid' => $row['rowid'],
//                 'labnumber' => $row['labnumber'],
//                 'doctor_name' => $row['doctor_name'],
//                 'assistant_name' => $row['assistant_name'],
//                 'station_type' => $row['station_type'],
//                 'bones_status' => $row['bones_status'],
//                 'specimen_name' => $row['specimen_name'],
//                 'status_name' => $row['status_name']
//             );
//         }
//         pg_free_result($result);
//     } else {
//         echo 'Error: ' . pg_last_error($pg_con);
//     }

//     return $existingdata;
// }


// Function to get lab number track status
function get_labNumber_track_status_by_lab_number($labNumber) {
    global $pg_con;
    $existingdata = array();
    
    // Escape the lab number to prevent SQL injection
    $labNumber = pg_escape_string($pg_con, $labNumber);
    
    // SQL query
    $sql = "SELECT *
FROM (
    SELECT 
        t.id,
        t.create_time AS TrackCreateTime, 
        t.labno, 
        CONCAT(u1.firstname, ' ', u1.lastname) AS TrackUserName,
        ws.create_time AS WSStatusCreateTime, 
        ws.name AS WSStatusName, 
        ws.section,
        c.rowid, 
        c.ref, 
        c.date_creation,  
        c.date_commande,
        CONCAT(u2.firstname, ' ', u2.lastname) AS UserName, 
        CASE 
            WHEN c.fk_statut = 1 THEN 'Validated'
            WHEN c.fk_statut = 0 THEN 'Draft'
            WHEN c.fk_statut = -1 THEN 'Cancel'
            WHEN c.fk_statut = 3 THEN 'Delivered'
            ELSE 'Unknown'
        END AS Status,
        c.amount_ht, 
        c.date_livraison, 
        c.multicurrency_total_ht, 
        c.multicurrency_total_tva, 
        c.multicurrency_total_ttc,
        e.test_type,
        f.ref as invoice,
        CASE 
            WHEN f.fk_statut = 1 THEN 'Unpaid'
            WHEN f.fk_statut = 0 THEN 'Draft'
            WHEN f.fk_statut = 2 THEN 'Paid partially or completely'
            WHEN f.fk_statut = 3 THEN 'Abandoned'
            ELSE 'Unknown'
        END AS PaymentStatus,
        s.nom AS patient_name,
        ROW_NUMBER() OVER (PARTITION BY t.id ORDER BY t.id) AS RowNumber
    FROM 
        llx_commande AS c
    INNER JOIN 
        llx_commande_extrafields e 
        ON c.rowid = e.fk_object
    INNER JOIN 
        llx_commande_trackws AS t
        ON c.ref = t.labno
    INNER JOIN 
        llx_user u1 
        ON t.user_id = u1.rowid
    INNER JOIN 
        llx_user u2 
        ON c.fk_user_author = u2.rowid
    INNER JOIN 
        llx_commande_wsstatus AS ws
        ON t.fk_status_id = ws.id
    INNER JOIN 
        llx_facture AS f 
        ON c.fk_soc = f.fk_soc
    LEFT JOIN 
        llx_societe s 
        ON f.fk_soc = s.rowid
    WHERE 
        c.ref = '$labNumber'
) AS Subquery
WHERE 
    RowNumber = 1";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'TrackCreateTime' => $row['trackcreatetime'],
                'labno' => $row['labno'],
                'TrackUserName' => $row['trackusername'],
                'WSStatusCreateTime' => $row['wsstatuscreatetime'],
                'section' => $row['section'],
                'WSStatusName' => $row['wsstatusname'],
                'rowid' => $row['rowid'],
                'ref' => $row['ref'],
                'date_creation' => $row['date_creation'],
                'date_commande' => $row['date_commande'],
                'UserName' => $row['username'],
                'status' => $row['status'],
                'amount_ht' => $row['amount_ht'],
                'date_livraison' => $row['date_livraison'],
                'multicurrency_total_ht' => $row['multicurrency_total_ht'],
                'multicurrency_total_tva' => $row['multicurrency_total_tva'],
                'multicurrency_total_ttc' => $row['multicurrency_total_ttc'],
                'test_type' => $row['test_type'],
                'invoice' => $row['invoice'],
                'PaymentStatus' => $row['paymentstatus'],
                'patient_name' => $row['patient_name']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

?>