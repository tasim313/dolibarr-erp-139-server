<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number']; 
    $description = $_POST['description'];
    $created_user = $_POST['created_user'];

    $sql = "INSERT INTO llx_mfc (lab_number, description, created_user, created_date) 
            VALUES ('$lab_number', '$description', '$created_user', NOW())";

    $result = pg_query($pg_con, $sql);
    
    if ($result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Error: " . pg_last_error($pg_con);
    }
}
?>