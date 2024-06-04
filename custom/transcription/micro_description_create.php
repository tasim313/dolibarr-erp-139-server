<?php 
include("connection.php");
include('../grossmodule/gross_common_function.php');
$fk_gross_id = $_GET['fk_gross_id'];
$loggedInUsername = $_GET['user'];
$LabNumber = get_lab_number($fk_gross_id);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

for ($i = 0; $i < count($specimens_list); $i++) {
    $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
    $sql_micro = "INSERT INTO llx_micro (fk_gross_id, specimen, lab_number, created_user, status)
                    VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

    $result_micro = pg_query($pg_con, $sql_micro);

    if (!$result_micro) {
        echo "Error inserting data: " . pg_last_error($pg_con);
        pg_close($pg_con);
        exit();
    }
}

              
for ($i = 0; $i < count($specimens_list); $i++) {
    $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
    $sql_diagnosis = "INSERT INTO llx_diagnosis (fk_gross_id, specimen, lab_number, created_user, status)
                                VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

    $result_specimen = pg_query($pg_con, $sql_diagnosis);

    if (!$result_specimen) {
        echo "Error inserting data: " . pg_last_error($pg_con);
        pg_close($pg_con);
        exit();
    }
}

echo '<script>';
echo 'window.location.href = "transcription.php?lab_number=' . $LabNumber . '";'; 
echo '</script>';
pg_close($pg_con);
exit();
?>