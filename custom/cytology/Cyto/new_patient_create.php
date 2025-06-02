<?php 
include('../connection.php');

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    $clinical_impression = $_POST['clinical_impression'] ?? '';
    $dry_slides_description = $_POST['dry_slides_description'] ?? '';
    $special_instructions = $_POST['special_instruction_input'] ?? '';
    $aspiration_materials = $_POST['aspiration_materials_input'] ?? '';
    $number_of_needle = $_POST['number_of_needle'] ?? '';
    $number_of_syringe = $_POST['number_of_syringe'] ?? '';
    $fixation_data = $_POST['fixation_data'] ?? [];

    // Check if record already exists
    $check_sql = "SELECT rowid FROM llx_cyto WHERE lab_number = '$lab_number' LIMIT 1";
    $check_result = pg_query($pg_con, $check_sql);
    
    if (pg_num_rows($check_result) > 0) {
        $row = pg_fetch_assoc($check_result);
        $cyto_id = $row['rowid'];
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO llx_cyto (lab_number, patient_code, fna_station_type, doctor, assistant, status, created_user)
                       VALUES ('$lab_number', '$patient_code', '$cyto_station_type', '$doctor_name', '$assistant', '$status', '$created_user')
                       RETURNING rowid";
        $insert_result = pg_query($pg_con, $insert_sql);
        if (!$insert_result) {
            echo "Error inserting into llx_cyto: " . pg_last_error($pg_con);
            exit;
        }
        $row = pg_fetch_assoc($insert_result);
        $cyto_id = $row['rowid'];
    }

    // Clinical info insert/update
    $chief_complain = ($reason_for_fnac == 'Others') ? $other_reason : $reason_for_fnac;

    if (empty($chief_complain) || empty($clinical_history) || empty($site_of_aspiration) || empty($clinical_impression)) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Required clinical fields are missing.',
                confirmButtonColor: '#d33'
            }).then(() => { window.history.back(); });
        </script>";
        exit;
    }

    $check_clinical = pg_query($pg_con, "SELECT 1 FROM llx_cyto_clinical_information WHERE cyto_id = '$cyto_id'");
    if (pg_num_rows($check_clinical) > 0) {
        $update_sql = "UPDATE llx_cyto_clinical_information SET
                        chief_complain = '$chief_complain',
                        relevant_clinical_history = '$clinical_history',
                        on_examination = '$site_of_aspiration',
                        clinical_impression = '$clinical_impression'
                       WHERE cyto_id = '$cyto_id'";
        $result_summary = pg_query($pg_con, $update_sql);
    } else {
        $insert_clinical = "INSERT INTO llx_cyto_clinical_information (cyto_id, chief_complain, relevant_clinical_history, on_examination, clinical_impression)
                            VALUES ('$cyto_id', '$chief_complain', '$clinical_history', '$site_of_aspiration', '$clinical_impression')";
        $result_summary = pg_query($pg_con, $insert_clinical);
    }

    // Fixation additional details insert/update
    $check_fixation = pg_query($pg_con, "SELECT 1 FROM llx_cyto_fixation_additional_details WHERE cyto_id = '$cyto_id'");
    if (pg_num_rows($check_fixation) > 0) {
        $update_fixation = "UPDATE llx_cyto_fixation_additional_details SET
                            dry_slides_description = '$dry_slides_description',
                            additional_notes_on_fixation = '$fixation_comments',
                            number_of_needle_used = '$number_of_needle',
                            number_of_syringe_used = '$number_of_syringe'
                            WHERE cyto_id = '$cyto_id'";
        $result_fixation_additional = pg_query($pg_con, $update_fixation);
    } else {
        $insert_fixation = "INSERT INTO llx_cyto_fixation_additional_details (
                                cyto_id, dry_slides_description, additional_notes_on_fixation,
                                number_of_needle_used, number_of_syringe_used
                            ) VALUES (
                                '$cyto_id', '$dry_slides_description', '$fixation_comments',
                                '$number_of_needle', '$number_of_syringe'
                            )";
        $result_fixation_additional = pg_query($pg_con, $insert_fixation);
    }


    if (!$result_summary) {
        echo "<script>alert('Clinical info save error: " . pg_last_error($pg_con) . "'); window.history.back();</script>";
        exit;
    }
    if (!$result_fixation_additional) {
        echo "<script>alert('Aspiration Note save error: " . pg_last_error($pg_con) . "'); window.history.back();</script>";
        exit;
    }
    

    // Insert fixation data (ALWAYS insert as multiple rows, no update assumed)
    if (!empty($fixation_data)) {
        foreach ($fixation_data as $fixation) {
            $slide_number = $fixation['slideNumber'] ?? '';
            $location = $fixation['location'] ?? '';
            $fixation_method = $fixation['fixationMethod'] ?? '';
            $dry = $fixation['isDry'] ?? '';
            $aspiration_materials = $fixation['aspirationMaterials'] ?? '';
            $special_instructions = $fixation['specialInstruction'] ?? '';

            if (strtolower($dry) === 'yes') $fixation_method = null;

            $sql_fixation = "INSERT INTO llx_cyto_fixation_details (
                                cyto_id, slide_number, location, fixation_method, dry,
                                aspiration_materials, special_instructions
                             ) VALUES (
                                '$cyto_id', '$slide_number', '$location',
                                " . ($fixation_method === null ? "NULL" : "'$fixation_method'") . ",
                                '$dry', '$aspiration_materials', '$special_instructions'
                             )";
            pg_query($pg_con, $sql_fixation); // log if needed
        }
    }

    // Redirect
    header("Location: $homeUrl");
    exit;
}

?>