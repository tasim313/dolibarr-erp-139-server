<?php 
include('connection.php');

function get_cyto_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            CONCAT(e.test_type, '', c.ref) AS lab_number, 
            c.rowid AS rowid
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
        LEFT JOIN 
            llx_cyto AS cy ON cy.lab_number = c.ref
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            fk_statut = 1 
            AND date_commande BETWEEN '2024-05-03' AND CURRENT_DATE 
            AND e.test_type = 'FNA'
            AND (cy.status <> 'done' OR cy.status IS NULL)";
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


function get_cyto_recall_list() {
    global $pg_con;

    $sql = "select rowid, lab_number, patient_code, 
            recall_reason, created_date, recalled_doctor, notified_user,
            notified_method, follow_up_date, status, updated_date from 
            llx_cyto_recall_management where status <> 'complete' OR  status <> 'Complete'";
    $result = pg_query($pg_con, $sql);

    $labnumbers = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = ['rowid' => $row['rowid'], 'lab_number' => $row['lab_number'],'patient_code' => $row['patient_code'],
            'recall_reason'=>$row['recall_reason'], 'created_date'=>$row['created_date'], 'recalled_doctor' =>$row['recalled_doctor'],
            'notified_user'=>$row['notified_user'], 'notified_method'=>$row['notified_method'], 'follow_up_date'=>$row['follow_up_date'],
            'status'=>$row['status'], 'updated_date'=>$row['updated_date']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}

?>