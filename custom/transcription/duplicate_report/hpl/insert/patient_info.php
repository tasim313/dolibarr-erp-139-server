<?php 

include("connection.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    $rowid = is_array($_POST['rowid']) ? $_POST['rowid'][0] : $_POST['rowid'];
    $lab_number = $_POST['lab_number'] ?? null;
    
    $name = is_array($_POST['name']) ? $_POST['name'][0] : $_POST['name'];
    $patient_code = is_array($_POST['patient_code']) ? $_POST['patient_code'][0] : $_POST['patient_code'];
    $address = is_array($_POST['address']) ? $_POST['address'][0] : $_POST['address'];
    $phone = is_array($_POST['phone']) ? $_POST['phone'][0] : $_POST['phone'];
    $fax = is_array($_POST['fax']) ? $_POST['fax'][0] : $_POST['fax'];
    $date_of_birth = is_array($_POST['date_of_birth']) ? $_POST['date_of_birth'][0] : $_POST['date_of_birth'];
    $gender = is_array($_POST['gender']) ? $_POST['gender'][0] : $_POST['gender'];
    $age = is_array($_POST['age']) ? $_POST['age'][0] : $_POST['age'];
    $attendant_name = is_array($_POST['att_name']) ? $_POST['att_name'][0] : $_POST['att_name'];
    $attendant_relation = is_array($_POST['att_relation']) ? $_POST['att_relation'][0] : $_POST['att_relation'];

    if ($rowid) {
        // Check if rowid exists
        $checkQuery = "SELECT rowid FROM llx_other_report_patient_information WHERE rowid = $1";
        $result = pg_query_params($pg_con, $checkQuery, [$rowid]);

        if (pg_num_rows($result) > 0) {
            // If exists, update the record
            $updateQuery = "UPDATE llx_other_report_patient_information 
                            SET lab_number = $1, nom = $2, code_client = $3, address = $4, phone = $5, fax = $6, date_of_birth = $7, 
                                sex = $8, ageyrs = $9, att_name = $10, att_relation = $11 
                            WHERE rowid = $12";
            $updateResult = pg_query_params($pg_con, $updateQuery, [$lab_number, $name, $patient_code, $address, $phone, $fax, $date_of_birth, $gender, $age, $attendant_name, $attendant_relation, $rowid]);

            if ($updateResult) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                echo "Error updating record: " . pg_last_error($pg_con);
            }
        } else {
            // If rowid does not exist, insert new record
            $insertQuery = "INSERT INTO llx_other_report_patient_information 
                            (lab_number, nom, code_client, address, phone, fax, date_of_birth, sex, ageyrs, att_name, att_relation) 
                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
            $insertResult = pg_query_params($pg_con, $insertQuery, [$lab_number, $name, $patient_code, $address, $phone, $fax, $date_of_birth, $gender, $age, $attendant_name, $attendant_relation]);

            if ($insertResult) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                echo "Error inserting record: " . pg_last_error($pg_con);
            }
        }
    } else {
        // If no rowid is provided, insert a new record
        $insertQuery = "INSERT INTO llx_other_report_patient_information 
                        (lab_number, nom, code_client, address, phone, fax, date_of_birth, sex, ageyrs, att_name, att_relation) 
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
        $insertResult = pg_query_params($pg_con, $insertQuery, [$lab_number, $name, $patient_code, $address, $phone, $fax, $date_of_birth, $gender, $age, $attendant_name, $attendant_relation]);

        if ($insertResult) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            echo "Error inserting record: " . pg_last_error($pg_con);
        }
    }
}

?>