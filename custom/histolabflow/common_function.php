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
            LIMIT 100
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
        c.ref IN ($labNumbersList) AND ws.section != 'Gross' AND ws.name NOT IN ('Gross Entry Done', 'Gross Completed', 
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


function sample_received_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Start output buffering to catch fatal errors
    ob_start();
    try {
        // Define the base SQL query with required joins
        $baseSQL = "
            SELECT DISTINCT ON (c.ref) 
                c.*, 
                ce.*, 
                array_agg(de.description) AS specimens -- Aggregate specimens into an array
            FROM 
                llx_commande AS c
            LEFT JOIN 
                llx_commande_extrafields AS ce ON ce.fk_object = c.rowid
            LEFT JOIN 
                llx_commandedet AS de ON de.fk_commande = c.rowid
        ";

        // Adjust the WHERE clause based on provided parameters or date options
        if ($startDate && $endDate) {
            // Query when both start and end dates are provided
            $sql = $baseSQL . "
                WHERE c.date_commande BETWEEN $1 AND $2
                GROUP BY c.rowid, ce.rowid -- Group by necessary fields
                ORDER BY c.ref, c.date_commande DESC
            ";
            $params = [$startDate, $endDate];
        } else {
            // Handle specific date options (today, yesterday, or both)
            switch ($dateOption) {
                case 'yesterday':
                    $sql = $baseSQL . "
                        WHERE c.date_commande = CURRENT_DATE - INTERVAL '1 day'
                        GROUP BY c.rowid, ce.rowid
                        ORDER BY c.ref, c.date_commande DESC
                    ";
                    $params = [];
                    break;

                case 'both':
                    $sql = $baseSQL . "
                        WHERE c.date_commande IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                        GROUP BY c.rowid, ce.rowid
                        ORDER BY c.ref, c.date_commande DESC
                    ";
                    $params = [];
                    break;

                case 'today':
                default:
                    $sql = $baseSQL . "
                        WHERE c.date_commande = CURRENT_DATE
                        GROUP BY c.rowid, ce.rowid
                        ORDER BY c.ref, c.date_commande DESC
                    ";
                    $params = [];
                    break;
            }
        }

        // Prepare the SQL query
        $preparedResult = pg_prepare($pg_con, "get_commande_ref", $sql);

        if (!$preparedResult) {
            throw new Exception('Error preparing SQL: ' . pg_last_error($pg_con));
        }

        // Execute the prepared query
        $executedResult = pg_execute($pg_con, "get_commande_ref", $params);

        if (!$executedResult) {
            throw new Exception('Error executing SQL: ' . pg_last_error($pg_con));
        }

        // Fetch and return the results, or an empty array if no results
        $existingData = pg_fetch_all($executedResult) ?: [];
        pg_free_result($executedResult);

        return $existingData;

    } catch (Throwable $e) {
        // Handle errors and log them for debugging
        error_log($e->getMessage());
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.',
        ];
    } finally {
        // Clear the buffer if there was a fatal error
        ob_end_clean();
    }
}

function get_user_groups(){
    global $pg_con; // Use the global PostgreSQL connection

    // Define the SQL query to fetch user groups
    $sql = "
        SELECT *
        FROM llx_usergroup
        ORDER BY rowid ASC
    ";

    // Prepare the SQL query
    $preparedResult = pg_prepare($pg_con, "get_user_groups", $sql);

    if (!$preparedResult) {
        // Handle preparation failure
        echo 'Error during query preparation: ' . pg_last_error($pg_con);
        return [];
    }

    // Execute the prepared query
    $executedResult = pg_execute($pg_con, "get_user_groups", []);

    if (!$executedResult) {
        // Handle execution failure
        echo 'Error during query execution: ' . pg_last_error($pg_con);
        return [];
    }

    // Fetch and return the results, or an empty array if no results
    $userGroups = pg_fetch_all($executedResult) ?: [];
    pg_free_result($executedResult);

    return $userGroups;
}


