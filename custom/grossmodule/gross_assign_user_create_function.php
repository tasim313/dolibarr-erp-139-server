<?php 

include("connection.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $lab_number = isset($_POST['lab_number']) ? pg_escape_string($pg_con, $_POST['lab_number']) : '';
    $gross_assistant_name = isset($_POST['gross_assistant_name']) ? pg_escape_string($pg_con, $_POST['gross_assistant_name']) : '';
    $gross_doctor_name = isset($_POST['gross_doctor_name']) ? pg_escape_string($pg_con, $_POST['gross_doctor_name']) : '';
    $gross_status = isset($_POST['gross_status']) ? pg_escape_string($pg_con, $_POST['gross_status']) : '';
    $gross_assign_created_user = isset($_POST['gross_assign_created_user']) ? pg_escape_string($pg_con, $_POST['gross_assign_created_user']) : ''; 
    $sql = "INSERT INTO llx_gross_assign
            (
            lab_number,
            gross_assistant_name, 
            gross_doctor_name, 
            gross_status, 
            gross_assign_created_user
            )
            VALUES (
                '$lab_number',
                '$gross_assistant_name',
                '$gross_doctor_name ', 
                '$gross_status', 
                '$gross_assign_created_user'
                )";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        echo '<script>';
        echo 'var LabNumber = "' . substr($lab_number, 3) . '";';
        echo 'window.location.href = "gross_assign_user_create.php?lab_number=' . $lab_number . '"';
        echo '</script>';
    } else {
        echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
    }
    pg_close($pg_con);
}else{
   header("Location: gross_assign.php");
   exit();
}


?>