<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $rowid = $data['rowid'];
    $section_code = $data['section_code'];
    $description = $data['description'];

    // Prepare the SQL query
    $sql = "UPDATE llx_gross_specimen_used SET section_code = $1, description = $2 WHERE rowid = $3";
    $result = pg_prepare($pg_con, "update_specimen", $sql);
    $result = pg_execute($pg_con, "update_specimen", array($section_code, $description, $rowid));

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => pg_last_error($pg_con)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>