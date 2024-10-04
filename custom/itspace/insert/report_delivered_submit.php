<?php
include '../connection.php';  // Include the connection file
include '../common_function.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Retrieve form data
$lab_number = $_POST['lab_number'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$fk_status_id = $_POST['fk_status_id'] ?? '';

// Check if the required fields are not empty
if (empty($lab_number) || empty($user_id) || empty($fk_status_id)) {
    echo "<script>
            alert('Missing required fields.');
            window.history.back();
          </script>";
    exit;
}

// Get the current status of the lab number
$summary_data = get_summary_list($lab_number);

// Debugging output to check fetched data
error_log(print_r($summary_data, true));

// Initialize a flag to check status
$status_found = false;

// Loop through the summary data to find if any status is 'Diagnosis Completed'
foreach ($summary_data as $data) {
    if (isset($data['status_name']) && $data['status_name'] === 'Diagnosis Completed') {
        $status_found = true;
        break;  // Exit loop once the required status is found
    }
}

// Debugging output to check the status found
error_log("Status found: " . ($status_found ? 'Yes' : 'No'));

if (!$status_found) {
    echo "<script>
            alert('Please First Finalized. Current status: " . htmlspecialchars(implode(', ', array_column($summary_data, 'status_name'))) . "');
            window.history.back();
          </script>";
    exit;
}

// Prepare the query to insert data
$query = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id) VALUES ($1, $2, $3)";
$result = pg_prepare($pg_con, "insert_query", $query);

if (!$result) {
    echo "<script>
            alert('Error preparing the query: " . pg_last_error($pg_con) . "');
            window.history.back();
          </script>";
    exit;
}

// Execute the prepared query
$result = pg_execute($pg_con, "insert_query", array($lab_number, $user_id, $fk_status_id));

if ($result) {
    echo "<script>
            window.history.back();
          </script>";
} else {
    $error = pg_last_error($pg_con);
    error_log("Database Error: " . $error);  // Log the error to a file or system log
    echo "<script>
            alert('Error: " . htmlspecialchars($error) . "');
            window.history.back();
          </script>";
}

// Close the database connection
pg_close($pg_con);
?>