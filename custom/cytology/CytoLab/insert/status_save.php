<?php
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number'] ?? '';
    $status =  'complete';

    if (!empty($lab_number) && !empty($status)) {
        // Begin a transaction to ensure both updates are executed atomically
        pg_query($pg_con, "BEGIN");

        // Step 1: Update the status in llx_cyto_lab_instruction_status table
        $query = "
        INSERT INTO llx_cyto_lab_instruction_status (lab_number, status)
        VALUES ($1, $2)
        ON CONFLICT (lab_number)
        DO UPDATE SET status = EXCLUDED.status;
        ";
        $stmt = pg_prepare($pg_con, "save_status", $query);
        $result = pg_execute($pg_con, "save_status", [$lab_number, $status]);

        if ($result) {
            // Step 2: Update the status_list with the new entry if the status is 'complete'
            $current_time = date('d F, Y h:i A'); // Get the current timestamp in the required format
            $status_list_query = "
            UPDATE llx_cyto_lab_instruction_status
            SET status_list = COALESCE(status_list::JSONB, '[]'::JSONB) || $1
            WHERE lab_number = $2;
            ";
            $status_list_entry = json_encode([
                'status' => 'complete',
                'timestamp' => $current_time
            ]);

            $status_list_stmt = pg_prepare($pg_con, "update_status_list", $status_list_query);
            $status_list_result = pg_execute($pg_con, "update_status_list", [$status_list_entry, $lab_number]);

            if ($status_list_result) {
                // Commit the transaction if both operations succeed
                pg_query($pg_con, "COMMIT");
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                // Rollback the transaction if updating status_list fails
                pg_query($pg_con, "ROLLBACK");
                echo "Error updating status_list: " . pg_last_error($pg_con);
            }
        } else {
            // Rollback the transaction if updating status fails
            pg_query($pg_con, "ROLLBACK");
            echo "Error updating status: " . pg_last_error($pg_con);
        }
    } else {
        echo "Invalid input.";
    }
} else {
    echo "Invalid request method.";
}


?>