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
    $specimen_name = pg_escape_string($pg_con, $_POST['specimen_name'] ?? '');
    $gross_note = pg_escape_string($pg_con, $_POST['gross-note'] ?? '');
    $recall = pg_escape_string($pg_con, $_POST['recall-description'] ?? '');
    $created_user = pg_escape_string($pg_con, $_POST['created_user'] ?? '');
    $chief_complain = pg_escape_string($pg_con, $_POST['chief-complain'] ?? ''); // Sanitize chief complain input

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
                specimen_name = '$specimen_name',
                gross_note = '$gross_note',
                recall = '$recall',
                chief_complain = '$chief_complain',  -- Update the chief_complain field
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
                specimen_name,
                gross_note,
                recall,
                chief_complain,   -- Insert the chief_complain field
                created_user
            )
            VALUES (
                '$LabNumber',
                '$microscopic',
                '$conclusion',
                '$comment',
                '$specimen_name',
                '$gross_note',
                '$recall',
                '$chief_complain',  -- Insert chief_complain value
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
