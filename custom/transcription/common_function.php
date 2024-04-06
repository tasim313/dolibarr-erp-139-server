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
    AND lab_number = '$lab_number'";

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

    $sql = "SELECT row_id, lab_number, fk_gross_id, description, created_user, status, specimen FROM llx_micro WHERE lab_number = '$labNumber'";
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
                'specimen' => $row['specimen']
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

    $sql = "SELECT row_id, lab_number, fk_gross_id, description, created_user, status, specimen FROM llx_diagnosis WHERE lab_number = '$labNumber'";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingDiagnosisDescriptions[] = array(
                'row_id' => $row['row_id'],
                'lab_number' => $row['lab_number'],
                'fk_gross_id' => $row['fk_gross_id'],
                'description' => $row['description'],
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

?>