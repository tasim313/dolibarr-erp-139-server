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

    // Main query
    $sql = "
    SELECT 
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
        e.test_type
    FROM 
        llx_commande AS c
    INNER JOIN 
        llx_user AS u ON c.fk_user_author = u.rowid
    INNER JOIN 
        llx_commande_extrafields AS e ON e.fk_object = c.rowid
    WHERE 
        c.ref IN ($labNumbersList)";

    $result = pg_query($pg_con, $sql);
    $orderStatusData = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Secondary query for payment and invoice details
            $invoiceSql = "
            SELECT
                f.ref AS invoice_ref,
                f.total_ttc AS total_amount,
                COALESCE(f.total_ttc - SUM(p.amount), f.total_ttc) AS remaining_amount_due,
                COALESCE(SUM(p.amount), 0) AS already_paid,
                fd.description AS line_description,
                fd.remise_percent AS line_discount_percentage,
                (fd.total_ht * fd.remise_percent / 100) AS line_discount_value,
                t.code AS payment_term_code,
                pm.code AS payment_mode_code,
                ba.bank AS bank_name,
                ba.bic AS bank_bic,
                CONCAT(ba.iban_prefix, ba.country_iban, ba.cle_iban) AS bank_iban
            FROM
                llx_facture f
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
            INNER JOIN
                llx_element_element ee ON ee.fk_target = f.rowid AND ee.targettype = 'facture'
            INNER JOIN
                llx_commande c ON ee.fk_source = c.rowid AND ee.sourcetype = 'commande'
            WHERE
                c.ref = '" . pg_escape_string($pg_con, $row['ref']) . "'
            GROUP BY
                f.ref, f.total_ttc, f.remise_absolue, f.remise_percent, t.code, pm.code, ba.bank, ba.bic, ba.iban_prefix, ba.country_iban, ba.cle_iban, fd.rowid, fd.description, fd.total_ht, fd.remise_percent
            ORDER BY
                f.ref";

            $invoiceResult = pg_query($pg_con, $invoiceSql);
            $invoiceData = [];

            if ($invoiceResult) {
                $invoiceData = pg_fetch_all($invoiceResult) ?: [];
                pg_free_result($invoiceResult);
            }

            $orderStatusData[] = array_merge($row, ['invoiceDetails' => $invoiceData]);
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

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
        ws.create_time,
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
        c.ref IN ($labNumbersList)  AND ws.section != 'Gross'";

    $result = pg_query($pg_con, $sql);
    $trackingData = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $trackingData[] = array(
                'TrackCreateTime' => $row['create_time'],
                'labno' => $row['labno'],
                'TrackUserName' => $row['login'],
                'WSStatusCreateTime' => $row['create_time'],
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