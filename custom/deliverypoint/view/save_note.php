<?php 
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_public = $_POST['note_public'];
    $rowid = $_POST['rowid'];

    if (!empty($note_public) && !empty($rowid)) {
        // Use pg_query_params for parameterized queries (safe against SQL injection)
        $query = "UPDATE llx_facture SET note_public = $1 WHERE rowid = $2";
        $result = pg_query_params($pg_con, $query, array($note_public, $rowid));

        if ($result) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
}
?>

