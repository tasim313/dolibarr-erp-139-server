<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ensure all necessary fields are set and escape them
    $rowid = $_POST['rowid'] ?? null; 
    $chief_complain = $_POST['chief_complain'] ?? ''; 
    $relevant_clinical_history = $_POST['relevant_clinical_history'] ?? '';
    $on_examination = $_POST['on_examination'] ?? '';

    // Check if required field is missing
    if ($rowid === null) {
        echo "Error: Missing rowid";
        exit();
    }

     // Sanitize the data (optional but recommended)
     $chief_complain = pg_escape_string($chief_complain);
     $relevant_clinical_history = pg_escape_string($relevant_clinical_history);
     $on_examination = pg_escape_string($on_examination);

    // Construct the SQL query with placeholders
    $sql = "UPDATE llx_cyto_clinical_information
            SET chief_complain = $1, 
                relevant_clinical_history = $2, 
                on_examination = $3
            WHERE rowid = $4";

    try {
        // Prepare the statement
        $stmt = pg_prepare($pg_con, "update_clinical_info", $sql);

        // Bind the parameters and execute the query
        $result = pg_execute($pg_con, "update_clinical_info", array($chief_complain, $relevant_clinical_history, $on_examination, $rowid));

        // Check the result of the query
        if ($result) {
            header("Location: " . $_SERVER['HTTP_REFERER']);  // Redirect to the previous page
            exit();
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER']);  // Redirect to the previous page
            exit();
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>