<?php
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $loggedInUserId = $_POST['loggedInUserId'];

    // Loop through the submitted status array
    foreach ($_POST['status'] as $labno => $status) {
        // Ensure that a status has been selected
        if (!empty($status)) {
            // Prepare SQL query to insert the data
            $sql = "INSERT INTO llx_commande_trackws (labno, fk_status_id, user_id) 
                    VALUES ($1, $2, $3)
                   ";

            // Prepare and execute the query
            $result = pg_prepare($pg_con, "insert_status", $sql);
            $result = pg_execute($pg_con, "insert_status", array($labno, $status, $loggedInUserId));

            // Check if the query was successful
            if (!$result) {
                echo "Error inserting status for lab number " . htmlspecialchars($labno) . ": " . pg_last_error($pg_con);
            }
        }
    }
    // Redirect back and reload the page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit; // Stop further execution
}

?>