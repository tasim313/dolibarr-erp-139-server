<?php

include('connection.php');

$doctorName = $_POST['doctor_name'];
$assistant = $_POST['assistant'];
$action = $_POST['action'];

if ($action === 'start') {
    // INSERT new row with current date and time
    $sql = "INSERT INTO llx_doctor_collaboratewithAssist 
            (Doctor_name, Assistant_name, Start_Date, Start_Time, Status) 
            VALUES ($1, $2, CURRENT_DATE, CURRENT_TIME, 'Start')";
    $result = pg_query_params($pg_con, $sql, [$doctorName, $assistant]);

    if ($result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Failed to start.";
    }

} elseif ($action === 'finished') {
    // UPDATE the most recent 'Start' row
    $sql = "UPDATE llx_doctor_collaboratewithAssist 
            SET Finished_Date = CURRENT_DATE, Finished_Time = CURRENT_TIME, Status = 'Finished'
            WHERE rowid = (
                SELECT rowid FROM llx_doctor_collaboratewithAssist 
                WHERE Doctor_name = $1 AND Status = 'Start' 
                ORDER BY rowid DESC LIMIT 1
            )";
    $result = pg_query_params($pg_con, $sql, [$doctorName]);

    if ($result) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Failed to mark as finished.";
    }
}

?>