function get_users_by_group($groupName) {
    global $pg_con; // Use the global PostgreSQL connection

    // Define the SQL query to fetch users in the specified group
    $sql = "
        SELECT u.rowid, u.login, u.firstname, u.lastname, u.email
        FROM llx_usergroup_user ugu
        JOIN llx_user u ON ugu.fk_user = u.rowid
        WHERE ugu.fk_usergroup = (
            SELECT rowid FROM llx_usergroup WHERE nom = $1
        )
    ";

    // Prepare the SQL query
    $preparedResult = pg_prepare($pg_con, "get_users_by_group", $sql);

    if (!$preparedResult) {
        // Handle preparation failure
        echo 'Error during query preparation: ' . pg_last_error($pg_con);
        return [];
    }

    // Execute the prepared query with the dynamic group name
    $executedResult = pg_execute($pg_con, "get_users_by_group", [$groupName]);

    if (!$executedResult) {
        // Handle execution failure
        echo 'Error during query execution: ' . pg_last_error($pg_con);
        return [];
    }

    // Fetch and return the results, or an empty array if no results
    $users = pg_fetch_all($executedResult) ?: [];
    pg_free_result($executedResult);

    return $users;
}


function reception_sample_received_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Start output buffering to catch fatal errors
    ob_start();

    // SQL Base Query
    $baseSQL = "
        SELECT 
            c.rowid, 
            c.ref,
            c.date_creation,
            c.fk_statut,
            c.note_public,
            c.date_livraison,
            ce.test_type,
            u.login AS author_login
        FROM 
            llx_commande AS c
        LEFT JOIN 
            llx_user AS u ON c.fk_user_author = u.rowid
        LEFT JOIN 
            llx_commande_extrafields AS ce ON ce.fk_object = c.rowid
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            WHERE c.date_commande BETWEEN $1 AND $2
            ORDER BY c.ref ASC
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE c.date_commande = CURRENT_DATE - INTERVAL '1 day'
                    ORDER BY c.ref ASC
                ";
                $params = [];
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE c.date_commande IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                    ORDER BY c.ref ASC
                ";
                $params = [];
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE c.date_commande = CURRENT_DATE
                    ORDER BY c.ref ASC
                ";
                $params = [];
                break;
        }
    }

    // Log SQL Query and Parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for errors in query execution
    if (!$result) {
        // Log any error from PostgreSQL
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;

    // Clear the output buffer in case of errors
    ob_end_clean();
}

function gross_complete_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Start output buffering to catch fatal errors
    ob_start();

    // SQL Base Query
    $baseSQL = "
        SELECT 
            g.gross_id,
            g.lab_number,
            g.gross_station_type,
            g.gross_assistant_name,
            g.gross_doctor_name,
            g.gross_create_date,
            g.batch
        FROM 
            llx_gross AS g
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            WHERE DATE(g.gross_create_date) BETWEEN $1 AND $2
            ORDER BY g.gross_id ASC
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(g.gross_create_date) = CURRENT_DATE - INTERVAL '1 day'
                    ORDER BY g.gross_id ASC
                ";
                $params = [];
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(g.gross_create_date) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                    ORDER BY g.gross_id ASC
                ";
                $params = [];
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(g.gross_create_date) = CURRENT_DATE
                    ORDER BY g.gross_id ASC
                ";
                $params = [];
                break;
        }
    }

    // Log SQL Query and Parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for errors in query execution
    if (!$result) {
        // Log any error from PostgreSQL
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;

    // Clear the output buffer in case of errors
    ob_end_clean();
}


function worksheet_tracking_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Start output buffering to catch fatal errors
    ob_start();

    // SQL Base Query
    $baseSQL = "
        SELECT 
            t.create_time,
            t.labno,
            t.user_id,
            t.fk_status_id,
            t.description,
            t.lab_room_status,
            ws.name AS status_name,
            u.login AS user_login
        FROM 
            llx_commande_trackws t
        LEFT JOIN 
            llx_commande_wsstatus ws ON ws.id = t.fk_status_id
        LEFT JOIN 
            llx_user u ON u.rowid = t.user_id
        WHERE
            ws.id NOT IN (1, 2)  -- Exclude statuses with ID 1 and 2
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            AND DATE(t.create_time) BETWEEN $1 AND $2
            ORDER BY t.create_time ASC
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    AND DATE(t.create_time) = CURRENT_DATE - INTERVAL '1 day'
                    ORDER BY t.create_time ASC
                ";
                $params = [];
                break;

            case 'both':
                $sql = $baseSQL . "
                    AND DATE(t.create_time) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                    ORDER BY t.create_time ASC
                ";
                $params = [];
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    AND DATE(t.create_time) = CURRENT_DATE
                    ORDER BY t.create_time ASC
                ";
                $params = [];
                break;
        }
    }

    // Log SQL Query and Parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for errors in query execution
    if (!$result) {
        // Log any error from PostgreSQL
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;

    // Clear the output buffer in case of errors
    ob_end_clean();
}

