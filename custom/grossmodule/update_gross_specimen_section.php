<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['gross_specimen_section_Id', 'sectionCode', 'specimen_section_description', 'tissue'];
    $missing_fields = array_diff($required_fields, array_keys($_POST));

    if (!empty($missing_fields)) {
        echo "Error: Missing required inputs: " . implode(', ', $missing_fields);
        exit();
    }

    // Prepare the SQL statement outside the loop
    $sql = "UPDATE llx_gross_specimen_section
            SET section_code = $2,
                specimen_section_description = $3,
                tissue = $4,
                bone = $5,
                requires_slide_for_block = $6
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
        $tissue = $_POST['tissue'][$i];
        $bone = isset($_POST['bone'][$i]) ? $_POST['bone'][$i] : '';
        $requires_slide_for_block = $_POST['requires_slide_for_block'][$i]; 

        // Execute the prepared statement with the parameters
        $result = pg_execute($pg_con, "update_specimen_section", [$gross_specimen_section_Id, $section_code, 
        $specimen_section_description, $tissue, $bone, $requires_slide_for_block]);

    }

    if (!$result) { 
            $error_message = "Error updating data for section : " . pg_last_error($pg_con);
            error_log($error_message);
            echo "<script>alert('$error_message');</script>";
        } else {
            echo '';
        }

    // Redirect to the summary page after updating all data
    header("Location: " . $_SERVER['HTTP_REFERER']);  // Redirects to the previous page
    exit();  // Stops further script execution

    pg_close($pg_con);
} else {
    header("Location: gross_specimens.php");
    exit();
}
?>