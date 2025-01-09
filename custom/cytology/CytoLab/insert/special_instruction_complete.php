<?php 
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch data from the form submission
    $fixation_details = $_POST['fixation_details'];
    $created_user = isset($_POST['created_user']) ? $_POST['created_user'] : '';

    // Prepare the SQL query for insertion
    $query = "
        INSERT INTO llx_cyto_special_instructions_complete (fixation_details, created_user) 
        VALUES ($1, $2)
    ";

    // Execute the query with parameters
    $result = pg_query_params(
        $pg_con, 
        $query, 
        [$fixation_details, $created_user]
    );

    // Check the result
    if ($result) {
        // Redirect back to the referring page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;  // Ensure no further code is executed
    } else {
        // Display error message in case of failure
        echo "Error: " . pg_last_error($pg_con);
    }
}

?>