<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['gross_specimen_section_Id', 'sectionCode', 'specimen_section_description', 'cassetteNumber'];
    $missing_fields = array_diff($required_fields, array_keys($_POST));

    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', $missing_fields);
        exit();
    }

    // Prepare the SQL statement outside the loop
    $sql = "UPDATE llx_gross_specimen_section
            SET section_code = $2,
                specimen_section_description = $3,
                cassettes_numbers = $4
            WHERE gross_specimen_section_Id = $1";

    $stmt = pg_prepare($pg_con, "update_specimen_section", $sql);

    if (!$stmt) {
        $error_message = "Error preparing statement: " . pg_last_error($pg_con);
        error_log($error_message);
        echo "<script>alert('$error_message');</script>";
        exit();
    }

    // Loop through each specimen section and update the data
    for ($i = 0; $i < count($_POST['gross_specimen_section_Id']); $i++) {
        $gross_specimen_section_Id = intval($_POST['gross_specimen_section_Id'][$i]); // Ensure integer
        $section_code = $_POST['sectionCode'][$i];
        $specimen_section_description = pg_escape_string($pg_con, $_POST['specimen_section_description'][$i]); // Sanitize user input
        $cassette_number = $_POST['cassetteNumber'][$i];

        

        // Execute the prepared statement with the parameters
        $result = pg_execute($pg_con, "update_specimen_section", [$gross_specimen_section_Id, $section_code, $specimen_section_description, $cassette_number]);

        if (!$result) {
            $error_message = "Error updating data for section " . ($i + 1) . ": " . pg_last_error($pg_con);
            error_log($error_message);
            echo "<script>alert('$error_message');</script>";
        } else {
            echo '<script>alert("Data for section ' . ($i + 1) . ' updated successfully!");</script>';
        }
    }

    // Redirect to the summary page after updating all data
    $fk_gross_id = $_POST['fk_gross_id'][0]; // Assuming fk_gross_id is the same for all sections
    echo '<script>window.location.href = "gross_update.php?fk_gross_id=' . $fk_gross_id . '";</script>';

    pg_close($pg_con);
} else {
    header("Location: gross_specimens.php");
    exit();
}
?>
