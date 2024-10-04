<?php

include('connection.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['loggedInUserId']) && is_array($input['values'])) {
        $loggedInUserId = $input['loggedInUserId'];
        $statusChanges = $input['values'];

        foreach ($statusChanges as $trackId => $data) {
            $labNumber = $data['labNumber'];
            $status = $data['status'];
            $labRoomStatus = ''; 
            
            switch ($status) {
                case 'In-Progress':
                    $labRoomStatus = 'in_progress';
                    break;
                case 'On-Hold':
                    $labRoomStatus = 'on-hold';
                    break;
                default:
                    continue; 
            }

            if ($labRoomStatus) {
                // Check if the row already exists
                $checkQuery = pg_prepare($pg_con, "check_query", "SELECT id FROM llx_commande_trackws WHERE id = $1 AND labno = $2");
                $checkResult = pg_execute($pg_con, "check_query", array($trackId, $labNumber));

                if (pg_num_rows($checkResult) > 0) {
                    // Update the existing record
                    $updateQuery = pg_prepare($pg_con, "update_query", "UPDATE llx_commande_trackws SET lab_room_status = $1 WHERE id = $2");
                    $updateResult = pg_execute($pg_con, "update_query", array($labRoomStatus, $trackId));

                    if (!$updateResult) {
                        echo json_encode(["success" => false, "message" => "Error updating data: " . pg_last_error($pg_con)]);
                        exit;
                    }
                } else {
                    // Insert a new record if not exists
                    $insertQuery = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (id, labno, user_id, lab_room_status) VALUES ($1, $2, $3, $4)");
                    $insertResult = pg_execute($pg_con, "insert_query", array($trackId, $labNumber, $loggedInUserId, $labRoomStatus));

                    if (!$insertResult) {
                        echo json_encode(["success" => false, "message" => "Error inserting data: " . pg_last_error($pg_con)]);
                        exit;
                    }
                }
            }
        }

        echo json_encode(["success" => true, "message" => "Data processed successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: Missing or invalid data."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}

?>                            