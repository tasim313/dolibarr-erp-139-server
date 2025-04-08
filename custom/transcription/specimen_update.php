<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Print POST data
    echo "<pre>POST Data:\n";
    print_r($_POST);
    echo "</pre>";

    $specimen_rowids = $_POST['specimen_rowid'] ?? [];
    $new_descriptions = $_POST['new_description'] ?? [];

    if (empty($specimen_rowids) || empty($new_descriptions) || count($specimen_rowids) !== count($new_descriptions)) {
        echo json_encode(['error' => 'Invalid specimen_rowid or new_description']);
        exit;
    }

    // Ensure database connection
    if (!$pg_con) {
        die(json_encode(['error' => 'Database connection failed: ' . pg_last_error()]));
    }

    foreach ($specimen_rowids as $index => $commandedet_rowid) {
        $new_specimen_name = $new_descriptions[$index];

        // Debug: Print current specimen_rowid and new_specimen_name
        echo "Processing specimen_rowid: $commandedet_rowid, new_specimen_name: $new_specimen_name\n";

        // Step 1: Verify if the commandedet_rowid exists
        $query_check = "SELECT rowid FROM llx_commandedet WHERE rowid = $1";
        $result_check = pg_query_params($pg_con, $query_check, [$commandedet_rowid]);

        if (!$result_check || pg_num_rows($result_check) == 0) {
            echo json_encode(['error' => "commandedet_rowid $commandedet_rowid not found in llx_commandedet"]);
            continue;
        }

        // Step 2: Get the reference (ref) from llx_commande using llx_commandedet.rowid
        $query_ref = "SELECT c.ref 
                      FROM llx_commandedet cd
                      JOIN llx_commande c ON cd.fk_commande = c.rowid
                      WHERE cd.rowid = $1";
        $result_ref = pg_query_params($pg_con, $query_ref, [$commandedet_rowid]);

        if (!$result_ref || pg_num_rows($result_ref) == 0) {
            echo json_encode(['error' => "No reference found for commandedet_rowid: $commandedet_rowid"]);
            continue;
        }

        $ref_row = pg_fetch_assoc($result_ref);
        $ref = $ref_row['ref'];

        // Debug: Print ref value
        echo "Ref value: $ref\n";

        // Step 3: Add prefix "HPL" to the ref value
        $prefixed_ref = "HPL" . $ref;

        // Debug: Print prefixed ref value
        echo "Prefixed ref value: $prefixed_ref\n";

        // Step 4: Get lab_number from llx_gross using the prefixed reference
        $query_lab_number = "SELECT lab_number, gross_id FROM llx_gross WHERE lab_number = $1";
        $result_lab = pg_query_params($pg_con, $query_lab_number, [$prefixed_ref]);

        if (!$result_lab || pg_num_rows($result_lab) == 0) {
            echo json_encode(['error' => "No lab_number found for prefixed reference: $prefixed_ref"]);
            continue;
        }

        $lab_row = pg_fetch_assoc($result_lab);
        $lab_number = $lab_row['lab_number'];
        $gross_id = $lab_row['gross_id'];

        // Debug: Print lab_number and gross_id
        echo "Lab Number: $lab_number, Gross ID: $gross_id\n";

        // Step 5: Fetch the current specimen name from llx_commandedet
        $query_current_specimen = "SELECT description FROM llx_commandedet WHERE rowid = $1";
        $result_current_specimen = pg_query_params($pg_con, $query_current_specimen, [$commandedet_rowid]);

        if (!$result_current_specimen || pg_num_rows($result_current_specimen) == 0) {
            echo json_encode(['error' => "No specimen found for commandedet_rowid: $commandedet_rowid"]);
            continue;
        }

        $current_specimen_row = pg_fetch_assoc($result_current_specimen);
        $current_specimen_name = $current_specimen_row['description'];

        // Debug: Print current specimen name
        echo "Current Specimen Name: $current_specimen_name\n";

        // Step 6: Update llx_gross_specimen
        $query_update_gross_specimen = "UPDATE llx_gross_specimen 
                                       SET specimen = $1 
                                       WHERE fk_gross_id = $2 AND specimen = $3";
        $result_update_gross = pg_query_params($pg_con, $query_update_gross_specimen, [
            $new_specimen_name, $gross_id, $current_specimen_name
        ]);

        // Debug: Check if update was successful
        if (!$result_update_gross) {
            echo "Error updating llx_gross_specimen: " . pg_last_error($pg_con) . "\n";
        } else {
            echo "Updated llx_gross_specimen successfully. Rows affected: " . pg_affected_rows($result_update_gross) . "\n";
        }

        // Step 7: Update llx_micro
        $query_update_micro = "UPDATE llx_micro 
                               SET specimen = $1 
                               WHERE lab_number = $2 AND specimen = $3";
        $result_update_micro = pg_query_params($pg_con, $query_update_micro, [
            $new_specimen_name, $lab_number, $current_specimen_name
        ]);

        // Debug: Check if update was successful
        if (!$result_update_micro) {
            echo "Error updating llx_micro: " . pg_last_error($pg_con) . "\n";
        } else {
            echo "Updated llx_micro successfully. Rows affected: " . pg_affected_rows($result_update_micro) . "\n";
        }

        // Step 8: Update llx_diagnosis
        $query_update_diagnosis = "UPDATE llx_diagnosis 
                                  SET specimen = $1 
                                  WHERE lab_number = $2 AND specimen = $3";
        $result_update_diagnosis = pg_query_params($pg_con, $query_update_diagnosis, [
            $new_specimen_name, $lab_number, $current_specimen_name
        ]);

        // Debug: Check if update was successful
        if (!$result_update_diagnosis) {
            echo "Error updating llx_diagnosis: " . pg_last_error($pg_con) . "\n";
        } else {
            echo "Updated llx_diagnosis successfully. Rows affected: " . pg_affected_rows($result_update_diagnosis) . "\n";
        }

        // Step 9: Update llx_commandedet description
        $query_update_commandedet = "UPDATE llx_commandedet 
                                    SET description = $1 
                                    WHERE rowid = $2";
        $result_update_commandedet = pg_query_params($pg_con, $query_update_commandedet, [
            $new_specimen_name, $commandedet_rowid
        ]);

        // Debug: Check if update was successful
        if (!$result_update_commandedet) {
            echo "Error updating llx_commandedet: " . pg_last_error($pg_con) . "\n";
        } else {
            echo "Updated llx_commandedet successfully. Rows affected: " . pg_affected_rows($result_update_commandedet) . "\n";
        }

        if ($result_update_gross && $result_update_micro && $result_update_diagnosis && $result_update_commandedet) {
            // echo json_encode(['success' => "Updated specimen_name for lab_number: $lab_number with prefixed ref: $prefixed_ref"]);
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            echo json_encode(['error' => "Failed to update specimen_name for lab_number: $lab_number"]);
        }
    }

    pg_close($pg_con);
}
?>