<?php 
include("../connection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$pg_con) {
        die("Database connection failed: " . pg_last_error());
    }

    // Safely escape all input values
    $comment = pg_escape_string($pg_con, $_POST['commentText']);
    $labNumber = pg_escape_string($pg_con, $_POST['labNumber']);
    $sorsetype = pg_escape_string($pg_con, $_POST['sorsetype']);
    $targettype = pg_escape_string($pg_con, $_POST['targettype']);
    $fk_user_author = pg_escape_string($pg_con, $_POST['fk_user_author']);

    // 1. Get fk_source
    $commande_query = "SELECT rowid FROM llx_commande WHERE ref = '$labNumber'";
    $commande_result = pg_query($pg_con, $commande_query);
    if (!$commande_result) {
        die("Error in SQL query (commande): " . pg_last_error($pg_con));
    }
    $commande_row = pg_fetch_assoc($commande_result);
    $fk_source = $commande_row ? $commande_row['rowid'] : null;

    // 2. Get fk_target
    $microscopic_query = "SELECT row_id FROM llx_preliminary_report_microscopic WHERE lab_number = 'HPL' || '$labNumber' ORDER BY row_id ASC";
    $microscopic_result = pg_query($pg_con, $microscopic_query);
    if (!$microscopic_result) {
        die("Error in SQL query (microscopic): " . pg_last_error($pg_con));
    }
    $microscopic_row = pg_fetch_assoc($microscopic_result);
    $fk_target = $microscopic_row ? $microscopic_row['row_id'] : null;

    // 3. Check if the element relationship already exists
    $check_query = "SELECT rowid FROM llx_custom_element_element 
                    WHERE fk_source = '$fk_source' 
                    AND sourcetype = '$sorsetype' 
                    AND fk_target = '$fk_target' 
                    AND targettype = '$targettype'";
    $check_result = pg_query($pg_con, $check_query);
    if (!$check_result) {
        die("Error checking existing element: " . pg_last_error($pg_con));
    }

    if (pg_num_rows($check_result) > 0) {
        // Relationship already exists
        $element_row = pg_fetch_assoc($check_result);
        $fk_element = $element_row['rowid'];
    } else {
        // Insert new relationship
        $element_query = "INSERT INTO llx_custom_element_element (fk_source, sourcetype, fk_target, targettype) 
                          VALUES ('$fk_source', '$sorsetype', '$fk_target', '$targettype') RETURNING rowid";
        $element_result = pg_query($pg_con, $element_query);
        if (!$element_result) {
            die("Error inserting into llx_custom_element_element: " . pg_last_error($pg_con));
        }
        $element_row = pg_fetch_assoc($element_result);
        $fk_element = $element_row['rowid'];
    }

    // 4. Insert into llx_custom_comment
    date_default_timezone_set('Asia/Dhaka');
    $currentDate = date('Y-m-d H:i:s');

    $comment_query = "INSERT INTO llx_custom_comment (datec, description, fk_user_author, fk_element, element_type) 
                      VALUES ('$currentDate', '$comment', '$fk_user_author', '$fk_element', 'Preliminary Report')";
    $comment_result = pg_query($pg_con, $comment_query);
    if (!$comment_result) {
        die("Error inserting into llx_custom_comment: " . pg_last_error($pg_con));
    }

    // Redirect back to previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>