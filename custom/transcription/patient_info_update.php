<?php
// Include database connection
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $names = isset($_POST['name']) ? $_POST['name'] : [];
    $patient_codes = isset($_POST['patient_code']) ? $_POST['patient_code'] : [];
    $addresses = isset($_POST['address']) ? $_POST['address'] : [];
    $phones = isset($_POST['phone']) ? $_POST['phone'] : [];
    $faxes = isset($_POST['fax']) ? $_POST['fax'] : [];
    $dates_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : [];
    $genders = isset($_POST['gender']) ? $_POST['gender'] : [];
    $ages = isset($_POST['age']) ? $_POST['age'] : [];
    $attendant_names = isset($_POST['att_name']) ? $_POST['att_name'] : [];
    $attendant_relations = isset($_POST['att_relation']) ? $_POST['att_relation'] : [];
    $rowids = isset($_POST['rowid']) ? $_POST['rowid'] : [];
    print('rowid'.$rowids);
    // Prepare and execute the UPDATE statements for llx_societe table
    for ($i = 0; $i < count($names); $i++) {
        $sql_societe = "UPDATE llx_societe SET 
                        nom='" . pg_escape_string($names[$i]) . "', 
                        code_client='" . pg_escape_string($patient_codes[$i]) . "', 
                        address='" . pg_escape_string($addresses[$i]) . "', 
                        phone='" . pg_escape_string($phones[$i]) . "', 
                        fax='" . pg_escape_string($faxes[$i]) . "'
                        WHERE rowid='" . pg_escape_string($rowids[$i]) . "'";

        $result_societe = pg_query($pg_con, $sql_societe);
        if (!$result_societe) {
            echo "Error updating record in llx_societe table: " . pg_last_error($pg_con);
            exit();
        }
    }

    // Prepare and execute the UPDATE statements for llx_societe_extrafields table
    for ($i = 0; $i < count($dates_of_birth); $i++) {
        // Check if the date_of_birth is not empty before including it in the SQL query
        $date_of_birth = !empty($dates_of_birth[$i]) ? "'" . pg_escape_string($dates_of_birth[$i]) . "'" : "NULL";
    
        $sql_extrafields = "UPDATE llx_societe_extrafields SET 
                            date_of_birth=" . $date_of_birth . ", 
                            sex='" . pg_escape_string($genders[$i]) . "', 
                            ageyrs='" . pg_escape_string($ages[$i]) . "', 
                            att_name='" . pg_escape_string($attendant_names[$i]) . "', 
                            att_relation='" . pg_escape_string($attendant_relations[$i]) . "'
                            WHERE fk_object='" . pg_escape_string($rowids[$i]) . "'";    

        $result_extrafields = pg_query($pg_con, $sql_extrafields);
        if (!$result_extrafields) {
            echo "Error updating record in llx_societe_extrafields table: " . pg_last_error($pg_con);
            exit();
        }
    }

    // Close database connection
    pg_close($pg_con);

    // Redirect after successful updates
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Redirect if not a POST request
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}
?>
