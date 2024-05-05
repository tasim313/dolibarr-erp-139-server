<?php 

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['fk_gross_id', 'sectionCode', 'specimen_section_description', 'cassetteNumber', 'tissue'];
    $missing_fields = array_diff_key(array_flip($required_fields), $_POST);
    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', array_keys($missing_fields));
        exit();
    }

    $sql = "INSERT INTO llx_gross_specimen_section (fk_gross_id, section_code, specimen_section_description, cassettes_numbers, tissue)
             VALUES ($1, $2, $3, $4,$5)";

    $stmt = pg_prepare($pg_con, "insert_specimen_section", $sql);

    if (!$stmt) {
        error_log("Error preparing statement: " . pg_last_error($pg_con));
        echo "Error preparing statement.";
        exit();
    }

    $fk_gross_id = $_POST['fk_gross_id'];
    $section_codes = $_POST['sectionCode'];
    $specimen_section_descriptions = $_POST['specimen_section_description'];
    $cassette_numbers = $_POST['cassetteNumber'];
    $tissues = $_POST['tissue'];

    // Insert each specimen section data
    foreach ($section_codes as $key => $section_code) {
        $specimen_section_description = $specimen_section_descriptions[$key];
        $cassette_number = $cassette_numbers[$key];
        $tissue = $tissues[$key];

        $result = pg_execute($pg_con, "insert_specimen_section", [$fk_gross_id, $section_code, $specimen_section_description, $cassette_number, $tissue]);

        if (!$result) {
            error_log("Error inserting data: " . pg_last_error($pg_con));
            echo "Error inserting data.";
            exit();
        }
    }

    echo '<script>';
    echo 'window.location.href = "gross_specimen_section.php?fk_gross_id=' . $fk_gross_id . '";'; 
    echo '</script>';

    pg_close($pg_con);

} else {
    header("Location: gross_specimens.php");
    exit();
}
?>