<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect the POST data
    $labNumber = htmlspecialchars($_POST['lab_number']);
    $doctorName = htmlspecialchars($_POST['doctor_name']);
    $grossAssistantName = htmlspecialchars($_POST['gross_assistant_name']);
    $grossStationType = htmlspecialchars($_POST['gross_station_type']);

    // Insert query
    $sql = "INSERT INTO llx_re_gross (lab_number, doctor_name, gross_assistant_name, gross_station) 
            VALUES ($1, $2, $3, $4)";

    // Prepare and execute the query (PostgreSQL)
    $result = pg_prepare($pg_con, "insert_query", $sql);
    $result = pg_execute($pg_con, "insert_query", array($labNumber, $doctorName, $grossAssistantName, $grossStationType));

    // Check for successful execution and redirect to the previous page
    if ($result) {
        // Redirect to the previous page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit(); // Make sure to exit after redirection to avoid further code execution
    } else {
        echo "Error: Unable to insert data. " . pg_last_error($pg_con);
    }
}

?>