function transcription_complete_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Input validation for dates
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        return ['error' => true, 'message' => 'Invalid start date format.'];
    }
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        return ['error' => true, 'message' => 'Invalid end date format.'];
    }

    // SQL Base Query
    $baseSQL = "
        SELECT 
            lab_number, 
            created_user, 
            create_date 
        FROM 
            llx_micro
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        $sql = $baseSQL . "
            WHERE DATE(create_date) BETWEEN $1 AND $2
            ORDER BY row_id ASC
        ";
        $params = [$startDate, $endDate];
    } else {
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(create_date) = DATE(CURRENT_DATE - INTERVAL '1 day')
                    ORDER BY row_id ASC
                ";
                break;
            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(create_date) IN (CURRENT_DATE, DATE(CURRENT_DATE - INTERVAL '1 day'))
                    ORDER BY row_id ASC
                ";
                break;
            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(create_date) = CURRENT_DATE
                    ORDER BY row_id ASC
                ";
                break;
        }
    }

    // Log SQL Query and Parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for errors in query execution
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data
    return $existingData;
}

function invoice_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Input validation for dates
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        return ['error' => true, 'message' => 'Invalid start date format.'];
    }
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        return ['error' => true, 'message' => 'Invalid end date format.'];
    }

    // SQL Base Query
    $baseSQL = "
        SELECT 
            p.rowid AS payment_rowid, 
            p.ref AS payment_ref, 
            p.amount AS payment_amount, 
            p.datec AS payment_date, 
            f.rowid AS invoice_rowid, 
            f.ref AS invoice_ref -- Invoice reference
        FROM llx_paiement AS p
        LEFT JOIN llx_paiement_facture AS pf ON p.rowid = pf.fk_paiement
        LEFT JOIN llx_facture AS f ON pf.fk_facture = f.rowid
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        $sql = $baseSQL . "
            WHERE DATE(p.datec) BETWEEN $1 AND $2
            ORDER BY p.rowid DESC
        ";
        $params = [$startDate, $endDate];
    } else {
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(p.datec) = DATE(CURRENT_DATE - INTERVAL '1 day')
                    ORDER BY p.rowid DESC
                ";
                break;
            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(p.datec) IN (CURRENT_DATE, DATE(CURRENT_DATE - INTERVAL '1 day'))
                    ORDER BY p.rowid DESC
                ";
                break;
            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(p.datec) = CURRENT_DATE
                    ORDER BY p.rowid DESC
                ";
                break;
        }
    }

    // Log SQL Query and Parameters
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for errors in query execution
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data
    return $existingData;
}

