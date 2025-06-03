<?php 
include('../connection.php');

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect POST data
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
    $check_sql = "SELECT rowid FROM llx_cyto WHERE lab_number = $1 LIMIT 1";
    $check_result = pg_query_params($pg_con, $check_sql, [$lab_number]);

    if (pg_num_rows($check_result) > 0) {
        $row = pg_fetch_assoc($check_result);
        $cyto_id = $row['rowid'];
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO llx_cyto (lab_number, patient_code, fna_station_type, doctor, assistant, status, created_user)
                       VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING rowid";
        $insert_result = pg_query_params($pg_con, $insert_sql, [
            $lab_number, $patient_code, $cyto_station_type,
            $doctor_name, $assistant, $status, $created_user
        ]);

        if (!$insert_result) {
            echo "Error inserting into llx_cyto: " . pg_last_error($pg_con);
            exit;
        }

        $row = pg_fetch_assoc($insert_result);
        $cyto_id = $row['rowid'];
    }

    // Clinical info insert/update
    $chief_complain = ($reason_for_fnac === 'Others') ? $other_reason : $reason_for_fnac;

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

    $check_clinical = pg_query_params($pg_con, "SELECT 1 FROM llx_cyto_clinical_information WHERE cyto_id = $1", [$cyto_id]);

    if (pg_num_rows($check_clinical) > 0) {
        $update_sql = "UPDATE llx_cyto_clinical_information SET
                        chief_complain = $1,
                        relevant_clinical_history = $2,
                        on_examination = $3,
                        clinical_impression = $4
                       WHERE cyto_id = $5";
        $result_summary = pg_query_params($pg_con, $update_sql, [
            $chief_complain, $clinical_history, $site_of_aspiration, $clinical_impression, $cyto_id
        ]);
    } else {
        $insert_clinical = "INSERT INTO llx_cyto_clinical_information (
                                cyto_id, chief_complain, relevant_clinical_history, on_examination, clinical_impression
                            ) VALUES ($1, $2, $3, $4, $5)";
        $result_summary = pg_query_params($pg_con, $insert_clinical, [
            $cyto_id, $chief_complain, $clinical_history, $site_of_aspiration, $clinical_impression
        ]);
    }

    // Fixation additional details insert/update
    $check_fixation = pg_query_params($pg_con, "SELECT 1 FROM llx_cyto_fixation_additional_details WHERE cyto_id = $1", [$cyto_id]);

    if (pg_num_rows($check_fixation) > 0) {
        $update_fixation = "UPDATE llx_cyto_fixation_additional_details SET
                            dry_slides_description = $1,
                            additional_notes_on_fixation = $2,
                            number_of_needle_used = $3,
                            number_of_syringe_used = $4
                            WHERE cyto_id = $5";
        $result_fixation_additional = pg_query_params($pg_con, $update_fixation, [
            $dry_slides_description, $fixation_comments, $number_of_needle, $number_of_syringe, $cyto_id
        ]);
    } else {
        $insert_fixation = "INSERT INTO llx_cyto_fixation_additional_details (
                                cyto_id, dry_slides_description, additional_notes_on_fixation,
                                number_of_needle_used, number_of_syringe_used
                            ) VALUES ($1, $2, $3, $4, $5)";
        $result_fixation_additional = pg_query_params($pg_con, $insert_fixation, [
            $cyto_id, $dry_slides_description, $fixation_comments, $number_of_needle, $number_of_syringe
        ]);
    }

    if (!$result_summary) {
        echo "<script>alert('Clinical info save error: " . pg_last_error($pg_con) . "'); window.history.back();</script>";
        exit;
    }

    if (!$result_fixation_additional) {
        echo "<script>alert('Aspiration Note save error: " . pg_last_error($pg_con) . "'); window.history.back();</script>";
        exit;
    }

    // Insert fixation data (multiple rows)
    if (!empty($fixation_data)) {
        foreach ($fixation_data as $fixation) {
            $slide_number = $fixation['slideNumber'] ?? '';
            $location = $fixation['location'] ?? '';
            $fixation_method = $fixation['fixationMethod'] ?? '';
            $dry = $fixation['isDry'] ?? '';
            $aspiration_materials = $fixation['aspirationMaterials'] ?? '';
            $special_instructions = $fixation['specialInstruction'] ?? '';

            // If dry, skip fixation_method
            $fixation_method = (strtolower($dry) === 'yes') ? null : $fixation_method;

            $sql_fixation = "INSERT INTO llx_cyto_fixation_details (
                                cyto_id, slide_number, location, fixation_method, dry,
                                aspiration_materials, special_instructions
                             ) VALUES ($1, $2, $3, $4, $5, $6, $7)";
            pg_query_params($pg_con, $sql_fixation, [
                $cyto_id, $slide_number, $location, $fixation_method,
                $dry, $aspiration_materials, $special_instructions
            ]);
        }
    }

    // Redirect to home
    header("Location: $homeUrl");
    exit;
}
?>