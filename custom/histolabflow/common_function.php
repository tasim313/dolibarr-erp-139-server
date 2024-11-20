<?php 
include('connection.php');


function labNumber_list($startDate = null, $endDate = null) {
    global $pg_con;

    // Check if both startDate and endDate are provided
    if ($startDate && $endDate) {
        // Query when both start and end date are provided
        $sql = "
            SELECT c.ref 
            FROM llx_commande AS c
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid AND e.test_type = 'HPL'
            WHERE c.date_commande BETWEEN $1 AND $2
        ";
        $params = [$startDate, $endDate];
    } else {
        // Query when no date range is provided, limited to 100 records
        $sql = "
            SELECT c.ref 
            FROM llx_commande AS c
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid AND e.test_type = 'HPL'
            LIMIT 100;
        ";
        $params = [];
    }

    // Prepare the SQL query
    $result = pg_prepare($pg_con, "get_commande_ref", $sql);

    if ($result) {
        // Execute the prepared statement with or without parameters based on date range
        $result = pg_execute($pg_con, "get_commande_ref", $params);

        $existingdata = [];

        if ($result) {
            $existingdata = pg_fetch_all($result) ?: [];
            pg_free_result($result);
        } else {
            echo 'Error: ' . pg_last_error($pg_con);
        }

        return $existingdata;
    } else {
        // Handle the case where the prepared statement failed
        echo 'Error: ' . pg_last_error($pg_con);
        return [];
    }
}


// function get_order_status_data($labNumbers) {
//     global $pg_con;

//     $escapedLabNumbers = array_map(function($labNumber) use ($pg_con) {
//         return "'" . pg_escape_string($pg_con, $labNumber) . "'";
//     }, $labNumbers);

//     $labNumbersList = implode(",", $escapedLabNumbers);

//     $sql = "
//     SELECT 
//         c.ref, 
//         c.date_creation,  
//         c.date_commande,
//         u.login,
//         c.fk_statut,
//         c.amount_ht, 
//         c.date_livraison, 
//         c.multicurrency_total_ht, 
//         c.multicurrency_total_tva, 
//         c.multicurrency_total_ttc,
//         e.test_type
//     FROM 
//         llx_commande AS c
//     INNER JOIN 
//         llx_user AS u
//         ON c.fk_user_author = u.rowid
//     JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
//     WHERE 
//         c.ref IN ($labNumbersList)";

//     $result = pg_query($pg_con, $sql);
//     $orderStatusData = [];

//     if ($result) {
//         while ($row = pg_fetch_assoc($result)) {
//             $orderStatusData[] = array(
//                 'ref' => $row['ref'],
//                 'date_creation' => $row['date_creation'],
//                 'date_commande' => $row['date_commande'],
//                 'UserName' => $row['login'],
//                 'status' => $row['fk_statut'],
//                 'amount_ht' => $row['amount_ht'],
//                 'date_livraison' => $row['date_livraison'],
//                 'multicurrency_total_ht' => $row['multicurrency_total_ht'],
//                 'multicurrency_total_tva' => $row['multicurrency_total_tva'],
//                 'multicurrency_total_ttc' => $row['multicurrency_total_ttc'],
//                 'testType' => $row['test_type']
//             );
//         }
//         pg_free_result($result);
//     } else {
//         echo 'Error: ' . pg_last_error($pg_con);
//     }

//     return $orderStatusData;
// }

