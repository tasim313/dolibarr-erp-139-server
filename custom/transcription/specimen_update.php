<?php
include("connection.php");
include('../grossmodule/gross_common_function.php');


// Check if lab_number is set in the URL parameters
if (!isset($_GET['lab_number'])) {
    echo "Error: lab_number is not set.";
    exit();
}

$LabNumber = $_GET['lab_number'];

// Debugging output to confirm that lab_number is retrieved
// echo "Debug: lab_number retrieved from URL is: " . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $new_descriptions = isset($_POST['new_description']) ? $_POST['new_description'] : array();
    $specimen_rowids = isset($_POST['specimen_rowid']) ? $_POST['specimen_rowid'] : [];

    // Ensure the arrays have the same length
    if (count($new_descriptions) !== count($specimen_rowids)) {
        echo "Error: Mismatch between descriptions and specimen IDs.";
        exit();
    }

    // Prepare update statement (excluding lab_number update)
    $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_commandedet SET description = $1 WHERE rowid = $2");

    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    $success = true;

    // Debugging output to log the data
    echo "Debugging data before update:<br>";
    for ($i = 0; $i < count($specimen_rowids); $i++) {
        echo "Description: " . htmlspecialchars($new_descriptions[$i], ENT_QUOTES, 'UTF-8') . " - Specimen Row ID: " . htmlspecialchars($specimen_rowids[$i], ENT_QUOTES, 'UTF-8') . "<br>";

        // Validate specimen_rowid
        if (empty($specimen_rowids[$i]) || !is_numeric($specimen_rowids[$i])) {
            echo "Error: Specimen row ID is invalid.";
            $success = false;
            break;
        }

        // Execute the prepared statement
        $result = pg_execute($pg_con, "update_statement", array(
            pg_escape_string($pg_con, $new_descriptions[$i]),
            $specimen_rowids[$i]
        ));

        if (!$result) {
            echo "Error updating data: " . pg_last_error($pg_con);
            $success = false;
            break;
        }
    }

    if ($success) {
        // Redirect after successful update
        echo '<script>';
        echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
        echo '</script>';
        exit();
    } else {
        echo "Error: One or more updates failed.";
    }
} else {
    // Redirect if not a POST request
    echo '<script>';
    echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
    echo '</script>';
    exit();
}
?>
