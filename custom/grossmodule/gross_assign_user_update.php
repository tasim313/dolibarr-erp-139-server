<?php 

include("connection.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assign_id = isset($_POST['assign_id']) ? pg_escape_string($pg_con, $_POST['assign_id']) : '';
    $gross_assistant_name = isset($_POST['gross_assistant_name']) ? pg_escape_string($pg_con, $_POST['gross_assistant_name']) : '';
    $gross_doctor_name = isset($_POST['gross_doctor_name']) ? pg_escape_string($pg_con, $_POST['gross_doctor_name']) : '';
    $gross_assign_updated_user = isset($_POST['gross_assign_updated_user']) ? pg_escape_string($pg_con, $_POST['gross_assign_updated_user']) : ''; 
    
    
    if (!empty($assign_id)) {
        $sql = "UPDATE llx_gross_assign
                SET 
                gross_assistant_name = '$gross_assistant_name',
                gross_doctor_name = '$gross_doctor_name', 
                gross_assign_updated_user = '$gross_assign_updated_user'
                WHERE assign_id = '$assign_id'";

        $result = pg_query($pg_con, $sql);

        

        if ($result) {
            echo '<script>';
            echo 'window.location.href = "gross_assign.php"';
            echo '</script>';
        } else {
            echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
        }
    } else {
        echo "Error: Missing assign_id";
    }
    pg_close($pg_con);
} else {
    header("Location: gross_assign.php");
    exit();
}


?>