function get_order_status_data($labNumbers) {
    global $pg_con;

    $escapedLabNumbers = array_map(function($labNumber) use ($pg_con) {
        return "'" . pg_escape_string($pg_con, $labNumber) . "'";
    }, $labNumbers);

    $labNumbersList = implode(",", $escapedLabNumbers);

    $sql = "
    SELECT 
        -- Order details
        c.ref, 
        c.date_creation,  
        c.date_commande,
        u.login AS UserName,
        c.fk_statut AS status,
        c.amount_ht,
        c.date_livraison, 
        c.multicurrency_total_ht, 
        c.multicurrency_total_tva, 
        c.multicurrency_total_ttc,
        e.test_type,

        -- Invoice details
        f.ref AS invoice_ref,
        f.total_ttc AS total_amount,
        COALESCE(f.total_ttc - SUM(p.amount), f.total_ttc) AS remaining_amount_due,
        COALESCE(SUM(p.amount), 0) AS already_paid,
        STRING_AGG(fd.description, ', ') AS line_descriptions,
        STRING_AGG(fd.remise_percent::TEXT, ', ') AS line_discount_percentages,
        SUM((fd.total_ht * fd.remise_percent / 100)) AS total_line_discount_value,
        t.code AS payment_term_code,
        pm.code AS payment_mode_code,
        ba.bank AS bank_name,
        ba.bic AS bank_bic,
        CONCAT(ba.iban_prefix, ba.country_iban, ba.cle_iban) AS bank_iban,

        -- Company details
        s.nom AS nom,
        s.code_client AS code_client,
        s.address AS address,
        s.phone AS phone,
        s.fax AS fax,
        se.att_name AS attendant_name,
        se.att_relation AS attendant_relation,
        se.ageyrs As age,
	    se.sex as sex,
	    se.date_of_birth as date_of_birth
    FROM 
        llx_commande AS c
    INNER JOIN 
        llx_user AS u ON c.fk_user_author = u.rowid
    INNER JOIN 
        llx_commande_extrafields AS e ON e.fk_object = c.rowid
    LEFT JOIN 
        llx_element_element ee ON ee.fk_source = c.rowid AND ee.sourcetype = 'commande'
    LEFT JOIN 
        llx_facture f ON ee.fk_target = f.rowid AND ee.targettype = 'facture'
    LEFT JOIN 
        llx_facturedet fd ON fd.fk_facture = f.rowid
    LEFT JOIN 
        llx_paiement_facture pf ON f.rowid = pf.fk_facture
    LEFT JOIN 
        llx_paiement p ON pf.fk_paiement = p.rowid
    LEFT JOIN 
        llx_c_payment_term t ON f.fk_cond_reglement = t.rowid
    LEFT JOIN 
        llx_c_paiement pm ON f.fk_mode_reglement = pm.id
    LEFT JOIN 
        llx_bank_account ba ON p.fk_bank = ba.rowid
    JOIN 
        llx_societe AS s ON c.fk_soc = s.rowid
    LEFT JOIN 
        llx_societe_extrafields AS se ON s.rowid = se.fk_object
    WHERE 
        c.ref IN ($labNumbersList)
    GROUP BY 
        c.ref, c.date_creation, c.date_commande, u.login, c.fk_statut, c.amount_ht, 
        c.date_livraison, c.multicurrency_total_ht, c.multicurrency_total_tva, 
        c.multicurrency_total_ttc, e.test_type, 
        f.ref, f.total_ttc, t.code, pm.code, ba.bank, ba.bic, ba.iban_prefix, 
        ba.country_iban, ba.cle_iban, 
        s.nom, s.code_client, s.address, s.phone, s.fax, 
        se.att_name, se.att_relation, se.ageyrs, se.sex, se.date_of_birth
    ORDER BY 
        c.ref, f.ref";

    $result = pg_query($pg_con, $sql);
    if (!$result) {
        echo 'Error: ' . pg_last_error($pg_con);
        return [];
    }

    $orderStatusData = pg_fetch_all($result) ?: [];
    pg_free_result($result);

    return $orderStatusData;
}


