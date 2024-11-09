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

    $selected_batch = isset($_POST['select_batch']) ? pg_escape_string($pg_con, $_POST['select_batch']) : '';
    
    // Insert into llx_gross table and return the generated id
    $sql = "INSERT INTO llx_gross
        (
            lab_number,
            gross_station_type, 
            gross_assistant_name, 
            gross_doctor_name, 
            gross_status, 
            gross_created_user,
            batch
        )
        VALUES (
            '$lab_number',
            '$gross_station_type',
            '$gross_assistant_name',
            '$gross_doctor_name ', 
            '$gross_status', 
            '$gross_created_user',
            '$selected_batch'
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
            
            // Remove 'HPL' prefix if it exists
            $labnumberProcessed = preg_replace('/^HPL/', '', $lab_number);

            if (!empty($labnumberProcessed) && !empty($gross_created_user)) {
                // Insert into llx_commande_trackws
                $sql_commande_trackws = "INSERT INTO llx_commande_trackws (labno, user_id, fk_status_id) 
                                         VALUES ('$labnumberProcessed', '$gross_created_user', 1)";

                $result_commande_trackws = pg_query($pg_con, $sql_commande_trackws);

                if (!$result_commande_trackws) {
                    echo "Error inserting into llx_commande_trackws: " . pg_last_error($pg_con);
                    pg_close($pg_con);
                    exit();
                }
            } else {
                error_log('Skipping empty lab number or user: ' . $labnumberProcessed);
            }
            

            // Insert into llx_batch_details if a batch is selected
            if (!empty($selected_batch)) {
                $sql_batch_details = "INSERT INTO llx_batch_details (batch_number, lab_number, gross_station) 
                                      VALUES ('$selected_batch', '$LabNumber', '$gross_station_type')";
                $result_batch_details = pg_query($pg_con, $sql_batch_details);

                if (!$result_batch_details) {
                    error_log("Error inserting into llx_batch_details: " . pg_last_error($pg_con));
                    pg_close($pg_con);
                    exit();
                }
            }


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