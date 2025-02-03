<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch data from the form submission
    $fixation_details = $_POST['fixation_details'];

    // Prepare the SQL query for updating the data
    $query = "
        UPDATE llx_cyto_fixation_details 
        SET special_instructions = ''  
        WHERE rowid = $1
    ";

    // Execute the query with parameters
    $result = pg_query_params($pg_con, $query, [$fixation_details]);

    // Check the result
    if ($result) {
        echo "<script>
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
        exit;  // Ensure no further code is executed
    } else {
        echo "Error: " . pg_last_error($pg_con);
    }
}
?>