<?php

include('connection.php');  // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input) {
        $LabNumber = isset($input['labNumber']) ? $input['labNumber'] : '';
        $loggedInUserId = isset($input['loggedInUserId']) ? $input['loggedInUserId'] : '';
        $selectedDatetime = isset($input['selectedDatetime']) ? $input['selectedDatetime'] : '';
        
        if ($LabNumber && $loggedInUserId && $selectedDatetime) {
            $fk_status_id = 69;

            // Treat selectedDatetime as local (Asia/Dhaka) time directly
            try {
                $datetime = new DateTime($selectedDatetime, new DateTimeZone('Asia/Dhaka'));
                $formattedDatetime = $datetime->format('F j, Y g:i A'); 
            } catch (Exception $e) {
                echo "Error formatting datetime: " . $e->getMessage();
                exit;
            }

            $description = "Final Report available on $formattedDatetime";

            $stmt = pg_prepare($pg_con, "insert_query", "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id, description) VALUES ($1, $2, $3, $4)");
            $result = pg_execute($pg_con, "insert_query", array($LabNumber, $loggedInUserId, $fk_status_id, $description));

            if (!$result) {
                echo "Error inserting data : " . pg_last_error($pg_con);
            } else {
                echo "Data inserted successfully.";
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