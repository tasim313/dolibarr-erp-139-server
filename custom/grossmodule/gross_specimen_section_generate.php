<?php 

include("connection.php");
include('gross_common_function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['fk_gross_id', 'sectionCode', 'specimen_section_description', 'cassetteNumber', 'tissue'];
    $missing_fields = array_diff_key(array_flip($required_fields), $_POST);
    
    if (!empty($missing_fields)) {
        exit();
    }

    $sql = "INSERT INTO llx_gross_specimen_section (fk_gross_id, section_code, specimen_section_description, cassettes_numbers, 
            tissue, bone, requires_slide_for_block, decalcified_bone, update_user)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";

    $stmt = pg_prepare($pg_con, "insert_specimen_section", $sql);

    if (!$stmt) {
        exit();
    }

    // Prepare statement for batch cassettes
    $sql_batch_cassette = "INSERT INTO llx_batch_details_cassettes (batch_details, cassettes_number, created_user)
                            VALUES ($1, $2, $3)";
    $stmt_batch_cassette = pg_prepare($pg_con, "insert_batch_cassette", $sql_batch_cassette);
    if (!$stmt_batch_cassette) {
        exit();
    }

    $fk_gross_id = $_POST['fk_gross_id'];
    $section_codes = $_POST['sectionCode'];
    $specimen_section_descriptions = $_POST['specimen_section_description'];
    $cassette_numbers = $_POST['cassetteNumber'];
    $tissues = $_POST['tissue'];
    $bones = isset($_POST['bone']) ? $_POST['bone'] : [];
    $requires_slide_for_block = isset($_POST['requires_slide_for_block']) ? $_POST['requires_slide_for_block'] : [];
    $decalcified_bone = isset($_POST['decalcified_bone']) ? $_POST['decalcified_bone'] : [];
    $update_user = $_POST['update_user'];
    $lab_number = $_POST['lab_number'];

    // Call the function to get batch details
    $batch_details = get_batch_details_list($lab_number);
    $created_user = $update_user;

    // Check if any batch details are found
    if (empty($batch_details)) {
        exit();
    }

    // Only one batch detail; we'll use the first entry
    $batch_detail = $batch_details[0];
    $batch_detail_id = $batch_detail['batch_number']; // Get the rowid from the first entry

    // Insert each specimen section data
    foreach ($section_codes as $key => $section_code) {
        $specimen_section_description = $specimen_section_descriptions[$key];
        $cassette_number = $cassette_numbers[$key];
        $tissue = $tissues[$key];

        // Check if bone is set for this particular section
        $bone = in_array($section_code, $bones) ? "yes" : "no";  // Set to "yes" if checked, otherwise "no"

        // Get the requires_slide_for_block value for the current index
        $requiresSlideForBlock = $requires_slide_for_block[$key]; // Capture the input for each section
        $decalcifiedbone = $decalcified_bone[$key];


        // Execute the SQL statement
        $result = pg_execute($pg_con, "insert_specimen_section", [$fk_gross_id, $section_code, 
                            $specimen_section_description, $cassette_number, $tissue, $bone, $requiresSlideForBlock, $decalcifiedbone,
                            $update_user]);

        if (!$result) {
            exit();
        }
        
        // Insert batch details cassettes
        $batch_result = pg_execute($pg_con, "insert_batch_cassette", [
            $batch_detail_id, // Use the batch_detail_id correctly
            $cassette_number, 
            $created_user
        ]);

        if (!$batch_result) {
            exit();
        }
        
    }

    // to redirect after successful insertion
    echo '<script>';
    echo 'window.location.href = "gross_update.php?fk_gross_id=' . $fk_gross_id . '";'; 
    echo '</script>';

    pg_close($pg_con);

} else {
    header("Location: gross_specimens.php");
    exit();
}
?>