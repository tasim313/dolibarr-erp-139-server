<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = $_POST['rowid'];
    $slide_number = $_POST['slide_number'];
    $location = $_POST['location'];
    $fixation_method = $_POST['fixation_method'];
    $dry = $_POST['dry'];
    $aspiration_materials = $_POST['aspiration_materials'];
    $special_instructions = $_POST['special_instructions'];

    // Update the record using prepared statements
    $sql = "UPDATE llx_cyto_fixation_details
            SET slide_number = $1, 
                location = $2, 
                fixation_method = $3, 
                dry = $4,
                aspiration_materials = $5,
                special_instructions = $6
            WHERE rowid = $7";

    $result = pg_query_params($pg_con, $sql, [
        $slide_number,
        $location,
        $fixation_method,
        $dry,
        $aspiration_materials,      
        $special_instructions,
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
