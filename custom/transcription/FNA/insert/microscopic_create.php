<?php
include("../../connection.php");
$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/transcription/FNA/index.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user input to prevent SQL injection
    $LabNumber = pg_escape_string($pg_con, $_POST['LabNumber'] ?? '');
    $microscopic = pg_escape_string($pg_con, $_POST['microscopic-description'] ?? '');
    $conclusion = pg_escape_string($pg_con, $_POST['conclusion-description'] ?? '');
    $comment = pg_escape_string($pg_con, $_POST['comment-description'] ?? '');
    $aspiration_notes = pg_escape_string($pg_con, $_POST['aspiration-notes'] ?? '');
    $gross_note = pg_escape_string($pg_con, $_POST['gross-note'] ?? '');
    $recall = pg_escape_string($pg_con, $_POST['recall-description'] ?? '');
    $created_user = pg_escape_string($pg_con, $_POST['created_user'] ?? '');

    // Check if the record already exists
    $checkSql = "SELECT rowid FROM llx_cyto_microscopic_description WHERE lab_number = '$LabNumber'";
    $checkResult = pg_query($pg_con, $checkSql);

    if ($checkResult && pg_num_rows($checkResult) > 0) {
        // Update existing record
        $updateSql = "
            UPDATE llx_cyto_microscopic_description
            SET 
                microscopic_description = '$microscopic',
                conclusion = '$conclusion',
                comment = '$comment',
                aspiration_notes = '$aspiration_notes',
                gross_note = '$gross_note',
                recall = '$recall',
                updated_user = '$created_user',
                updated_date = NOW()
            WHERE lab_number = '$LabNumber'
        ";

        $updateResult = pg_query($pg_con, $updateSql);
        if ($updateResult) {
            // Redirect to the previous page
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error: " . pg_last_error($pg_con);
        }
    } else {
        // Insert new record
        $insertSql = "
            INSERT INTO llx_cyto_microscopic_description
            (
                lab_number,
                microscopic_description,
                conclusion,
                comment,
                aspiration_notes,
                gross_note,
                recall,
                created_user
            )
            VALUES (
                '$LabNumber',
                '$microscopic',
                '$conclusion',
                '$comment',
                '$aspiration_notes',
                '$gross_note',
                '$recall',
                '$created_user'
            ) RETURNING rowid, lab_number
        ";

        $insertResult = pg_query($pg_con, $insertSql);
        if ($insertResult) {
            // Redirect to the previous page
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            echo "Error: " . pg_last_error($pg_con);
        }
    }

    // Close the connection
    pg_close($pg_con);
}
?>