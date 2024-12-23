<?php
include("../../connection.php");
$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/transcription/FNA/index.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user input to prevent SQL injection
    $lab_number = pg_escape_string($pg_con, $_POST['lab_number'] ?? '');
    $doctor_username = pg_escape_string($pg_con, $_POST['doctor_username'] ?? '');
    $created_user = pg_escape_string($pg_con, $_POST['created_user'] ?? '');

    // Prepare the SQL query for inserting data
    $sql = "INSERT INTO llx_doctor_assisted_by_signature
    (
        lab_number,
        doctor_username,
        created_user
    )
    VALUES (
        '$lab_number',
        '$doctor_username',
        '$created_user'
    ) RETURNING row_id";

    // Execute the query
    $result = pg_query($pg_con, $sql);

    if ($result) {
        // Redirect to the previous page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "Error: " . pg_last_error($pg_con);
    }

    // Close the connection
    pg_close($pg_con);
}
?>