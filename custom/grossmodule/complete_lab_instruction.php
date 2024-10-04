<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['loggedInUserId']) && isset($input['statusChanges']) && is_array($input['statusChanges'])) {
        $loggedInUserId = $input['loggedInUserId'];
        $statusChanges = $input['statusChanges'];

        $success = true;
        $message = '';

        foreach ($statusChanges as $change) {
            $labNumber = $change['labNumber'];
            $statusId = $change['status'];
            $trackId = $change['trackId'];

            // Log the data to check if trackId is received
            error_log("Track ID received: " . $trackId);

            if ($statusId) {
                // Update lab_room_status to 'done' based on track_id
                if ($trackId) {
                    $updateStmt = pg_prepare($pg_con, "update_query", "UPDATE llx_commande_trackws SET lab_room_status = 'done' WHERE id = $1");
                    $updateResult = pg_execute($pg_con, "update_query", array($trackId));
                    
                    if (!$updateResult) {
                        error_log("Error executing update query: " . pg_last_error($pg_con));
                        $success = false;
                        $message = "Failed to update lab_room_status for Track ID: " . $trackId;
                        break;
                    }
                }

                // Insert new record into llx_commande_trackws
                $insertStmt = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, '')");
                $insertResult = pg_execute($pg_con, "insert_query", array($labNumber, $loggedInUserId, $statusId));

                if (!$insertResult) {
                    error_log("Error executing insert query: " . pg_last_error($pg_con));
                    $success = false;
                    $message = "Failed to save status for Lab Number: " . $labNumber;
                    break;
                }
            }
        }

        if ($success) {
            echo json_encode(array("success" => true, "message" => "Status changes saved successfully."));
        } else {
            echo json_encode(array("success" => false, "message" => $message));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid request data."));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Invalid request method."));
}
?>