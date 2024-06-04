<?php

include("connection.php");
include('../grossmodule/gross_common_function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $specimen_ids = isset($_POST['specimen_id']) ? array_map('pg_escape_string', $_POST['specimen_id']) : [];
    $specimens = isset($_POST['specimen']) ? array_map('pg_escape_string', $_POST['specimen']) : [];
    $gross_descriptions = isset($_POST['gross_description']) ? array_map('pg_escape_string', $_POST['gross_description']) : [];

    
    $fk_gross_id = isset($_POST['fk_gross_id'][0]) ? pg_escape_string($_POST['fk_gross_id'][0]) : '';

    if (!empty($fk_gross_id)) {
        for ($i = 0; $i < count($specimen_ids); $i++) {
            $specimen_id = pg_escape_string($pg_con, $specimen_ids[$i]);
            $specimen = pg_escape_string($pg_con, $specimens[$i]);
            $gross_description = pg_escape_string($pg_con, $gross_descriptions[$i]);

            $sql = "UPDATE llx_gross_specimen 
                    SET gross_description = '$gross_description', specimen = '$specimen'
                    WHERE specimen_id = '$specimen_id'";

            $result = pg_query($pg_con, $sql);

            if (!$result) {
                echo "Error updating data: " . pg_last_error($pg_con);
                exit();
            }
        }
        $LabNumber = get_lab_number($fk_gross_id);
        echo '<script>';
        echo 'window.location.href = "transcription.php?lab_number='.$LabNumber.'"'; 
        echo '</script>';
    } else {
        echo "fk_gross_id is empty!";
    }

    pg_close($pg_con); 
} else {
    header("Location: gross_specimens.php");
    exit();
}
?>