function get_tracking_data($labNumbers) {
    global $pg_con;

    $escapedLabNumbers = array_map(function($labNumber) use ($pg_con) {
        return "'" . pg_escape_string($pg_con, $labNumber) . "'";
    }, $labNumbers);

    $labNumbersList = implode(",", $escapedLabNumbers);

    $sql = "
    SELECT 
        t.create_time,
        t.labno,
        u.login,
        ws.name,
        ws.section,
        c.rowid,
        c.ref
    FROM 
        llx_commande AS c
    INNER JOIN 
        llx_commande_trackws AS t
        ON c.ref = t.labno
    INNER JOIN 
        llx_user u 
        ON t.user_id = u.rowid
    INNER JOIN 
        llx_commande_wsstatus AS ws
        ON t.fk_status_id = ws.id
    WHERE 
        c.ref IN ($labNumbersList)  AND ws.section != 'Gross' AND ws.name NOT IN ('Gross Entry Done', 'Gross Completed', 
        'Regross Completed', 'Recut or Special Stain Completed', 'Waiting - Patient History / Investigation', 'Waiting - Study',
        'Re-gross Requested', 'Recut or Special Stain Requested', 'Diagnosis Completed', 'Regross Slides Prepared', 'R/C requested', 'R/C Completed',
        'M/R/C requested', 'M/R/C Completed', 'Deeper Cut requested', 'Deeper Cut Completed', 'Serial Sections requested', 'Serial Sections Completed',
        'Block D/C & R/C requested', 'Block D/C & R/C Completed', 'Special Stain AFB requested', 'Special Stain AFB Completed', 'Special Stain GMS requested',
        'Special Stain GMS Completed', 'Special Stain PAS requested', 'Special Stain PAS Completed', 'Special Stain PAS with Diastase requested',
        'Special Stain PAS with Diastase Completed', 'Special Stain Fite Faraco requested', 'Special Stain Fite Faraco Completed', 'Special Stain Brown-Brenn requested',
        'Special Stain Brown-Brenn Completed', 'Special Stain Congo-Red requested', 'Special Stain Congo-Red Completed', 'Special Stain others requested', 
        'Special Stain others Completed', 'Special Stain Bone Decalcification requested', 'Special Stain Bone Decalcification Completed', 'IHC-Block-Markers-requested',
        'IHC-Block-Markers-completed', 'Final Screening Start', 'Bones Slide Ready', 'R/C Completed')";

    $result = pg_query($pg_con, $sql);
    $trackingData = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $trackingData[] = array(
                'create_time' => $row['create_time'],
                'labno' => $row['labno'],
                'TrackUserName' => $row['login'],
                'section' => $row['section'],
                'WSStatusName' => $row['name'],
                'rowid' => $row['rowid'],
                'ref' => $row['ref']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $trackingData;
}

function getGrossDetailsByLabNumbers($labNumbers) {
    global $pg_con;

    // Prefix "HPL" to lab numbers unless they start with "(PROV"
    $transformedLabNumbers = array_map(function($labNumber) {
        if (strpos($labNumber, '(PROV') === 0) {
            return $labNumber; // Keep "(PROVxxx)" as is
        }
        return 'HPL' . $labNumber; // Prefix with "HPL"
    }, $labNumbers);

    // Escape each lab number
    $escapedLabNumbers = array_map(function($labNumber) use ($pg_con) {
        return "'" . pg_escape_string($pg_con, $labNumber) . "'";
    }, $transformedLabNumbers);

    // Join the lab numbers into a single string for SQL
    $labNumbersList = implode(",", $escapedLabNumbers);

    // Main SQL query
    $sql = "
    SELECT 
        g.lab_number AS gross_lab_number,
        g.gross_station_type,
        g.gross_assistant_name,
        g.gross_doctor_name,
        g.gross_create_date,
        u.login AS gross_created_by,
        g.batch,
        m.created_user AS micro_created_user,
        m.create_date AS micro_created_date
    FROM 
        llx_gross g
    LEFT JOIN 
        llx_user u ON g.gross_created_user = u.rowid
    LEFT JOIN 
        (
            SELECT lab_number, MAX(row_id) AS max_row_id
            FROM llx_micro
            WHERE lab_number IN ($labNumbersList)
            GROUP BY lab_number
        ) m_sub ON g.lab_number = m_sub.lab_number
    LEFT JOIN 
        llx_micro m ON m.row_id = m_sub.max_row_id
    WHERE 
        g.lab_number IN ($labNumbersList)";

    // Execute the query
    $result = pg_query($pg_con, $sql);
    $trackingData = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $trackingData[] = array(
                'gross_lab_number' => $row['gross_lab_number'],
                'gross_station_type' => $row['gross_station_type'],
                'gross_assistant_name' => $row['gross_assistant_name'],
                'gross_doctor_name' => $row['gross_doctor_name'],
                'gross_create_date' => $row['gross_create_date'],
                'gross_created_by' => $row['gross_created_by'],
                'batch' => $row['batch'],
                'micro_created_user' => $row['micro_created_user'],
                'micro_created_date' => $row['micro_created_date']
            );
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $trackingData;
}

?>