<?php 
include('../../connection.php');

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
    $site_of_aspiration = $_POST['site-of-aspiration-editor'] ?? '';
    $fixation_comments = $_POST['fixation_comments'] ?? '';
    $dry_slides_description = $_POST['dry_slides_description'] ?? '';
    $special_instructions = $_POST['special_instruction_input'] ?? '';
    $aspiration_materials = $_POST['aspiration_materials_input'] ?? '';
    $number_of_needle = $_POST['number_of_needle'] ?? '';
    $number_of_syringe = $_POST['number_of_syringe'] ?? '';
    $fixation_data = $_POST['fixation_data'] ?? [];


    // Insert into llx_cyto_recall table and return the generated rowid
    $sql = "INSERT INTO llx_cyto_recall
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
        
        var_dump($cyto_id, $chief_complain, $clinical_history, $site_of_aspiration);
        $sql_summary = "INSERT INTO llx_cyto_recall_clinical_information (
                            cyto_id,
                            chief_complain,
                            relevant_clinical_history,
                            on_examination
                            
                        ) VALUES (
                            '$cyto_id',
                            '$chief_complain',
                            '$clinical_history',
                            '$site_of_aspiration'
                        )";

        $result_summary = pg_query($pg_con, $sql_summary);

        $sql_fixation_additional = "INSERT INTO llx_cyto_recall_fixation_additional_details (
                            cyto_id,
                            dry_slides_description,
                            additional_notes_on_fixation,
                            number_of_needle_used,
                            number_of_syringe_used
                        ) VALUES (
                            '$cyto_id',
                            '$dry_slides_description',
                            '$fixation_comments',
                            '$number_of_needle',
                            '$number_of_syringe'
                        )";

        $result_fixation_additional = pg_query($pg_con, $sql_fixation_additional);

        // Insert fixation data if available
        if (!empty($fixation_data)) {
            foreach ($fixation_data as $fixation) {
                $slide_number = $fixation['slideNumber'] ?? ''; // Use the correct key
                $location = $fixation['location'] ?? ''; // Use the correct key
                $fixation_method = $fixation['fixationMethod'] ?? ''; // Use the correct key
                $dry = $fixation['isDry'] ?? ''; // Use the correct key
                $aspiration_materials = $fixation['aspirationMaterials'] ?? ''; // Use the correct key
                $special_instructions = $fixation['specialInstruction'] ?? ''; // Use the correct key

                // If $dry is 'Yes', set $fixation_method to null
                if (strtolower($dry) === 'yes') {
                    $fixation_method = null; // or an empty string if the database expects it
                }

                // Insert each fixation entry into llx_cyto_fixation_details
                $sql_fixation = "INSERT INTO llx_cyto_recall_fixation_details (
                                    cyto_id,
                                    slide_number,
                                    location,
                                    fixation_method,
                                    dry,
                                    aspiration_materials,
                                    special_instructions
                                ) VALUES (
                                    '$cyto_id',
                                    '$slide_number',
                                    '$location',
                                    " . ($fixation_method === null ? "NULL" : "'$fixation_method'") . ",
                                    '$dry',
                                    '$aspiration_materials',
                                    '$special_instructions'
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