<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true; // Flag to track the success of all updates

    foreach ($_POST['fna_station_type'] as $rowid => $fna_station_type) {
        // Retrieve current values from the database for the row
        $query = "SELECT doctor, assistant FROM llx_cyto WHERE rowid = $1";
        $result = pg_query_params($pg_con, $query, [$rowid]);
        $current_record = pg_fetch_assoc($result);

        // Fallback to current database values if POST data is missing
        $doctor_name = $_POST['doctor'][$rowid] ?? $current_record['doctor'];
        $assistant_name = $_POST['assistant'][$rowid] ?? $current_record['assistant'];
        $updated_user = $_POST['updated_user'] ?? '';

        // Use prepared statements for safety
        $sql = "UPDATE llx_cyto 
                SET 
                    fna_station_type = $1, 
                    doctor = $2, 
                    assistant = $3, 
                    updated_user = $4,
                    updated_date = NOW()
                WHERE rowid = $5";

        $result = pg_query_params($pg_con, $sql, [
            $fna_station_type, 
            $doctor_name, 
            $assistant_name, 
            $updated_user, 
            $rowid
        ]);

        if (!$result) {
            error_log("Error updating row with ID $rowid: " . pg_last_error($pg_con));
            $success = false;
        }
    }

    if ($success) {
        // Output JavaScript for success message and redirect
        echo "<script>
                alert('Data successfully updated!');
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
    } else {
        echo "<script>
                alert('There was an issue updating the data. Please try again.');
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
    }
    exit;
}
?>