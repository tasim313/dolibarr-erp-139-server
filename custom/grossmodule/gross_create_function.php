<?php 

include("connection.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape the form values to prevent SQL injection
    $lab_number = isset($_POST['lab_number']) ? pg_escape_string($pg_con, $_POST['lab_number']) : '';
    $gross_station_type = isset($_POST['gross_station_type']) ? pg_escape_string($pg_con, $_POST['gross_station_type']) : '';
    $gross_assistant_name = isset($_POST['gross_assistant_name']) ? pg_escape_string($pg_con, $_POST['gross_assistant_name']) : '';
    $gross_doctor_name = isset($_POST['gross_doctor_name']) ? pg_escape_string($pg_con, $_POST['gross_doctor_name']) : '';
    $gross_status = isset($_POST['gross_status']) ? pg_escape_string($pg_con, $_POST['gross_status']) : '';
    $gross_created_user = isset($_POST['gross_created_user']) ? pg_escape_string($pg_con, $_POST['gross_created_user']) : ''; 
    $gross_create_date = isset($_POST['gross_create_date']) ? pg_escape_string($pg_con, $_POST['gross_create_date']) : '';
    $sql = "INSERT INTO llx_gross
            (
            lab_number,
            gross_station_type, 
            gross_assistant_name, 
            gross_doctor_name, 
            gross_status, 
            gross_created_user
            )
            VALUES (
                '$lab_number',
                '$gross_station_type',
                '$gross_assistant_name',
                '$gross_doctor_name ', 
                '$gross_status', 
                '$gross_created_user'
                )";

    $result = pg_query($pg_con, $sql);

    if ($result) {
        echo '<script>';
        echo 'var LabNumber = "' . substr($lab_number, 3) . '";';
        echo 'window.location.href = "gross_specimens.php?LabNumber=" + LabNumber;';
        echo '</script>';
    } else {
        echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
    }
    pg_close($pg_con);
}else{
   header("Location: gross_create.php");
   exit();
}


?>