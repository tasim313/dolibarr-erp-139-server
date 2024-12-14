<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    var_dump($_POST);

    $rowid = isset($_POST['rowid']) ? $_POST['rowid'] : null;
    $dry_slides_description = isset($_POST['dry_slides_description']) ? $_POST['dry_slides_description'] : '';
    $additional_notes_on_fixation = isset($_POST['additional_notes_on_fixation']) ? $_POST['additional_notes_on_fixation'] : '';
    $special_instructions_or_tests_required = isset($_POST['special_instructions_or_tests_required']) ? $_POST['special_instructions_or_tests_required'] : '';
    $number_of_needle_used = isset($_POST['number_of_needle_used']) ? $_POST['number_of_needle_used'] : '';
    $number_of_syringe_used = isset($_POST['number_of_syringe_used']) ? $_POST['number_of_syringe_used'] : '';

    if ($rowid) {
        $sql = "UPDATE llx_cyto_fixation_additional_details
                SET dry_slides_description = $1, 
                    additional_notes_on_fixation = $2, 
                    special_instructions_or_tests_required = $3, 
                    number_of_needle_used = $4,
                    number_of_syringe_used = $5
                WHERE rowid = $6";

        $result = pg_query_params($pg_con, $sql, [
            $dry_slides_description,
            $additional_notes_on_fixation,
            $special_instructions_or_tests_required,
            $number_of_needle_used,
            $number_of_syringe_used,
            $rowid
        ]);

        if ($result) {
            // Redirect back to the previous page
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error updating record: " . pg_last_error($pg_con);
        }
    } else {
        echo "Row ID is missing.";
    }
}

?>