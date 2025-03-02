<?php 
include('connection.php');

function diagonsis_micro_complete_by_lab($lab_number) {
    global $pg_con;

    // SQL query to check if both micro and diagnosis statuses are 'done'
    $sql = "
        SELECT 
            COALESCE(CASE 
                        WHEN LOWER(TRIM(m.status)) = 'done' AND LOWER(TRIM(d.status)) = 'done' THEN 'OK'
                        ELSE 'Not OK'
                    END, 'Not OK') AS status_check
        FROM 
            llx_micro m
        LEFT JOIN 
            llx_diagnosis d ON m.lab_number = d.lab_number
        WHERE 
            m.lab_number = $1";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_status_check", $sql);
    $result = pg_execute($pg_con, "get_status_check", array($lab_number));

    if ($result) {
        $row = pg_fetch_assoc($result);
        pg_free_result($result);

        return $row['status_check']; // Return 'OK' or 'Not OK'
    } else {
        return 'Error';
    }
}


function get_cyto_labnumber_list_doctor_module() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            c.ref AS lab_number, 
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
            date_commande BETWEEN '2023-01-12' AND CURRENT_DATE 
            AND e.test_type = 'FNA'
            AND (cy.status = 'done')";
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


function get_mfc_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            c.ref AS lab_number,  
            c.rowid AS rowid
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
       
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            date_commande BETWEEN '2023-01-01' AND CURRENT_DATE 
            AND e.test_type = 'MFC'";
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


function get_hpl_labnumber_list() {
    global $pg_con;

    $sql = "SELECT 
            soc.code_client AS patient_code, 
            c.ref AS lab_number, 
            c.rowid AS rowid
        FROM 
            llx_commande AS c
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid 
       
        JOIN 
            llx_societe AS soc ON c.fk_soc = soc.rowid
        WHERE 
            date_commande BETWEEN '2023-01-01' AND CURRENT_DATE 
            AND e.test_type = 'HPL'";
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

function get_duplicate_report_doctor_assisted_by_signature_details($labNumber) {
    global $pg_con;

    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
    dd.designation as designation, ds.rowid as row_id
    FROM llx_doctor_degination AS dd
    INNER JOIN llx_duplicate_report_doctor_assisted AS ds ON dd.username = ds.doctor_username
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


function get_duplicate_report_doctor_finalized_by_signature_details($labNumber) {
    global $pg_con;
    $existingdata = array();

    $sql = "SELECT dd.username as username, dd.doctor_name as doctor_name, dd.education as education, 
            dd.designation as designation, ds.rowid as row_id
            FROM llx_doctor_degination AS dd
            INNER JOIN llx_duplicate_report_doctor_finalized_by_signature AS ds ON dd.username = ds.doctor_username
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

?>