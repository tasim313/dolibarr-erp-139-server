<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ensure all necessary fields are set and escape them
    $rowid = $_POST['rowid'] ?? null; 
    $chief_complain = $_POST['chief_complain'] ?? ''; 
    $relevant_clinical_history = $_POST['relevant_clinical_history'] ?? '';
    $on_examination = $_POST['on_examination'] ?? '';
    $aspiration_note = $_POST['aspiration_note'] ?? '';

    // Check if required field is missing
    if ($rowid === null) {
        echo "Error: Missing rowid";
        exit();
    }

    // Construct the SQL query with placeholders
    $sql = "UPDATE llx_cyto_clinical_information
            SET chief_complain = $1, 
                relevant_clinical_history = $2, 
                on_examination = $3, 
                aspiration_note = $4
            WHERE rowid = $5";

    try {
        // Prepare the statement
        $stmt = pg_prepare($pg_con, "update_clinical_info", $sql);

        // Bind the parameters and execute the query
        $result = pg_execute($pg_con, "update_clinical_info", array($chief_complain, $relevant_clinical_history, $on_examination, $aspiration_note, $rowid));

        // Check the result of the query
        if ($result) {
            header("Location: " . $_SERVER['HTTP_REFERER']);  // Redirect to the previous page
            exit();
        } else {
            echo 'Failed to update Clinical Information: ' . pg_last_error($pg_con);
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>