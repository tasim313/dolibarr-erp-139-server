<?php
// include("connection.php");
// include('../grossmodule/gross_common_function.php');


// // Check if lab_number is set in the URL parameters
// if (!isset($_GET['lab_number'])) {
//     echo "Error: lab_number is not set.";
//     exit();
// }

// $LabNumber = $_GET['lab_number'];

// // Debugging output to confirm that lab_number is retrieved
// // echo "Debug: lab_number retrieved from URL is: " . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . "<br>";

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // Escape and sanitize input data
//     $new_descriptions = isset($_POST['new_description']) ? $_POST['new_description'] : array();
//     $specimen_rowids = isset($_POST['specimen_rowid']) ? $_POST['specimen_rowid'] : [];

//     // Ensure the arrays have the same length
//     if (count($new_descriptions) !== count($specimen_rowids)) {
//         echo "Error: Mismatch between descriptions and specimen IDs.";
//         exit();
//     }

//     // Prepare update statement (excluding lab_number update)
//     $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_commandedet SET description = $1 WHERE rowid = $2");

//     if (!$stmt) {
//         echo "Error preparing statement: " . pg_last_error($pg_con);
//         exit();
//     }

//     $success = true;

//     // Debugging output to log the data
//     echo "Debugging data before update:<br>";
//     for ($i = 0; $i < count($specimen_rowids); $i++) {
//         echo "Description: " . htmlspecialchars($new_descriptions[$i], ENT_QUOTES, 'UTF-8') . " - Specimen Row ID: " . htmlspecialchars($specimen_rowids[$i], ENT_QUOTES, 'UTF-8') . "<br>";

//         // Validate specimen_rowid
//         if (empty($specimen_rowids[$i]) || !is_numeric($specimen_rowids[$i])) {
//             echo "Error: Specimen row ID is invalid.";
//             $success = false;
//             break;
//         }

//         // Execute the prepared statement
//         $result = pg_execute($pg_con, "update_statement", array(
//             pg_escape_string($pg_con, $new_descriptions[$i]),
//             $specimen_rowids[$i]
//         ));

//         if (!$result) {
//             echo "Error updating data: " . pg_last_error($pg_con);
//             $success = false;
//             break;
//         }
//     }

//     if ($success) {
//         // Redirect after successful update
//         echo '<script>';
//         echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
//         echo '</script>';
//         exit();
//     } else {
//         echo "Error: One or more updates failed.";
//     }
// } else {
//     // Redirect if not a POST request
//     echo '<script>';
//     echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
//     echo '</script>';
//     exit();
// }
?>
<?php
include("connection.php");
include('../grossmodule/gross_common_function.php');

// Check if lab_number is set in the URL parameters
if (!isset($_GET['lab_number'])) {
    echo "Error: lab_number is not set.";
    exit();
}

$LabNumber = $_GET['lab_number'];
// Fetch the gross_id securely using a parameterized query
$query_gross_id = "SELECT gross_id FROM llx_gross WHERE lab_number = $1";
$result_gross_id = pg_query_params($pg_con, $query_gross_id, array($LabNumber));

if ($result_gross_id) {
    $row = pg_fetch_assoc($result_gross_id);
    
    if ($row) {
        $gross_id = $row['gross_id'];
        
        // Fetch the specimen_id and specimen based on the gross_id
        $query_specimen = "SELECT specimen_id, specimen FROM llx_gross_specimen WHERE fk_gross_id = $1";
        $result_specimen = pg_query_params($pg_con, $query_specimen, array($gross_id));

        // Store specimen names in an array for reference
        $specimen_names = [];
        if ($result_specimen) {
            while ($specimen_row = pg_fetch_assoc($result_specimen)) {
                $specimen_id = $specimen_row['specimen_id'];
                $specimen_name = $specimen_row['specimen'];
                $specimen_names[$specimen_id] = $specimen_name; // Store specimen names by their IDs
            }
        } else {
            echo "Error fetching specimen data: " . pg_last_error($pg_con);
        }
    } else {
        echo "Error: No gross_id found for the given lab_number.";
    }
} else {
    echo "Error fetching gross_id: " . pg_last_error($pg_con);
}


// Fetch the micro specimen if available
$micro_specimen = "SELECT row_id, specimen FROM llx_micro WHERE lab_number = $1";
$result_micro_specimen = pg_query_params($pg_con, $micro_specimen, array($LabNumber));
$micro_specimen_names = [];

