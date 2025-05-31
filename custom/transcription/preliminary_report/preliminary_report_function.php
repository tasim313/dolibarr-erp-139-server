<?php

include('connection.php');

function get_preliminary_report_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            c.ref AS lab_number, 
            c.rowid AS rowid,
            e.test_type 
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
       
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            date_commande BETWEEN '2025-01-01' AND CURRENT_DATE 
            AND e.test_type IN ('HPL', 'IHC')";
    $result = pg_query($pg_con, $sql);

    $labnumbers = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = [
                'patient_code' => $row['patient_code'],
                'lab_number' => $row['lab_number'],
                'fk_commande'=>$row['rowid'],
                'test_type' => $row['test_type'] 
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}


function getExistingPreliminaryReportMicroDescriptions($labNumber) {
    global $pg_con;

    $existingPreliminaryReportMicroDescriptions = array();

    $sql = "SELECT row_id, lab_number, fk_gross_id, description, created_user, status, 
    specimen FROM llx_preliminary_report_microscopic WHERE lab_number = '$labNumber' ORDER BY row_id ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingPreliminaryReportMicroDescriptions[] = array(
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

    return $existingPreliminaryReportMicroDescriptions;
}

function getExistingPreliminaryReportDiagnosisDescriptions($labNumber) {
    global $pg_con;

    $existingPreliminaryReportDiagnosisDescriptions = array();

    $sql = "SELECT row_id, lab_number, fk_gross_id, description, created_user, status, specimen, title, comment 
    FROM llx_preliminary_report_diagnosis WHERE lab_number = '$labNumber' order by row_id ASC";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingPreliminaryReportDiagnosisDescriptions[] = array(
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

    return $existingPreliminaryReportDiagnosisDescriptions;
}

function get_preliminary_report_doctor_assisted_by_signature_details($labNumber) {
    global $pg_con;

    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
    dd.designation as designation, ds.row_id as row_id
    FROM llx_doctor_degination AS dd
    INNER JOIN llx_preliminary_report_doctor_assisted_by_signature AS ds ON dd.username = ds.doctor_username
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

function get_preliminary_report_doctor_finalized_by_signature_details($labNumber) {
    global $pg_con;
    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
            dd.designation as designation, ds.row_id as row_id
            FROM llx_doctor_degination AS dd
            INNER JOIN llx_preliminary_report_doctor_finalized_by_signature AS ds ON dd.username = ds.doctor_username
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

function get_preliminary_report_collect_date($labNumber) {
    global $pg_con;
    $existingdata = array();

    $sql = "select description from llx_commande_trackws where labno = '$labNumber' and fk_status_id = '69' order by id DESC";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'description' => $row['description']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

function get_preliminary_report_comment($labNumber) {
    global $pg_con;
    $existingdata = array();

    $sql = "SELECT 
        c.rowid AS comment_id,
        c.datec,
        c.description,
        c.fk_user_author,
        c.fk_element,
        c.element_type,
        e.fk_source,
        e.sourcetype,
        e.fk_target,
        e.targettype,
        cmd.ref AS lab_number
    FROM llx_custom_comment c
    JOIN llx_custom_element_element e ON c.fk_element = e.rowid
    JOIN llx_commande cmd ON cmd.rowid = e.fk_source
    WHERE cmd.ref = '$labNumber' AND c.element_type = 'Preliminary Report'
    ORDER BY c.rowid DESC limit 1";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $existingdata[] = array(
                'description' => $row['description']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

?>