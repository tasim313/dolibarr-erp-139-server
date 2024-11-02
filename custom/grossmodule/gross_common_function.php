<?php 
include('connection.php');

function get_patient_information($lab_number) {

    global $pg_con;

    $sql = "SELECT s.nom as nom, s.code_client as code_client, s.address as address, s.phone as phone, s.fax as fax 
	FROM llx_commande AS c 
	JOIN llx_societe AS s ON c.fk_soc = s.rowid
	WHERE ref='$lab_number'";

    $result = pg_query($pg_con, $sql);

   $patients = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
           $patients[] = [
			'name' => $row['nom'], 
		   'patient_code' => $row['code_client'], 
		   'address'=> $row['address'],
		   'phone' => $row['phone'],
		   'fax' => $row['fax']
		];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return$patients;
}


function get_gross_specimens_list($lab_number) {
    global $pg_con;

    $sql = "
        SELECT de.fk_commande, de.fk_product, de.description as specimen,  c.ref, e.num_containers, de.rowid as specimen_id,
        (
            SELECT COUNT(*) 
            FROM llx_commandedet AS inner_de 
            WHERE inner_de.fk_commande = c.rowid
        ) AS number_of_specimens
        FROM 
            llx_commande AS c 
        JOIN 
            llx_commandedet AS de ON de.fk_commande = c.rowid
        JOIN 
            llx_commande_extrafields AS e ON e.fk_object = c.rowid
        WHERE 
            c.ref = '$lab_number' ORDER BY de.rowid ASC";

    $result = pg_query($pg_con, $sql);

    $specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = [
                'fk_commande' =>$row['fk_commande'] , 
                'num_containers' => $row['num_containers'], 
                'fk_product' => $row['fk_product'], 
                'number_of_specimens' => $row['number_of_specimens'],
                'specimen' => $row['specimen'],
                'specimen_rowid' => $row['specimen_id']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}

function numberToAlphabet($number) {
    $alphabet = range('A', 'Z');
    $result = '';
    for ($i = 0; $i < $number; $i++) {
        $result .= $alphabet[$i];
        if ($i < $number - 1) {
            $result .= ', ';
        }
    }
    return $result;
}

function get_doctor_list() {
    global $pg_con;

    $sql = "SELECT u.rowid, u.firstname, u.lastname, u.login
            FROM llx_usergroup_user AS ugu
            JOIN llx_usergroup AS ug ON ugu.fk_usergroup = ug.rowid
            JOIN llx_user AS u ON ugu.fk_user = u.rowid
            WHERE ug.nom = 'Consultants'";
    $result = pg_query($pg_con, $sql);

    $doctors = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $doctors[] = ['doctor_name' =>$row['firstname'] . ' ' . $row['lastname'], 'doctor_username' => $row['login'], 'userId' => $row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $doctors;
}

function get_gross_assistant_list() {
    global $pg_con;

    $sql = "SELECT u.rowid, u.firstname, u.lastname, u.login
            FROM llx_usergroup_user AS ugu
            JOIN llx_usergroup AS ug ON ugu.fk_usergroup = ug.rowid
            JOIN llx_user AS u ON ugu.fk_user = u.rowid
            WHERE ug.nom = 'Gross assistants'";
    $result = pg_query($pg_con, $sql);

    $assistants = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $assistants[] = ['assistants_name' =>$row['firstname'] . ' ' . $row['lastname'], 'username' => $row['login'], 'userId' => $row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $assistants;
}

function get_gross_assign_list($LabNumber) {
    global $pg_con;

    $sql = "SELECT lab_number, assign_id,
            gross_assistant_name AS assistant, 
            gross_doctor_name AS doctor, 
            gross_assign_created_user AS assignperson, 
            assign_create_date AS date,
            gross_status AS status
            FROM llx_gross_assign 
            WHERE lab_number = '$LabNumber'";
      
    $result = pg_query($pg_con, $sql);

    $assign = null;

    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row) {
            $assign = [
                'Doctor' => $row['doctor'], 
                'Assistant' => $row['assistant'], 
                'AssignPerson' => $row['assignperson'], 
                'Date' => $row['date'], 
                'Status' => $row['status'],
                'assign_id' => $row['assign_id']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $assign;
}



function get_pending_gross_list() {
    global $pg_con;

    $sql = "SELECT soc.code_client as patient_code, CONCAT(e.test_type, '', c.ref) AS lab_number, c.date_commande as received_date,
    CONCAT(e.referredby_dr, ' ', e.referred_from, ' ', e.referred_by_dr_text, '  ', e.referredfrom_text) AS referr
            FROM llx_commande AS c
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
            LEFT JOIN llx_gross AS g ON g.fk_commande = c.rowid
            JOIN llx_societe AS soc ON c.fk_soc = soc.rowid
            WHERE fk_statut = 1 AND date_commande BETWEEN '2024-05-3' AND CURRENT_DATE 
            AND e.test_type = 'HPL'
            AND (g.gross_is_completed = false OR g.fk_commande IS NULL)";

    $result = pg_query($pg_con, $sql);

    $pending_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $pending_list[] = ['patient_code' => $row['patient_code'], 'lab_number' => $row['lab_number'], 
            'received_date' => $row['received_date'], 'referr' => $row['referr']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $pending_list;
}



function get_reception_assign_doctor_pending_gross_list() {
    global $pg_con;

    $sql = "SELECT 
    c.date_commande AS received_date,
    soc.code_client AS patient_code, 
    CONCAT(e.test_type, '', c.ref) AS lab_number, 
    CONCAT(e.referredby_dr, ' ', e.referred_from, ' ', e.referred_by_dr_text, ' ', e.referred_by_dr_text, ' ', e.referredfrom_text) AS referr,
    socpeople.lastname AS assign_doctor
    FROM 
        llx_commande AS c
    JOIN 
        llx_commande_extrafields AS e ON e.fk_object = c.rowid 
    LEFT JOIN 
        llx_gross AS g ON g.fk_commande = c.rowid
    JOIN 
        llx_societe AS soc ON c.fk_soc = soc.rowid
    JOIN 
        llx_socpeople AS socpeople ON e.aikl_dr::integer = socpeople.rowid
    JOIN 
        llx_categorie_contact AS categorie_contact ON socpeople.rowid = categorie_contact.fk_socpeople
    WHERE 
    c.fk_statut = 1 
    AND c.date_commande BETWEEN '2024-05-3' AND CURRENT_DATE 
    AND e.test_type = 'HPL'
    AND (g.gross_is_completed = false OR g.fk_commande IS NULL)
     AND (categorie_contact.fk_categorie = 5 OR categorie_contact.fk_categorie IS NULL OR categorie_contact.fk_categorie = 0)";

    $result = pg_query($pg_con, $sql);

    $pending_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $pending_list[] = ['patient_code' => $row['patient_code'], 'lab_number' => $row['lab_number'], 
            'received_date' => $row['received_date'], 'referr' => $row['referr'], 'assign_doctor' =>$row['assign_doctor']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $pending_list;
}

function get_gross_management_list() {
    global $pg_con;

    $sql = "SELECT u.rowid, u.login
    FROM llx_usergroup_user AS ugu
    JOIN llx_usergroup AS ug ON ugu.fk_usergroup = ug.rowid
    JOIN llx_user AS u ON ugu.fk_user = u.rowid
    WHERE ug.nom = 'Gross Management'";
    $result = pg_query($pg_con, $sql);

   $managements = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
           $managements[] = ['username' => $row['login'], 'userId' => $row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return$managements;
}


function get_pending_gross_value() {
    global $pg_con;

    $sql = "SELECT COUNT(ref) AS count FROM llx_commande AS c 
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
            LEFT JOIN llx_gross AS g ON g.fk_commande = c.rowid
            JOIN llx_societe AS soc ON c.fk_soc = soc.rowid
            WHERE fk_statut = 1 
            AND date_commande BETWEEN '2024-05-3' AND CURRENT_DATE 
            AND e.test_type = 'HPL'
            AND (g.gross_is_completed = false OR g.fk_commande IS NULL)";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}


function get_complete_gross_value() {
    global $pg_con;

    $sql = "SELECT COUNT(ref) AS count FROM llx_commande AS c 
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
            LEFT JOIN llx_gross AS g ON g.fk_commande = c.rowid
            JOIN llx_societe AS soc ON c.fk_soc = soc.rowid
            WHERE fk_statut = 1 
            AND date_commande BETWEEN '2024-05-3' AND CURRENT_DATE 
            AND e.test_type = 'HPL'
            AND (g.gross_is_completed = true OR g.fk_commande IS NOT NULL)";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['count'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}


function get_labnumber_list() {
    global $pg_con;

    $sql = "SELECT soc.code_client as patient_code, CONCAT(e.test_type, '', c.ref) AS lab_number, c.rowid as rowid
    FROM llx_commande AS c
    JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
    LEFT JOIN llx_gross AS g ON g.fk_commande = c.rowid
    JOIN llx_societe AS soc ON c.fk_soc = soc.rowid
    WHERE fk_statut = 1 AND date_commande BETWEEN '2024-05-3' AND CURRENT_DATE 
    AND e.test_type = 'HPL'
    AND (g.gross_is_completed = false OR g.fk_commande IS NULL)";
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

function get_lab_number($GrossId){
    global $pg_con;

    $sql = "select lab_number from llx_gross where gross_id='$GrossId'";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $lab_number = $row['lab_number'];
        pg_free_result($result);
        return $lab_number;
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        return null;
    }
}


function number_of_specimen($GrossId){
    global $pg_con;

    $sql = "SELECT COUNT(*) AS specimen_count FROM llx_gross_specimen WHERE fk_gross_id = '$GrossId'";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $specimen_count = $row['specimen_count'];
        pg_free_result($result);
        return $specimen_count;
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        return null;
    }
}

function get_gross_instance($LabNumber) {
    global $pg_con;

    $sql = "select gross_id from llx_gross where lab_number='HPL' || '$LabNumber' and gross_id is NOT Null";
    $result = pg_query($pg_con, $sql);

    $gross_instances = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $gross_instances[] = ['gross_id' =>$row['gross_id'] ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $gross_instances;
}

function getUserGroupNames($loggedInUserId) {
    global $pg_con;
    $sql = "SELECT ug.nom
            FROM llx_usergroup ug
            JOIN llx_usergroup_user ugu ON ug.rowid = ugu.fk_usergroup
            JOIN llx_user u ON u.rowid = ugu.fk_user
            WHERE u.rowid = $loggedInUserId";

    $result = pg_query($pg_con, $sql);
    $userGroupNames = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $userGroupNames[] = ['group' => $row['nom']];
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }
    return $userGroupNames;
}

function get_total_gross_values_for_assistant($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT
                SUM(CASE WHEN EXTRACT(MONTH FROM gross_create_date) = EXTRACT(MONTH FROM NOW()) THEN 1 ELSE 0 END) AS total_gross_current_month,
                COUNT(*) AS total_gross_current_year
            FROM llx_gross
            WHERE gross_assistant_name = $1";

    $result = pg_query_params($pg_con, $sql, array($loggedInUsername));

    if ($result) {
        $row = pg_fetch_assoc($result);
        $total_gross_current_month = $row['total_gross_current_month'];
        $total_gross_current_year = $row['total_gross_current_year'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $total_gross_current_month = 0;
        $total_gross_current_year = 0;
    }

    return array($total_gross_current_month, $total_gross_current_year);
}


function get_total_gross_values_for_doctor($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT
                SUM(CASE WHEN EXTRACT(MONTH FROM gross_create_date) = EXTRACT(MONTH FROM NOW()) THEN 1 ELSE 0 END) AS total_gross_current_month,
                COUNT(*) AS total_gross_current_year
            FROM llx_gross
            WHERE LOWER(TRIM(gross_doctor_name)) = $1";

    $result = pg_query_params($pg_con, $sql, array($loggedInUsername));

    if ($result) {
        $row = pg_fetch_assoc($result);
        $total_gross_current_month_doctor = $row['total_gross_current_month'];
        $total_gross_current_year_doctor = $row['total_gross_current_year'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $total_gross_current_month_doctor = 0;
        $total_gross_current_year_doctor = 0;
    }

    return array($total_gross_current_month_doctor, $total_gross_current_year_doctor);
}

// $loggedInUsername
function get_gross_list_by_assistant() {
    global $pg_con;

    $sql = "SELECT gross_id, lab_number, patient_code, gross_station_type,
	gross_assistant_name, gross_doctor_name, gross_create_date
	FROM llx_gross";
	// WHERE gross_assistant_name = '$loggedInUsername'";
    $result = pg_query($pg_con, $sql);

    $assistants = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $assistants[] = [
				'gross_id' =>$row['gross_id'], 
				'lab_number' => $row['lab_number'], 
				'patient_code' => $row['patient_code'],
				'gross_station_type' => $row['gross_station_type'],
				'gross_assistant_name' => $row['gross_assistant_name'],
				'gross_doctor_name' => $row['gross_doctor_name'],
				'gross_create_date' => $row['gross_create_date']
			];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $assistants;
}

function get_gross_list_by_doctor($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT gross_id, lab_number, patient_code, gross_station_type,
	gross_assistant_name, gross_doctor_name, gross_create_date
	FROM llx_gross
	WHERE LOWER(TRIM(gross_doctor_name)) = '$loggedInUsername'";
    $result = pg_query($pg_con, $sql);

    $assistants = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $assistants[] = [
				'gross_id' =>$row['gross_id'], 
				'lab_number' => $row['lab_number'], 
				'patient_code' => $row['patient_code'],
				'gross_station_type' => $row['gross_station_type'],
				'gross_assistant_name' => $row['gross_assistant_name'],
				'gross_doctor_name' => $row['gross_doctor_name'],
				'gross_create_date' => $row['gross_create_date']
			];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $assistants;
}

function get_gross_specimen_description($fk_gross_id) {
    global $pg_con;
    $sql = "SELECT specimen_id, specimen, gross_description FROM llx_gross_specimen WHERE fk_gross_id = $1 ORDER BY specimen_id ASC";
    $result = pg_query_params($pg_con, $sql, array($fk_gross_id));

    $specimens = array();

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}

function get_gross_specimen_section($fk_gross_id) {
    global $pg_con;
    $sql = "select gross_specimen_section_id, 
    fk_gross_id, section_code, 
    specimen_section_description, cassettes_numbers, tissue, bone, re_gross, requires_slide_for_block, decalcified_bone
    from llx_gross_specimen_section WHERE TRIM(fk_gross_id) = $1 ORDER BY 
    LEFT(section_code, 1) ASC, 
    CAST(SUBSTRING(section_code, 2) AS INTEGER) ASC, 
    gross_specimen_section_id ASC";
    $result = pg_query_params($pg_con, $sql, array($fk_gross_id));

    $specimens = array();

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}

function get_gross_summary_of_section($fk_gross_id) {
    global $pg_con;
    $sql = "select gross_summary_id, fk_gross_id, summary, 
    ink_code from llx_gross_summary_of_section WHERE fk_gross_id = $1 ORDER BY gross_summary_id ASC;";
    $result = pg_query_params($pg_con, $sql, array($fk_gross_id));

    $specimens = array();

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = $row;
        }
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $specimens;
}

function getGrossAssignmentsByAssistantName($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT assign_id, lab_number, gross_assistant_name, gross_doctor_name, gross_status
            FROM llx_gross_assign
            WHERE gross_assistant_name = '$loggedInUsername' And gross_status = 'Pending'";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        $assignments = [];

        while ($row = pg_fetch_assoc($result)) {
           
            $assignments[] = $row;
        }

        pg_free_result($result);

        return $assignments;
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        return [];
    }
}

function getGrossAssignmentsByDoctorName($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT assign_id, lab_number, gross_assistant_name, gross_doctor_name, gross_status
    FROM llx_gross_assign
    WHERE LOWER(TRIM(gross_doctor_name)) = '$loggedInUsername'  
    AND LOWER(TRIM(gross_status)) = 'pending';
            ";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        $doctors = [];

        while ($row = pg_fetch_assoc($result)) {
           
            $doctors[] = $row;
        }

        pg_free_result($result);

        return $doctors;
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        return [];
    }
}

function get_assigned_gross_value_pending($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT COUNT(*) AS total_value
    FROM llx_gross_assign
    WHERE gross_assistant_name = '$loggedInUsername' AND gross_status = 'Pending';";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_value'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}

function get_assigned_gross_value_done($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT COUNT(*) AS total_value
    FROM llx_gross_assign
    WHERE gross_assistant_name = '$loggedInUsername' AND gross_status = 'Done';";
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_value'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }

    return $count;
}


function get_assigned_gross_value_pending_by_doctor($loggedInUsername) {
    global $pg_con;
  
    $sql = "SELECT COUNT(*) AS total_value
    FROM llx_gross_assign
    WHERE LOWER(TRIM(gross_doctor_name)) = '$loggedInUsername' AND gross_status = 'Pending';";
    $result = pg_query($pg_con, $sql);
  
    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_value'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }
  
    return $count;
}

function get_assigned_gross_value_done_by_doctor($loggedInUsername) {
    global $pg_con;
  
    $sql = "SELECT COUNT(*) AS total_value
    FROM llx_gross_assign
    WHERE LOWER(TRIM(gross_doctor_name)) = '$loggedInUsername' AND gross_status = 'Done';";
    $result = pg_query($pg_con, $sql);
  
    if ($result) {
        $row = pg_fetch_assoc($result);
        $count = $row['total_value'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }
  
    return $count;
}

function get_patient_invoice_number($lab_number){
    global $pg_con;

    $sql = "SELECT f.ref AS invoice
    FROM llx_facture AS f 
    JOIN llx_societe s ON f.fk_soc = s.rowid 
    JOIN llx_commande AS c ON c.fk_soc = s.rowid 
    WHERE c.ref = '$lab_number';";
    $result = pg_query($pg_con, $sql);
  
    if ($result) {
        $row = pg_fetch_assoc($result);
        $invoice_number = $row['invoice'];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
        $count = 0; 
    }
  
    return $invoice_number;

}


function get_single_doctor_information($username) {
    global $pg_con;

    $sql = "SELECT rowid, firstname, lastname, login FROM llx_user 
            WHERE login = '$username'";
    $result = pg_query($pg_con, $sql);

    $doctors = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $doctors[] = ['doctor_name' =>$row['firstname'] . ' ' . $row['lastname'], 'doctor_username' => $row['login'], 'userId' => $row['rowid']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $doctors;
}

function get_single_doctor_details($username) {
    global $pg_con;

    $sql = "select username, doctor_name, education, designation 
            from llx_doctor_designation
            WHERE username = '$username'";
    $result = pg_query($pg_con, $sql);

    $doctors = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $doctors[] = ['doctor_name' =>$row['doctor_name'], 'username' => $row['username'], 
            'education' => $row['education'], 'designation' => $row['designation']];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $doctors;
}


function get_abbreviations_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "SELECT rowid, abbreviation_key, abbreviation_full_text 
            FROM llx_abbreviations 
            -- WHERE fk_user_id = $1 
            ORDER BY abbreviation_key ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_abbreviations", $sql);
    // $result = pg_execute($pg_con, "get_abbreviations", array($user_id));
    $result = pg_execute($pg_con, "get_abbreviations", array());

    $existingdata = [];

    if ($result) {
        // Use pg_fetch_all to fetch all rows at once if the dataset is not too large
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function isUserAdmin($userId) {
    global $pg_con;

    // Correct SQL query with a parameter placeholder for the user ID
    $sql = "
        SELECT COUNT(*) as admin_count
        FROM llx_usergroup_user AS ugu
        JOIN llx_usergroup AS ug ON ugu.fk_usergroup = ug.rowid
        WHERE ugu.fk_user = $1 AND ug.nom = 'Administrator'
    ";

    // Prepare the SQL query
    $result = pg_prepare($pg_con, "check_user_admin", $sql);

    if (!$result) {
        echo "Error in preparing query: " . pg_last_error($pg_con);
        return false;
    }

    // Execute the query with the user ID as a parameter
    $result = pg_execute($pg_con, "check_user_admin", array($userId));

    if (!$result) {
        echo "Error in query execution: " . pg_last_error($pg_con);
        return false;
    }

    // Fetch the result
    $row = pg_fetch_assoc($result);

    // Return whether the user is an admin
    return $row ? $row['admin_count'] > 0 : false;
}


function get_re_gross_request_list($lab_number) {
    global $pg_con;

    // Use placeholders for the prepared statement
    $sql = "SELECT * FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = '6'";

    // Prepare the SQL query
    $result_prepare = pg_prepare($pg_con, "get_gross_request", $sql);
    
    // Execute the prepared query with the parameter (lab_number)
    if ($result_prepare) {
        $result = pg_execute($pg_con, "get_gross_request", array($lab_number));
        
        $existingdata = [];

        if ($result) {
            // Fetch all rows if available
            $existingdata = pg_fetch_all($result) ?: [];
            pg_free_result($result);  // Free the result after processing
        } else {
            // Log or handle the error
            echo 'Error during execution: ' . pg_last_error($pg_con);
        }

        return $existingdata;
    } else {
        // Handle preparation error
        echo 'Error during preparation: ' . pg_last_error($pg_con);
        return [];
    }
} 


function gross_specimen_used_list($fk_gross_id) {
    global $pg_con;

    // Modified SQL to filter by fk_gross_id
    $sql = "SELECT 
                rowid,
                fk_gross_id,
                section_code,
                description
            FROM llx_gross_specimen_used
            WHERE fk_gross_id = $1
            ORDER BY rowid ASC";

    // Prepare and execute the SQL query with fk_gross_id as a parameter
    $result = pg_prepare($pg_con, "get_specimen_used", $sql);
    $result = pg_execute($pg_con, "get_specimen_used", array($fk_gross_id));

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_batch_list() {
    global $pg_con;

    // Ensure that we are using a prepared statement to prevent SQL injection
    $sql = "select rowid,
            name,
            created_date,
            created_user,
            updated_user,
            created_time,
            updated_time from llx_batch
            ORDER BY rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch", $sql);
    $result = pg_execute($pg_con, "get_batch", array());

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_batch_details_list($lab_number) {
    global $pg_con;

    // Define the SQL with a parameter placeholder
    $sql = "SELECT batch_number    
            FROM llx_batch_details 
            WHERE lab_number = $1
            ORDER BY rowid ASC";

    // Prepare the SQL statement
    $result = pg_prepare($pg_con, "get_batch_details", $sql);

    // Execute the prepared statement with $lab_number as a parameter
    $result = pg_execute($pg_con, "get_batch_details", array($lab_number));

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_cassettes_count_list() {
    global $pg_con;

    // Get today's date in 'Y-m-d' format
    $today = date('Y-m-d');

    // SQL query to get cassette counts for the current day only
    $sql = "SELECT 
                c.rowid, 
                b.name, 
                COALESCE(c.total_cassettes_count, 0) AS total_cassettes_count, 
                c.created_date, 
                c.description 
            FROM 
                llx_batch_cassette_counts AS c
            JOIN 
                llx_batch AS b ON c.batch_details_cassettes = b.rowid 
            WHERE 
                c.created_date = $1
            ORDER BY 
                c.rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batch_count", $sql);
    $result = pg_execute($pg_con, "get_batch_count", array($today));

    $existingdata = [];

    if ($result) {
        $existingdata = pg_fetch_all($result) ?: [];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}


function get_batches_with_counts() {
    global $pg_con;

    // Get today's date in 'Y-m-d' format
    $today = date('Y-m-d');

    // SQL query to get batch details and cassette counts for the current day
    $sql = "
        SELECT 
            b.rowid, 
            b.name, 
            COALESCE(c.total_cassettes_count, 0) AS total_cassettes_count,
            (120 - COALESCE(c.total_cassettes_count, 0)) AS remaining_count  -- Calculate remaining count
        FROM 
            llx_batch AS b
        LEFT JOIN 
            llx_batch_cassette_counts AS c 
        ON 
            b.rowid = c.batch_details_cassettes 
            AND c.created_date = $1  -- Only include today's counts
        ORDER BY 
            b.rowid ASC";

    // Prepare and execute the SQL query
    $result = pg_prepare($pg_con, "get_batches_with_counts", $sql);
    $result = pg_execute($pg_con, "get_batches_with_counts", array($today));

    $batches_with_counts = [];

    if ($result) {
        $batches_with_counts = pg_fetch_all($result) ?: [];
        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $batches_with_counts;
}


function get_labNumber_batch_details_list($lab_number) {
    global $pg_con;

    // Define the SQL with a parameter placeholder
    $sql = "SELECT 
            d.rowid, 
            d.batch_number, 
            d.lab_number, 
            d.gross_station, 
            b.name, 
            c.total_cassettes_count 
            FROM llx_batch_details AS d
            JOIN llx_batch AS b ON d.batch_number = b.rowid
            JOIN llx_batch_cassette_counts AS c ON d.batch_number = c.batch_details_cassettes
            WHERE d.lab_number = $1 AND c.created_date=current_date";

    // Prepare the SQL statement
    $result = pg_prepare($pg_con, "get_labNumber_batch_details", $sql);

    // Execute the prepared statement with $lab_number as a parameter
    $result = pg_execute($pg_con, "get_labNumber_batch_details", array($lab_number));

    $existingdata = [];

    if ($result) {
        $data = pg_fetch_all($result) ?: [];
        pg_free_result($result);

        // Use associative arrays to filter duplicates based on a combination of fields
        $uniqueData = [];
        $seenCombinations = [];

        foreach ($data as $row) {
            // Create a unique key based on a combination of fields
            $uniqueKey = $row['rowid'];

            if (!in_array($uniqueKey, $seenCombinations)) {
                $uniqueData[] = $row;
                $seenCombinations[] = $uniqueKey;
            }
        }

        $existingdata = $uniqueData;
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $existingdata;
}

?>