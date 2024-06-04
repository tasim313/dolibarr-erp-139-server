<?php

include("connection.php");
include('../grossmodule/gross_common_function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fk_gross_id = isset($_POST['fk_gross_id']) ? pg_escape_string($pg_con, $_POST['fk_gross_id']) : '';
    $summary = isset($_POST['summary']) ? pg_escape_string($pg_con, $_POST['summary']) : '';
    $ink_code = isset($_POST['ink_code']) ? pg_escape_string($pg_con, $_POST['ink_code']) : '';
   
    $gross_summary_id = isset($_POST['gross_summary_id']) ? pg_escape_string($pg_con, $_POST['gross_summary_id']) : '';
    $LabNumber = get_lab_number($fk_gross_id);

    // Build the SQL query based on the provided data
    $sql = "UPDATE llx_gross_summary_of_section SET";
    $update_values = array();
    
    if (!empty($summary)) {
        $update_values[] = "summary = '$summary'";
    }
    if (!empty($ink_code)) {
        $update_values[] = "ink_code = '$ink_code'";
    }
    
    // Check if any update values are provided
    if (!empty($update_values)) {
        $sql .= " " . implode(", ", $update_values) . " WHERE gross_summary_id = '$gross_summary_id'";
        // Execute the SQL query
        $result = pg_query($pg_con, $sql);

        if ($result) {
            echo '<script>window.location.href = "transcription.php?lab_number='.$LabNumber.'";</script>';
            exit(); 
        } else {
            echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
        }
    } else {
        echo "Error: No update values provided.";
    }

    pg_close($pg_con);
} else {
    header("Location: list.php");
    exit();
}


?>

