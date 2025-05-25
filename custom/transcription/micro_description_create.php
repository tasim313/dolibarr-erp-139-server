<?php 
// include("connection.php");
// include('../grossmodule/gross_common_function.php');
// $fk_gross_id = $_GET['fk_gross_id'];
// $loggedInUsername = $_GET['user'];
// $LabNumber = get_lab_number($fk_gross_id);
// $LabNumberWithoutPrefix = substr($LabNumber, 3);
// $specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);



// for ($i = 0; $i < count($specimens_list); $i++) {
//     $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
//     $sql_micro = "INSERT INTO llx_micro (fk_gross_id, specimen, lab_number, created_user, status)
//                     VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

//     $result_micro = pg_query($pg_con, $sql_micro);

//     if (!$result_micro) {
//         echo "Error inserting data: " . pg_last_error($pg_con);
//         pg_close($pg_con);
//         exit();
//     }
// }

              
// for ($i = 0; $i < count($specimens_list); $i++) {
//     $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
//     $sql_diagnosis = "INSERT INTO llx_diagnosis (fk_gross_id, specimen, lab_number, created_user, status)
//                                 VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

//     $result_specimen = pg_query($pg_con, $sql_diagnosis);

//     if (!$result_specimen) {
//         echo "Error inserting data: " . pg_last_error($pg_con);
//         pg_close($pg_con);
//         exit();
//     }
// }

// echo '<script>';
// echo 'window.location.href = "transcription.php?lab_number=' . $LabNumber . '";'; 
// echo '</script>';
// pg_close($pg_con);
// exit();
?>

<?php 
include("connection.php");
include('../grossmodule/gross_common_function.php');
$fk_gross_id = $_GET['fk_gross_id'];
$loggedInUsername = $_GET['user'];
$LabNumber = get_lab_number($fk_gross_id);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

// Check and copy microscopic preliminary data if exists
$sql_check_micro = "SELECT * FROM llx_preliminary_report_microscopic 
                    WHERE lab_number = '$LabNumber'";
$result_check_micro = pg_query($pg_con, $sql_check_micro);

if ($result_check_micro && pg_num_rows($result_check_micro) > 0) {
    // Copy all matching microscopic records
    while ($row = pg_fetch_assoc($result_check_micro)) {
        $specimen = pg_escape_string($pg_con, $row['specimen']);
        $description = pg_escape_string($pg_con, $row['description']);
        
        $sql_micro = "INSERT INTO llx_micro (fk_gross_id, specimen, lab_number, description, created_user, status)
                      VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$description', '$loggedInUsername', 'Done')";
        
        $result_micro = pg_query($pg_con, $sql_micro);
        
        if (!$result_micro) {
            echo "Error inserting microscopic data: " . pg_last_error($pg_con);
            pg_close($pg_con);
            exit();
        }
    }
} else {
    // Insert default microscopic data if no preliminary data exists
    for ($i = 0; $i < count($specimens_list); $i++) {
        $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
        $sql_micro = "INSERT INTO llx_micro (fk_gross_id, specimen, lab_number, created_user, status)
                      VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

        $result_micro = pg_query($pg_con, $sql_micro);

        if (!$result_micro) {
            echo "Error inserting microscopic data: " . pg_last_error($pg_con);
            pg_close($pg_con);
            exit();
        }
    }
}

// Check and copy diagnosis preliminary data if exists
$sql_check_diagnosis = "SELECT * FROM llx_preliminary_report_diagnosis 
                        WHERE lab_number = '$LabNumber'";
$result_check_diagnosis = pg_query($pg_con, $sql_check_diagnosis);

if ($result_check_diagnosis && pg_num_rows($result_check_diagnosis) > 0) {
    // Copy all matching diagnosis records
    while ($row = pg_fetch_assoc($result_check_diagnosis)) {
        $specimen = pg_escape_string($pg_con, $row['specimen']);
        $description = pg_escape_string($pg_con, $row['description']);
        $comment = pg_escape_string($pg_con, $row['comment']);
        $title = pg_escape_string($pg_con, $row['title']);
        
        $sql_diagnosis = "INSERT INTO llx_diagnosis (fk_gross_id, specimen, lab_number, description, comment, title, created_user, status)
                          VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$description', '$comment', '$title', '$loggedInUsername', 'Done')";
        
        $result_diagnosis = pg_query($pg_con, $sql_diagnosis);
        
        if (!$result_diagnosis) {
            echo "Error inserting diagnosis data: " . pg_last_error($pg_con);
            pg_close($pg_con);
            exit();
        }
    }
} else {
    // Insert default diagnosis data if no preliminary data exists
    for ($i = 0; $i < count($specimens_list); $i++) {
        $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
        $sql_diagnosis = "INSERT INTO llx_diagnosis (fk_gross_id, specimen, lab_number, created_user, status)
                          VALUES ('$fk_gross_id', '$specimen', '$LabNumber', '$loggedInUsername', 'Done')";

        $result_specimen = pg_query($pg_con, $sql_diagnosis);

        if (!$result_specimen) {
            echo "Error inserting diagnosis data: " . pg_last_error($pg_con);
            pg_close($pg_con);
            exit();
        }
    }
}

echo '<script>';
echo 'window.location.href = "transcription.php?lab_number=' . $LabNumber . '";'; 
echo '</script>';

// header("Location: " . $_SERVER['HTTP_REFERER']);

pg_close($pg_con);
exit();
?>