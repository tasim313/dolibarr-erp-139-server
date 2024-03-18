<?php 

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $specimens = isset($_POST['specimen']) ? array_map('pg_escape_string', $_POST['specimen']) : [];
    $gross_descriptions = isset($_POST['gross_description']) ? array_map('pg_escape_string', $_POST['gross_description']) : [];
    $fk_gross_id = isset($_POST['fk_gross_id']) ? array_map('pg_escape_string', $_POST['fk_gross_id']) : [];

    
    for ($i = 0; $i < count($specimens); $i++) {
        $sql = "INSERT INTO llx_gross_specimen (fk_gross_id, specimen, gross_description)
                VALUES ('{$fk_gross_id[$i]}', '{$specimens[$i]}', '{$gross_descriptions[$i]}')";

        $result = pg_query($pg_con, $sql);

        if (!$result) {
            
            echo "Error inserting data: " . pg_last_error($pg_con);
            exit();
        }
    }

    
    echo '<script>';
    echo 'window.location.href = "gross_specimen_section.php?fk_gross_id=' . $fk_gross_id[0] . '";'; 
    echo '</script>';
    pg_close($pg_con); 

} else {
    
    header("Location: gross_specimens.php");
    exit();
}





?>