function payment_list($invoiceIds = [], $startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Ensure $invoiceIds is an array
    if (!is_array($invoiceIds)) {
        return ['error' => true, 'message' => 'Invalid input. Invoice IDs should be an array.'];
    }

    // Prepare the escaped invoice IDs
    $escapedInvoiceIds = array_map(function($invoiceId) use ($pg_con) {
        return "'" . pg_escape_string($pg_con, $invoiceId) . "'";
    }, $invoiceIds);

    // Create the list of invoice IDs for the WHERE clause
    $invoiceIdsList = implode(",", $escapedInvoiceIds);

    // Base SQL query
    $sql = "
    SELECT 
        p.rowid AS payment_rowid, 
        p.ref AS payment_ref, 
        p.amount AS payment_amount, 
        p.datec AS payment_date, 
        pf.rowid AS pf_rowid, 
        pf.fk_paiement AS fk_payment, 
        pf.fk_facture AS fk_invoice, 
        pf.amount AS allocated_amount,
        f.ref AS invoice_ref, 
        f.datec AS invoice_date_created, 
        f.datef AS invoice_due_date, 
        f.date_valid AS invoice_validation_date, 
        f.date_closing AS invoice_closing_date, 
        f.paye AS amount_paid, 
        f.remise_percent AS discount_percent, 
        f.remise_absolue AS absolute_discount, 
        f.remise AS total_discount, 
        f.close_code AS closing_code, 
        f.close_note AS closing_note, 
        f.total_ht AS total_without_tax, 
        f.fk_statut AS status_numeric,  
        CASE 
            WHEN f.fk_statut = 0 THEN 'Draft'
            WHEN f.fk_statut = 1 THEN 'Unpaid'
            WHEN f.fk_statut = 2 THEN 'Partially/Completely Paid'
            WHEN f.fk_statut = 3 THEN 'Abandoned'
            ELSE 'Unknown'
        END AS status_text,  
        author.login AS author_user_login, 
        closer.login AS closer_user_login, 
        f.note_private AS private_note, 
        f.note_public AS public_note
    FROM llx_paiement_facture AS pf
    JOIN llx_paiement AS p ON pf.fk_paiement = p.rowid
    JOIN llx_facture AS f ON pf.fk_facture = f.rowid
    LEFT JOIN llx_user AS author ON f.fk_user_author = author.rowid 
    LEFT JOIN llx_user AS closer ON f.fk_user_closing = closer.rowid
    ";

    // Add invoice filter if provided
    if (!empty($invoiceIdsList)) {
        $sql .= " WHERE pf.fk_facture IN ($invoiceIdsList)";
    }

    // Add date filtering based on the dateOption
    if ($dateOption === 'range' && $startDate && $endDate) {
        // Date range filter: BETWEEN startDate and endDate
        $sql .= " AND DATE(p.datec) BETWEEN $1 AND $2";
    } elseif ($dateOption === 'yesterday') {
        // Yesterday filter
        $sql .= " AND DATE(p.datec) = DATE(CURRENT_DATE - INTERVAL '1 day')";
    } elseif ($dateOption === 'today') {
        // Today filter (current date)
        $sql .= " AND DATE(p.datec) = CURRENT_DATE";
    }

    // Add order by clause
    $sql .= " ORDER BY p.rowid DESC";

    // Prepare parameters for query
    $params = [];
    if ($dateOption === 'range' && $startDate && $endDate) {
        $params[] = $startDate;
        $params[] = $endDate;
    }

    // Execute the query with parameters
    $result = pg_query_params($pg_con, $sql, $params);

    // Check for query errors
    if (!$result) {
        echo 'Error: ' . pg_last_error($pg_con);
        return [];
    }

    // Fetch all results
    $paymentData = pg_fetch_all($result) ?: [];

    // Free the result resource
    pg_free_result($result);

    // Return the payment data
    return $paymentData;
}


function cyto_doctor_complete_case($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // SQL Base Query
    $baseSQL = "
        SELECT lab_number, screening_done, screening_done_date_time, screening_done_count, screening_done_count_data,
               finalization_done, finalization_done_date_time, finalization_done_count, finalization_done_count_data
        FROM llx_cyto_doctor_complete_case
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            WHERE DATE(screening_done_date_time) BETWEEN $1 AND $2
               OR DATE(finalization_done_date_time) BETWEEN $1 AND $2
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(screening_done_date_time) = CURRENT_DATE - INTERVAL '1 day'
                       OR DATE(finalization_done_date_time) = CURRENT_DATE - INTERVAL '1 day'
                ";
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(screening_done_date_time) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                       OR DATE(finalization_done_date_time) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                ";
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(screening_done_date_time) = CURRENT_DATE
                       OR DATE(finalization_done_date_time) = CURRENT_DATE
                ";
                break;
        }
    }

    // Log SQL Query
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params when parameters exist, otherwise use pg_query
    if (!empty($params)) {
        $result = pg_query_params($pg_con, $sql, $params);
    } else {
        $result = pg_query($pg_con, $sql);
    }

    // Check for errors in query execution
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;
}


function cyto_doctor_aspiration_history($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // SQL Base Query
    $baseSQL = "
        SELECT rowid, lab_number, fna_station_type, doctor, assistant, status, created_user from llx_cyto
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            WHERE DATE(created_date) BETWEEN $1 AND $2
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(created_date) = CURRENT_DATE - INTERVAL '1 day'
                ";
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(created_date) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                ";
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(created_date) = CURRENT_DATE
                ";
                break;
        }
    }

    // Log SQL Query
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params when parameters exist, otherwise use pg_query
    if (!empty($params)) {
        $result = pg_query_params($pg_con, $sql, $params);
    } else {
        $result = pg_query($pg_con, $sql);
    }

    // Check for errors in query execution
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;
}


