<?php
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $specimens = isset($_POST['specimen']) ? $_POST['specimen'] : [];
    $lab_numbers = isset($_POST['lab_number']) ? $_POST['lab_number'] : [];
    $fk_gross_ids = isset($_POST['fk_gross_id']) ? $_POST['fk_gross_id'] : [];
    $descriptions = isset($_POST['description']) ? $_POST['description'] : [];
    $created_users = isset($_POST['created_user']) ? $_POST['created_user'] : [];
    $statuses = isset($_POST['status']) ? $_POST['status'] : [];
    $row_ids = isset($_POST['row_id']) ? $_POST['row_id'] : [];
    $histologic_types = isset($_POST['histologic_type']) ? $_POST['histologic_type'] : [];
    $hitologic_grades = isset($_POST['hitologic_grade']) ? $_POST['hitologic_grade'] : [];
    $pattern_of_growths = isset($_POST['pattern_of_growth']) ? $_POST['pattern_of_growth'] : [];
    $stromal_reactions = isset($_POST['stromal_reaction']) ? $_POST['stromal_reaction'] : [];
    $depth_of_invasions = isset($_POST['depth_of_invasion']) ? $_POST['depth_of_invasion'] : [];
    $lymphovascular_invasions = isset($_POST['lymphovascular_invasion']) ? $_POST['lymphovascular_invasion'] : [];
    $perineural_invasions = isset($_POST['perineural_invasion']) ? $_POST['perineural_invasion'] : [];
    $bones = isset($_POST['bone']) ? $_POST['bone'] : [];
    $lim_nodes = isset($_POST['lim_node']) ? $_POST['lim_node'] : [];
    $ptnm_titles = isset($_POST['ptnm_title']) ? $_POST['ptnm_title'] : [];
    $pt2s = isset($_POST['pt2']) ? $_POST['pt2'] : [];
    $pnxs = isset($_POST['pnx']) ? $_POST['pnx'] : [];
    $pmxs = isset($_POST['pmx']) ? $_POST['pmx'] : [];
    $resection_margins = isset($_POST['resection_margin']) ? $_POST['resection_margin'] : [];

    // Prepare update statement (excluding lab_number update)
    $stmt = pg_prepare($pg_con, "update_statement", 
    "UPDATE llx_micro SET 
    fk_gross_id = $1, 
    specimen = $2, 
    description = $3, 
    created_user = $4, 
    status = $5,
    histologic_type = $6,
    hitologic_grade = $7,
    pattern_of_growth = $8, 
    stromal_reaction = $9,
    depth_of_invasion = $10,
    lymphovascular_invasion = $11,
    perineural_invasion = $12,
    bone = $13,
    lim_node = $14,
    ptnm_title = $15,
    pnx = $16,
    pmx = $17,
    resection_margin = $18,
    pt2 = $19
    WHERE row_id = $20");

    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    $success = true;

    // Loop through each description and update database
    for ($i = 0; $i < count($row_ids); $i++) {
        $result = pg_execute($pg_con, "update_statement", array(
            $fk_gross_ids[$i],
            pg_escape_string($specimens[$i]),
            pg_escape_string($descriptions[$i]),
            pg_escape_string($created_users[$i]),
            pg_escape_string($statuses[$i]),
            pg_escape_string($histologic_types[$i]),
            pg_escape_string($hitologic_grades[$i]),
            pg_escape_string($pattern_of_growths[$i]),
            pg_escape_string($stromal_reactions[$i]),
            pg_escape_string($depth_of_invasions[$i]),
            pg_escape_string($lymphovascular_invasions[$i]),
            pg_escape_string($perineural_invasions[$i]),
            pg_escape_string($bones[$i]),
            pg_escape_string($lim_nodes[$i]),
            pg_escape_string($ptnm_titles[$i]),
            pg_escape_string($pnxs[$i]),
            pg_escape_string($pmxs[$i]),
            pg_escape_string($resection_margins[$i]),
            pg_escape_string($pt2s[$i]),
            $row_ids[$i]
        ));

        if (!$result) {
            echo "Error updating data: " . pg_last_error($pg_con);
            $success = false;
            exit();
        }
    }

    $update_lab_number = trim($lab_numbers[0], '.');
    print('Update Lab Number'. $update_lab_number);
    if ($success) {
        // Redirect after successful update
        echo '<script>alert("Data updated successfully!");</script>';
        echo '<script>';
        echo 'window.location.href = "hpl_transcription_list.php?lab_number=' . $update_lab_number . '"';
        echo '</script>';
        exit();
    }

    header("Location: transcriptionindex.php");
    exit();
} else {
    // Redirect if not a POST request
    header("Location: transcriptionindex.php");
    exit();
}
?>
