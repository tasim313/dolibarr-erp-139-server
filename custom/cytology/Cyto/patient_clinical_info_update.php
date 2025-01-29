<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = $_POST['rowid'] ?? null; 
    $chief_complain = $_POST['chief_complain'] ?? null; 
    $relevant_clinical_history = $_POST['relevant_clinical_history'] ?? null;
    $on_examination = $_POST['on_examination'] ?? null;
    $clinical_impression = $_POST['clinical_impression'] ?? null;
    $updated_user = $_POST['username'] ?? 'Unknown';
    $update_date = date('Y-m-d H:i:s');

    if ($rowid === null) {
        echo "Error: Missing rowid";
        exit();
    }

    // Fetch old values
    $fetch_sql = "SELECT chief_complain, relevant_clinical_history, on_examination, clinical_impression,
                         previous_chief_complain, previous_history, previous_on_examination, previous_clinical_impression
                  FROM llx_cyto_clinical_information WHERE rowid = $1";

    $fetch_stmt = pg_prepare($pg_con, "fetch_old_values", $fetch_sql);
    $fetch_result = pg_execute($pg_con, "fetch_old_values", array($rowid));

    if ($fetch_result) {
        $old_data = pg_fetch_assoc($fetch_result);
    } else {
        echo "Error fetching old data";
        exit();
    }

    // Prepare dynamic SQL update statement
    $update_fields = [];
    $update_values = [];
    $jsonb_updates = [];

    if ($chief_complain !== null && $chief_complain !== $old_data['chief_complain']) {
        $update_fields[] = "chief_complain = $" . (count($update_values) + 1);
        $update_values[] = $chief_complain;
        $jsonb_updates[] = "previous_chief_complain = COALESCE(previous_chief_complain, '[]'::jsonb) || $" . (count($update_values) + 1) . "::jsonb";
        $update_values[] = json_encode([
            'value' => $old_data['chief_complain'],
            'update_date' => $update_date,
            'updated_user' => $updated_user
        ]);
    }

    if ($relevant_clinical_history !== null && $relevant_clinical_history !== $old_data['relevant_clinical_history']) {
        $update_fields[] = "relevant_clinical_history = $" . (count($update_values) + 1);
        $update_values[] = $relevant_clinical_history;
        $jsonb_updates[] = "previous_history = COALESCE(previous_history, '[]'::jsonb) || $" . (count($update_values) + 1) . "::jsonb";
        $update_values[] = json_encode([
            'value' => $old_data['relevant_clinical_history'],
            'update_date' => $update_date,
            'updated_user' => $updated_user
        ]);
    }

    if ($on_examination !== null && $on_examination !== $old_data['on_examination']) {
        $update_fields[] = "on_examination = $" . (count($update_values) + 1);
        $update_values[] = $on_examination;
        $jsonb_updates[] = "previous_on_examination = COALESCE(previous_on_examination, '[]'::jsonb) || $" . (count($update_values) + 1) . "::jsonb";
        $update_values[] = json_encode([
            'value' => $old_data['on_examination'],
            'update_date' => $update_date,
            'updated_user' => $updated_user
        ]);
    }

    if ($clinical_impression !== null && $clinical_impression !== $old_data['clinical_impression']) {
        $update_fields[] = "clinical_impression = $" . (count($update_values) + 1);
        $update_values[] = $clinical_impression;
        $jsonb_updates[] = "previous_clinical_impression = COALESCE(previous_clinical_impression, '[]'::jsonb) || $" . (count($update_values) + 1) . "::jsonb";
        $update_values[] = json_encode([
            'value' => $old_data['clinical_impression'],
            'update_date' => $update_date,
            'updated_user' => $updated_user
        ]);
    }

    if (empty($update_fields)) {
        echo "No changes detected.";
        exit();
    }

    // Combine update fields
    $update_sql = "UPDATE llx_cyto_clinical_information SET " . implode(", ", array_merge($update_fields, $jsonb_updates)) . " WHERE rowid = $" . (count($update_values) + 1);
    $update_values[] = $rowid;

    try {
        $stmt = pg_prepare($pg_con, "update_clinical_info", $update_sql);
        $result = pg_execute($pg_con, "update_clinical_info", $update_values);

        if ($result) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            echo "Error updating data";
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>

