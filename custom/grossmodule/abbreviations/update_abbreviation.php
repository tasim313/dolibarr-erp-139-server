<?php

// Include the connection file
include('../connection.php');
include("common_function_for_abbreviations.php");

// Handle form submission for editing abbreviations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_abbreviation'])) {
   
    $rowid = intval($_POST['rowid']);
    $abbreviationKey = isset($_POST['abbreviation_key']) ? trim(htmlspecialchars($_POST['abbreviation_key'])) : '';
    $abbreviationFullText = isset($_POST['abbreviation_full_text']) ? trim($_POST['abbreviation_full_text']) : '';

    // Retrieve the user ID from the form submission
    $loggedInUserId = isset($_POST['loggedInUserId']) ? intval($_POST['loggedInUserId']) : null;

    if (!$loggedInUserId) {
        die('User not logged in.');
    }

    // Check if the abbreviationKey or abbreviationFullText is different from the original
    $sql_select = "SELECT abbreviation_key, abbreviation_full_text FROM llx_abbreviations WHERE rowid = $1 AND fk_user_id = $2";
    $result_select = pg_prepare($pg_con, "select_abbreviation", $sql_select);
    if (!$result_select) {
        die('Error preparing select statement: ' . pg_last_error($pg_con));
    }
    $result_select = pg_execute($pg_con, "select_abbreviation", array($rowid, $loggedInUserId));
    if (!$result_select) {
        die('Error executing select statement: ' . pg_last_error($pg_con));
    }

    if ($row = pg_fetch_assoc($result_select)) {
        $originalKey = $row['abbreviation_key'];
        $originalFullText = $row['abbreviation_full_text'];

        // Normalize HTML content by trimming and stripping excess whitespace
        $normalizedOriginalFullText = trim($originalFullText);
        $normalizedNewFullText = trim($abbreviationFullText);

        // Only perform the update if the values have changed
        if ($abbreviationKey !== $originalKey || $normalizedNewFullText !== $normalizedOriginalFullText) {
            // Prepare the SQL update statement
            $sql_update = "UPDATE llx_abbreviations 
                           SET abbreviation_key = $1, abbreviation_full_text = $2 
                           WHERE rowid = $3 AND fk_user_id = $4";
            
            $result_update = pg_prepare($pg_con, "update_abbreviation", $sql_update);
            if (!$result_update) {
                die('Error preparing update statement: ' . pg_last_error($pg_con));
            }
            $result_update = pg_execute($pg_con, "update_abbreviation", array($abbreviationKey, $abbreviationFullText, $rowid, $loggedInUserId));
            if (!$result_update) {
                die('Error executing update statement: ' . pg_last_error($pg_con));
            }

            if ($result_update) {
                echo '<script>';
                echo 'window.location.href = "List.php";'; 
                echo '</script>';
            } else {
                echo 'Error updating abbreviation: ' . pg_last_error($pg_con);
            }
        } else {
            echo 'No changes made to the abbreviation.';
        }
    } else {
        echo 'Abbreviation not found.';
    }

    pg_close($pg_con);
}
?>