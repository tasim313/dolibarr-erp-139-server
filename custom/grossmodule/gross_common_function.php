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

    $sql = "SELECT de.fk_commande, de.fk_product, de.description as specimen,  c.ref, e.num_containers,
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
    c.ref = '$lab_number'";

    $result = pg_query($pg_con, $sql);

    $specimens = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $specimens[] = [
                'fk_commande' =>$row['fk_commande'] , 
                'num_containers' => $row['num_containers'], 
                'fk_product' => $row['fk_product'], 
                'number_of_specimens' => $row['number_of_specimens'],
                'specimen' => $row['specimen']];
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
    CONCAT(e.referredby_dr, ' ', e.referred_from, ' ', e.referred_by_dr_text, ' ', e.referred_by_dr_text, ' ', e.referredfrom_text) AS referr
            FROM llx_commande AS c
            JOIN llx_commande_extrafields AS e ON e.fk_object = c.rowid 
            LEFT JOIN llx_gross AS g ON g.fk_commande = c.rowid
            JOIN llx_societe AS soc ON c.fk_soc = soc.rowid
            WHERE fk_statut = 1 AND date_commande BETWEEN '2024-02-5' AND CURRENT_DATE 
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
    AND c.date_commande BETWEEN '2024-02-5' AND CURRENT_DATE 
    AND e.test_type = 'HPL'
    AND (g.gross_is_completed = false OR g.fk_commande IS NULL)
     AND (categorie_contact.fk_categorie = 5 OR categorie_contact.fk_categorie IS NULL OR categorie_contact.fk_categorie = 0)
";

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
            AND date_commande BETWEEN '2024-02-5' AND CURRENT_DATE 
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
            AND date_commande BETWEEN '2024-02-5' AND CURRENT_DATE 
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
    WHERE fk_statut = 1 AND date_commande BETWEEN '2024-02-5' AND CURRENT_DATE 
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


function get_gross_list_by_assistant($loggedInUsername) {
    global $pg_con;

    $sql = "SELECT gross_id, lab_number, patient_code, gross_station_type,
	gross_assistant_name, gross_doctor_name, gross_create_date
	FROM llx_gross
	WHERE gross_assistant_name = '$loggedInUsername'";
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
    $sql = "SELECT specimen_id, specimen, gross_description FROM llx_gross_specimen WHERE fk_gross_id = $1";
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
    specimen_section_description, cassettes_numbers from llx_gross_specimen_section WHERE fk_gross_id = $1";
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
    $sql = "select gross_summary_id, fk_gross_id, summary, ink_code from llx_gross_summary_of_section WHERE fk_gross_id = $1";
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

?>