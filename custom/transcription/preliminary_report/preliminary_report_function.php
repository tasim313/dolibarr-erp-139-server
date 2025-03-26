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

?>