<?php
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect LabNumber
    $labNumber = isset($_POST['LabNumber']) ? $_POST['LabNumber'] : null;

    // Check if the form contains 'rowid' for update
    $rowid = isset($_POST['rowid']) && !empty($_POST['rowid']) ? $_POST['rowid'] : null;

    if (!$rowid) {
        // Only fetch cyto_id when data is being inserted (not when updating)
        if ($labNumber) {
            $sql = "SELECT rowid FROM llx_cyto WHERE lab_number = $1";
            $params = [$labNumber];
            $result = pg_query_params($pg_con, $sql, $params);

            if ($result) {
                $row = pg_fetch_assoc($result);
                $cyto_id = $row['rowid'];
            } else {
                echo "Error fetching cyto_id for LabNumber: $labNumber";
                exit;
            }
        } else {
            echo "Error: LabNumber is missing for new records.";
            exit;
        }
    }

    if ($rowid) {
        // Update existing record (single row)
        $slide_number = $_POST['slide_number'];
        $location = $_POST['location'];
        $fixation_method = $_POST['fixation_method'];
        $dry = $_POST['dry'];
        $aspiration_materials = $_POST['aspiration_materials'];
        $special_instructions = $_POST['special_instructions'];

        $sql = "UPDATE llx_cyto_fixation_details
                SET slide_number = $1, 
                    location = $2, 
                    fixation_method = $3, 
                    dry = $4,
                    aspiration_materials = $5,
                    special_instructions = $6
                WHERE rowid = $7";

        $params = [
            $slide_number,
            $location,
            $fixation_method,
            $dry,
            $aspiration_materials,
            $special_instructions,
            $rowid
        ];

        $result = pg_query_params($pg_con, $sql, $params);
    } else {
        // Insert multiple new records (only when cyto_id is available)
        if (isset($_POST['fixation_data']) && is_array($_POST['fixation_data'])) {
            foreach ($_POST['fixation_data'] as $data) {
                $slide_number = $data['slideNumber'];
                $location = $data['location'];
                $fixation_method = $data['fixationMethod'];
                $dry = $data['isDry'];
                $aspiration_materials = $data['aspirationMaterials'];
                $special_instructions = $data['specialInstruction'];

                // Insert only when cyto_id is available (for new records)
                if (isset($cyto_id)) {
                    $sql = "INSERT INTO llx_cyto_fixation_details 
                            (slide_number, location, fixation_method, dry, aspiration_materials, special_instructions, cyto_id) 
                            VALUES ($1, $2, $3, $4, $5, $6, $7)";

                    $params = [
                        $slide_number,
                        $location,
                        $fixation_method,
                        $dry,
                        $aspiration_materials,
                        $special_instructions,
                        $cyto_id
                    ];

                    $result = pg_query_params($pg_con, $sql, $params);
                    if (!$result) {
                        echo "Error inserting record: " . pg_last_error($pg_con);
                    }
                }
            }
        }
    }

    // Redirect after completion
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>