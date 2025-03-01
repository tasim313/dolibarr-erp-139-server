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

// **Check if the Lab Number is of Type FNA**
$fna_check_sql = "SELECT test_type FROM llx_commande_extrafields AS e 
                  INNER JOIN llx_commande AS c ON e.fk_object = c.rowid 
                  WHERE c.ref = $1 AND test_type = 'FNA'";

$fna_result = pg_query_params($pg_con, $fna_check_sql, [$lab_number]);

if ($fna_result && pg_num_rows($fna_result) > 0) {
    // **Check if data already exists for this FNA Lab Number**
    $check_existing_sql = "SELECT 1 FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 11";
    $existing_result = pg_query_params($pg_con, $check_existing_sql, [$lab_number]);

    if ($existing_result && pg_num_rows($existing_result) > 0) {
        // **If Lab Number already exists, show message**
        echo "<script>
                alert('Already Report Ready.');
                window.history.back();
              </script>";
        pg_close($pg_con);
        exit;
    }

    // **If Lab Number is FNA and not already inserted, Insert Data**
    $insert_query = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id) VALUES ($1, $2, $3)";
    $insert_result = pg_query_params($pg_con, $insert_query, [$lab_number, $user_id, $fk_status_id]);

    if ($insert_result) {
        echo "<script>
                window.history.back();
              </script>";
            //   alert('Data inserted successfully for FNA.');
    } else {
        echo "<script>
                alert('Error inserting data for FNA: " . htmlspecialchars(pg_last_error($pg_con)) . "');
                window.history.back();
              </script>";
    }
    pg_close($pg_con);
    exit;
}


// **Check if the Lab Number is of Type DPR**
$dpr_check_sql = "SELECT test_type FROM llx_commande_extrafields AS e 
                  INNER JOIN llx_commande AS c ON e.fk_object = c.rowid 
                  WHERE c.ref = $1 AND test_type = 'DPR'";

$dpr_result = pg_query_params($pg_con, $dpr_check_sql, [$lab_number]);

if ($dpr_result && pg_num_rows($dpr_result) > 0) {
    // **Check if data already exists for this DPR Lab Number**
    $check_existing_sql = "SELECT 1 FROM llx_commande_trackws WHERE labno = $1 AND fk_status_id = 54";
    $existing_result = pg_query_params($pg_con, $check_existing_sql, [$lab_number]);

    if ($existing_result && pg_num_rows($existing_result) > 0) {
        // **If Lab Number already exists, show message**
        echo "<script>
                alert('Already Report Ready.');
                window.history.back();
              </script>";
        pg_close($pg_con);
        exit;
    }

    // **If Lab Number is DPR and not already inserted, Insert Data**
    $insert_query = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id) VALUES ($1, $2, $3)";
    $insert_result = pg_query_params($pg_con, $insert_query, [$lab_number, $user_id, 54]);

    if ($insert_result) {
        echo "<script>
                alert('Data inserted successfully for DPR.');
                window.history.back();
              </script>";
    } else {
        $error_message = pg_last_error($pg_con);
        echo "<script>
                alert('Error inserting data for DPR: " . addslashes($error_message) . "');
                window.history.back();
              </script>";
    }
    pg_close($pg_con);
    exit;
}


// Get the current status of the lab number
$summary_data = get_summary_list($lab_number);

// Debugging output to check fetched data
error_log(print_r($summary_data, true));

// Initialize a flag to check status
$status_found = false;
$report_ready_found = false;

// Loop through the summary data to find if any status is 'Diagnosis Completed'
foreach ($summary_data as $data) {
    if (isset($data['status_name']) && $data['status_name'] === 'Diagnosis Completed') {
        $status_found = true;
        break;  // Exit loop once the required status is found
    }
    if ( $data['status_name'] === 'Report Ready') {
        $report_ready_found = true;
        break; // Exit loop once "Report Ready" is found
    }
}

// Check if "Report Ready" status is found
if ($report_ready_found) {
    echo "<script>
            alert('New data cannot be inserted because the status is already \"Report Ready\".');
            window.history.back();
          </script>";
    exit;
}


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
    echo "<div id='success-message' style='position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px; border-radius: 4px;'>
            Report Ready
          </div>";
    echo "<script>
            setTimeout(function() {
                document.getElementById('success-message').style.display = 'none';
                window.history.back();
            }, 0);  // Hide the message after 0 seconds
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