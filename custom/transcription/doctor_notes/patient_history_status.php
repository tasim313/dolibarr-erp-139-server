<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['loggedInUserId']) && is_array($input['values'])) {
        $loggedInUserId = $input['loggedInUserId'];
        $statusChanges = $input['values'];

        // Communication to fk_status_id mapping
        $communicationMap = [
            'Whatsapp' => 72,
            'By Call' => 73,
            'Message' => 74,
            'Face-to-face conversations' => 75
        ];

        foreach ($statusChanges as $trackId => $data) {
            $labNumber = $data['labNumber'];
            $status = $data['status'];
            $username = $data['username'];
            $communication = $data['communication'] ?? null;
            $labRoomStatus = '';

            switch ($status) {
                case 'In-Progress':
                    $labRoomStatus = 'in_progress';
                    break;
                case 'On-Hold':
                    $labRoomStatus = 'on-hold';
                    break;
                case 'Done':
                    $labRoomStatus = 'done';
                    break;
                default:
                    continue;
            }

            if ($labRoomStatus && $username) {
                // Fetch existing status_update_user
                $selectQuery = pg_prepare($pg_con, "select_user_$trackId", "SELECT status_update_user FROM llx_commande_trackws WHERE id = $1 AND labno = $2");
                $selectResult = pg_execute($pg_con, "select_user_$trackId", array($trackId, $labNumber));

                $statusHistory = [];

                if ($row = pg_fetch_assoc($selectResult)) {
                    if (!empty($row['status_update_user'])) {
                        $statusHistory = json_decode($row['status_update_user'], true);
                    }
                }

                // Add new update to status history
                array_unshift($statusHistory, [ $labRoomStatus => $username ]);
                $statusUpdateUserJson = json_encode($statusHistory);

                // Update lab_room_status and status_update_user
                $updateQuery = pg_prepare($pg_con, "update_query_$trackId", "UPDATE llx_commande_trackws SET lab_room_status = $1, status_update_user = $2 WHERE id = $3");
                $updateResult = pg_execute($pg_con, "update_query_$trackId", array($labRoomStatus, $statusUpdateUserJson, $trackId));

                if (!$updateResult) {
                    echo json_encode(["success" => false, "message" => "Error updating data: " . pg_last_error($pg_con)]);
                    exit;
                }
            }

            // Handle communication insert
            if ($communication && isset($communicationMap[$communication])) {
                $fkStatusId = $communicationMap[$communication];

                // Prepare insert for communication log
                $insertQuery = pg_prepare($pg_con, "insert_comm_$trackId", "
                    INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id)
                    VALUES ($1, $2, $3)
                ");
                $insertResult = pg_execute($pg_con, "insert_comm_$trackId", array($labNumber, $loggedInUserId, $fkStatusId));

                if (!$insertResult) {
                    echo json_encode(["success" => false, "message" => "Error inserting communication: " . pg_last_error($pg_con)]);
                    exit;
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