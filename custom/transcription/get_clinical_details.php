<?php

include("connection.php");

// Check if the lab number is provided
if (isset($_GET['lab_number'])) {
    $lab_number = $_GET['lab_number'];

    // Query to fetch existing clinical details for the lab number
    $query = "SELECT clinical_details FROM llx_clinical_details WHERE lab_number = $1";
    $result = pg_query_params($pg_con, $query, [$lab_number]);

    if ($result) {
        // If clinical details exist, return them as JSON response
        $row = pg_fetch_assoc($result);
        $clinical_details = $row['clinical_details'];
        echo json_encode(['success' => true, 'data' => ['clinical_details' => $clinical_details]]);
    } else {
        // If an error occurs, return error message
        echo json_encode(['success' => false, 'error' => 'Error fetching clinical details']);
    }
} else {
    // If the lab number is not provided, return error message
    echo json_encode(['success' => false, 'error' => 'Lab number not provided']);
}

?>