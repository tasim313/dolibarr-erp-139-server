<?php
include("../../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve POST data with validation
    $lab_number = $_POST['lab_number'] ?? null;
    $new_diagnosis = $_POST['diagnosis'] ?? null;
    $loggedInUsername = $_POST['user'] ?? null; // Retrieve logged-in username

    // Basic validation
    if (!$loggedInUsername) {
        echo "Error: User information is missing. Please log in again.";
        exit;
    }
    if (!$lab_number || !$new_diagnosis) {
        echo "Error: Both Lab number and new diagnosis are required.";
        exit;
    }

    try {
        // Debugging: Print the lab number to verify it's correctly received
        echo "Lab Number: " . $lab_number . "<br>";

        // Fetch existing diagnosis data using pg_query
        $query = "SELECT diagnosis, previous_diagnosis, created_user FROM llx_cyto_doctor_diagnosis WHERE lab_number = '$lab_number'";
        echo "Query: " . $query . "<br>";  // Debugging: print the query
        $result = pg_query($pg_con, $query);

        if ($result && pg_num_rows($result) > 0) {
            // If lab number exists, fetch the row
            $row = pg_fetch_assoc($result);
            print_r($row);  // Debugging: print the result row

            // Process previous and current diagnosis
            $current_diagnosis = $row['diagnosis'];
            $previous_diagnosis = $row['previous_diagnosis'] ? json_decode($row['previous_diagnosis'], true) : [];
            $created_user = $row['created_user'];

            if ($current_diagnosis) {
                $previous_diagnosis[] = [
                    'previous' => $current_diagnosis,
                    'Date' => date('j F, Y h:i A'),
                    'created_user' => $created_user,
                    'updated_user' => $loggedInUsername,
                ];
            }

            // Update diagnosis using pg_query
            $update_query = "
                UPDATE llx_cyto_doctor_diagnosis
                SET 
                    diagnosis = '$new_diagnosis',
                    previous_diagnosis = '" . pg_escape_string(json_encode($previous_diagnosis)) . "',
                    updated_user = '$loggedInUsername',
                    updated_date = NOW()
                WHERE lab_number = '$lab_number'";
            
            echo "Update Query: " . $update_query . "<br>";  // Debugging: print the update query
            $update_result = pg_query($pg_con, $update_query);

            if ($update_result) {
                $referer = $_SERVER['HTTP_REFERER'];
                header("Location: $referer");
            } else {
                echo "Error: Failed to update the diagnosis.";
            }
        } else {
            // If lab number doesn't exist, insert a new record
            echo "Lab number not found, inserting a new record...<br>";

            // Prepare data for insertion
            $previous_diagnosis = json_encode([
                [
                    'previous' => $new_diagnosis,
                    'Date' => date('j F, Y h:i A'),
                    'created_user' => $loggedInUsername, // You can change this to the logged-in user if needed
                    'updated_user' => $loggedInUsername,
                ]
            ]);

            // Insert query
            $insert_query = "
                INSERT INTO llx_cyto_doctor_diagnosis (lab_number, diagnosis, previous_diagnosis, created_user, updated_user, created_date)
                VALUES ('$lab_number', '$new_diagnosis', '" . pg_escape_string($previous_diagnosis) . "', '$loggedInUsername', '$loggedInUsername', NOW())";
            
            echo "Insert Query: " . $insert_query . "<br>";  // Debugging: print the insert query
            $insert_result = pg_query($pg_con, $insert_query);

            if ($insert_result) {
                $referer = $_SERVER['HTTP_REFERER'];
                header("Location: $referer");
            } else {
                echo "Error: Failed to insert the new diagnosis.";
            }
        }
    } catch (Exception $e) {
        // General error handling
        echo "An error occurred: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Error: Invalid request method.";
}
?>