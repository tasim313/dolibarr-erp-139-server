<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = pg_escape_string($_POST['rowid']);
    $name = pg_escape_string($_POST['name']);
    $patient_code = pg_escape_string($_POST['patient_code']);
    $address = pg_escape_string($_POST['address']);
    $phone = pg_escape_string($_POST['phone']);
    $fax = pg_escape_string($_POST['fax']);
    $gender = pg_escape_string($_POST['gender']);
    $age = pg_escape_string($_POST['age']);
    $att_name = pg_escape_string($_POST['att_name']);
    $att_relation = pg_escape_string($_POST['att_relation']);

    $sql_societe = "UPDATE llx_societe SET 
                    nom='$name', 
                    code_client='$patient_code', 
                    address='$address', 
                    phone='$phone', 
                    fax='$fax'
                    WHERE rowid='$rowid'";

    $result_societe = pg_query($pg_con, $sql_societe);

    $sql_extrafields = "UPDATE llx_societe_extrafields SET 
                        sex='$gender', 
                        ageyrs='$age', 
                        att_name='$att_name', 
                        att_relation='$att_relation'
                        WHERE fk_object='$rowid'";

    $result_extrafields = pg_query($pg_con, $sql_extrafields);

    if ($result_societe && $result_extrafields) {
        echo "Success";
    } else {
        echo "Error updating data: " . pg_last_error($pg_con);
    }

    pg_close($pg_con);
}

?>