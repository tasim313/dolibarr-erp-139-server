<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = $_POST['rowid'];
    $slide_number = $_POST['slide_number'];
    $location = $_POST['location'];
    $fixation_method = $_POST['fixation_method'];
    $dry = $_POST['dry'];

    // Update the record using prepared statements
    $sql = "UPDATE llx_cyto_fixation_details
            SET slide_number = $1, 
                location = $2, 
                fixation_method = $3, 
                dry = $4 
            WHERE rowid = $5";

    $result = pg_query_params($pg_con, $sql, [
        $slide_number,
        $location,
        $fixation_method,
        $dry,
        $rowid
    ]);

    if ($result) {
        // Redirect back to the previous page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "Error updating record: " . pg_last_error($pg_con);
    }
}
?>
