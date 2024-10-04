<?php

include('connection.php');  // Ensure this path is correct based on your file structure

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input) {
        $LabNumber = isset($input['labNumber']) ? $input['labNumber'] : '';
        $loggedInUserId = isset($input['loggedInUserId']) ? $input['loggedInUserId'] : '';
        
        if ($LabNumber && $loggedInUserId) {
            $fk_status_id = 14;
            $description = '';

                
            $stmt = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
            $result = pg_execute($pg_con, "insert_query", array($LabNumber, $loggedInUserId, $fk_status_id, $description));

            if (!$result) {
                echo "Error inserting data : " . pg_last_error($pg_con);
            }
                
            echo "Data inserted successfully.";
        } else {
            echo "Error: Missing or invalid data .";
           
        }
    } else {
        echo "Error: Invalid input.";
    }
} else {
    echo "Invalid request method ";
}



?>