<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_number = $_POST['lab_number'];
    $description = $_POST['description'];
    $updated_user = $_POST['updated_user'];

    // Fetch current data (including old description, created user, created time)
    $fetch_sql = "SELECT description, created_user, created_date, previous_description FROM llx_mfc WHERE lab_number = '$lab_number'";
    $fetch_result = pg_query($pg_con, $fetch_sql);
    $old_data = pg_fetch_assoc($fetch_result);

    // Decode existing previous_description (if any)
    $previous_description = [];
    if (!empty($old_data['previous_description'])) {
        $previous_description = json_decode($old_data['previous_description'], true);
    }

    // Get current timestamp
    $updated_time = date('Y-m-d H:i:s');

    // Append new history to previous_description
    $new_entry = [
        'old_description' => $old_data['description'] ?? '',
        'created_user' => $old_data['created_user'] ?? '', 
        'updated_user' => $updated_user,           
        'created_time' => $old_data['created_date'] ?? '',
        'updated_time' => $updated_time
    ];

    $previous_description[] = $new_entry; // Add new entry to history

    // Convert back to JSON for storage
    $previous_description_json = json_encode($previous_description);

    // Update the record
    $update_sql = "UPDATE llx_mfc 
                   SET description = '$description', 
                       updated_user = '$updated_user', 
                       updated_date = NOW(), 
                       previous_description = '$previous_description_json' 
                   WHERE lab_number = '$lab_number'";

    $update_result = pg_query($pg_con, $update_sql);

    if ($update_result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Error: " . pg_last_error($pg_con);
    }
}
?>