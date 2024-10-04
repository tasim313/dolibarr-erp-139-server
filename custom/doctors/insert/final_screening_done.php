<?php

include('connection.php');  // Ensure this path is correct based on your file structure

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input) {
        $LabNumber = isset($input['labNumber']) ? $input['labNumber'] : '';
        $loggedInUserId = isset($input['loggedInUserId']) ? $input['loggedInUserId'] : '';
        
        if ($LabNumber && $loggedInUserId) {
            $description = ''; // Adjust this if you need a specific description

            // Prepare the first insert statement
            $stmt1 = pg_prepare($pg_con, "insert_query1", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
            $result1 = pg_execute($pg_con, "insert_query1", array($LabNumber, $loggedInUserId, 15, $description));

            // Prepare the second insert statement
            $stmt2 = pg_prepare($pg_con, "insert_query2", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
            $result2 = pg_execute($pg_con, "insert_query2", array($LabNumber, $loggedInUserId, 10, $description));

            // Check if both insertions were successful
            if ($result1 && $result2) {
                echo "Data inserted successfully for both status IDs.";
            } else {
                echo "Error inserting data: " . pg_last_error($pg_con);
            }
        } else {
            echo "Error: Missing or invalid data.";
        }
    } else {
        echo "Error: Invalid input.";
    }
} else {
    echo "Invalid request method.";
}

?>