if ($result_micro_specimen) {
    while ($micro_specimen_row = pg_fetch_assoc($result_micro_specimen)) {
        $row_id = $micro_specimen_row['row_id'];
        $micro_specimen_name = $micro_specimen_row['specimen'];
        $micro_specimen_names[$row_id] = $micro_specimen_name; // Store specimen names by their IDs
    }
} else {
    echo "Error fetching micro specimen data: " . pg_last_error($pg_con);
}

// Ensure previous description is fetched before updating
if (!empty($micro_specimen_names)) {
    // Continue with the updating logic
}


// Fetch the diagnosis specimen if available
$diagnosis_specimen = "SELECT row_id, specimen FROM llx_diagnosis WHERE lab_number = $1";
$result_diagnosis_specimen = pg_query_params($pg_con, $diagnosis_specimen, array($LabNumber));
$diagnosis_specimen_names = [];

if ($result_diagnosis_specimen) {
    while ($diagnosis_specimen_row = pg_fetch_assoc($result_diagnosis_specimen)) {
        $row_id = $diagnosis_specimen_row['row_id'];
        $diagnosis_specimen_name = $diagnosis_specimen_row['specimen'];
        $diagnosis_specimen_names[$row_id] = $diagnosis_specimen_name; // Store specimen names by their IDs
    }
} else {
    echo "Error fetching diagnosis specimen data: " . pg_last_error($pg_con);
}

