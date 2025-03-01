<?php

include('connection.php'); // Ensure correct path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use $_POST instead of json_decode(file_get_contents('php://input'))
    $LabNumber = isset($_POST['lab_number']) ? $_POST['lab_number'] : '';
    $loggedInUserId = isset($_POST['user_id']) ? $_POST['user_id'] : ''; 

    if ($LabNumber && $loggedInUserId) {
        $fk_status_id = 53;
        $description = '';

        $stmt = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
        $result = pg_execute($pg_con, "insert_query", array($LabNumber, $loggedInUserId, $fk_status_id, $description));

        if (!$result) {
            echo "Error inserting data: " . pg_last_error($pg_con);
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }
    } else {
        echo "Error: Missing or invalid data.";
    }
} else {
    echo "Invalid request method.";
}


?>