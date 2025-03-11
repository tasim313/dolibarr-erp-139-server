<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fk_gross_id = $_POST['fk_gross_id'];
    $sectionCodes = $_POST['sectionCode'];
    $descriptions = $_POST['specimen_section_description'];
    $cassetteNumbers = $_POST['cassetteNumber'];
    $tissues = $_POST['tissue'];
    $bones = isset($_POST['bone']) ? $_POST['bone'] : []; // Handle checkboxes
    $re_gross_values = $_POST['re_gross'];
    $requires_slide_values = $_POST['requires_slide_for_block'];
    $decalcified_bone_values = $_POST['decalcified_bone'];
    $lab_number = $_POST['lab_number'];
    $gross_specimen_section_ids = $_POST['gross_specimen_section_id']; // Collecting gross_specimen_section_id

    foreach ($sectionCodes as $index => $section_code) {
        $gross_specimen_section_id = $gross_specimen_section_ids[$index];
        $description = $descriptions[$index];
        $cassette_number = $cassetteNumbers[$index];
        $tissue = $tissues[$index];
        $bone = in_array($section_code, $bones) ? 'Yes' : 'No';
        $re_gross = $re_gross_values[$index];
        $requires_slide = $requires_slide_values[$index];
        $decalcified_bone = $decalcified_bone_values[$index];

        // Check if lab_number and section_code exist
        $checkQuery = "SELECT rowid FROM llx_other_report_gross_specimen_section WHERE lab_number = $1 AND section_code = $2";
        $checkResult = pg_query_params($pg_con, $checkQuery, [$lab_number, $section_code]);

        if (pg_num_rows($checkResult) > 0) {
            // Update existing record
            $updateSQL = "UPDATE llx_other_report_gross_specimen_section 
                SET gross_specimen_section_id = $1, specimen_section_description = $2, cassettes_numbers = $3, 
                    tissue = $4, bone = $5, re_gross = $6, requires_slide_for_block = $7, 
                    decalcified_bone = $8
                WHERE lab_number = $9 AND section_code = $10";
            pg_query_params($pg_con, $updateSQL, [$gross_specimen_section_id, $description, $cassette_number, $tissue, $bone, $re_gross, $requires_slide, $decalcified_bone, $lab_number, $section_code]);
        } else {
            // Insert new record
            $insertSQL = "INSERT INTO llx_other_report_gross_specimen_section 
                (gross_specimen_section_id, fk_gross_id, section_code, specimen_section_description, cassettes_numbers, 
                 tissue, bone, re_gross, requires_slide_for_block, decalcified_bone, lab_number) 
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
            pg_query_params($pg_con, $insertSQL, [$gross_specimen_section_id, $fk_gross_id, $section_code, $description, $cassette_number, $tissue, $bone, $re_gross, $requires_slide, $decalcified_bone, $lab_number]);
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
}
?>