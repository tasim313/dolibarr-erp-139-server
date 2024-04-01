<?php 
include('connection.php');

function getUserGroupNames($loggedInUserId) {
    global $pg_con;
    
    // Prepare the SQL query with a placeholder for the user ID
    $sql = "SELECT ug.nom
            FROM llx_usergroup ug
            JOIN llx_usergroup_user ugu ON ug.rowid = ugu.fk_usergroup
            JOIN llx_user u ON u.rowid = ugu.fk_user
            WHERE u.rowid = $1"; // Placeholder for the user ID
    
    // Prepare the statement
    $stmt = pg_prepare($pg_con, "", $sql);
    
    // Execute the statement with the user ID parameter
    $result = pg_execute($pg_con, "", array($loggedInUserId));
    
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


function get_done_gross_list() {
    global $pg_con;

    $sql = "SELECT gross_id, lab_number, patient_code, gross_assistant_name, gross_doctor_name
            FROM llx_gross
            WHERE gross_status = 'Done' AND gross_is_completed = 'true'";

    $result = pg_query($pg_con, $sql);

    $done_list = [];

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $done_list[] = [
                'gross_id' => $row['gross_id'],
                'lab_number' => $row['lab_number'],
                'patient_code' => $row['patient_code'],
                'gross_assistant_name' => $row['gross_assistant_name'],
                'gross_doctor_name' => $row['gross_doctor_name']
            ];
        }

        pg_free_result($result);
    } else {
        echo 'Error: ' . pg_last_error($pg_con);
    }

    return $done_list;
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

?>