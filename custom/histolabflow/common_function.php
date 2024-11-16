<?php 
include('connection.php');

function labNumber_list($startDate = null, $endDate = null) {
    global $pg_con;

    // Check if both startDate and endDate are provided
    if ($startDate && $endDate) {
        // Query when both start and end date are provided
        $sql = "SELECT ref FROM llx_commande WHERE date_commande BETWEEN $1 AND $2";
        $params = [$startDate, $endDate];
    } else {
        // Query when no date range is provided, limited to 100 records
        $sql = "SELECT ref FROM llx_commande LIMIT 100";
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


function get_order_status_data($labNumbers) {
    global $pg_con;

    $escapedLabNumbers = array_map(function($labNumber) use ($pg_con) {
        return "'" . pg_escape_string($pg_con, $labNumber) . "'";
    }, $labNumbers);

    $labNumbersList = implode(",", $escapedLabNumbers);

    $sql = "
    SELECT 
        c.ref, 
        c.date_creation,  
        c.date_commande,
        u.login,
        c.fk_statut,
        c.amount_ht, 
        c.date_livraison, 
        c.multicurrency_total_ht, 
        c.multicurrency_total_tva, 
        c.multicurrency_total_ttc
    FROM 
        llx_commande AS c
    INNER JOIN 
        llx_user AS u
        ON c.fk_user_author = u.rowid
    WHERE 
        c.ref IN ($labNumbersList)";

    $result = pg_query($pg_con, $sql);
    $orderStatusData = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $orderStatusData[] = array(
                'ref' => $row['ref'],
                'date_creation' => $row['date_creation'],
                'date_commande' => $row['date_commande'],
                'UserName' => $row['login'],
                'status' => $row['fk_statut'],
                'amount_ht' => $row['amount_ht'],
                'date_livraison' => $row['date_livraison'],
                'multicurrency_total_ht' => $row['multicurrency_total_ht'],
                'multicurrency_total_tva' => $row['multicurrency_total_tva'],
                'multicurrency_total_ttc' => $row['multicurrency_total_ttc']
            );
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
        c.ref IN ($labNumbersList)";

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


?>