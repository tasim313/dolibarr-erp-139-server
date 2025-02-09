<?php
include("../connection.php");
include('../../../grossmodule/gross_common_function.php');
include('../../../transcription/common_function.php');
include('../../../transcription/FNA/function.php');
include('../../common_function.php');


$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("cytology@cytology"));

$action = GETPOST('action', 'aZ09');


$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("CytologyArea"));



$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);

$LabNumber = $_GET['labNumber'];


$assistants = get_cyto_tech_list();
foreach ($assistants as $assistant) {
    if ($assistant['username'] == $loggedInUsername) {
        $isCytoAssistant = true;
        break;
    }
}

$doctors = get_doctor_list();
foreach ($doctors as $doctor) {
    if ($doctor['doctor_username'] == $loggedInUsername) {
        $isDoctor = true;
        break;
    }
}

// Access control using switch statement
switch (true) {
    case $isCytoAssistant:
        // cyto Assistant has access, continue with the page content...
        break;
    case $isDoctor:
        // Doctor has access, continue with the page content...
        break;
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
        
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../bootstrap-3.4.1-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../bootstrap-3.4.1-dist/js/bootstrap.min.js">
</head>
<body>

<div class="container">
    <h3>Cyto Lab WorkFlow</h3>
        <ul class="nav nav-tabs">
            <li class="active"><a href="../index.php">Home</a></li>
            <li><a href="./mfc.php">MFC</a></li>
            <li><a href="./special_instruction.php" class="tab">Special Instructions</a></li>
            <li><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
            <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
            <li><a href="./sbo.php">SBO(Slide Block Order)</a></li>
            <li><a href="../recall.php">Re-Call</a></li>
            <li><a href="./doctor_instruction.php">Doctor's Instructions</a></li>
            <li><a href="./cancel_information.php">Cancel Information</a></li>
            <li><a href="./postpone_information.php">Postpone</a></li>
        </ul>
    <br>
    <div class="content">
        <h1>Status</h1>

        <div id="form-box">
            <div id="input-group">
                <input id="input-field" placeholder="Scan Lab number" type="text" autofocus>
                <label id="input-label">Enter or Scan the Lab Number</label>
            </div>
        </div>
         <br>
        <div class="content">
            <!-- cyto information -->
            <?php 
                    // Get the data
                    $cyto_status = get_cyto_list($LabNumber);
                    
                    if (is_array($cyto_status) && count($cyto_status) > 0) {
                        $cyto_id = $cyto_status[0]['rowid'];
                        // If data is found, create a table to display it
                        echo '<table class="table table-bordered table-striped">';
                        
                        // List of fields to exclude
                        $exclude_fields = ['rowid', 'status', 'created_user', 'updated_user', 'updated_date'];
                    
                        // Loop through the rows of the cyto_status
                        foreach ($cyto_status as $row) {
                            // Check if row is an array
                            if (is_array($row)) {
                                // Create a row for the field names (headers) - this will be done only once
                                echo '<tr>';
                                foreach ($row as $field => $value) {
                                    // Skip the fields we want to exclude
                                    if (in_array($field, $exclude_fields)) {
                                        continue;
                                    }
                    
                                    // If the field exists and is not empty, display the field name as header
                                    if (!empty($value)) {
                                        // If the field is 'created_date', format it as '1 December, 2024'
                                        if ($field === 'created_date' && !empty($value)) {
                                            $formatted_date = date('j F, Y', strtotime($value)); // Format date
                                            echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                        } else {
                                            echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                        }
                                    }
                                }
                                echo '</tr>';
                    
                                // Create a row for the field values (values row)
                                echo '<tr>';
                                foreach ($row as $field => $value) {
                                    // Skip the fields we want to exclude
                                    if (in_array($field, $exclude_fields)) {
                                        continue;
                                    }
                    
                                    // If the field exists and is not empty, display the value
                                    if (!empty($value)) {
                                        // If the field is 'created_date', display the formatted date
                                        if ($field === 'created_date' && !empty($value)) {
                                            $formatted_date = date('j F, Y', strtotime($value)); // Format date
                                            echo '<td>' . htmlspecialchars($formatted_date) . '</td>'; // Value
                                        } else {
                                            echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                        }
                                    }
                                }
                                echo '</tr>';
                            }
                        }
                    
                        echo '</table>';
                    }
            ?>

            <!-- Clinical Information -->
            <?php 
                // Fetch the data
                $cyto_clinical_information = get_cyto_clinical_information($cyto_id);

                if (is_array($cyto_clinical_information) && count($cyto_clinical_information) > 0) {
                    // If data is found, create a table to display it
                    echo '<table class="table table-bordered table-striped">';
                    
                    // List of fields to exclude
                    $exclude_fields = ['rowid', 'cyto_id'];

                    // Create a flag to check if headers are already displayed
                    $headers = false;

                    // Loop through each row in the data
                    foreach ($cyto_clinical_information as $row) {
                        // Ensure the row is an array before continuing
                        if (is_array($row)) {
                            // Create a row for the field names (headers) - this will be done only once
                            if (!$headers) {
                                echo '<tr>';
                                foreach ($row as $field => $value) {
                                    // Skip the fields we want to exclude
                                    if (in_array($field, $exclude_fields)) {
                                        continue;
                                    }

                                    // Display the field name as a header if it exists
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>';
                                }
                                echo '</tr>';
                                $headers = true; // Only display headers once
                            }

                            // Create a row for the field values (values row)
                            echo '<tr>';
                            foreach ($row as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // Check if the value is empty, if so, display an empty cell
                                if (empty($value)) {
                                    echo '<td></td>'; // Empty cell for missing value
                                } else {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Display value
                                }
                            }
                            echo '</tr>';
                        }
                    }

                    echo '</table>';
                } 
            ?>
            
            <!-- fixation details -->
            <?php 
                // Fetch the data
                $cyto_fixation_information = get_cyto_fixation_details($cyto_id);

                if (is_array($cyto_fixation_information) && count($cyto_fixation_information) > 0) {
                    // If data is found, create a table to display it
                    echo '<table class="table table-bordered table-striped">';
                    
                    // List of fields to exclude
                    $exclude_fields = ['rowid', 'cyto_id'];

                    // Create a flag to check if headers are already displayed
                    $headers = false;

                    // Loop through each row in the data
                    foreach ($cyto_fixation_information as $row) {
                        // Ensure the row is an array before continuing
                        if (is_array($row)) {
                            // Create a row for the field names (headers) - this will be done only once
                            if (!$headers) {
                                echo '<tr>';
                                foreach ($row as $field => $value) {
                                    // Skip the fields we want to exclude
                                    if (in_array($field, $exclude_fields)) {
                                        continue;
                                    }

                                    // Display the field name as a header if it exists
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>';
                                }
                                echo '</tr>';
                                $headers = true; // Only display headers once
                            }

                            // Create a row for the field values (values row)
                            echo '<tr>';
                            foreach ($row as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // Check if the value is empty, if so, display an empty cell
                                if (empty($value)) {
                                    echo '<td></td>'; // Empty cell for missing value
                                } else {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Display value
                                }
                            }
                            echo '</tr>';
                        }
                    }

                    echo '</table>';
                } 
            ?>
            
            <!-- fixation additional details -->
            <?php 
                // Fetch the data
                $cyto_fixation_additional_information = get_cyto_fixation_additional_details($cyto_id);

                if (is_array($cyto_fixation_additional_information) && count($cyto_fixation_additional_information) > 0) {
                    // If data is found, create a table to display it
                    echo '<table class="table table-bordered table-striped">';
                    
                    // List of fields to exclude
                    $exclude_fields = ['rowid', 'cyto_id'];

                    // Create a flag to check if headers are already displayed
                    $headers = false;

                    // Loop through each row in the data
                    foreach ($cyto_fixation_additional_information as $row) {
                        // Ensure the row is an array before continuing
                        if (is_array($row)) {
                            // Create a row for the field names (headers) - this will be done only once
                            if (!$headers) {
                                echo '<tr>';
                                foreach ($row as $field => $value) {
                                    // Skip the fields we want to exclude
                                    if (in_array($field, $exclude_fields)) {
                                        continue;
                                    }

                                    // Display the field name as a header if it exists
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>';
                                }
                                echo '</tr>';
                                $headers = true; // Only display headers once
                            }

                            // Create a row for the field values (values row)
                            echo '<tr>';
                            foreach ($row as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // Check if the value is empty, if so, display an empty cell
                                if (empty($value)) {
                                    echo '<td></td>'; // Empty cell for missing value
                                } else {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Display value
                                }
                            }
                            echo '</tr>';
                        }
                    }

                    echo '</table>';
                } 
            ?>
            
            <!-- Recall -->
           <?php 
             
             
             $formatted_LabNumber = substr($LabNumber, 3);
             $recall_status = cyto_recall_lab_number($formatted_LabNumber);
             
                if ($recall_status ) {
                    echo("<h3>Recall Information</h3><br>");
                    $recall_cyto_id = $recall_status['rowid'];
                    // recall clinical information
                    $recall_clinical_information = cyto_recall_clinical_information($recall_cyto_id);
                        // Check if we have valid data or an error message
                        if (is_array($recall_clinical_information)) {
                            // If data is found, create a table to display it
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id', 'chief_complain'];

                            // Create a row for the field names (headers)
                            echo '<tr>';
                            foreach ($recall_clinical_information as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // If the field exists and is not empty, display the field name
                                if (!empty($value)) {
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                }
                            }
                            echo '</tr>';

                            // Create a row for the field values
                            echo '<tr>';
                            foreach ($recall_clinical_information as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }

                                // If the field exists and is not empty, display the value
                                if (!empty($value)) {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                }
                            }
                            echo '</tr>';

                            echo '</table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<p>' . $recall_clinical_information . '</p>';
                        }
                        
                        // recall fixation details
                        $recall_fixation_details = cyto_recall_fixation_details($recall_cyto_id);
                        if (is_array($recall_fixation_details)) {
                            // If data is found, create a table to display it
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id'];
                        
                            // Create a row for the field names (headers)
                            echo '<tr>';
                            foreach ($recall_fixation_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the field name
                                if (!empty($value)) {
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                }
                            }
                            echo '</tr>';
                        
                            // Create a row for the field values
                            echo '<tr>';
                            foreach ($recall_fixation_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the value
                                if (!empty($value)) {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                }
                            }
                            echo '</tr>';
                        
                            echo '</table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<p>' . $recall_fixation_details . '</p>';
                        }


                        // recall fixation additional details
                        $recall_fixation_additional_details = cyto_recall_fixation_additional_details($recall_cyto_id);
                        if (is_array($recall_fixation_additional_details)) {
                            // If data is found, create a table to display it
                            echo '<table class="table table-bordered table-striped">';
                            
                            // List of fields to exclude
                            $exclude_fields = ['rowid', 'cyto_id'];
                        
                            // Create a row for the field names (headers)
                            echo '<tr>';
                            foreach ($recall_fixation_additional_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the field name
                                if (!empty($value)) {
                                    echo '<th>' . ucfirst(str_replace('_', ' ', $field)) . '</th>'; // Field Name
                                }
                            }
                            echo '</tr>';
                        
                            // Create a row for the field values
                            echo '<tr>';
                            foreach ($recall_fixation_additional_details as $field => $value) {
                                // Skip the fields we want to exclude
                                if (in_array($field, $exclude_fields)) {
                                    continue;
                                }
                        
                                // If the field exists and is not empty, display the value
                                if (!empty($value)) {
                                    echo '<td>' . htmlspecialchars($value) . '</td>'; // Value
                                }
                            }
                            echo '</tr>';
                        
                            echo '</table>';
                        } else {
                            // If no data was found or there's an error
                            echo '<p>' . $recall_fixation_additional_details . '</p>';
                        }
                    
                } 
           ?>

           <!-- recall management -->
            <?php 
                // Fetch the data
                $cyto_recall_information = cyto_recall_information_list_by_lab_number($formatted_LabNumber);

                // Check for errors or empty data
                if (isset($cyto_recall_information['error'])) {
                    echo "<p>Error: " . $recall_data['error'] . "</p>";
                } elseif (isset($cyto_recall_information['message'])) {
                    echo "<p>" . $cyto_recall_information['message'] . "</p>";
                } else {
                    // Display data in a table if available
                    if (count($cyto_recall_information) > 0) {
                        echo '<table id="recallTable" class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Lab Number</th>';
                        echo '<th>Recall Reason</th>';
                        echo '<th>Created Date</th>';
                        echo '<th>Doctor</th>';
                        echo '<th>Notified User</th>';
                        echo '<th>Notified Method</th>';
                        echo '<th>Follow-Up Date</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        // Loop through each record and create a table row
                        foreach ($cyto_recall_information as $row) {
                            // Decode the JSON data for recall reasons (dynamic keys)
                            $recall_data_json = json_decode($row['recall_reason'], true);

                            // Prepare the output for each person dynamically
                            $formatted_recall_reasons = '';
                            foreach ($recall_data_json as $person => $records) {
                                $formatted_recall_reasons .= '<strong style="font-size: 18px;">' . htmlspecialchars($person) . '</strong><br>';
                                foreach ($records as $record) {
                                    $reasons = isset($record['reason']) ? implode(", ", $record['reason']) : '';
                                    $timestamp = new DateTime($record['timestamp']);
                                    $timestamp->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    $formatted_recall_reasons .= 'Reasons: ' . htmlspecialchars($reasons) . '<br>';
                                    $formatted_recall_reasons .= 'Timestamp: ' . $timestamp->format('d F, Y h:i A') . '<br><br>';
                                }
                            }

                            // Format the created date
                            $created_date = new DateTime($row['created_date']);
                            $created_date->setTimezone(new DateTimeZone('Asia/Dhaka'));
                            $formatted_date = $created_date->format('d F, Y h:i A');

                            // Format notified methods
                            $notified_methods = $row['notified_method'] ? implode(", ", explode(", ", $row['notified_method'])) : '';

                            // Format follow-up date
                            $follow_up_date = $row['follow_up_date'] ? (new DateTime($row['follow_up_date']))->setTimezone(new DateTimeZone('Asia/Dhaka'))->format('d F, Y h:i A') : '';

                            // Display each row
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['lab_number']) . '</td>';
                            echo '<td>' . $formatted_recall_reasons . '</td>';
                            echo '<td>' . $formatted_date . '</td>';
                            echo '<td>' . htmlspecialchars($row['recalled_doctor']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['notified_user']) . '</td>';
                            echo '<td>' . $notified_methods . '</td>';
                            echo '<td>' . $follow_up_date . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo "<p>No data available for the specified status.</p>";
                    }
                }
            ?>
            
        </div>


    </div>

</div>


<script>
    document.getElementById('input-field').addEventListener('keypress', function (e) {
        const labNumber = document.getElementById('input-field').value; // Capture the value

        if (e.key === 'Enter' && labNumber) {  // Redirect only if 'Enter' is pressed and the input field is not empty
            // Redirect to view/search.php with the lab number as a query parameter
            const labNumberWithPrefix = 'FNA' + labNumber;
            window.location.href = `search.php?labNumber=${encodeURIComponent(labNumberWithPrefix)}`;
        }
    });
</script>

</body>
</html>
