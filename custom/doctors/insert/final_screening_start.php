<?php

include('connection.php');  // Ensure this path is correct based on your file structure

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input) {
        $LabNumber = isset($input['labNumber']) ? $input['labNumber'] : '';
        $loggedInUserId = isset($input['loggedInUserId']) ? $input['loggedInUserId'] : '';
        
        if ($LabNumber && $loggedInUserId) {

            // Step 1: Check if screening with fk_status_id 46 exists
            $check_sql = "SELECT 1 FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = '46' LIMIT 1";
            $check_result = pg_query_params($pg_con, $check_sql, array($LabNumber));

            if ($check_result && pg_num_rows($check_result) > 0) {
                // Screening exists, allow insertion with fk_status_id 47
                $fk_status_id = 47;
                $description = '';

                $insert_sql = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)";
                $insert_result = pg_query_params($pg_con, $insert_sql, array($LabNumber, $loggedInUserId, $fk_status_id, $description));

                if ($insert_result) {
                    echo json_encode(["status" => "inserted", "message" => "Data inserted successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Error inserting data: " . pg_last_error($pg_con)]);
                }

            } else {
                // Screening does not exist
                echo json_encode(["status" => "not_found", "message" => "Please Screening First"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Missing or invalid data."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid input."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

?>