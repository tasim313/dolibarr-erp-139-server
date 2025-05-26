<?php 
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
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

$LabNumber = $_GET['lab_number'];
$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
if ($LabNumber !== null) {
    $last_value = substr($LabNumber, 8);
} else {
    echo 'Error: Lab number not found';
}


$userGroupNames = getUserGroupNames($loggedInUserId);
$patient_information = get_patient_details_information($LabNumberWithoutPrefix);
$specimens = get_gross_specimen_description($fk_gross_id);

$hasTranscriptionist = false;
$hasConsultants = false;

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Transcription') {
        $hasTranscriptionist = true;
    } 
}

// Access control using switch statement
switch (true) {
  case $hasTranscriptionist:
      // Transcription  has access, continue with the page content...
      break;
  default:
      echo "<h1>Access Denied</h1>";
      echo "<p>You are not authorized to view this page.</p>";
      exit; // Terminate script execution
}

$abbreviations = get_abbreviations_list();
$specimenIformation   = get_gross_specimens_list($LabNumberWithoutPrefix);
$sections = get_gross_specimen_section($fk_gross_id);
$specimen_count_value = number_of_specimen($fk_gross_id);
$alphabet_string = numberToAlphabet($specimen_count_value);
$existingMicroDescriptions = getExistingMicroDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix); 
$existingDiagnosisDescriptions = getExistingDiagnosisDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);
$details = get_doctor_assisted_by_signature_details($LabNumber);
$finialized_by = get_doctor_finalized_by_signature_details($LabNumber);
$information = get_doctor_degination_details();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
    <script src="bootstrap-3.4.1-dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include Quill's CSS and JS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <style>
        /* Custom CSS for side-by-side panels */
        .row {
        display: flex;
        /* height: 100%; */
        margin: 0;
        }
        
        /* Tab styling */
        .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        }
        
        .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        }
        
        .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        }
        
        .tab-content {
        padding: 15px;
        background: white;
        border: 1px solid #dee2e6;
        border-top: none;
        /* height: calc(100% - 42px); Adjust based on your tab height */
        overflow-y: auto;
        }
    </style>
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
        .ql-editor {
            padding: 0 !important;
            margin: 0 !important;
            line-height: 1.2 !important;
            text-align: left !important;
            text-indent: 0 !important;
        }
        .ql-editor p {
            margin: 0 !important;
            padding: 0 !important;
            text-indent: 0 !important;
            text-align: left !important;
        }
    </style>
