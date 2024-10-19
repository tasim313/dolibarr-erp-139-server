<?php
include('connection.php');

function get_gross_instance($LabNumber) {
    global $pg_con;

    // Prepare the SQL query
    $sql = "SELECT gross_id FROM llx_gross WHERE lab_number = 'HPL' || '$LabNumber' AND gross_id IS NOT NULL";

    // Log the SQL query to debug
    error_log("SQL Query: " . $sql); // Log the SQL query for debugging

    // Execute the query
    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result); // Fetch only one row

        if ($row && isset($row['gross_id'])) {
            pg_free_result($result); // Free the result memory
            return $row['gross_id']; // Return the gross_id directly
        } else {
            error_log("No gross_id found for lab_number: " . $LabNumber); // Log if no result is found
            return null; // Return null if no result
        }
    } else {
        error_log("SQL Error: " . pg_last_error($pg_con)); // Log any SQL errors
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['loggedInUserId']) && isset($input['statusChanges']) && is_array($input['statusChanges'])) {
        $loggedInUserId = $input['loggedInUserId'];
        $statusChanges = $input['statusChanges'];

        $success = true;
        $message = '';
        $fk_gross_id = null; // Placeholder for fk_gross_id

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

                // Concatenate 'HPL' with the lab number before calling get_gross_instance
                $lab_number_for_gross_id = 'HPL' . $labNumber;
                // Fetch the gross_id for the current lab number
                $fk_gross_id = get_gross_instance($labNumber);

                // Debug the result to see the returned gross_id
                error_log("Fetched fk_gross_id: " . $fk_gross_id);


                if (!$fk_gross_id) {
                    $success = false;
                    $message = "Failed to fetch fk_gross_id for Lab Number: " .$labNumber;
                    break;
                }

            }
        }

        if ($success) {
            echo json_encode(array("success" => true, "message" => "Status changes saved successfully.", "fk_gross_id" => $fk_gross_id));
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