// Ensure previous description is fetched before updating
if (!empty($diagnosis_specimen_names)) {
    // Continue with the updating logic
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape and sanitize input data
    $new_descriptions = isset($_POST['new_description']) ? $_POST['new_description'] : array();
    $specimen_rowids = isset($_POST['specimen_rowid']) ? $_POST['specimen_rowid'] : [];

    // Ensure the arrays have the same length
    if (count($new_descriptions) !== count($specimen_rowids)) {
        echo "Error: Mismatch between descriptions and specimen IDs.";
        exit();
    }

    // Prepare update statement for llx_commandedet
    $stmt = pg_prepare($pg_con, "update_statement", "UPDATE llx_commandedet SET description = $1 WHERE rowid = $2");

    if (!$stmt) {
        echo "Error preparing statement: " . pg_last_error($pg_con);
        exit();
    }

    $success = true;

    // Loop through each specimen_rowid and update the description
    for ($i = 0; $i < count($specimen_rowids); $i++) {
        $specimen_rowid = $specimen_rowids[$i]; // Get the current rowid

        // Fetch the previous description for the current specimen_rowid
        $query = "SELECT description FROM llx_commandedet WHERE rowid = $1";
        $result = pg_query_params($pg_con, $query, array($specimen_rowid));

        if (!$result) {
            echo "Error fetching description: " . pg_last_error($pg_con);
            $success = false;
            break;
        }

        // Fetch the previous description if available  gross specimen
        if ($row = pg_fetch_assoc($result)) {
            $previous_description = $row['description'];
            // Update descriptions for all matching specimens
            $update_count = 0; // To count how many updates were successful
            foreach ($specimen_names as $specimen_id => $specimen_name) {
                // Check if the previous_description matches the current specimen_name
                if ($previous_description === $specimen_name) {
                    // Check if the new description is different from the previous
                    if ($new_descriptions[$i] !== $previous_description) {
                        // Update the description in llx_gross_specimen
                        $update_specimen_query = "UPDATE llx_gross_specimen SET specimen = $1 WHERE specimen_id = $2";
                        $update_result = pg_query_params($pg_con, $update_specimen_query, array($new_descriptions[$i], $specimen_id));

                        if (!$update_result) {
                            // Log error if the update fails
                            echo "Error updating specimen data for specimen_id: " . htmlspecialchars($specimen_id, ENT_QUOTES, 'UTF-8') . ": " . pg_last_error($pg_con) . "<br>";
                            $success = false;
                        } else {
                            // Successful update logging
                            $update_count++; // Increment the successful update count
                        }
                    } else {
                        // Skip redundant update
                        echo "No update needed for specimen_id: " . htmlspecialchars($specimen_id, ENT_QUOTES, 'UTF-8') . ", the description is the same.<br>";
                    }
                } else {
                    // Log if there's no match
                    echo "No match for rowid: " . htmlspecialchars($specimen_rowid, ENT_QUOTES, 'UTF-8') . " and specimen_id: " . htmlspecialchars($specimen_id, ENT_QUOTES, 'UTF-8') . "<br>";
                }
            }

            // Log total updates
            // echo "Total updates made for rowid: " . htmlspecialchars($specimen_rowid, ENT_QUOTES, 'UTF-8') . ": " . $update_count . "<br>";
        } else {
            echo "No description found for rowid: " . htmlspecialchars($specimen_rowid, ENT_QUOTES, 'UTF-8') . "<br>";
        }


        // Updating micro specimens
        if ($previous_description) {
            $micro_update_count = 0;
            foreach ($micro_specimen_names as $row_id => $micro_specimen_name) {
                $normalized_micro_specimen_name = trim(strtolower($micro_specimen_name));
                $normalized_previous_description = trim(strtolower($previous_description));

                // Check if previous description matches the micro specimen name
                if ($normalized_previous_description === $normalized_micro_specimen_name) {
                    // Perform update if new description is different
                    if ($new_descriptions[$i] !== $micro_specimen_name) {
                        $update_micro_query = "UPDATE llx_micro SET specimen = $1 WHERE row_id = $2";
                        $update_micro_result = pg_query_params($pg_con, $update_micro_query, array($new_descriptions[$i], $row_id));

                        if (!$update_micro_result) {
                            echo "Error updating micro specimen for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . ": " . pg_last_error($pg_con) . "<br>";
                            $success = false;
                        } else {
                            $micro_update_count++;
                        }
                    } else {
                        echo "No update needed for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . ", the description is the same.<br>";
                    }
                } else {
                    echo "No match for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . " and previous_description: " . htmlspecialchars($previous_description, ENT_QUOTES, 'UTF-8') . "<br>";
                }
            }

            // Log total updates for micro specimens
            // echo "Total updates made for micro specimens: " . $micro_update_count . "<br>";
        } else {
            echo "No previous description found for micro specimens.<br>";
        }

        // Updating micro specimens
        if ($previous_description) {
            $diagnosis_update_count = 0;
            foreach ($diagnosis_specimen_names as $row_id => $diagnosis_specimen_name) {
                $normalized_diagnosis_specimen_name = trim(strtolower($diagnosis_specimen_name));
                $normalized_previous_description = trim(strtolower($previous_description));

                // Check if previous description matches the diagnosis specimen name
                if ($normalized_previous_description === $normalized_diagnosis_specimen_name) {
                    // Perform update if new description is different
                    if ($new_descriptions[$i] !== $diagnosis_specimen_name) {
                        $update_diagnosis_query = "UPDATE llx_diagnosis SET specimen = $1 WHERE row_id = $2";
                        $update_diagnosis_result = pg_query_params($pg_con, $update_diagnosis_query, array($new_descriptions[$i], $row_id));

                        if (!$update_diagnosis_result) {
                            echo "Error updating diagnosis specimen for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . ": " . pg_last_error($pg_con) . "<br>";
                            $success = false;
                        } else {
                            $diagnosis_update_count++;
                        }
                    } else {
                        echo "No update needed for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . ", the description is the same.<br>";
                    }
                } else {
                    echo "No match for row_id: " . htmlspecialchars($row_id, ENT_QUOTES, 'UTF-8') . " and previous_description: " . htmlspecialchars($previous_description, ENT_QUOTES, 'UTF-8') . "<br>";
                }
            }

            // Log total updates for micro specimens
            // echo "Total updates made for micro specimens: " . $micro_update_count . "<br>";
        } else {
            echo "No previous description found for diagnosis specimens.<br>";
        }


        // Update the description for the current specimen_rowid in llx_commandedet
        $result = pg_execute($pg_con, "update_statement", array(
            pg_escape_string($pg_con, $new_descriptions[$i]),
            $specimen_rowid
        ));

        if (!$result) {
            echo "Error updating data: " . pg_last_error($pg_con);
            $success = false;
            break;
        }
    }

    if ($success) {
        // Redirect after successful update
        echo '<script>';
        echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
        echo '</script>';
        exit();
    } else {
        echo "Error: One or more updates failed.";
    }
} else {
    // Redirect if not a POST request
    echo '<script>';
    echo 'window.location.href = "transcription.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '";'; 
    echo '</script>';
    exit();
}
?>