</head>
<body>
    <!-- abbreviations -->
    <script>
            const abbreviations_value = <?php echo json_encode($abbreviations); ?>;
            console.log('abbreviations :', abbreviations_value);
            
            const abbreviations = {};

            // Loop through abbreviations_value and map it to the abbreviations object
            abbreviations_value.forEach(item => {
                // Remove HTML tags using replace with a regex
                const plainText = item.abbreviation_full_text.replace(/<[^>]*>/g, '');
                abbreviations[item.abbreviation_key] = plainText;
            });

    </script>

    <div class="container-fluid">
        <div class="row">
             <!-- Left Panel: Tabbed Interface -->
             <div class="col-md-6 panel" style="background: #f8f9fa; border-right: 1px solid #ddd;">
                <ul class="nav nav-tabs" id="myTab">
                    <li><a href="#patient" data-toggle="tab">Patient Information</a></li>
                    <li><a href="#clinical_details" data-toggle="tab">Clinical Details</a></li>
                    <li><a href="#site_of_specimen" data-toggle="tab">Site Of Specimen</a></li>
                    <li><a href="#gross" data-toggle="tab">Gross Description</a></li>
                    <li class="active"><a href="#micro" data-toggle="tab">Microscopic Description</a></li>
                    <li><a href="#doctor_signature" data-toggle="tab">Doctor's Signature</a></li>
                    <li><button class="btn btn-primary" onclick="history.back()" class="styled-back-btn">Back</button></li>
                </ul><br>
                <div class="tab-content">
                   <div class="tab-pane fade" id="patient">
                            <form id="patientForm" method="post" action="patient_info_update.php">
                                    <?php foreach ($patient_information as $list): ?>
                                        <?php
                                            $genderOptions = [
                                                '1' => 'Male',
                                                '2' => 'Female',
                                                '3' => 'Other'
                                            ];
                                            $currentGender = $list['Gender'];
                                        ?>
                                        <div class="panel panel-default" style="margin-bottom: 20px;">
                                            <div class="panel-heading" style="background-color: #f5f5f5;">
                                                <strong>Patient: <?= htmlspecialchars($list['name']) ?></strong>
                                            </div>
                                            <div class="panel-body">
                                                <input type="hidden" name="rowid[]" value="<?= htmlspecialchars($list['rowid']) ?>">

                                                <div class="form-group">
                                                    <label>Name</label>
                                                    <input type="text" name="name[]" value="<?= htmlspecialchars($list['name']) ?>" class="form-control" placeholder="Patient Name">
                                                </div>

                                                <div class="form-group">
                                                    <label>Patient Code</label>
                                                    <input type="text" name="patient_code[]" value="<?= htmlspecialchars($list['patient_code']) ?>" class="form-control" placeholder="Patient Code">
                                                </div>

                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <input type="text" name="address[]" value="<?= htmlspecialchars($list['address']) ?>" class="form-control" placeholder="Address">
                                                </div>

                                                <div class="form-group">
                                                    <label>Phone</label>
                                                    <input type="text" name="phone[]" value="<?= htmlspecialchars($list['phone']) ?>" class="form-control" placeholder="Phone">
                                                </div>

                                                <div class="form-group">
                                                    <label>Attendant Number</label>
                                                    <input type="text" name="fax[]" value="<?= htmlspecialchars($list['fax']) ?>" class="form-control" placeholder="Attendant Phone">
                                                </div>

                                                <div class="form-group">
                                                    <label>Date of Birth</label>
                                                    <input type="date" name="date_of_birth[]" value="<?= htmlspecialchars($list['date_of_birth']) ?>" class="form-control">
                                                </div>

                                                <div class="form-group">
                                                    <label>Age</label>
                                                    <input type="text" name="age[]" value="<?= htmlspecialchars($list['Age']) ?>" class="form-control" placeholder="Age">
                                                </div>

                                                <div class="form-group">
                                                    <label>Attendant Name</label>
                                                    <input type="text" name="att_name[]" value="<?= htmlspecialchars($list['att_name']) ?>" class="form-control" placeholder="Attendant Name">
                                                </div>

                                                <div class="form-group">
                                                    <label>Attendant Relation</label>
                                                    <input type="text" name="att_relation[]" value="<?= htmlspecialchars($list['att_relation']) ?>" class="form-control" placeholder="Relation">
                                                </div>

                                                <div class="form-group">
                                                    <label>Gender</label>
                                                    <select name="gender[]" class="form-control">
                                                        <?php foreach ($genderOptions as $value => $label): ?>
                                                            <option value="<?= $value ?>" <?= ($currentGender == $value ? 'selected' : '') ?>>
                                                                <?= $label ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="text-right">
                                                    <button type="submit" name="submit" value="att_relation" class="btn btn-primary btn-sm">
                                                        Save
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                            </form>
                        <!-- end of patient div -->
                   </div>
                    <div class="tab-pane fade" id="clinical_details">
                        <form id="clinicalDetailsForm" method="post" action="clinical_details.php">
                            <div class="card shadow-sm p-3 mb-3 bg-white rounded">
                                <div class="form-group mb-3">
                                    <label for="clinicalDetailsTextarea" class="form-label"><strong>Clinical Details</strong></label>
                                    <textarea 
                                        id="clinicalDetailsTextarea" 
                                        name="clinical_details" 
                                        class="form-control" 
                                        style="resize: both; overflow: auto;"
                                        placeholder="Enter clinical details here..."></textarea>
                                </div>

                                <input type="hidden" id="labNumberInput" name="lab_number" value="<?= htmlspecialchars($LabNumber) ?>">
                                <input type="hidden" id="createdUserInput" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">

                                <div class="d-flex justify-content-between">
                                    <button 
                                        id="saveBtn" 
                                        type="submit" 
                                        class="btn btn-primary btn-sm">
                                        Save
                                    </button>

                                    <button 
                                        id="updateBtn" 
                                        type="submit" 
                                        class="btn btn-success btn-sm" 
                                        style="display: none;">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="site_of_specimen">
                        <form method="post" action="specimen_update.php?lab_number=<?= htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="card shadow-sm p-3 mb-3 bg-white rounded">
                                <h6 class="mb-3"><strong>Site of Specimen</strong></h6>

                                <?php foreach ($specimenIformation as $list): ?>
                                    <div class="form-group mb-2">
                                        <input 
                                            type="text" 
                                            name="new_description[]" 
                                            value="<?= htmlspecialchars($list['specimen']) ?>" 
                                            class="form-control" 
                                            placeholder="Enter specimen description">
                                        <input 
                                            type="hidden" 
                                            name="specimen_rowid[]" 
                                            value="<?= htmlspecialchars($list['specimen_rowid']) ?>">
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex justify-content-end mt-3">
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary btn-sm shadow-sm">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="gross">
                        <?php
                            
                            echo '<form method="post" action="update_gross_specimens.php" class="form-horizontal">';

                            foreach ($specimens as $index => $specimen) {
                                echo '<div class="form-group">';
                                echo '<label class="col-sm-2 control-label">Specimen</label>';
                                echo '<div class="col-sm-10">';
                                echo '<input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '">';
                                echo '<input type="text" class="form-control" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '" readonly>';
                                echo '</div>';
                                echo '</div>';

                                echo '<div class="form-group">';
                                echo '<label class="col-sm-2 control-label">Gross Description</label>';
                                echo '<div class="col-sm-10">';
                                echo '<div id="editor_' . $index . '" class="editor" style="border: 1px solid #ccc; min-height: 100px; padding: 10px;"></div>';
                                echo '<textarea name="gross_description[]" id="hidden_gross_description_' . $index . '" style="display:none;">' . htmlspecialchars($specimen['gross_description']) . '</textarea>';
                                echo '</div>';
                                echo '</div>';

                                echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
                            }

                            echo '<div class="form-group">';
                            echo '<div class="col-sm-offset-2 col-sm-10">';
                            echo '<button type="submit" class="btn btn-primary">Save</button>';
                            echo '</div>';
                            echo '</div>';

                            echo '</form>';

                            print("<div class='container'>");
                            
                            for ($i = 1; $i <= $specimen_count_value; $i++) {
                                $specimenLetter = chr($i + 64); 
                                $button_id =  "add-more-" . $i ;
                                echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
                                echo '<button class="button-class secondary" type="submit" id="' . $button_id . '" data-specimen-letter="' . $specimenLetter . '" onclick="handleButtonClick(this)">Generate Section Codes</button>';
                                echo '<br><br>';
                            }
                            print('<form id="specimen_section_form" method="post" action="gross_specimen_section_generate.php">
                            <div id="fields-container"> 
                            </div>
                            <br>
                            <button id="saveButton">Save</button>
                            </form>');
                            print("</div><br>");
                            
                            
                            // Print the form container
                            print('<div style="width: 60%; text-align: left; margin-left: 0;">');
                            
                            // Add CSS styles for the table and its elements
                            print('<style>
                                    table {
                                        margin-top: 20px;
                                        border-collapse: collapse;
                                        width: 100%;
                                        table-layout: fixed;  /* Ensure fixed table layout for consistent column width */
                                    }
                                    
                                    th, td {
                                        text-align: center;
                                        padding: 2px;  /* Reduce padding further to bring columns closer */
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
                                    
                                    tr:nth-child(even) {background-color: #f2f2f2;}
                            </style>');
                            
                            // Begin the form
                            print('<form id="section-code-form" method="post" action="update_gross_specimen_section.php">');
                            
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
                                echo '<textarea name="specimen_section_description[]" style="resize: both; overflow: auto;">' . htmlspecialchars($section['specimen_section_description']) . '</textarea>';
                                echo '<input type="hidden" name="gross_specimen_section_Id[]" value="' . htmlspecialchars($section['gross_specimen_section_id']) . '">';
                                echo '<input type="hidden" name="sectionCode[]" value="' . htmlspecialchars($section['section_code']) . '" readonly>';
                                echo '</td>';
                                
                                // Tissue
                                echo '<td>';
                                echo '<input type="text" name="tissue[]" value="' . htmlspecialchars($section['tissue']) . '" style="width:60%;">';
                                echo '</td>';
                                
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
                            
                            // summary of section
                            // Initialize counters and summary text
                            $total_sections = count($sections); // Number of section codes
                            $total_tissues = 0;
                            $tissue_description = '';
                            
                            // Loop through the sections to count tissues and form the description
                            foreach ($sections as $section) {
                                // Check if the tissue value contains the string "multiple"
                                if (strpos($section['tissue'], 'multiple') !== false) {
                                    $tissue_description = 'multiple pieces';  // Set description as "multiple pieces"
                                    break;  // Exit the loop, as we found a string indicating "multiple"
                                } elseif (is_numeric($section['tissue']) && $section['tissue'] > 1) {
                                    $total_tissues += $section['tissue'];  // Sum the tissue pieces if numeric
                                } else {
                                    $total_tissues += 1;  // Default to 1 tissue piece if not "multiple"
                                }
                            }
                            
                            // Formulate the generated summary text based on the findings
                            if (!empty($tissue_description)) {
                                // If tissue description is "multiple pieces"
                                $generated_summary = ucfirst($tissue_description) . " embedded in " . numberToWords($total_sections) . " block" . ($total_sections > 1 ? 's' : '') . ".";
                            } else {
                                // Otherwise, use the total tissue count
                                $generated_summary = ucfirst(numberToWords($total_tissues)) . " pieces embedded in " . numberToWords($total_sections) . " block" . ($total_sections > 1 ? 's' : '') . ".";
                            }
                            
                            // Function to convert numbers to words
                            function numberToWords($number)
                            {
                                $words = array(
                                    0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
                                    7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
                                    14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen',
                                    20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
                                    80 => 'eighty', 90 => 'ninety'
                                );
                            
                                if ($number <= 20) {
                                    return $words[$number];
                                } else {
                                    return $words[10 * floor($number / 10)] . (($number % 10) ? '-' . $words[$number % 10] : '');
                                }
                            }
                            
                            
                            
                            $summaries = get_gross_summary_of_section($fk_gross_id);
                            print('<form method="post" action="../../update_gross_summary.php" id="auto-submit-form">');
                            foreach ($summaries as $summary) {
                                echo '<div class="row">';
                                echo '<div class="col-25">';
                                echo '<label for="summary">Summary</label>';
                                echo '</div>';
                                echo '<div class="col-75">';
                                print('<textarea name="summary" id="summary" style="resize: both; overflow: auto;">'. htmlspecialchars($generated_summary) .'</textarea>');
                                echo '</div>';
                                echo '</div>';
                                echo '<div class="row">';
                                echo '<div class="col-25">';
                                echo '<label for="ink_code">Ink Code</label>';
                                echo '</div>';
                                echo '<div class="col-75">';
                                print('<textarea name="ink_code" id="ink_code" style="resize: both; overflow: auto;">'.htmlspecialchars($summary['ink_code']) .'</textarea>');
                                echo '</div>';
                                echo '</div>';
                                echo '<input type="hidden" name="gross_summary_id" value="' . htmlspecialchars($summary['gross_summary_id']) . '">';
                                echo '<input type="hidden" name="fk_gross_id" value="' . htmlspecialchars($fk_gross_id) . '">';
                            }
                            echo '<input type="submit" value="Save">';
                            echo '</form>';
                        ?>
                    </div>

                    <div class="tab-pane fade in active" id="micro">
                        <?php
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
                                        <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars($existingDescription['specimen']); ?></textarea>
                                        <div id="quill-editor-<?php echo $key; ?>" class="editor"></div>

                                        <!-- Hidden textarea to store Quill content -->
                                        <textarea style="display:none;" id="hidden_description<?php echo $key; ?>" name="description[]" data-index="<?php echo $key; ?>">
                                        <?php 
                                            // Check if the description is empty and set a default value if it is
                                            $micro_pre_define_text = trim("Sections Show");
                                            $descriptionValue = !empty($existingDescription['description']) ? htmlspecialchars($existingDescription['description']) : $micro_pre_define_text;
                                            echo $descriptionValue; 
                                            ?>
                                        </textarea>

                                        <!-- Hidden input fields -->
                                        <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($existingDescription['fk_gross_id']); ?>">
                                        <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                                        <input type="hidden" name="status[]" value="<?php echo htmlspecialchars($existingDescription['status']); ?>">
                                        <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($existingDescription['lab_number']); ?>">
                                        <input type="hidden" name="row_id[]" value="<?php echo htmlspecialchars($existingDescription['row_id']); ?>">
                                    </div>
                                    
                                    <div class="mt-4 text-end">
                                        <br><button type="submit" 
                                            class="btn btn-primary px-4 py-2 shadow">
                                            <i class="bi bi-save me-2"></i> Save
                                        </button>
                                    </div>
                                </form>
                                <?php
                            }
                        ?>
                        <?php 
                            if (!is_array($existingDiagnosisDescriptions)) {
                                $existingDiagnosisDescriptions = array();
                            }
                        ?>

                       <h2 class="mt-4 mb-4  fw-bold">Diagnosis Description</h2>
                        <form action="" id="diagnosisDescriptionForm" method="POST">
                            <div class="row g-4">
                                <?php foreach ($existingDiagnosisDescriptions as $index => $specimen): 
                                    $description = $specimen['description'] ?? '';
                                    $title = $specimen['title'] ?? '';
                                    $comment = $specimen['comment'] ?? '';
                                    $fk_gross_id = $specimen['fk_gross_id'] ?? '';
                                    $created_user = $specimen['created_user'] ?? '';
                                    $status = $specimen['status'] ?? '';
                                    $lab_number = $specimen['lab_number'] ?? '';
                                    $row_id = $specimen['row_id'] ?? '';
                                ?>

                                <div class="col-md-12">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body">

                                            <!-- Specimen Field -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Specimen</label>
                                                <input type="hidden" name="specimen_id[]" value="<?= htmlspecialchars($specimen['specimen_id']) ?>">
                                                <input type="text" class="form-control" name="specimen[]" value="<?= htmlspecialchars($specimen['specimen']) ?>" readonly>
                                            </div>

                                            <!-- Title Field -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Title</label>
                                                <input type="text" class="form-control" name="title[]" value="<?= !empty($title) ? htmlspecialchars($title) : 'biopsy' ?>">
                                            </div>

                                            <!-- Description Field -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Description</label>
                                                <div id="diagnosis-quill-editor-<?= $index ?>" class="editor bg-white border rounded" style="min-height: 150px;"></div>
                                                <textarea name="description[]" id="diagnosis-textarea-<?= $index ?>" style="display:none;"><?= htmlspecialchars($description) ?></textarea>
                                            </div>

                                            <!-- Comment Field -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Comment</label>
                                                <div id="comment-quill-editor-<?= $index ?>" class="editor bg-white border rounded" style="min-height: 150px;"></div>
                                                <textarea name="comment[]" id="comment-textarea-<?= $index ?>" style="display:none;"><?= htmlspecialchars($comment) ?></textarea>
                                            </div>

                                            <!-- Hidden fields -->
                                            <input type="hidden" name="fk_gross_id[]" value="<?= htmlspecialchars($fk_gross_id) ?>">
                                            <input type="hidden" name="created_user[]" value="<?= htmlspecialchars($created_user) ?>">
                                            <input type="hidden" name="status[]" value="<?= htmlspecialchars($status) ?>">
                                            <input type="hidden" name="lab_number[]" value="<?= htmlspecialchars($lab_number) ?>">
                                            <input type="hidden" name="row_id[]" value="<?= htmlspecialchars($row_id) ?>">
                                        </div>
                                    </div>
                                </div>

                                <?php endforeach; ?>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-4 text-end">
                                <br><button type="submit" id="diagnosisDescriptionSaveButton" name="submit" value="att_relation"
                                    class="btn btn-primary px-4 py-2 shadow">
                                    <i class="bi bi-save me-2"></i> Save
                                </button>
                            </div>
                        </form>

                    </div>
                     
                    <div class="tab-pane fade" id="doctor_signature">
                        <div class="container-fluid" style="padding: 15px;">
                                <!-- Assisted By Panel -->
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <strong><i class="glyphicon glyphicon-user"></i> Assisted By</strong>
                                    </div>
                                    <div class="panel-body">
                                        <?php if (!empty($details)): ?>
                                            <?php foreach ($details as $list): ?>
                                                <form method="post" action="doctor_signature_update.php" class="form-horizontal">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Doctor</label>
                                                        <div class="col-sm-8">
                                                            <select class="form-control" name="doctor_username">
                                                                <option value=""></option>
                                                                <?php foreach ($information as $list_info): ?>
                                                                    <option value="<?= $list_info['username'] ?>" <?= ($list_info['username'] == $list['username']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($list_info['username']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="lab_number" value="<?= htmlspecialchars($LabNumber) ?>">
                                                    <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($list['row_id']) ?>">
                                                    <div class="form-group text-right">
                                                        <div class="col-sm-12">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                Update
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <hr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <form method="post" action="doctor_signature_create.php" class="form-horizontal">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Doctor</label>
                                                    <div class="col-sm-8">
                                                        <select class="form-control" name="doctor_username">
                                                            <option value=""></option>
                                                            <?php foreach ($information as $list): ?>
                                                                <option value="<?= $list['username'] ?>" <?= ($list['username'] == $loggedInUsername) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($list['username']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="lab_number" value="<?= htmlspecialchars($LabNumber) ?>">
                                                <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                                <div class="form-group text-right">
                                                    <div class="col-sm-12">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            Save
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Finalized By Panel -->
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <strong><i class="glyphicon glyphicon-ok-sign"></i> Finalized By</strong>
                                    </div>
                                    <div class="panel-body">
                                        <?php if (!empty($finialized_by)): ?>
                                            <?php foreach ($finialized_by as $list): ?>
                                                <form method="post" action="doctor_signature_finalized_update.php" class="form-horizontal">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Doctor</label>
                                                        <div class="col-sm-8">
                                                            <select class="form-control" name="doctor_username">
                                                                <option value=""></option>
                                                                <?php foreach ($information as $list_info): ?>
                                                                    <option value="<?= $list_info['username'] ?>" <?= ($list_info['username'] == $list['username']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($list_info['username']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="lab_number" value="<?= htmlspecialchars($LabNumber) ?>">
                                                    <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($list['row_id']) ?>">
                                                    <div class="form-group text-right">
                                                        <div class="col-sm-12">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                Update
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <hr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <form method="post" action="doctor_signature_finalized_create.php" class="form-horizontal">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Doctor</label>
                                                    <div class="col-sm-8">
                                                        <select class="form-control" name="doctor_username">
                                                            <option value=""></option>
                                                            <?php foreach ($information as $list): ?>
                                                                <option value="<?= $list['username'] ?>" <?= ($list['username'] == $loggedInUsername) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($list['username']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="lab_number" value="<?= htmlspecialchars($LabNumber) ?>">
                                                <input type="hidden" name="created_user" value="<?= htmlspecialchars($loggedInUsername) ?>">
                                                <input type="hidden" name="status" value="Finalized">
                                                <div class="form-group text-right">
                                                    <div class="col-sm-12">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            Save
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- end of div tab content -->
                </div>
                 <!-- end of div left panel -->
             </div>
             <!-- Right Panel: Report Frame -->
            <div class="col-md-6 panel" style="padding: 0;">
                <iframe id="reportFrame" style="width: 110%; height: 800%; border: none;"></iframe>
            </div>
      
            <!-- end div of row -->
        </div>
        <!-- end div of container -->
    </div>

    <script>
        function loadReport() {
            var labNumber = "<?php echo urlencode('HPL' . $LabNumberWithoutPrefix); ?>";
            var username = "<?php echo urlencode($loggedInUsername); ?>";

            var iframe = document.getElementById('reportFrame');
            iframe.src = "../grossmodule/hpl_report.php?lab_number=" + labNumber + "&username=" + username;
        }

        // Initialize when DOM is loaded
        document.addEventListener("DOMContentLoaded", function() {
            loadReport();
            
            // Enable all tabs - 
            var tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabElms.forEach(function(tabEl) {
            tabEl.addEventListener('click', function(event) {
                event.preventDefault();
                var tab = new bootstrap.Tab(this);
                tab.show();
            });
            });
        });
    </script>
</body>
</html>



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

    // Clinical Details
    document.addEventListener('keydown', function(event) {
        // Check for Ctrl + S (or Command + S on Mac)
        if (event.ctrlKey && event.key === 's') {
            event.preventDefault(); // Prevent default behavior of Enter key
			var updateBtn = document.getElementById("updateBtn");
            var saveBtn = document.getElementById("saveBtn");
                if (updateBtn.style.display === "inline-block") {
                    updateBtn.click();
                } else {
                    saveBtn.click();
                } 
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
    // Fetch existing clinical details using AJAX when the page loads
    fetchExistingClinicalDetails();

    function fetchExistingClinicalDetails() {
        // Get the lab number from the hidden input field
        var labNumber = document.getElementById("labNumberInput").value;

        // Make an AJAX request to fetch existing clinical details
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "get_clinical_details.php?lab_number=" + labNumber, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Parse the JSON response
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Populate the textarea with existing clinical details
                    document.getElementById("clinicalDetailsTextarea").value = response.data.clinical_details;
                    // Toggle visibility of Save and Update buttons based on whether data exists
                    if (response.data.clinical_details) {
                        document.getElementById("saveBtn").style.display = "none";
                        document.getElementById("updateBtn").style.display = "inline-block";
                    } else {
                        document.getElementById("saveBtn").style.display = "inline-block";
                        document.getElementById("updateBtn").style.display = "none";
                    }
                } else {
                    console.error("Error fetching existing clinical details:", response.error);
                }
            }
        };
        xhr.send();
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


    document.addEventListener("DOMContentLoaded", function() {
        // Add event listener to all forms
        document.querySelectorAll("form[id^='microDescriptionForm']").forEach(function(form) {
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                
                // Add dynamic fields manually if necessary
                document.querySelectorAll(`#${this.id} [data-field] textarea`).forEach(textarea => {
                    formData.append(textarea.name, textarea.value);
                });

                fetch("update_micro_descriptions.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log('Micro Description:', data);
                    var labNumber = "<?php echo $LabNumber; ?>"; 
                    window.location.href = `transcription.php?lab_number=${labNumber}`;
                })
                .catch(error => {
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

    document.getElementById("diagnosisDescriptionForm").addEventListener("submit", function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        
        fetch("update_diagnosis_descriptions.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); 
            var labNumber = "<?php echo $LabNumber; ?>"; 
            window.location.href = `transcription.php?lab_number=${labNumber}`
        })
        .catch(error => {
            console.error("Error:", error);
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

<!-- Include Quill's CSS and JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<!-- Gross Description Abbreviations -->
<script>
        
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
            lastSectionCodes[specimenLetter] = sectionCode;

            // Bootstrap Card container
            const fieldSet = document.createElement("div");
            fieldSet.classList.add("card", "mb-3");
            fieldSet.innerHTML = `
                <div class="card-body">
                    <input type="hidden" name="fk_gross_id" value="${fk_gross_id}">

                    <div class="mb-3">
                        <label class="form-label fw-bold">${sectionCode}</label>
                        <input type="hidden" class="form-control" name="sectionCode[]" value="${sectionCode}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description:</label>
                        <input type="text" class="form-control" name="specimen_section_description[]" value="${section_text}" data-shortcut-file="shortcuts.json">
                    </div>

                    <div class="mb-3">
                        <input type="hidden" name="cassetteNumber[]" value="${sectionCode}-${last_value}/${lastTwoDigits}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tissue:</label>
                        <input type="text" class="form-control" name="tissue[]" placeholder="Tissue Pieces In ${sectionCode}">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="bone-${sectionCode}" name="bone[]" value="${sectionCode}">
                        <label class="form-check-label" for="bone-${sectionCode}">Bone?</label>
                    </div>
                </div>
            `;

            // Append to container
            fieldsContainer.appendChild(fieldSet);

            // Show the save button
            const saveButton = document.getElementById("saveButton");
            saveButton.style.display = "block";
            
            console.log("Field Container: ", fieldSet);
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






<!-- JavaScript to handle scroll -->
<script>
        // Scroll to the top of the page when the "up" arrow button is clicked
        document.getElementById('scrollUpBtn').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'  // Smooth scroll
            });
        });

        // Scroll to the bottom of the page when the "down" arrow button is clicked
        document.getElementById('scrollDownBtn').addEventListener('click', function() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'  // Smooth scroll
            });
        });
</script>