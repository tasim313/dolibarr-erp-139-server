<?php 
include('../connection.php');
include ("function.php");

// Get the lab number from the request
if (isset($_GET['lab_number'])) {
    $lab_number = $_GET['lab_number'];
    $status = diagonsis_micro_complete_by_lab($lab_number);

    // Return the result
    echo $status;
}

?>