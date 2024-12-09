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
            llx_cyto AS cy ON TRIM(LEADING 'FNA' FROM cy.lab_number) = c.ref
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


function get_cyto_patient_history_list($labnumber) {
    global $pg_con;

    // Updated query with subqueries for referred_by_dr_lastname and referred_from_lastname
    $sql = "
        SELECT 
            e.rowid, 
            e.test_type, 
            e.prev_fnac, 
            e.prev_biopsy_date, 
            e.prev_biopsy_op, 
            e.informed, 
            e.given, 
            e.add_history, 
            e.other_labno, 
            e.referred_by_dr_text, 
            e.referredfrom_text, 
            -- Subquery for e.referred_by_dr
            (SELECT lastname 
            FROM llx_socpeople sp1
            WHERE sp1.rowid IN (
                SELECT fk_socpeople 
                FROM llx_categorie_contact 
                WHERE fk_categorie = 3
            ) 
            AND sp1.rowid = e.referred_by_dr::integer
            ) AS referred_by_dr_lastname,
            -- Subquery for e.referred_from
            (SELECT lastname 
            FROM llx_socpeople sp2
            WHERE sp2.rowid IN (
                SELECT fk_socpeople 
                FROM llx_categorie_contact 
                WHERE fk_categorie = 4
            ) 
            AND sp2.rowid = e.referred_from::integer
            ) AS referred_from_lastname
            FROM 
                llx_commande_extrafields e
            JOIN 
                llx_commande c
            ON 
                c.rowid = e.fk_object
            WHERE 
                c.ref = $1
    ";

    // Execute the query with parameterized values
    $result = pg_query_params($pg_con, $sql, [$labnumber]);

    $labnumbers = [];

    if ($result) {
        // Fetch results into the labnumbers array
        while ($row = pg_fetch_assoc($result)) {
            $labnumbers[] = [
                'rowid' => $row['rowid'],
                'test_type' => $row['test_type'],
                'prev_fnac' => $row['prev_fnac'],
                'given' => $row['given'],
                'prev_biopsy_op' => $row['prev_biopsy_op'],
                'informed' => $row['informed'],
                'add_history' => $row['add_history'],
                'other_labno' => $row['other_labno'],
                'referred_by_dr_text' => $row['referred_by_dr_text'],
                'referredfrom_text' => $row['referredfrom_text'],
                'referred_by_dr_lastname' => $row['referred_by_dr_lastname'], // Newly added field
                'referred_from_lastname' => $row['referred_from_lastname']  // Newly added field
            ];
        }

        pg_free_result($result);
    } else {
        // Log or handle the error
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $labnumbers;
}

?>