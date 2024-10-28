<?php 

include('../../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowid = $_POST['rowid'];
    $name = $_POST['name'];
    $updated_user = $_POST['updated_user'];

    // Update query using prepared statement
    $sql = "UPDATE llx_batch SET name = $1, updated_user = $2, updated_time = NOW() WHERE rowid = $3";
    $result = pg_prepare($pg_con, "update_batch", $sql);
    $result = pg_execute($pg_con, "update_batch", array($name, $updated_user, $rowid));

    if ($result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo 'Error updating batch: ' . pg_last_error($pg_con);
    }
}

?>