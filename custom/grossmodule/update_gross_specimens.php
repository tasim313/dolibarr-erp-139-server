<?php

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $specimen_ids = isset($_POST['specimen_id']) ? array_map('pg_escape_string', $_POST['specimen_id']) : [];
    $specimens = isset($_POST['specimen']) ? array_map('pg_escape_string', $_POST['specimen']) : [];
    $gross_descriptions = isset($_POST['gross_description']) ? array_map('pg_escape_string', $_POST['gross_description']) : [];

    
    $fk_gross_id = isset($_POST['fk_gross_id'][0]) ? pg_escape_string($_POST['fk_gross_id'][0]) : '';

    if (!empty($fk_gross_id)) {
        for ($i = 0; $i < count($specimen_ids); $i++) {
            $sql = "UPDATE llx_gross_specimen 
                    SET gross_description = '{$gross_descriptions[$i]}'
                    WHERE specimen_id = '{$specimen_ids[$i]}'";

            $result = pg_query($pg_con, $sql);

            if (!$result) {
                echo "Error updating data: " . pg_last_error($pg_con);
                exit();
            }
        }

        echo '<script>';
        echo 'window.location.href = "gross_update.php?fk_gross_id=' . $fk_gross_id . '";'; 
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
