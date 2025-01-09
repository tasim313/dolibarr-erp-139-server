<?php
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch data from the form submission
    $lab_number = $_POST['lab_number'];
    $slide_number = $_POST['slide_number'];
    $pipette_tips = $_POST['pipette_tips'];
    $filter_paper = $_POST['filter_paper'];
    $created_user = $_POST['created_user'];

    // Prepare the SQL query for insertion
    $query = "
        INSERT INTO llx_cyto_slide_centrifuge (lab_number, slide_number, pipette_tips, filter_paper, created_user)
            VALUES ($1, $2, $3, $4, $5)
    ";

    // Execute the query with parameters
    $result = pg_query_params(
        $pg_con, 
        $query, 
        [$lab_number, $slide_number, $pipette_tips, $filter_paper, $created_user]
    );

    // Check the result
    if ($result) {
        // Redirect back to the referring page or a specific page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;  // Ensure no further code is executed
    } else {
        // Display error message in case of failure
        echo "Error: " . pg_last_error($pg_con);
    }
}
?>