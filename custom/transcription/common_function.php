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





?>