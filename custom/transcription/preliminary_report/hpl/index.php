<?php 
include('../connection.php');
include('../preliminary_report_function.php');
include('../../common_function.php');
include('../../../grossmodule/gross_common_function.php');

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

$LabNumber = $_GET['LabNumber'];
$fk_gross_id = getGrossIdByLabNumber($LabNumber);
$LabNumberWithoutPrefix = substr($LabNumber, 3);
if ($LabNumber !== null) {
    $last_value = substr($LabNumber, 8);
} else {
    echo 'Error: Lab number not found';
}


$userGroupNames = getUserGroupNames($loggedInUserId);

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

print('
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
            width: 15%;
            margin-top: 6px;
        }

        .col-75 {
            float: left;
            width: 85%;
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

    </style>'
);

$patient_information = get_patient_details_information($LabNumberWithoutPrefix);
print('<div class="sticky">');
print('<form id="patientForm" method="post" action="../../patient_info_update.php">'); 
print('<div class="flex-table-container">
    <br>
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
			<button style="background-color: rgb(118, 145, 225);
			color: white;
			padding: 12px 20px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			float: right;
			transition: box-shadow 0.3s ease;" id="patient-button" type="submit" name="submit" value="att_relation" class="btn btn-primary">Save</button>
		</td>
		</tr>
		</tbody>
		</table>
	</form>';
}
print('</div>');
print('<button class="button-class secondary">
<a href="../../../grossmodule/hpl_report.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '&username=' . urlencode($loggedInUsername) . '" target="_blank">Preview</a>
</button>');

// Button displaying the LabNumber with different styling
print('<button class="button-class secondary">
' . htmlspecialchars($LabNumber) . '
</button>');

echo '<button class="button-class secondary" onclick="history.back()" class="styled-back-btn">Back</button>';
echo '<button class="button-class secondary" class="styled-back-btn">Preliminary Report</button>';


print("
<form id='clinicalDetailsForm' method='post' action='../../clinical_details.php'>
    <div class='form-group'>
        <h2 class='heading'>Clinical Details</h2>
            <div class='controls'>
                <textarea id='clinicalDetailsTextarea' name='clinical_details' cols='40' rows='2'></textarea>
                <input type='hidden' id='labNumberInput' name='lab_number' value='$LabNumber'>
                <input type='hidden' id='createdUserInput' name='created_user' value='$loggedInUsername'>
            </div>
            <div class='grid'>
                <button style='background-color: rgb(118, 145, 225);
                color: white;
                padding: 12px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                float: right;
                transition: box-shadow 0.3s ease;' id='saveBtn' type='submit'>Save</button>
                <button id='updateBtn' type='submit' style='display: none;'>Update</button>
            </div>  
    </div>
</form>");

print('<form method="post" action="../../specimen_update.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">'); 
print('<div class="form-group">
<h2 class="heading">Site Of Specimen</h2>
'); 
foreach ($specimenIformation as $list) {
        echo('  
            <div class="controls">
                <input type="text" name="new_description[]" value="' . $list['specimen'] . '">
                <input type="hidden" name="specimen_rowid[]" value="' . $list['specimen_rowid'] . '">
            </div>
        ');
}
echo('
<div class="grid">
    <button style="background-color: rgb(118, 145, 225);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
    transition: box-shadow 0.3s ease;" type="submit" class="btn btn-primary">Save</button>
</div>  
</div>');
print('</form>');

print('<br>');

$specimens = get_gross_specimen_description($fk_gross_id);

print('<form method="post" action="../../update_gross_specimens.php">');
foreach ($specimens as $index => $specimen) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="specimen">Specimen</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="text" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '" readonly>';
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
echo '<input style="margin-top: 10px; margin-bottom:10px;" type="submit" value="Save">';
echo '</form>';


$sections = get_gross_specimen_section($fk_gross_id);
$specimen_count_value = number_of_specimen($fk_gross_id);
$alphabet_string = numberToAlphabet($specimen_count_value); 
print("<div class='container'>");

for ($i = 1; $i <= $specimen_count_value; $i++) {
    $specimenLetter = chr($i + 64); 
    $button_id =  "add-more-" . $i ;
    echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
    echo '<button class="button-class secondary" type="submit" id="' . $button_id . '" data-specimen-letter="' . $specimenLetter . '" onclick="handleButtonClick(this)">Generate Section Codes</button>';
    echo '<br><br>';
}
print('<form id="specimen_section_form" method="post" action="../../gross_specimen_section_generate.php">
<div id="fields-container"> 
</div>
<br>
<button id="saveButton">Save</button>
</form>');
print("</div>");


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
print('<form id="section-code-form" method="post" action="../../update_gross_specimen_section.php">');

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
    print('<textarea name="summary" id="summary">'. htmlspecialchars($generated_summary) .'</textarea>');
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="ink_code">Ink Code</label>';
    echo '</div>';
    echo '<div class="col-75">';
    print('<textarea name="ink_code" id="ink_code" >'.htmlspecialchars($summary['ink_code']) .'</textarea>');
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="gross_summary_id" value="' . htmlspecialchars($summary['gross_summary_id']) . '">';
    echo '<input type="hidden" name="fk_gross_id" value="' . htmlspecialchars($fk_gross_id) . '">';
}
echo '<input type="submit" value="Save">';
echo '</form>';




// Preliminary Report Micro Description
$existingMicroDescriptions = getExistingPreliminaryReportMicroDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

// Ensure $existingMicroDescriptions is an array
if (!is_array($existingMicroDescriptions)) {
    $existingMicroDescriptions = array();
}
echo '<h2 class="heading">Microscopic Description</h2>';
if (empty($existingMicroDescriptions)) {
    // Show Insert Form when no records exist
    ?>
    <form action="insert_micro_description.php" method="POST" class="micro-description-insert-form">
        <?php foreach ($specimens_list as $index => $specimen) { ?>
            <div class="form-group">
                <label for="specimen_<?php echo $index; ?>" class="bold-label">Specimen:</label>
                <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars((string) $specimen['specimen']); ?></textarea>

                <div id="quill-editor-new-<?php echo $index; ?>" class="editor"></div>

                <!-- Hidden textarea to store Quill content -->
                <textarea style="display:none;" id="hidden_description_new_<?php echo $index; ?>" name="description[]"></textarea>
            </div>
        <?php } ?>

        <!-- Hidden input fields -->
        <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($LabNumber); ?>">
        <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($fk_gross_id); ?>">
        <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
        <input type="hidden" name="status[]" value="Done">
        

        <div class="grid">
            <button type="submit" class="btn btn-success">Save</button>
        </div>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var forms = document.querySelectorAll(".micro-description-insert-form");

            forms.forEach(function (form) {
                form.addEventListener("submit", function () {
                    <?php foreach ($specimens_list as $index => $specimen) { ?>
                        var quillEditor = document.querySelector("#quill-editor-new-<?php echo $index; ?> .ql-editor");
                        if (quillEditor) {
                            document.getElementById("hidden_description_new_<?php echo $index; ?>").value = quillEditor.innerHTML;
                        }
                    <?php } ?>
                });
            });

            <?php foreach ($specimens_list as $index => $specimen) { ?>
                var quill<?php echo $index; ?> = new Quill("#quill-editor-new-<?php echo $index; ?>", {
                    theme: "snow"
                });

                quill<?php echo $index; ?>.on("text-change", function () {
                    document.getElementById("hidden_description_new_<?php echo $index; ?>").value = quill<?php echo $index; ?>.root.innerHTML;
                });
            <?php } ?>
        });
    </script>

    <?php
}else {
    foreach ($existingMicroDescriptions as $key => $existingDescription) {
        $formId = 'microDescriptionForm' . $key;
        ?>
        <form action="" id="<?php echo $formId; ?>" class="micro-description-form">
            <div class="form-group">
                <label for="specimen" class="bold-label">Specimen:</label>
                <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars($existingDescription['specimen']); ?></textarea>
                
                <!-- Quill Editor Container -->
                <div id="quill-editor-<?php echo $key; ?>" class="editor"></div>
                
                <!-- Hidden textarea for form submission -->
                <textarea style="display:none;" id="hidden_description<?php echo $key; ?>" name="description[]">
                    <?php 
                    $micro_pre_define_text = "Sections Show";
                    $descriptionValue = !empty($existingDescription['description']) ? 
                        htmlspecialchars($existingDescription['description']) : 
                        $micro_pre_define_text;
                    echo $descriptionValue; 
                    ?>
                </textarea>
                
                <!-- Hidden fields -->
                <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($existingDescription['fk_gross_id']); ?>">
                <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($existingDescription['created_user']); ?>">
                <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($existingDescription['lab_number']); ?>">
                <input type="hidden" name="row_id[]" value="<?php echo htmlspecialchars($existingDescription['row_id']); ?>">
            </div>
            <div class="grid">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
        <?php
    }
}
?>


<!-- Diagnosis Description -->
<?php
$existingDiagnosisDescriptions = getExistingPreliminaryReportDiagnosisDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);

// Ensure $existingDiagnosisDescriptions is an array
if (!is_array($existingDiagnosisDescriptions)) {
    $existingDiagnosisDescriptions = array();
}

echo '<h2 class="heading">Diagnosis Description</h2>';

if (empty($existingDiagnosisDescriptions)) {
    // Show Insert Form when no records exist
    ?>
   
    <?php
}else {
    echo '<form action="" id="diagnosisDescriptionForm" method="POST">';
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
        echo '<input type="text" name="specimen[]" value="' . htmlspecialchars($specimen['specimen']) . '" readonly>';
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
            name="submit" value="att_relation" class="btn btn-primary">Save</button>
        </div>';
    echo '</form>';
}


echo "<h2 class='heading'>Doctor's Signature</h2>";
$details = get_doctor_assisted_by_signature_details($LabNumber);
$finialized_by = get_doctor_finalized_by_signature_details($LabNumber);
$information = get_doctor_degination_details();
if (!empty($details)) {
    foreach($details as $list){
        print '<div class="content">';
        print('<h1>Assisted By</h1>');
        print('<form method="post" action="doctor_signature_update.php">');
        echo '<div class="row">';
        echo '<label for="doctor_username">Doctor</label>';
        echo '<select id="doctor_username" name="doctor_username">';
        echo '<option value=""></option>';
        foreach ($information as $list_info) {
            $selected = '';
            if ($list_info['username'] == $list['username']) {
                $selected = 'selected';
            }
            echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
        }           
        echo '</select>';
        echo '</div>';
        echo '<input  type="hidden" name="lab_number"  value="' . $LabNumber . '" readonly>';
        echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        echo '<input type="hidden" name="row_id" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update
            
                    // If the status is 'Assist', allow user to update
        echo '<input type="submit" value="Update">';
        }
        print('</form>');
        echo '</div>';
} else {
    // For new creation 
        print '<div class="content">';
        print('<p>Assisted By </p>');
        print('<form method="post" action="doctor_signature_create.php">');
        echo '<div class="row">';
        echo '<label for="doctor_username">Doctor</label>';
        echo '<select id="doctor_username" name="doctor_username">';
        echo '<option value=""></option>';
        foreach ($information as $list) {
            $selected = '';
            if ($list['username'] == $loggedInUsername) {
                $selected = 'selected';
            }
            echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
        }        
        echo '</select>';
        echo '</div>';
        echo '<input  type="hidden" name="lab_number"  value="' . $LabNumber . '" readonly>';
        echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        echo '<input type="submit" value="Save">';
        print('</form>');
        echo '</div>';
    }
    
   

if (!empty($finialized_by)) {
    foreach($finialized_by as $list){
    print '<div class="content">';
    print('<h1>Finalized By </h1>');
    print('<form method="post" action="doctor_signature_finalized_update.php">');
    echo '<div class="row">';
    echo '<label for="doctor_username">Doctor</label>';
    echo '<select id="doctor_username" name="doctor_username">';
    echo '<option value=""></option>';
    
    foreach ($information as $list_info) {
        $selected = '';
        if ($list_info['username'] == $list['username']) {
            $selected = 'selected';
        }
        echo "<option value='{$list_info['username']}' $selected>{$list_info['username']}</option>";
    }     
            
        echo '</select>';
        echo '</div>';
        echo '<input  type="hidden" name="lab_number"  value="' . $LabNumber . '" readonly>';
        echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
        echo '<input type="hidden" name="row_id" value="' . htmlspecialchars($list['row_id']) . '">'; // Hidden input to store row_id for update
        // If the status is 'Assist', allow user to update
        echo '<input type="submit" value="Update">';
}
    print('</form>');
    echo '</div>';
} else {
    // For new creation 
    print '<div class="content">';
    print('<p>Finalized By </p>');
    print('<form method="post" action="doctor_signature_finalized_create.php">');
    echo '<div class="row">';
    echo '<label for="doctor_username">Doctor</label>';
    echo '<select id="doctor_username" name="doctor_username">';
    echo '<option value=""></option>';
    foreach ($information as $list) {
        $selected = '';
        if ($list['username'] == $loggedInUsername) {
            $selected = 'selected';
        }
        echo "<option value='{$list['username']}' $selected>{$list['username']}</option>";
    }        
    echo '</select>';
    echo '</div>';
    echo '<input  type="hidden" name="lab_number"  value="' . $LabNumber . '" readonly>';
    echo '<input type="hidden" name="created_user" value="' . htmlspecialchars($loggedInUsername) . '">';
    echo '<input type="hidden" name="status" value="Finalized">';
    echo '<input type="submit" value="Save">';
    print('</form>');
    echo '</div>';
}
?>



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
            xhr.open("GET", "../../get_clinical_details.php?lab_number=" + labNumber, true);
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

<!-- Include Quill's CSS and JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

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


    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all Quill editors
        <?php foreach ($existingMicroDescriptions as $key => $existingDescription): ?>
            var quillEditor<?php echo $key; ?> = new Quill('#quill-editor-<?php echo $key; ?>', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            // Set the initial content from hidden textarea
            var hiddenTextarea<?php echo $key; ?> = document.getElementById('hidden_description<?php echo $key; ?>');
            quillEditor<?php echo $key; ?>.root.innerHTML = hiddenTextarea<?php echo $key; ?>.value;

            // Update hidden textarea when editor content changes
            quillEditor<?php echo $key; ?>.on('text-change', function() {
                hiddenTextarea<?php echo $key; ?>.value = quillEditor<?php echo $key; ?>.root.innerHTML;
            });
        <?php endforeach; ?>

        // Update your form submission handler
        document.querySelectorAll("form[id^='microDescriptionForm']").forEach(function(form) {
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                
                // Update all hidden textareas before submission
                var formId = this.id;
                var key = formId.replace('microDescriptionForm', '');
                var quillEditor = new Quill('#quill-editor-' + key);
                document.getElementById('hidden_description' + key).value = quillEditor.root.innerHTML;

                const formData = new FormData(this);
                
                fetch("update_micro_descriptions.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    window.location.reload();
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