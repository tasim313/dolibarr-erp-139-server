<?php 
include('../connection.php');

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract values from POST with null coalescing operator for safety
    $doctor_name = $_POST['doctor_name'] ?? '';
    $assistant = $_POST['assistant'] ?? '';
    $cyto_station_type = $_POST['cyto_station_type'] ?? '';
    $lab_number = $_POST['lab_number'] ?? '';
    $patient_code = $_POST['patient_code'] ?? '';
    $status = $_POST['status'] ?? '';
    $created_user = $_POST['created_user'] ?? '';
    $reason_for_fnac = $_POST['reason_for_fnac'] ?? '';
    $other_reason = $_POST['other_reason'] ?? '';
    $clinical_history = $_POST['clinical_history'] ?? '';
    $site_of_aspiration = $_POST['site-of-aspiration'] ?? '';
    $indication_for_aspiration = $_POST['indication_for_aspiration'] ?? '';
    $fixation_comments = $_POST['fixation_comments'] ?? '';
    $dry_slides_description = $_POST['dry_slides_description'] ?? '';
    $special_instructions = $_POST['special_instructions'] ?? '';
    $number_of_needle = $_POST['number_of_needle'] ?? '';
    $number_of_syringe = $_POST['number_of_syringe'] ?? '';
    $fixation_data = $_POST['fixation_data'] ?? [];

    // Insert into llx_cyto table and return the generated rowid
    $sql = "INSERT INTO llx_cyto
        (
            lab_number,
            patient_code,
            fna_station_type,
            doctor,
            assistant,
            status,
            created_user
        )
        VALUES (
            '$lab_number',
            '$patient_code',
            '$cyto_station_type',
            '$doctor_name',
            '$assistant', 
            '$status', 
            '$created_user'
        )
        RETURNING rowid, lab_number";


    $result = pg_query($pg_con, $sql);
    if ($result) {
        $row = pg_fetch_assoc($result);
        $cyto_id = $row['rowid']; // Get the generated rowid

        // Check if $reason_for_fnac is 'Others'
        if ($reason_for_fnac == 'Others') {
            $chief_complain = $other_reason;  // If 'Others', use $other_reason
        } else {
            $chief_complain = $reason_for_fnac;  // Otherwise, use $reason_for_fnac
        }
        
        $sql_summary = "INSERT INTO llx_cyto_clinical_information (
                            cyto_id,
                            chief_complain,
                            relevant_clinical_history,
                            on_examination,
                            aspiration_note
                        ) VALUES (
                            '$cyto_id',
                            '$chief_complain',
                            '$clinical_history',
                            '$site_of_aspiration',
                            '$indication_for_aspiration'
                        )";

        $result_summary = pg_query($pg_con, $sql_summary);

        $sql_fixation_additional = "INSERT INTO llx_cyto_fixation_additional_details (
                            cyto_id,
                            dry_slides_description,
                            additional_notes_on_fixation,
                            special_instructions_or_tests_required,
                            number_of_needle_used,
                            number_of_syringe_used
                        ) VALUES (
                            '$cyto_id',
                            '$dry_slides_description',
                            '$fixation_comments',
                            '$special_instructions',
                            '$number_of_needle',
                            '$number_of_syringe'
                        )";

        $result_fixation_additional = pg_query($pg_con, $sql_fixation_additional);

         // Insert fixation data if available
        if (!empty($fixation_data)) {
            foreach ($fixation_data as $fixation) {
                $slide_number = isset($fixation['slide_number']) ? $fixation['slide_number'] : '';
                $location = isset($fixation['location']) ? $fixation['location'] : '';
                $fixation_method = isset($fixation['fixation_method']) ? $fixation['fixation_method'] : '';
                $dry = isset($fixation['dry']) ? $fixation['dry'] : '';

                 // If $dry is 'Yes', set $fixation_method to an empty string
                if (strtolower($dry) === 'yes') {
                    $fixation_method = null; // or an empty string if the database expects it
                }
                
                // Insert each fixation entry into llx_cyto_fixation_details
                $sql_fixation = "INSERT INTO llx_cyto_fixation_details (
                                    cyto_id,
                                    slide_number,
                                    location,
                                    fixation_method,
                                    dry
                                ) VALUES (
                                    '$cyto_id',
                                    '$slide_number',
                                    '$location',
                                    '$fixation_method',
                                    '$dry'
                                )";
                $result_fixation = pg_query($pg_con, $sql_fixation);
                if (!$result_fixation) {
                    echo "Error inserting fixation data: " . pg_last_error($pg_con);
                }
            }
        }

        // Redirect to the group URL
        header("Location: $homeUrl");
        exit; // Ensure script execution stops after redirection

        // header("Location: " . $_SERVER['HTTP_REFERER']);  // Redirects to the previous page

    } else {
        echo "Error: " . $sql . "<br>" . pg_last_error($pg_con);
    }
        pg_close($pg_con);
}
    
?>