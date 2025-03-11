<?php 

include ("../connection.php");
include ("../function.php");
include('../../common_function.php');
include("../../../grossmodule/gross_common_function.php");
include ("function.php");

$res = 0;

if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';


$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;
$LabNumber = "HPL" . $_GET['LabNumber'];
$lab_number =  $_GET['LabNumber'];
$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
if ($LabNumber !== null) {
    $last_value = substr($LabNumber, 8);
} else {
    echo 'Error: Lab number not found';
}
$abbreviations = get_abbreviations_list();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Number Search</title>
    <!-- Add Bootstrap  -->
    <link href="../../bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        main {
            display: flex;
        }
        main > * {
            border: 0px solid;
        }
        table {
            font-family: helvetica;
        }
        td,
        th {
            padding: 4px;
            min-width: 100px;
            background: white;
            box-sizing: border-box;
            text-align: left;
        }
        .table-container {
            position: relative;
            max-height: 200px;
            width: 500px;
            overflow: scroll;
        }
        
        thead th {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 2;
            
        }
        
        thead th:first-child {
            left: 0;
            z-index: 3;
        }
        
        tfoot {
            position: -webkit-sticky;
            bottom: 0;
            z-index: 2;
        }
        
        tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 2;
            
        }
        
        tfoot td:first-child {
            z-index: 3;
        }
        
        tbody {
            overflow: scroll;
            height: 200px;
        }
        
        /* MAKE LEFT COLUMN FIXEZ */
        tr > :first-child {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
        }
        
        tr > :first-child {
            box-shadow: inset 0px 0px black;
        }
        * {
        box-sizing: border-box;
        }

        input[type=text], select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
        }

        label {
            padding: 12px 12px 12px 0;
            display: inline-block;
        }

        input[type=submit] {
            background-color: rgb(118, 145, 225);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            transition: box-shadow 0.3s ease;
        }

        input[type=submit]:hover {
            background-color: rgb(118, 145, 225);
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        .container {
            border-radius: 5px;
            background-color: #f2f2f2;
            padding: 20px;
        }

        .col-25 {
            float: left;
            width: 10%;
            margin-top: 6px;
        }

        .col-75 {
            float: left;
            width: 90%;
            margin-top: 6px;
        }


        .row::after {
            content: "";
            display: table;
            clear: both;
        }

        div.sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            background-color: white;
            padding: 10px;
            font-size: 14px;
        }

        .field-group {
            margin-bottom: 2px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 2px;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: row;
        }

        .field-group label {
            margin-right: 10px; /* Space between label and input */
        }

        .field-group input[type="text"] {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Description Input: Make it wider */
        .field-group input[name="specimen_section_description[]"] {
            flex: 0.5; /* Larger size for description input */
            min-width: 60px; /* Ensure it is always at least 300px wide */
            margin-right: 10px;
        }

        /* Tissue Input: Make it smaller */
        .field-group input[name="tissue[]"] {
            flex: 0.2; /* Smaller size for tissue input */
            min-width: 10px; /* Minimum width for smaller input */
            margin-right: 10px;
        }
        @media screen and (max-width: 600px) {
        .col-25, .col-75, input[type=submit] {
        width: 100%;
        margin-top: 0;
        }
        }
    </style>
    <style>
        .micro-description-form {
            background-color: #f9f9f9;
            border: 0px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            /* box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); */
        }

        .bold-label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .specimen-textarea {
            width: 100%;
            height: 50px;
            margin-top: 8px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            resize: none; /* Prevent resizing */
        }

        .editor {
            border: 1px solid #ccc;
            border-radius: 4px;
            min-height: 150px;
            margin-bottom: 16px;
        }

        .btn {
            background-color: rgb(118, 145, 225);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .btn:hover {
            background-color: rgb(100, 125, 200);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .grid {
            display: flex;
            justify-content: flex-end;
        }
        .button-class {
            background-color: #4CAF50; /* Green background */
            border: none; /* Remove borders */
            color: white; /* White text */
            padding: 12px 24px; /* Padding */
            text-align: center; /* Center the text */
            text-decoration: none; /* Remove underline */
            display: inline-block; /* Keep it inline */
            font-size: 16px; /* Text size */
            margin: 10px 4px; /* Margin between buttons */
            cursor: pointer; /* Mouse pointer on hover */
            border-radius: 8px; /* Rounded corners */
            transition: background-color 0.3s ease, transform 0.3s ease; /* Transition effects */
        }

        .button-class a {
            color: white; /* Ensure link text is white */
            text-decoration: none; /* Remove underline */
        }

        .button-class:hover {
            background-color: #45a049; /* Darker green on hover */
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        /* Add some shadow and spacing */
        .button-class:active {
            background-color: #3e8e41; /* Even darker on click */
            transform: scale(0.98); /* Slight shrink on click */
        }

        /* Optional: different color for second button */
        .button-class.secondary {
            background-color: #008CBA; /* Blue background */
        }

        .button-class.secondary:hover {
            background-color: #007bb5; /* Darker blue on hover */
        }

    </style>
</head>
<body>
    <div class="mt-5">
        
        <div class="form-group">
            <label for="labNumber">Lab Number</label>
            <input type="text" class="form-control" id="labNumber" readonly value=<?php echo htmlspecialchars($LabNumber)?>>
        </div>

        <div>
            <h2>Select Report Type</h2>
            <form id="duplicateReportForm" action="../../save_duplicate_report_data.php" method="POST">
                <div class="form-group">
                    <label for="reportType">Choose Report Type:</label>
                    <select class="form-control" id="reportType" name="reportType">
                        <option value="duplicate">Duplicate Report</option>
                        <option value="correction">Correction of Report</option>
                        <option value="review">Internal Histopathology Review</option>
                        <option value="corrigendum">Corrigendum</option>
                        <option value="addendum">Addendum</option>
                    </select>
                </div>

                <!-- New Lab Number -->
                <div class="form-group">
                    <label for="newLabNumber">New Lab Number:</label>
                    <input type="text" class="form-control" id="newLabNumber" name="newLabNumber" required>
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" name="previous_lab_number" value="<?php echo htmlspecialchars($LabNumber); ?>">
                <input type="hidden" name="created_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                
                <!-- Additional Hidden Fields -->
                <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($loggedInUserId, ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Submit button -->
                <button class="btn btn-primary text-white" onclick="submitAndRedirect()">Submit</button>
            </form>
        </div>


        <br>
        <?php 
           
        echo '
            <script>
                function submitAndRedirect() {
                    var formData = new FormData(document.getElementById("duplicateReportForm"));
        
                    fetch("../../save_duplicate_report_data.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text()) // Handle response if needed
                    .then(() => {
                        window.open("index.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '&username=' . urlencode($loggedInUsername) . '", "_blank");
                    })
                    .catch(error => console.error("Error submitting form:", error));
                }
            </script>
        ';
        ?>

        <!-- Patient Information -->
        <?php 
            // Fetch other report patient information
            $other_patient_information = other_report_patient_information_by_lab($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_patient_information)) {
                $patient_information = $other_patient_information;
            } else {
                $patient_information = get_patient_details_information($LabNumberWithoutPrefix);
            }
            print('<div class="sticky">');
            print('<form id="patientForm" method="post" action="insert/patient_info.php">'); 
            print('<div class="flex-table-container">
                     <h1>Patient Information</h1>
            </div>');
            foreach ($patient_information as $list) {
                $genderOptions = [
                    '1' => 'Male',
                    '2' => 'Female',
                    '3' => 'Other'
                ];
                $currentGender = $list['Gender'];
                print('
                <table class="fixed-table">
                <thead>
                <tr>
                    <th>Name</th> 
                    <th>Patient Code</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Attendant Number</th>
                    <th>Date of Birth</th>
                    <th>Age</th>
                    <th>Attendant Name</th>
                    <th>Attendant Relation</th>
                    <th>Gender</th>			
                    <th></th>
                </tr>
                <thead>
                <tr>
                <td>
                <input type="hidden" name="lab_number" value="' . htmlspecialchars($LabNumber) . '">
                <input type="text" name="name[]" value="' . $list['name'] . '" placeholder="patinet name">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="patient_code[]" value="' . $list['patient_code'] . '" placeholder="patinet code">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="address[]" value="' . $list['address'] . '" placeholder="patinet address">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td>
                <td>
                <input type="text" name="phone[]" value="' . $list['phone'] . '" placeholder="patinet mobile number">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="fax[]" value="' . $list['fax'] . '" placeholder="Attendant mobile number">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="date_of_birth[]" value="' . $list['date_of_birth'] . '" placeholder="patinet Date of Birth">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="age[]" value="' . $list['Age'] . '" placeholder="Patient Age">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td> 
                <td>
                <input type="text" name="att_name[]" value="' . $list['att_name'] . '" placeholder="Patient Attendant Name">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td>
                <td>
                <input type="text" name="att_relation[]" value="' . $list['att_relation'] . '" placeholder="Patient Attendant Relation">
                <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                </td>
                '
            );

    
            echo '<td>
                <select name="gender[]">';
                    foreach ($genderOptions as $value => $label) {
                        echo '<option value="' . $value . '" ' . ($currentGender == $value ? 'selected' : '') . '>' . $label . '</option>';
                    }
            echo ' </select>
                        <input type="hidden" name="rowid[]" value="' . $list['rowid'] . '">
                    </td>
                    <td>
                        <button id="patient-button" type="submit" name="submit" value="att_relation" class="btn btn-primary">Save</button>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                </form>';
            }
            print('</div>');
        ?>

        <!-- Clinical Details -->
        <?php
            $other_clinical_Details = other_report_clinicalInformation($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_clinical_Details)) {
                $clinicalDetails = $other_clinical_Details;
            } else {
                $clinicalDetails = clinicalInformation($LabNumber);
            }
            
            $clinicalText = $clinicalDetails['clinical_details'] ?? ''; // Get clinical details or empty string
        ?>
            <form id="clinicalDetailsForm" method="post" action="insert/clinical_details.php" class="form-horizontal">
                <div class="form-group">
                    <h2 style="margin-left:15px;">Clinical Details</h2>
                    <div class="col-sm-12">
                        <textarea id="clinicalDetailsTextarea" name="clinical_details" class="form-control" rows="2"><?php echo htmlspecialchars($clinicalText); ?></textarea>
                    </div>
                    <input type="hidden" id="labNumberInput" name="lab_number" value="<?php echo htmlspecialchars($LabNumber); ?>">
                    <input type="hidden" id="createdUserInput" name="created_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">

                    <div class="col-sm-offset-2 col-sm-10 text-right">
                        <button style="margin-top:10px;" id="saveBtn" type="submit" class="btn btn-primary">
                            <?php echo $isUpdating ? 'Update' : 'Save'; ?>
                        </button>
                    </div>

                </div>
            </form>

        <!-- Specimen Information -->
        <?php
            $other_specimen_information = other_report_site_of_specimen($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_specimen_information)) {
                $specimenIformation = $other_specimen_information;
            } else {
                $specimenIformation   = get_gross_specimens_list($LabNumberWithoutPrefix);
            }
            
            print('<form method="post" action="insert/site_of_specimen.php" class="form-horizontal">'); 
            print('<div class="form-group">
                <h2 style="margin-left:15px;">Site Of Specimen</h2>'); 
                foreach ($specimenIformation as $list) {
                    echo('  
                        <div class="col-sm-12">
                            <input type="hidden" name="lab_number" value="' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">
                            <input type="text" name="new_description[]" value="' . $list['specimen'] . '" class="form-control">
                            <input type="hidden" name="specimen_rowid[]" value="' . $list['specimen_rowid'] . '">
                        </div>
                    ');
                }

            echo('
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10 text-right">
                      <button style="margin-top:10px;" type="submit" class="btn btn-primary">Save</button>
                    </div>  
                </div>
                </div>'
            );
            print('</form>');
        ?>

        <!-- Gross Information -->
        <?php 

            $other_specimen = other_report_gross_specimen_description($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_specimen)) {
                $specimens = $other_specimen;
            } else {
                $specimens = get_gross_specimen_description($fk_gross_id);
            } 
            

            print('<form method="post" action="insert/gross_specimen.php">');
            print('<input type="hidden" name="lab_number" value="' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">');
            foreach ($specimens as $index => $specimen) {
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="specimen">Specimen</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<input type="text" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '">';
                echo '</div>';
                echo '</div>';
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="gross_description">Gross Description</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<div id="editor_' . $index . '" class="editor"></div>';
                echo '<textarea name="gross_description[]" id="hidden_gross_description_' . $index . '" style="display:none;">' . htmlspecialchars($specimen['gross_description']) . '</textarea>';
                echo '</div>';
                echo '</div>';
                echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
            }
           
            echo(
                '
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10 text-right">
                        <button style="margin-top:10px;" type="submit" class="btn btn-primary">Save</button>
                    </div>  
                </div>
                '
            );
            echo '</form>';
        ?>

        <!-- Section Code -->
        <?php

           $other_sections = other_report_gross_specimen_section($LabNumber);

           // Check if the function returns valid data
           if (!empty($other_sections)) {
                $sections = $other_sections;
                $specimen_count_value = number_of_specimen($fk_gross_id);
                $alphabet_string = numberToAlphabet($specimen_count_value); 
           } else {
                $sections = get_gross_specimen_section($fk_gross_id);
                $specimen_count_value = number_of_specimen($fk_gross_id);
                $alphabet_string = numberToAlphabet($specimen_count_value); 
           } 

           print('<br>');print('<br>');print('<br>');print('<br>');
           print("<div>");
           
           for ($i = 1; $i <= $specimen_count_value; $i++) {
               $specimenLetter = chr($i + 64); 
               $button_id =  "add-more-" . $i ;
               echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
               echo '<button type="submit" id="' . $button_id . '" data-specimen-letter="' . $specimenLetter . '" onclick="handleButtonClick(this)">Generate Section Codes</button>';
               echo '<br><br>';
           }
           print('<form id="specimen_section_form" method="post" action="insert/specimen_section.php">
           <input type="hidden" name="lab_number" value="' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">
           <div id="fields-container"> 
           </div>
           <br>
           <button id="saveButton" style="display: none;">Save</button>
           </form>');
           print("</div>");
        ?>
        <?php 
            // here is Section code and Description values are displayed and update
            print('<div style="width: 60%; text-align: left; margin-left: 0;">');

            // Add CSS styles for the table and its elements
            echo '<style>
                    table {
                        margin-top: 20px;
                        border-collapse: collapse;
                        width: 100%;
                        table-layout: fixed;  /* Ensure fixed table layout for consistent column width */
                    
                    }

                    th, td {
                        text-align: center;
                        padding: 2px;  /* Reduce padding further to bring columns closer */
                        border: 1px solid #ddd; /* Add a border to table cells */
                    }

                    th {
                        width: 20%;  /* Set a fixed width for the table headers */
                    }

                    td {
                        padding: 2px;  /* Reduce padding for table data cells */
                    }

                    textarea, input[type="text"] {
                        width: 95%;  /* Ensure inputs and textareas fit within their cells */
                        box-sizing: border-box;  /* Prevent overflow by including padding and borders */
                    }

                    tr:nth-child(even) {
                        background-color: #f9f9f9;  /* Lighter shade for even rows */
                    }

                    tr:hover {
                        background-color: #d6e9f9;  /* Row hover effect */
                    }
            </style>';


            // Begin the form
            print('<form id="section-code-form" method="post" action="insert/specimen_section.php">');
            print('<input type="hidden" name="lab_number" value="' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">');
            // Start the table with headers
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Section Code</th>';
            echo '<th>Description</th>';
            echo '<th>Tissue</th>';
            echo '<th>Bone Present</th>';
            echo '</tr>';
            echo '</thead>';

            // Table body
            echo '<tbody>';
            $i = 0;  // Initialize a counter for unique radio button names
            foreach ($sections as $section) {
                echo '<tr>';
                
                // Section Code
                echo '<td>' . htmlspecialchars($section['section_code']) . '</td>';
                
                // Description
                echo '<td>';
                echo '<textarea name="specimen_section_description[]" style="width:120%;">' . htmlspecialchars($section['specimen_section_description']) . '</textarea>';
                echo '<input type="hidden" name="gross_specimen_section_Id[]" value="' . htmlspecialchars($section['gross_specimen_section_id']) . '">';
                echo '<input type="hidden" name="sectionCode[]" value="' . htmlspecialchars($section['section_code']) . '" readonly>';
                echo '</td>';
                
                // Tissue
                echo '<td>';
                echo '<input type="text" name="tissue[]" value="' . htmlspecialchars($section['tissue']) . '" style="width:30%;">';
                echo '</td>';
                print('<input type="hidden" name="cassettes_numbers[]" value="' . htmlspecialchars($section['cassettes_numbers']) . '">');
                print('<input type="hidden" name="re_gross[]" value="' . htmlspecialchars($section['re_gross']) . '">');
                print('<input type="hidden" name="gross_specimen_section_id[]" value="' . htmlspecialchars($section['gross_specimen_section_id']) . '">');
                print('<input type="hidden" name="requires_slide_for_block[]" value="' . htmlspecialchars($section['requires_slide_for_block']) . '">');
                print('<input type="hidden" name="decalcified_bone[]" value="' . htmlspecialchars($section['decalcified_bone']) . '">');
                
                // Bone Present (Radio buttons)
                echo '<td>';
                $boneValue = htmlspecialchars($section['bone']);
                $checkedYes = ($boneValue === 'yes') ? 'checked' : '';
                $checkedNo = ($boneValue === 'no') ? 'checked' : '';

                // Use the same name for the group, with [] for each entry
                echo '<input type="radio" name="bone[' . $i . ']" value="yes" ' . $checkedYes . '> Yes ';
                echo '<input type="radio" name="bone[' . $i . ']" value="no" ' . $checkedNo . '> No ';
                echo '</td>';
                
                echo '</tr>';
                $i++;  // Increment the counter for the next row
            }
            echo '</tbody>';
            echo '</table>';

            // Hidden field for fk_gross_id and submit button
            echo '<input type="hidden" name="fk_gross_id" value="' . htmlspecialchars($fk_gross_id) . '">';
            echo '<input type="submit" value="Save" style="margin-top: 15px;">';

            // End the form and container
            echo '</form>';
            print("</div>");
            print("<br>");
            print("<br>");
            echo '<div><br></div>';
            echo '<div><br></div>';

        ?>

        <!-- Micro Description -->
        <?php 

            // Micro Description
            $other_existingMicroDescriptions = other_report_ExistingMicroDescriptions($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_existingMicroDescriptions)) {
                $existingMicroDescriptions = $other_existingMicroDescriptions;
            } else {
                $existingMicroDescriptions = getExistingMicroDescriptions($LabNumber);
            } 
            
            $specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

            // Ensure $existingMicroDescriptions is an array
            if (!is_array($existingMicroDescriptions)) {
                $existingMicroDescriptions = array();
            }
            echo '<h2 class="heading">Microscopic Description</h2>';
            foreach ($existingMicroDescriptions as $key => $existingDescription) {
                $formId = 'microDescriptionForm' . $key;
                ?>
                <form action="" id="<?php echo $formId; ?>" class="micro-description-form">
                    <div class="form-group">
                        <label for="specimen" class="bold-label">Specimen:</label>
                        <textarea class="specimen-textarea" name="specimen[]"><?php echo htmlspecialchars($existingDescription['specimen']); ?></textarea>
                        <div id="quill-editor-<?php echo $key; ?>" class="editor"></div>

                        <!-- Hidden textarea to store Quill content -->
                        <textarea style="display:none;" id="hidden_description<?php echo $key; ?>" name="description[]" data-index="<?php echo $key; ?>">
                        <?php 
                            // Check if the description is empty and set a default value if it is
                            $micro_pre_define_text = "Sections show";
                            $descriptionValue = !empty($existingDescription['description']) ? htmlspecialchars($existingDescription['description']) : $micro_pre_define_text;
                            echo $descriptionValue; 
                        ?>
                        </textarea>

                        <!-- Hidden input fields -->
                        <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($existingDescription['fk_gross_id']); ?>">
                        <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($existingDescription['created_user']); ?>">
                        <input type="hidden" name="status[]" value="<?php echo htmlspecialchars($existingDescription['status']); ?>">
                        <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($existingDescription['lab_number']); ?>">
                        <input type="hidden" name="row_id[]" value="<?php echo htmlspecialchars($existingDescription['row_id']); ?>">
                    </div>
                    <div class="grid">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
                <?php
            }

        ?>

        <!-- Diagnosis Description -->
        <?php
            // Diagnosis Description
            $other_existingDiagnosisDescriptions = other_report_ExistingDiagnosisDescriptions($LabNumber);

            // Check if the function returns valid data
            if (!empty($other_existingDiagnosisDescriptions)) {
                $existingDiagnosisDescriptions = $other_existingDiagnosisDescriptions;
            } else {
                $existingDiagnosisDescriptions = getExistingDiagnosisDescriptions($LabNumber);
            } 
            
            $specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

            // Ensure $existingDiagnosisDescriptions is an array
            if (!is_array($existingDiagnosisDescriptions)) {
                $existingDiagnosisDescriptions = array();
            }

            echo '<h2 class="heading">Diagnosis Description</h2>';
            echo '<form action="insert/diagnosis.php" id="diagnosisDescriptionForm" method="POST">';

            foreach ($existingDiagnosisDescriptions as $index => $specimen) {
                // Prepare fallback values if some fields are missing
                $description = $specimen['description'] ?? '';
                $title = $specimen['title'] ?? '';
                $comment = $specimen['comment'] ?? '';
                $fk_gross_id = $specimen['fk_gross_id'] ?? '';
                $created_user = $specimen['created_user'] ?? '';
                $status = $specimen['status'] ?? '';
                $lab_number = $specimen['lab_number'] ?? '';
                $row_id = $specimen['row_id'] ?? '';

                // Specimen display
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="specimen">Specimen</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>';
                echo '<input type="text" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '" >';
                echo '</div>';
                echo '</div>';

                // title field 
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="title" class="bold-label" style="width: 120px;">Title:</label>';
                echo '</div>';
                echo '<div class="col-75">';
                // Check if the title is available; otherwise, use "biopsy"
                $titleValue = !empty($specimen['title']) ? htmlspecialchars($specimen['title']) : 'biopsy';
                echo '<input type="text" name="title[]" value="' . $titleValue . '">';
                echo '</div>';
                echo '</div>';

                // Description field with Quill editor
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="description" class="bold-label" style="width: 120px;">Description:</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<div id="diagnosis-quill-editor-' . $index . '" class="editor"></div>';
                echo '<textarea name="description[]" id="diagnosis-textarea-' . $index . '" style="display:none;">' . htmlspecialchars($description) . '</textarea>';
                echo '</div>';
                echo '</div>';

                // Comment field with Quill editor
                echo '<div class="row">';
                echo '<div class="col-25">';
                echo '<label for="comment" class="bold-label" style="width: 120px;">Comment:</label>';
                echo '</div>';
                echo '<div class="col-75">';
                echo '<div id="comment-quill-editor-' . $index . '" class="editor"></div>';
                echo '<textarea name="comment[]" id="comment-textarea-' . $index . '" style="display:none;">' . htmlspecialchars($comment) . '</textarea>';
                echo '</div>';
                echo '</div>';

                // Hidden fields for additional metadata
                echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
                echo '<input type="hidden" name="created_user[]" value="' . htmlspecialchars($created_user) . '">';
                echo '<input type="hidden" name="status[]" value="' . htmlspecialchars($status) . '">';
                echo '<input type="hidden" name="lab_number[]" value="' . htmlspecialchars($lab_number) . '">';
                echo '<input type="hidden" name="row_id[]" value="' . htmlspecialchars($row_id) . '">';
            }

            echo '<div class="grid">
                    <button style="background-color: rgb(118, 145, 225);
                    color: white;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    float: right;
                    transition: box-shadow 0.3s ease;" 
                    id="diagnosisDescriptionSaveButton" type="submit" 
                    name="submit" value="att_relation">Save</button>
                </div>';
            echo '</form>';
        ?>

        <!-- Signature -->
        <?php
            echo "<h2 class='text-center'>Doctor's Signature</h2>";

            // Fetch details and finalized information
            $details = get_duplicate_report_doctor_assisted_by_signature_details($LabNumber);
            $finialized_by = get_duplicate_report_doctor_finalized_by_signature_details($LabNumber);
            $information = get_doctor_degination_details();

            if (!empty($details)) {
                foreach ($details as $list) {
                    echo '<div class="panel panel-default">';
                    echo '<div class="panel-heading"><h3 class="panel-title">Assisted By</h3></div>';
                    echo '<div class="panel-body">';
                    echo '<form method="post" action="duplicate_doctor_signature_update.php" class="form-horizontal">';

                    // Doctor Username Select Field
                    echo '<div class="form-group">';
                    echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                    echo '<div class="col-sm-10">';
                    echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                    echo '<option value=""></option>';
                    foreach ($information as $list_info) {
                        $selected = ($list_info['username'] == $list['username']) ? 'selected' : '';
                        echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
                    }           
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';

                    // Hidden Inputs
                    echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                    echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                    echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update

                    // Submit Button
                    echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-2 col-sm-10 text-right">';
                    echo '<button type="submit" class="btn btn-primary">Update</button>';
                    echo '</div>';
                    echo '</div>';

                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // For new creation
                echo '<div class="panel panel-default">';
                echo '<div class="panel-heading"><h3 class="panel-title">Assisted By</h3></div>';
                echo '<div class="panel-body">';
                echo '<form method="post" action="duplicate_doctor_signature_create.php" class="form-horizontal">';

                // Doctor Username Select Field
                echo '<div class="form-group">';
                echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                echo '<div class="col-sm-10">';
                echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                echo '<option value=""></option>';
                foreach ($information as $list) {
                    $selected = ($list['username'] == $loggedInUsername) ? 'selected' : '';
                    echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
                }        
                echo '</select>';
                echo '</div>';
                echo '</div>';

                // Hidden Inputs
                echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';

                // Submit Button
                echo '<div class="form-group">';
                echo '<div class="col-sm-offset-2 col-sm-10 text-right">';
                echo '<button type="submit" class="btn btn-primary">Save</button>';
                echo '</div>';
                echo '</div>';

                echo '</form>';
                echo '</div>';
                echo '</div>';
            }

            if (!empty($finialized_by)) {
                foreach ($finialized_by as $list) {
                    echo '<div class="panel panel-default">';
                    echo '<div class="panel-heading"><h3 class="panel-title">Finalized By</h3></div>';
                    echo '<div class="panel-body">';
                    echo '<form method="post" action="duplicate_doctor_signature_finalized_update.php" class="form-horizontal">';

                    // Doctor Username Select Field
                    echo '<div class="form-group">';
                    echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                    echo '<div class="col-sm-10">';
                    echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                    echo '<option value=""></option>';
                    foreach ($information as $list_info) {
                        $selected = ($list_info['username'] == $list['username']) ? 'selected' : '';
                        echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
                    }     
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';

                    // Hidden Inputs
                    echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                    echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                    echo '<input type="hidden" name="rowid" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update

                    // Submit Button
                    echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-2 col-sm-10 text-right">';
                    echo '<button type="submit" class="btn btn-primary">Update</button>';
                    echo '</div>';
                    echo '</div>';

                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // For new creation
                echo '<div class="panel panel-default">';
                echo '<div class="panel-heading"><h3 class="panel-title">Finalized By</h3></div>';
                echo '<div class="panel-body">';
                echo '<form method="post" action="duplicate_doctor_signature_finalized_create.php" class="form-horizontal">';

                // Doctor Username Select Field
                echo '<div class="form-group">';
                echo '<label for="doctor_username" class="col-sm-2 control-label">Doctor</label>';
                echo '<div class="col-sm-10">';
                echo '<select id="doctor_username" name="doctor_username" class="form-control">';
                echo '<option value=""></option>';
                foreach ($information as $list) {
                    $selected = ($list['username'] == $loggedInUsername) ? 'selected' : '';
                    echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
                }        
                echo '</select>';
                echo '</div>';
                echo '</div>';

                // Hidden Inputs
                echo '<input type="hidden" name="lab_number" value="' . $LabNumber . '" readonly>';
                echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
                echo '<input type="hidden" name="status" value="Finalized">';

                // Submit Button
                echo '<div class="form-group">';
                echo '<div class="col-sm-offset-2 col-sm-10 text-right">';
                echo '<button type="submit" class="btn btn-primary">Save</button>';
                echo '</div>';
                echo '</div>';

                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
        ?>

        
    </div>


    <!-- Add Bootstrap and jQuery JS -->
    <script src="../../jquery/jquery.min.js"></script>
    <script src="../../bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <!-- Include Quill's CSS and JS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>
        // Patient Information
        document.addEventListener('keydown', function(event) {
            // Check for Ctrl + S (or Command + S on Mac)
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault(); // Prevent default behavior of Enter key
                document.getElementById('patient-button').click(); // Submit the form 
            }
        });

        document.addEventListener('keydown', function(event) {
            // Handle Tab key press
            if (event.key === 'Tab') {
                const form = document.getElementById('patientForm');
                const inputs = form.querySelectorAll('input, select, button');
                const activeElement = document.activeElement;
                const index = Array.prototype.indexOf.call(inputs, activeElement);
                if (index > -1) {
                    let nextIndex = index + 1;
                    while (nextIndex < inputs.length && inputs[nextIndex].disabled) {
                        nextIndex++;
                    }
                    if (nextIndex < inputs.length) {
                        inputs[nextIndex].focus();
                        event.preventDefault();
                    }
                }
            }
        });
    </script>


    <script>
        // Micro Description Update 

        document.addEventListener('keydown', function(event) {
                // Check for Ctrl + S (or Command + S on Mac)
                if (event.ctrlKey && event.key === 's') {
                    event.preventDefault(); // Prevent default behavior of Enter key
                    document.getElementById('micro-button').click(); // Submit the form 
                }
        });


        document.addEventListener('DOMContentLoaded', function() {
            fetch('shortcuts.json')
                .then(response => response.json())
                .then(shortcuts => {
                    function handleShortcutInput(inputElement, cursorPosition) {
                        let text = inputElement.value;
                        let wordStart = text.lastIndexOf(' ', cursorPosition - 1) + 1;
                        let wordEnd = cursorPosition;

                        let word = text.substring(wordStart, wordEnd).trim();

                        if (shortcuts[word]) {
                            inputElement.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                            inputElement.selectionEnd = wordStart + shortcuts[word].length;
                        }
                    }

                    document.querySelectorAll('textarea').forEach(textarea => {
                        textarea.addEventListener('keydown', function(event) {
                            if (event.key === 'Insert') { // Insert key
                                let cursorPosition = this.selectionStart;
                                handleShortcutInput(this, cursorPosition);
                            }
                        });
                    });
                })
                .catch(error => console.error('Error loading shortcuts:', error));
        });


        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".btn.btn-primary").forEach((button) => {
                button.addEventListener("click", function (event) {
                    event.preventDefault();

                    let formData = new FormData();
                    let index = 0;

                    document.querySelectorAll("form[id^='microDescriptionForm']").forEach((form) => {
                        formData.append(`specimen[]`, form.querySelector("[name='specimen[]']").value);
                        formData.append(`description[]`, form.querySelector("[name='description[]']").value);
                        formData.append(`fk_gross_id[]`, form.querySelector("[name='fk_gross_id[]']").value);
                        formData.append(`created_user[]`, form.querySelector("[name='created_user[]']").value);
                        formData.append(`status[]`, form.querySelector("[name='status[]']").value);
                        formData.append(`lab_number[]`, form.querySelector("[name='lab_number[]']").value);
                        formData.append(`row_id[]`, form.querySelector("[name='row_id[]']").value);
                        index++;
                    });

                   
                    
                    for (let pair of formData.entries()) {
                        console.log(pair[0], pair[1]);
                    }

                    fetch("insert/microscopic_description.php", {
                        method: "POST",
                        body: formData,
                    })
                        .then((response) => response.text())
                        .then((data) => {
                            
                            // alert("Microscopic Descriptions saved successfully.");
                            // window.location.reload(); // Refresh the page after saving
                        })
                        .catch((error) => {
                            console.error("Error:", error);
                        });
                });
            });
        });
    </script>

    <script>

        // Diagnosis Description
        document.addEventListener('keydown', function(event) {
                // Check for Ctrl + S (or Command + S on Mac)
                if (event.ctrlKey && event.key === 's') {
                    event.preventDefault(); // Prevent default behavior of Enter key
                    document.getElementById('diagnosisDescriptionSaveButton').click(); // Submit the form 
                }
        });

        document.addEventListener('DOMContentLoaded', function() {
            fetch('shortcuts.json')
                .then(response => response.json())
                .then(shortcuts => {
                    function handleShortcutInput(inputElement, cursorPosition) {
                        let text = inputElement.value;
                        let wordStart = text.lastIndexOf(' ', cursorPosition - 1) + 1;
                        let wordEnd = cursorPosition;

                        let word = text.substring(wordStart, wordEnd).trim();

                        if (shortcuts[word]) {
                            inputElement.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                            inputElement.selectionEnd = wordStart + shortcuts[word].length;
                        }
                    }

                    document.querySelectorAll('textarea').forEach(textarea => {
                        textarea.addEventListener('keydown', function(event) {
                            if (event.key === 'Insert') { // Insert key
                                let cursorPosition = this.selectionStart;
                                handleShortcutInput(this, cursorPosition);
                            }

                            if (event.ctrlKey && event.key === 's') {
                                event.preventDefault(); // Prevent default behavior of Ctrl+S
                                this.closest('form').submit(); // Submit the form containing the textarea
                            }
                        });
                    });
                })
                .catch(error => console.error('Error loading shortcuts:', error));
        });

        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("diagnosisDescriptionForm").addEventListener("submit", function (event) {
                event.preventDefault(); // Prevent the default form submission

                let formData = new FormData(this); // Use the form's data directly

                // Log FormData Key-Value Pairs
                console.log("🟢 Captured Form Data:");
                for (let pair of formData.entries()) {
                    console.log(pair[0], pair[1]);
                }

                fetch("insert/diagnosis.php", {
                    method: "POST",
                    body: formData,
                })
                    .then((response) => response.text())
                    .then((data) => {
                        // console.log("✅ Server Response:", data);
                        // window.location.reload(); // Uncomment to refresh the page after saving
                    })
                    .catch((error) => {
                        console.error("❌ Fetch Error:", error);
                    });
            });
        });

    </script>

    <script>
        document.querySelectorAll('form[id^="microDescriptionForm"]').forEach(form => {
            const formId = form.id.replace('microDescriptionForm', '');
            const fields = document.querySelectorAll(`#fields${formId} input[type="checkbox"]`);
            const dynamicFields = document.getElementById(`dynamicFields${formId}`);
            
            fields.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Track which fields are already added
                    const existingFields = Array.from(dynamicFields.children).map(child => child.dataset.field);
                    
                    // If checkbox is checked, add the field
                    if (this.checked) {
                        if (!existingFields.includes(this.value)) {
                            let fieldHtml = '';
                            switch(this.value) {
                                case 'histologic_type':
                                    fieldHtml = `
                                        <div class="controls" data-field="histologic_type">
                                            <label for="histologic_type" class="bold-label">Histologic Type</label> <button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="histologic_type[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'hitologic_grade':
                                    fieldHtml = `
                                        <div class="controls" data-field="hitologic_grade">
                                            <label for="hitologic_grade" class="bold-label">Histologic Grade</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="hitologic_grade[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'pattern_of_growth':
                                    fieldHtml = `
                                        <div class="controls" data-field="pattern_of_growth">
                                            <label for="pattern_of_growth" class="bold-label">Pattern of Growth</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="pattern_of_growth[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'stromal_reaction':
                                    fieldHtml = `
                                        <div class="controls" data-field="stromal_reaction">
                                            <label for="stromal_reaction" class="bold-label">Stromal Reaction</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="stromal_reaction[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'depth_of_invasion':
                                    fieldHtml = `
                                        <div class="controls" data-field="depth_of_invasion">
                                            <label for="depth_of_invasion" class="bold-label">Depth Of Invasion</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="depth_of_invasion[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'resection_margin':
                                    fieldHtml = `
                                        <div class="controls" data-field="resection_margin">
                                            <label for="resection_margin" class="bold-label">Resection Margin</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="resection_margin[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'lymphovascular_invasion':
                                    fieldHtml = `
                                        <div class="controls" data-field="lymphovascular_invasion">
                                            <label for="lymphovascular_invasion" class="bold-label">Lymphovascular Invasion</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="lymphovascular_invasion[]" cols="10" rows="2"></textarea> 
                                        </div>
                                    `;
                                    break;
                                case 'perineural_invasion':
                                    fieldHtml = `
                                        <div class="controls" data-field="perineural_invasion">
                                            <label for="perineural_invasion" class="bold-label">Perineural Invasion</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="perineural_invasion[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'bone':
                                    fieldHtml = `
                                        <div class="controls" data-field="bone">
                                            <label for="bone" class="bold-label">Bone</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="bone[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'lim_node':
                                    fieldHtml = `
                                        <div class="controls" data-field="lim_node">
                                            <label for="lim_node" class="bold-label">Lymph Node</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="lim_node[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'ptnm_title':
                                    fieldHtml = `
                                        <div class="controls" data-field="ptnm_title">
                                            <label for="ptnm_title" class="bold-label">Ptnm Title</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="ptnm_title[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'pt2':
                                    fieldHtml = `
                                        <div class="controls" data-field="pt2">
                                            <label for="pt2" class="bold-label">Pt2</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="pt2[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'pnx':
                                    fieldHtml = `
                                        <div class="controls" data-field="pnx">
                                            <label for="pnx" class="bold-label">Pnx</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="pnx[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                                case 'pmx':
                                    fieldHtml = `
                                        <div class="controls" data-field="pmx">
                                            <label for="pmx" class="bold-label">Pmx</label><button type="button" class="remove-btn" onclick="removeField(this)">&#10060;</button>
                                            <textarea style=" margin-top: 8px; margin-bottom: 8px;" name="pmx[]" cols="10" rows="2"></textarea>
                                        </div>
                                    `;
                                    break;
                            }
                            dynamicFields.insertAdjacentHTML('beforeend', fieldHtml);
                        }
                    } else {
                        // If checkbox is unchecked, remove the field
                        const fieldToRemove = dynamicFields.querySelector(`[data-field="${this.value}"]`);
                        if (fieldToRemove) {
                            fieldToRemove.remove();
                        }
                    }
                });
            });
        });

        function removeField(button) {
            button.parentElement.remove();
        }
    </script>

    <!-- Gross Description Abbreviations -->
    <script>
            const abbreviations_value = <?php echo json_encode($abbreviations); ?>;
            
            const abbreviations = {};

            // Loop through abbreviations_value and map it to the abbreviations object
            abbreviations_value.forEach(item => {
                // Remove HTML tags using replace with a regex
                const plainText = item.abbreviation_full_text.replace(/<[^>]*>/g, '');
                abbreviations[item.abbreviation_key] = plainText;
            });

            
            // Initialize Quill editor for each textarea
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.editor').forEach((element, index) => {
                    const editor = new Quill(element, {
                        theme: 'snow',
                        modules: {
                            toolbar: []
                        }
                    });

                    // Set the content from the hidden textarea
                    const hiddenTextarea = document.querySelector('#hidden_gross_description_' + index);
                    editor.root.innerHTML = hiddenTextarea.value;

                    // Update the hidden textarea when content changes
                    editor.on('text-change', function() {
                        hiddenTextarea.value = editor.root.innerHTML;
                    });

                    // Add functionality to replace abbreviations
                    editor.root.addEventListener('keydown', function(event) {
                        if (event.key === ' ') { // Check if the space bar is pressed
                            event.preventDefault(); // Prevent default space behavior

                            const text = editor.getText(); // Get the current text in the editor
                            const selection = editor.getSelection(); // Get the current selection range
                            const caretPosition = selection.index;

                            // Find the word before the caret position
                            const textBeforeCaret = text.substring(0, caretPosition);
                            const words = textBeforeCaret.trim().split(/\s+/);
                            const lastWord = words[words.length - 1]; // Get the last word in its original case

                            // Get the character before the word (check for the period rule)
                            const charBeforeLastWord = textBeforeCaret[caretPosition - lastWord.length - 1];

                            // Check if the word is preceded by a period with no space or if it's empty
                            if (charBeforeLastWord === '.' && textBeforeCaret[caretPosition - lastWord.length - 2] !== ' ') {
                                // Just insert the space if the rule applies (no abbreviation generated)
                                editor.insertText(caretPosition, ' ');
                                return;
                            }

                            // Find the abbreviation in a case-sensitive manner
                            const abbreviation = Object.keys(abbreviations).find(key => key.toLowerCase() === lastWord.toLowerCase());
                            
                            if (abbreviation) {
                                const fullAbbreviation = abbreviations[abbreviation];

                                // Replace the last word with the abbreviation only if it's not part of a longer word
                                replaceLastWordWithAbbreviation(editor, lastWord, fullAbbreviation, caretPosition);
                            } else {
                                // If no abbreviation found, just insert a space
                                editor.insertText(caretPosition, ' ');
                            }
                        }
                    });
                });
            });

            // Helper function to replace the last word with the abbreviation
            function replaceLastWordWithAbbreviation(editor, word, abbreviation, caretPosition) {
                const text = editor.getText();
                const textBeforeCaret = text.substring(0, caretPosition);
                
                // Find the start of the word that needs to be replaced
                const startOfWord = textBeforeCaret.lastIndexOf(word);

                // Remove the word first to avoid overlapping or incorrect replacement
                editor.deleteText(startOfWord, word.length);

                // Insert the abbreviation, making sure to trim any extra spaces
                editor.insertText(startOfWord, abbreviation.trim(), 'user');  // 'user' to simulate a normal typing action
                
                // Set the caret position after the inserted abbreviation
                editor.setSelection(startOfWord + abbreviation.length, 0);

                // Debug: Log text after replacement
                console.log("Text after replacement:", editor.getText());
            }
    </script>

    <!--   Display "No" or "Yes" options based on bones_status value -->
    <script>
        function toggleBlockNumber(rowid) {
            const bonesStatus = document.getElementById('bones_status_' + rowid).value;
            const blockNumberContainer = document.getElementById('block_number_container_' + rowid);

            if (bonesStatus === 'Yes') {
                blockNumberContainer.style.display = 'table-cell';
            } else {
                blockNumberContainer.style.display = 'none';
            }
        }
    </script>

    <script>

        const buttonClickCounts = {};
        
        document.getElementById("saveButton").addEventListener("click", function(event) {
            // Prevent the default form submission behavior
            event.preventDefault();

            // Get the form element
            const form = document.getElementById("specimen_section_form");

            // Submit the form
            form.submit();
        });

        let sections = <?php echo json_encode($sections); ?>;
        let lastSectionCodes = {};
        let lastCassetteNumbers = {};
        let lastTissues = {};

        // Iterate over each section to find the last section code, cassette number, and tissue for each specimen
        sections.forEach(function(section) {
            let specimenLetter = section.section_code.charAt(0); // Extract the specimen letter
            let sectionCode = section.section_code;
            let cassetteNumber = section.cassettes_numbers;
            let tissue = section.tissue;

            // Update the last section code for this specimen
            lastSectionCodes[specimenLetter] = sectionCode;

            // Update the last cassette number for this specimen
            if (!lastCassetteNumbers[specimenLetter] || cassetteNumber > lastCassetteNumbers[specimenLetter]) {
                lastCassetteNumbers[specimenLetter] = cassetteNumber;
            }

            // Update the last tissue for this specimen
            if (!lastTissues[specimenLetter] || tissue > lastTissues[specimenLetter]) {
                lastTissues[specimenLetter] = tissue;
            }
        });


        function generateNextSectionCode(specimenLetter) {
            // Generate the next section code
            let sectionCode = '';

            if (!lastSectionCodes[specimenLetter] || lastSectionCodes[specimenLetter] === '') {
                // If the last section code is empty or not set, generate it based on the specimen letter and button click count
                sectionCode = specimenLetter + '1';
            } else {
                // Otherwise, generate it sequentially based on the last section code
                const lastSectionNumber = parseInt(lastSectionCodes[specimenLetter].slice(1), 10);
                if (!isNaN(lastSectionNumber)) {
                    // Increment the last section number and generate the new section code
                    const nextSectionNumber = lastSectionNumber + 1;
                    sectionCode = specimenLetter + nextSectionNumber;
                } else {
                    // Handle the case where lastSectionNumber is NaN (e.g., if lastSectionCode doesn't follow the expected format)
                    console.error("Invalid last section code format:", lastSectionCodes[specimenLetter]);
                    // You can provide a default behavior here, such as setting sectionCode to a predefined value
                    // sectionCode = specimenLetter + "1";
                }
            }
            return sectionCode;
        }
        
        function handleButtonClick(button) {
            const buttonId = button.id;
            const specimenIndex = button.id.split("-")[1]; 
            const specimenLetter = button.getAttribute('data-specimen-letter');
            buttonClickCounts[buttonId] = (buttonClickCounts[buttonId] || 0) + 1;
            const section_text = 'Section from the ';
            const specimen_count_value = <?php echo $specimen_count_value; ?>;
            const last_value = "<?php echo $last_value; ?>";
            const fk_gross_id = "<?php echo $fk_gross_id;?>";
            const fieldsContainer = document.getElementById("fields-container");
            const addMoreButton = document.getElementById("<?php echo $button_id; ?>");
            const currentYear = new Date().getFullYear();
            const lastTwoDigits = currentYear.toString().slice(-2);

            // Generate the next section code
            let sectionCode = generateNextSectionCode(specimenLetter);
            
            // Update the last generated section code
            lastSectionCodes[specimenLetter] = sectionCode;
        

            // Create a new field set for each entry
            const fieldSet = document.createElement("fieldset");
            fieldSet.classList.add("field-group"); // Add a class for styling (optional)
            let sectionCodes = [];
            let cassetteNumbers = [];
            let descriptions = [];
            const br = document.createElement("br");

            const fkGrossIdInput = document.createElement("input");
            fkGrossIdInput.type = "hidden";
            fkGrossIdInput.name = "fk_gross_id"; // Set the name attribute to identify the input
            fkGrossIdInput.value = "<?php echo $fk_gross_id;?>";
            fieldSet.appendChild(fkGrossIdInput);

            // Create the label and input for Section Code
            const sectionCodeLabel = document.createElement("label");
            sectionCodeLabel.textContent = sectionCode +' :';
            const inputSectionCode = document.createElement("input");
            inputSectionCode.type = "hidden"; // Use "text" for Section Code input
            inputSectionCode.name =  "sectionCode[]"; // Assign unique name based on count
            inputSectionCode.value = sectionCode;
            inputSectionCode.type = "hidden";
            const descriptionInput = document.createElement("input");
            descriptionInput.type = "text"; // Use "text" for Description input
            descriptionInput.name = "specimen_section_description[]"; // Assign unique name based on count
            descriptionInput.value = section_text;
            descriptionInput.setAttribute('data-shortcut-file', 'shortcuts.json'); // Specify the shortcut JSON file
            fieldSet.appendChild(sectionCodeLabel);
            fieldSet.appendChild(inputSectionCode);
            fieldSet.appendChild(descriptionInput);
            fieldSet.appendChild(br);

            // Create the label and input for cassetteNumbers
            const cassetteNumberLabel = document.createElement("label");
            cassetteNumberLabel.textContent = "Cassette Number: " + sectionCode + '-' + last_value + '/' + lastTwoDigits;
            const cassetteNumberInput = document.createElement("input");
            cassetteNumberInput.type = "hidden"; // Use "text" for Cassette Number input
            cassetteNumberInput.name = "cassetteNumber[]"; // Assign unique name based on count
            cassetteNumberInput.value = sectionCode + '-' + last_value + '/' + lastTwoDigits;
            fieldSet.appendChild(cassetteNumberInput);
        
            const tissueLabel = document.createElement("label");
            tissueLabel.textContent = "Tissue Pieces In  " + sectionCode 
            const tissueInput = document.createElement("input");
            tissueInput.type = "text"; // Use "text" for Cassette Number input
            tissueInput.name = "tissue[]"; // Assign unique name based on count
            tissueInput.value = '';
            tissueInput.placeholder = "Tissue Pieces In  " + sectionCode ;
            fieldSet.appendChild(tissueInput);

            // Change the Bone selection to a checkbox instead of radio buttons
            const boneLabel = document.createElement("label");
            boneLabel.textContent = "Bone?";

            const boneInput = document.createElement("input");
            boneInput.type = "checkbox"; // Use checkbox for Bone selection
            boneInput.name = "bone[]"; // Use array notation to handle multiple inputs
            boneInput.value = sectionCode; // Use the section code or another identifier to keep track

            // Append the bone checkbox to the fieldSet
            fieldSet.appendChild(boneLabel);
            fieldSet.appendChild(boneInput);


            const saveButton = document.getElementById("saveButton");
            saveButton.style.display = "block";
            
            // fieldSet.appendChild(descriptionLabel);
            fieldsContainer.appendChild(fieldSet);
            console.log("Field Container: ", fieldSet)
        }
    </script>

    <!-- Micro Description Abbreviations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Quill editor for each dynamic microscopic description
                    <?php foreach ($existingMicroDescriptions as $key => $existingDescription): ?>
                        var quillEditor<?php echo $key; ?> = new Quill('#quill-editor-<?php echo $key; ?>', {
                            theme: 'snow',
                            modules: {
                                toolbar: []  // Customize toolbar if needed
                            }
                        });

                        // Set the content of the hidden textarea
                        var hiddenTextarea<?php echo $key; ?> = document.querySelector('#hidden_description<?php echo $key; ?>');
                        quillEditor<?php echo $key; ?>.root.innerHTML = hiddenTextarea<?php echo $key; ?>.value;

                        // Update the hidden textarea when content changes
                        quillEditor<?php echo $key; ?>.on('text-change', function() {
                            hiddenTextarea<?php echo $key; ?>.value = quillEditor<?php echo $key; ?>.root.innerHTML;
                        });

                        // Listen for space key and abbreviation replacement
                        quillEditor<?php echo $key; ?>.root.addEventListener('keyup', function(event) {
                            if (event.key === ' ') {
                                replaceAbbreviation(quillEditor<?php echo $key; ?>, abbreviations);
                            }
                        });
                    <?php endforeach; ?>

                    // Function to replace abbreviations with full text (case-insensitive)
                    function replaceAbbreviation(quillEditor, abbreviations) {
                        var selection = quillEditor.getSelection();
                        if (!selection) return;  // Exit if no selection

                        // Get text before the current cursor position
                        var textBeforeCursor = quillEditor.getText(0, selection.index);
                        
                        // Find the last word before the cursor
                        var lastWordMatch = textBeforeCursor.match(/(\S+)\s*$/);
                        if (!lastWordMatch) return;  // Exit if no word found

                        var lastWord = lastWordMatch[1];
                        var abbrevLowerCase = lastWord.toLowerCase();  // Use lowercase for case-insensitive match

                        // Check if the last word matches any abbreviation
                        for (var abbrev in abbreviations) {
                            if (abbrevLowerCase === abbrev.toLowerCase()) {
                                // Call the helper function to replace the last word with the abbreviation
                                replaceLastWordWithAbbreviation(quillEditor, lastWord, abbreviations[abbrev], selection.index);
                                break; // Exit the loop after replacement
                            }
                        }
                    }

                    // Helper function to replace the last word with the abbreviation
                    function replaceLastWordWithAbbreviation(editor, word, abbreviation, caretPosition) {
                        const text = editor.getText();
                        const textBeforeCaret = text.substring(0, caretPosition);
                        
                        // Find the start of the word that needs to be replaced
                        const startOfWord = textBeforeCaret.lastIndexOf(word);

                        // Remove the word first to avoid overlapping or incorrect replacement
                        editor.deleteText(startOfWord, word.length);

                        // Insert the abbreviation, making sure to trim any extra spaces
                        editor.insertText(startOfWord, abbreviation.trim(), 'user');  // 'user' to simulate a normal typing action
                        
                        // Set the caret position after the inserted abbreviation
                        editor.setSelection(startOfWord + abbreviation.length, 0);
                    }
                }
            );

    </script>

    <!-- Diagnosis Description Abbreviations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Quill editor for each dynamic microscopic description and comment
                    <?php foreach ($existingDiagnosisDescriptions as $key => $existingDescription): ?>
                        console.log('Initializing Quill editor for element: diagnosis-quill-editor-<?php echo $key; ?>'); // Debugging

                        // Description Quill Editor
                        var diagnosisquillEditor<?php echo $key; ?> = new Quill('#diagnosis-quill-editor-<?php echo $key; ?>', {
                            theme: 'snow',
                            modules: {
                                toolbar: []  // Customize toolbar if needed
                            }
                        });

                        // Set content of hidden textarea for description
                        var diagnosisTextarea<?php echo $key; ?> = document.getElementById('diagnosis-textarea-<?php echo $key; ?>');
                        diagnosisquillEditor<?php echo $key; ?>.root.innerHTML = diagnosisTextarea<?php echo $key; ?>.value;

                        // Update hidden textarea when content changes for description
                        diagnosisquillEditor<?php echo $key; ?>.on('text-change', function() {
                            diagnosisTextarea<?php echo $key; ?>.value = diagnosisquillEditor<?php echo $key; ?>.root.innerHTML;
                        });

                        // Comment Quill Editor
                        var commentquillEditor<?php echo $key; ?> = new Quill('#comment-quill-editor-<?php echo $key; ?>', {
                            theme: 'snow',
                            modules: {
                                toolbar: []  // Customize toolbar if needed
                            }
                        });

                        // Set content of hidden textarea for comment
                        var commentTextarea<?php echo $key; ?> = document.getElementById('comment-textarea-<?php echo $key; ?>');
                        commentquillEditor<?php echo $key; ?>.root.innerHTML = commentTextarea<?php echo $key; ?>.value;

                        // Update hidden textarea when content changes for comment
                        commentquillEditor<?php echo $key; ?>.on('text-change', function() {
                            commentTextarea<?php echo $key; ?>.value = commentquillEditor<?php echo $key; ?>.root.innerHTML;
                        });

                        // Handle space key and abbreviation replacement for both description and comment
                        diagnosisquillEditor<?php echo $key; ?>.root.addEventListener('keyup', function(event) {
                            if (event.key === ' ') {
                                diagnosisreplaceAbbreviation(diagnosisquillEditor<?php echo $key; ?>, abbreviations);
                            }
                        });

                        commentquillEditor<?php echo $key; ?>.root.addEventListener('keyup', function(event) {
                            if (event.key === ' ') {
                                diagnosisreplaceAbbreviation(commentquillEditor<?php echo $key; ?>, abbreviations);
                            }
                        });

                    <?php endforeach; ?>

                    // Function to replace abbreviations with full text (case-insensitive)
                    function diagnosisreplaceAbbreviation(quillEditor, abbreviations) {
                        var selection = quillEditor.getSelection();
                        if (!selection) return;  // Exit if no selection

                        // Get text before the current cursor position
                        var textBeforeCursor = quillEditor.getText(0, selection.index);
                        
                        // Find the last word before the cursor
                        var lastWordMatch = textBeforeCursor.match(/(\S+)\s*$/);
                        if (!lastWordMatch) return;  // Exit if no word found

                        var lastWord = lastWordMatch[1];
                        var abbrevLowerCase = lastWord.toLowerCase();  // Use lowercase for case-insensitive match

                        // Check if the last word matches any abbreviation
                        for (var abbrev in abbreviations) {
                            if (abbrevLowerCase === abbrev.toLowerCase()) {
                                // Replace last word with abbreviation
                                diagnosisreplaceLastWordWithAbbreviation(quillEditor, lastWord, abbreviations[abbrev], selection.index);
                                break; // Exit the loop after replacement
                            }
                        }
                    }

                    // Helper function to replace the last word with the abbreviation
                    function diagnosisreplaceLastWordWithAbbreviation(editor, word, abbreviation, caretPosition) {
                        const text = editor.getText();
                        const textBeforeCaret = text.substring(0, caretPosition);
                        
                        // Find the start of the word that needs to be replaced
                        const startOfWord = textBeforeCaret.lastIndexOf(word);

                        // Remove the word first to avoid overlapping or incorrect replacement
                        editor.deleteText(startOfWord, word.length);

                        // Insert the abbreviation, making sure to trim any extra spaces
                        editor.insertText(startOfWord, abbreviation.trim(), 'user');  // 'user' to simulate a normal typing action
                        
                        // Set the caret position after the inserted abbreviation
                        editor.setSelection(startOfWord + abbreviation.length, 0);
                    }
        });
    </script>

   
</body>
</html>