function cyto_doctor_study_patient_history($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // Base SQL Query
    $baseSQL = "
        SELECT rowid, 
               lab_number, 
               screening_study, 
               screening_patient_history, 
               screening_study_count, 
               screening_study_count_data, 
               finalization_study,
               finalization_patient_history, 
               screening_doctor_name, 
               finalization_doctor_name, 
               finalization_study_count, 
               finalization_study_count_data
        FROM llx_cyto_doctor_study_patient_info
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // If both startDate and endDate are provided
    if ($startDate && $endDate) {
        $sql = $baseSQL . "
            WHERE 
                EXISTS (
                    SELECT 1 
                    FROM jsonb_each_text(screening_study_count_data::jsonb) AS e(username, timestamps)
                    CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                    WHERE DATE(elem::timestamp) BETWEEN $1 AND $2
                )
                OR 
                EXISTS (
                    SELECT 1 
                    FROM jsonb_each_text(finalization_study_count_data::jsonb) AS e(username, timestamps)
                    CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                    WHERE DATE(elem::timestamp) BETWEEN $1 AND $2
                )
        ";
        $params = [$startDate, $endDate];

    } else {
        // Handling specific date options
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(screening_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) = CURRENT_DATE - INTERVAL '1 day'
                        )
                        OR 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(finalization_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) = CURRENT_DATE - INTERVAL '1 day'
                        )
                ";
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(screening_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                        )
                        OR 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(finalization_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                        )
                ";
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(screening_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) = CURRENT_DATE
                        )
                        OR 
                        EXISTS (
                            SELECT 1 
                            FROM jsonb_each_text(finalization_study_count_data::jsonb) AS e(username, timestamps)
                            CROSS JOIN LATERAL jsonb_array_elements_text(timestamps::jsonb) AS elem
                            WHERE DATE(elem::timestamp) = CURRENT_DATE
                        )
                ";
                break;
        }
    }

    // Log SQL Query
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute query
    if (!empty($params)) {
        $result = pg_query_params($pg_con, $sql, $params);
    } else {
        $result = pg_query($pg_con, $sql);
    }

    // Check for query execution errors
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch and return the results
    $existingData = pg_fetch_all($result) ?: [];
    error_log("Fetched Data: " . json_encode($existingData));
    pg_free_result($result);

    return $existingData;
}

function cyto_transcription_entery_list($startDate = null, $endDate = null, $dateOption = 'today') {
    global $pg_con;

    // SQL Base Query
    $baseSQL = "
        select lab_number, created_user from llx_cyto_microscopic_description 
    ";

    // SQL Query and Parameters Initialization
    $sql = '';
    $params = [];

    // Build the query based on date options
    if ($startDate && $endDate) {
        // Case when both start and end dates are provided
        $sql = $baseSQL . "
            WHERE DATE(created_date) BETWEEN $1 AND $2
        ";
        $params = [$startDate, $endDate];
    } else {
        // Handling date options (today, yesterday, or both)
        switch ($dateOption) {
            case 'yesterday':
                $sql = $baseSQL . "
                    WHERE DATE(created_date) = CURRENT_DATE - INTERVAL '1 day'
                ";
                break;

            case 'both':
                $sql = $baseSQL . "
                    WHERE DATE(created_date) IN (CURRENT_DATE, CURRENT_DATE - INTERVAL '1 day')
                ";
                break;

            case 'today':
            default:
                $sql = $baseSQL . "
                    WHERE DATE(created_date) = CURRENT_DATE
                ";
                break;
        }
    }

    // Log SQL Query
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . json_encode($params));

    // Execute the query using pg_query_params when parameters exist, otherwise use pg_query
    if (!empty($params)) {
        $result = pg_query_params($pg_con, $sql, $params);
    } else {
        $result = pg_query($pg_con, $sql);
    }

    // Check for errors in query execution
    if (!$result) {
        error_log("Error executing SQL: " . pg_last_error($pg_con));
        return [
            'error' => true,
            'message' => 'An error occurred while loading the data. Please try again later.'
        ];
    }

    // Fetch the result as an associative array
    $existingData = pg_fetch_all($result) ?: [];

    // Log the data for debugging
    error_log("Fetched Data: " . json_encode($existingData));

    // Free the result
    pg_free_result($result);

    // Return the data or an empty array if no data found
    return $existingData;
}

?>