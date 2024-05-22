<?php 

include("connection.php");
include('gross_common_function.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape the form values to prevent SQL injection
    $lab_number = isset($_POST['lab_number']) ? pg_escape_string($pg_con, $_POST['lab_number']) : '';
    $gross_station_type = isset($_POST['gross_station_type']) ? pg_escape_string($pg_con, $_POST['gross_station_type']) : '';
    $gross_assistant_name = isset($_POST['gross_assistant_name']) ? pg_escape_string($pg_con, $_POST['gross_assistant_name']) : '';
    $gross_doctor_name = isset($_POST['gross_doctor_name']) ? pg_escape_string($pg_con, $_POST['gross_doctor_name']) : '';
    $gross_status = isset($_POST['gross_status']) ? pg_escape_string($pg_con, $_POST['gross_status']) : '';
    $gross_created_user = isset($_POST['gross_created_user']) ? pg_escape_string($pg_con, $_POST['gross_created_user']) : ''; 
    $gross_create_date = isset($_POST['gross_create_date']) ? pg_escape_string($pg_con, $_POST['gross_create_date']) : '';
    
    // Insert into llx_gross table and return the generated id
    $sql = "INSERT INTO llx_gross
        (
            lab_number,
            gross_station_type, 
            gross_assistant_name, 
            gross_doctor_name, 
            gross_status, 
            gross_created_user
        )
        VALUES (
            '$lab_number',
            '$gross_station_type',
            '$gross_assistant_name',
            '$gross_doctor_name ', 
            '$gross_status', 
            '$gross_created_user'
        )
        RETURNING gross_id, lab_number";


    $result = pg_query($pg_con, $sql);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $fk_gross_id = $row['gross_id']; // Get the generated id
        $LabNumber = $row['lab_number'];
        $Lab_Number =  substr($lab_number, 3) ;

        // Fetch existing data and prepare for specimen insertions
        $specimens_list = get_gross_specimens_list($Lab_Number);
              
        for ($i = 0; $i < count($specimens_list); $i++) {
            $specimen = pg_escape_string($pg_con, $specimens_list[$i]['specimen']);
            $sql_specimen = "INSERT INTO llx_gross_specimen (fk_gross_id, specimen)
                             VALUES ('$fk_gross_id', '$specimen')";

            $result_specimen = pg_query($pg_con, $sql_specimen);

            if (!$result_specimen) {
                echo "Error inserting data: " . pg_last_error($pg_con);
                pg_close($pg_con);
                exit();
            }
        }

        $sql_summary = "INSERT INTO llx_gross_summary_of_section (
                            fk_gross_id
                        ) VALUES (
                            '$fk_gross_id'
                        )";

        $result_summary = pg_query($pg_con, $sql_summary);

        if ($result_summary) {
            echo '<script>';
            echo 'window.location.href = "gross_update.php?fk_gross_id=' . $fk_gross_id . '";'; 
            echo '</script>';
            exit(); 
        } else {
            echo "Error: " . $sql_summary . "<br>" . pg_last_error($pg_con);
        }



    } else {
        echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
    }
    pg_close($pg_con);
}else{
   header("Location: gross_create.php");
   exit();
}


?>