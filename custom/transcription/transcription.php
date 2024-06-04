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


$specimenIformation   = get_gross_specimens_list($LabNumberWithoutPrefix);
print('<style>
main {
	display: flex;
  }
  main > * {
	border: 1px solid;
  }
  .flex-table-container { 
    display: flex; 
    justify-content: space-between; 
    margin: 20px; 
} 
  table {
	border-collapse: collapse;
	font-family: helvetica;
  }
  td,
  th {
	border: 0.1px solid white;
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
	
  }
  
  thead th {
	top: 0;
	z-index: 2;
	background:rgb(4, 106, 170);
    color: white;
  }

  thead th:first-child {
	left: 0;
	z-index: 3;
  }
  
  tfoot {
	bottom: 0;
	z-index: 2;
  }
  
  tfoot td {
	bottom: 0;
	z-index: 2;
	background: rgb(4, 106, 170);
  }
  
  tfoot td:first-child {
	z-index: 3;
  }
  
  tbody {
	
	height: 200px;
  }
  .fixed-table input[type="text"], .fixed-table input[type="hidden"] {
    border: none;
    outline: none;
}
  /* MAKE LEFT COLUMN FIXEZ */
  tr > :first-child {
	// background: hsl(180, 50%, 70%);
	left: 0;
  }
 
  tr > :first-child {
	// box-shadow: inset 0px 1px black;
  }

.flex-table-container h1 {
    margin: 0;
}

.button-class {
    background-color: rgb(118, 145, 225);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
    transition: box-shadow 0.3s ease;
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

div.sticky {
    position: -webkit-sticky;
    position: sticky;
    top: 0;
    background-color: white;
    padding: 1px;
    font-size: 14px;
}

.bold-label {
    font-weight: bold;
}

@media screen and (max-width: 600px) {
.col-25, .col-75, input[type=submit] {
width: 100%;
margin-top: 0;
}
}
</style>');

$patient_information = get_patient_details_information($LabNumberWithoutPrefix);
print('<div class="sticky">');
print('<form id="patientForm" method="post" action="patient_info_update.php">'); 
print('<div class="flex-table-container">
    <h1>Patient Information</h1>
   
    <button class="button-class">
            <a href="../grossmodule/hpl_report.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '" target="_blank" >Preview</a>
    </button>
    
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

print("
<form id='clinicalDetailsForm' method='post' action='clinical_details.php'>
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

print('<form method="post" action="specimen_update.php?lab_number=' . htmlspecialchars($LabNumber, ENT_QUOTES, 'UTF-8') . '">'); 
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
print('<form method="post" action="update_gross_specimens.php">');
echo('<div class="form-group">
<h2 class="heading">Gross</h2>
');
foreach ($specimens as $specimen) {

    echo('  <div class="controls">
                <label for="specimen" class="bold-label">Specimen</label>
                <input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>
			    <textarea name="specimen[]" cols="30" rows="1">' . htmlspecialchars($specimen['specimen']) . '</textarea>
            </div>
            <div class="controls">
                <label for="ink_code" class="bold-label">Gross Description</label>
                <textarea name="gross_description[]" cols="60" rows="3">' . htmlspecialchars($specimen['gross_description']) . '</textarea>
			    <input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) .'">
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
    transition: box-shadow 0.3s ease;" 
    id="gross-button" type="submit" name="submit" value="att_relation" class="btn btn-primary">Save</button>
</div>  
</div> ');
echo '</form>';

$sections = get_gross_specimen_section($fk_gross_id);
$specimen_count_value = number_of_specimen($fk_gross_id);
$alphabet_string = numberToAlphabet($specimen_count_value); 

print('<br>');print('<br>');
print("<div class='container'>");

for ($i = 1; $i <= $specimen_count_value; $i++) {
    $specimenLetter = chr($i + 64); 
    $button_id =  "add-more-" . $i ;
    echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
    echo '<button type="submit" id="' . $button_id . '" data-specimen-letter="' . $specimenLetter . '" onclick="handleButtonClick(this)">Generate Section Codes</button>';
    echo '<br><br>';
}
print('<form id="specimen_section_form" method="post" action="gross_specimen_section_generate.php">
<div id="fields-container"> 
</div>
<br>
<button id="saveButton" style="display: none;">Save</button>
</form>');
print("</div>");

// here is Section code and Description values are displayed and update
print('<form id="section-code-form" method="post" action="update_gross_specimen_section.php">');
echo "<table class='fixed-table'>
    <thead>
        <tr>
            <th>Section Code</th> 
            <th>Description</th>        
            <th>Pieces of Tissue</th>
            <th></th>
        </tr>
    </thead>";

foreach ($sections as $section) {
    echo "<tr>
        <td>
            ". htmlspecialchars($section['section_code']) . "
        </td>
        <td>
            <input type='hidden' name='gross_specimen_section_Id[]' value='" . htmlspecialchars($section['gross_specimen_section_id']) . "'>
            <input type='hidden' name='sectionCode[]' value='" . htmlspecialchars($section['section_code']) . "' readonly>
            <input type='hidden' name='cassetteNumber[]' value='" . htmlspecialchars($section['cassettes_numbers']) . "' readonly>
            <textarea name='specimen_section_description[]' cols='40' rows='4'>" . htmlspecialchars($section['specimen_section_description']) . "</textarea>
        </td>
        <td>
            <textarea name='tissue[]' cols='6' rows='4'>".htmlspecialchars($section['tissue'])."</textarea>
        </td>
        <td>        
            <button style='background-color: rgb(118, 145, 225);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            transition: box-shadow 0.3s ease;' id='section-button' type='submit' name='submit' value='att_relation' class='btn btn-primary'>Save</button>
        </td>
    </tr>";
}

echo "</table>";
echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
echo '</form>';

print('<br><br>');
$summaries = get_gross_summary_of_section($fk_gross_id);
print('<form method="post" action="update_gross_summary.php">');
echo("<div class='form-group'>
<h2 class='heading'>Summary Of Gross Section</h2>");
foreach ($summaries as $summary) {
    echo('  
    <div class="controls">
        <label for="summary" class="bold-label">Summary Of Section</label>
        <textarea name="summary" id="summary">'. htmlspecialchars($summary['summary']) .'</textarea>
    </div>
    <div class="controls">
        <label for="ink_code" class="bold-label">Ink Code</label>
        <textarea name="ink_code" id="ink_code" >'.htmlspecialchars($summary['ink_code']) .'</textarea>
        <input type="hidden" name="gross_summary_id" value="' . htmlspecialchars($summary['gross_summary_id']) . '">
        <input type="hidden" name="fk_gross_id" value="' . htmlspecialchars($fk_gross_id) . '">
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
transition: box-shadow 0.3s ease;" 
id="summery-button" type="submit" 
name="submit" value="att_relation" class="btn btn-primary">Save</button>
</div>  
</div>');
echo '</form>';

$existingMicroDescriptions = getExistingMicroDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);
// Ensure $existingMicroDescriptions is an array
if (!is_array($existingMicroDescriptions)) {
    $existingMicroDescriptions = array();
}
echo('<form action="" id="microDescriptionForm">
    <div class="form-group">
    <h2 class="heading">Microscopic</h2>');

 // Loop through specimens list to generate form fields

foreach ($existingMicroDescriptions as $existingDescription) { 
            echo('  <div class="controls">
                        <label for="specimen" class="bold-label">Specimen</label>
                        <input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>
                        <textarea name="specimen[]" cols="20" rows="2">' . $existingDescription['specimen'] . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="microscopic_description" class="bold-label">Microscopic Description</label>
                        <textarea id="description" name="description[]" data-index=' . $key . ' cols="60" rows="4" >' . htmlspecialchars($existingDescription['description']) . '</textarea>
                        <input type="hidden" name="fk_gross_id[]" value=' . $existingDescription['fk_gross_id'] . '>
                        <input type="hidden" name="created_user[]" value=' . $existingDescription['created_user'] . '>
                        <input type="hidden" name="status[]" value=' . $existingDescription['status'] . '>
                        <input type="hidden" name="lab_number[]" value=' . $existingDescription['lab_number'] . '>
                        <input type="hidden" name="row_id[]" value=' . $existingDescription['row_id'] . '>
                    </div>       
                    <div class="controls">
                        <label for="histologic_type" class="bold-label">Histologic Type</label>
                        <textarea name="histologic_type[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['histologic_type']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="hitologic_grade" class="bold-label">Histologic Grade</label>
                        <textarea name="hitologic_grade[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['hitologic_grade']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="pattern_of_growth" class="bold-label">Pattern of growth</label>
                        <textarea name="pattern_of_growth[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['pattern_of_growth']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="stromal_reaction" class="bold-label">Stromal Reaction</label>
                        <textarea name="stromal_reaction[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['stromal_reaction']) . '</textarea>
                    </div>
                    <div class="controls">
                            <label for="depth_of_invasion" class="bold-label">Depth Of Invasion</label>
                            <textarea name="depth_of_invasion[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['depth_of_invasion']) . '</textarea>
                    </div>

                    <div class="controls">
                        <label for="resection_margin" class="bold-label">Resection Margin</label>
                        <textarea name="resection_margin[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['resection_margin']) . '</textarea>
                    </div>

                    <div class="controls">
                        <label for="lymphovascular_invasion" class="bold-label">Lymphovascular Invasion</label>
                        <textarea name="lymphovascular_invasion[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['lymphovascular_invasion']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="perineural_invasion" class="bold-label">Perineural Invasion</label>
                        <textarea name="perineural_invasion[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['perineural_invasion']) . '</textarea>
                    </div>
        
                    <div class="controls">
                        <label for="bone" class="bold-label">Bone</label>
                        <textarea name="bone[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['bone']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="lim_node" class="bold-label">Lymph Node</label>
                        <textarea name="lim_node[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['lim_node']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="ptnm_title" class="bold-label">Ptnm Title</label>
                        <textarea name="ptnm_title[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['ptnm_title']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="pt2" class="bold-label">Pt2</label>
                        <textarea name="pt2[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['pt2']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="pnx" class="bold-label">Pnx</label>
                        <textarea name="pnx[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['pnx']) . '</textarea>
                    </div>
                    <div class="controls">
                        <label for="pmx" class="bold-label">Pmx</label>
                        <textarea name="pmx[]" cols="10" rows="2" >' . htmlspecialchars($existingDescription['pmx']) . '</textarea>
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
            transition: box-shadow 0.3s ease;" 
            id="micro-button" type="submit" 
            name="submit" value="att_relation" class="btn btn-primary">Save</button>
        </div>
    </div>
 ');
echo '</form>';

$existingDiagnosisDescriptions = getExistingDiagnosisDescriptions($LabNumber);
$specimens_list = get_gross_specimens_list($LabNumberWithoutPrefix);
// Ensure $existingDiagnosisDescriptions is an array
if (!is_array($existingDiagnosisDescriptions)) {
    $existingDiagnosisDescriptions = array();
}
echo('<form action="" id="diagnosisDescriptionForm">
    <div class="form-group">
    <h2 class="heading">Diagnosis</h2>');
        
foreach ($existingDiagnosisDescriptions as $existingDescription) {
            echo '
            <div class="controls">
                <label for="specimen" class="bold-label">Specimen</label>
                <input type="hidden" name="specimen_id[]" value="' . htmlspecialchars($specimen['specimen_id']) . '" readonly>
                <textarea name="specimen[]" cols="20" rows="2">' . $existingDescription['specimen'] . '</textarea>
            </div>
            <div class="controls">
                <label for="title" class="bold-label">Title</label>
                <textarea name="title[' . $key . ']">' . $existingDescription['title'] .'</textarea>
            </div>
            <div class="controls">
                <label for="description" class="bold-label">Diagnosis Description</label>
                <textarea id="' . $text_area_id . '" name="description[' . $key . ']" data-index="' . $key . '" cols="60" rows="2">' . htmlspecialchars($existingDescription['description']) . '</textarea>
                <input type="hidden" name="specimen[' . $key . ']" value="' . $specimen['specimen'] . '">
                <input type="hidden" name="fk_gross_id[' . $key . ']" value="' . $existingDescription['fk_gross_id'] . '">
                <input type="hidden" name="created_user[' . $key . ']" value="' . $existingDescription['created_user'] . '">
                <input type="hidden" name="status[' . $key . ']" value="' . $existingDescription['status'] . '">
                <input type="hidden" name="lab_number[' . $key . ']" value="' . $existingDescription['lab_number'] . '">
                <input type="hidden" name="row_id[' . $key . ']" value="' . $existingDescription['row_id'] . '">
            </div>       
            
            <div class="controls">
                <label for="comment" class="bold-label">Comment</label>
                <textarea name="comment[' . $key . ']">' . $existingDescription['comment'] .'</textarea>
            </div>
           ';       
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
            transition: box-shadow 0.3s ease;" 
            id="diagnosisDescriptionSaveButton" type="submit" 
            name="submit" value="att_relation" class="btn btn-primary">Save</button>
        </div>
    </div>
 ');
echo '</form>';


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
        echo '<label for="lab_number">Labnumber</label>';
        echo '<input  type="text" name="lab_number"  value="' . $LabNumber . '" readonly>';
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
        echo '<label for="lab_number">Labnumber</label>';
        echo '<input  type="text" name="lab_number"  value="' . $LabNumber . '" readonly>';
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
        echo '<label for="lab_number">Labnumber</label>';
        echo '<input  type="text" name="lab_number"  value="' . $LabNumber . '" readonly>';
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
    echo '<label for="lab_number">Labnumber</label>';
    echo '<input  type="text" name="lab_number"  value="' . $LabNumber . '" readonly>';
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

    // Gross Description

    document.addEventListener('keydown', function(event) {
        // Check for Ctrl + S (or Command + S on Mac)
        if (event.ctrlKey && event.key === 's') {
            event.preventDefault(); // Prevent default behavior of Enter key
			document.getElementById('gross-button').click(); // Submit the form 
        }
    });
document.addEventListener('DOMContentLoaded', function() {
    fetch('shortcuts.json')
        .then(response => response.json())
        .then(shortcuts => {
            document.querySelectorAll('textarea[name="gross_description[]"]').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    let cursorPosition = this.selectionStart;
                });

                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Insert') { // Insert key
                        let cursorPosition = this.selectionStart;
                        let text = this.value;
                        let wordStart = text.lastIndexOf(' ', cursorPosition - 2) + 1;
                        let wordEnd = cursorPosition;

                        let word = text.substring(wordStart, wordEnd).trim();

                        if (shortcuts[word]) {
                            this.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                            this.selectionEnd = wordStart + shortcuts[word].length;
                        }
                    }
                });
            });
        })
        .catch(error => console.error('Error loading shortcuts:', error));
});
</script>


<script>
    
    // Section Code

    document.addEventListener('keydown', function(event) {
        // Check for Ctrl + S (or Command + S on Mac)
        if (event.ctrlKey && event.key === 's') {
            event.preventDefault(); // Prevent default behavior of Enter key
			document.getElementById('section-button').click(); // Submit the form 
        }
    });

document.addEventListener('DOMContentLoaded', function() {
    fetch('shortcuts.json')
        .then(response => response.json())
        .then(shortcuts => {
            document.querySelectorAll('textarea[name="specimen_section_description[]"]').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    let cursorPosition = this.selectionStart;
                });

                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Insert') { // Insert key
                        let cursorPosition = this.selectionStart;
                        let text = this.value;
                        let wordStart = text.lastIndexOf(' ', cursorPosition - 2) + 1;
                        let wordEnd = cursorPosition;

                        let word = text.substring(wordStart, wordEnd).trim();

                        if (shortcuts[word]) {
                            this.value = text.substring(0, wordStart) + shortcuts[word] + text.substring(wordEnd);
                            this.selectionEnd = wordStart + shortcuts[word].length;
                        }
                    }
                });
            });
        })
        .catch(error => console.error('Error loading shortcuts:', error));
});

</script>


<script>

// Micro Description
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
                let wordStart = text.lastIndexOf(' ', cursorPosition - 2) + 1;
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
                let wordStart = text.lastIndexOf(' ', cursorPosition - 2) + 1;
                let wordEnd = cursorPosition;

                let word = text.substring(wordStart, wordEnd).trim();

                if (shortcuts[word]) {
                    let before = text.substring(0, wordStart);
                    let after = text.substring(wordEnd);
                    inputElement.value = before + shortcuts[word] + after;
                    inputElement.selectionEnd = wordStart + shortcuts[word].length;
                }
            }

            document.getElementById('ink_code').addEventListener('keydown', function(event) {
                if (event.key === 'Insert') { // Insert key
                    let cursorPosition = this.selectionStart;
                    handleShortcutInput(this, cursorPosition);
                }
            });

            document.getElementById('shortcutInput').addEventListener('keydown', function(event) {
                if (event.key === 'Insert') { // Insert key
                    let cursorPosition = this.selectionStart;
                    handleShortcutInput(this, cursorPosition);
                }
            });

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
        const fk_gross_id = "<?php echo $fk_gross_id; ?>";
        const fieldsContainer = document.getElementById("fields-container");
        const addMoreButton = document.getElementById("<?php echo $button_id; ?>");
        const currentYear = new Date().getFullYear();
        const lastTwoDigits = currentYear.toString().slice(-2);

        // Generate the next section code
        let sectionCode = generateNextSectionCode(specimenLetter);
        
        // Update the last generated section code
        lastSectionCodes[specimenLetter] = sectionCode;

        // Create a new table for each entry
        const table = document.createElement("table");
        table.classList.add("fixed-table");

        // Create table header
        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");
        headerRow.innerHTML = `
            <th>Section Code</th>
            <th>Description</th>
            <th>Pieces of Tissue</th>
        `;
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create table body
        const tbody = document.createElement("tbody");
        const row = document.createElement("tr");

        // Section Code
        const sectionCodeCell = document.createElement("td");
        const sectionCodeLabel = document.createElement("label");
        sectionCodeLabel.textContent = sectionCode;
        const inputSectionCode = document.createElement("input");
        inputSectionCode.type = "hidden";
        inputSectionCode.name = "sectionCode[]";
        inputSectionCode.value = sectionCode;
        sectionCodeCell.appendChild(sectionCodeLabel);
        sectionCodeCell.appendChild(inputSectionCode);
        row.appendChild(sectionCodeCell);

        // Description
        const descriptionCell = document.createElement("td");
        const descriptionInput = document.createElement("textarea");
        descriptionInput.cols = 40;
        descriptionInput.rows = 2;
        descriptionInput.name = "specimen_section_description[]";
        descriptionInput.value = section_text;
        descriptionInput.setAttribute('data-shortcut-file', 'shortcuts.json');
        descriptionInput.addEventListener('input', function() {
            const shortcutsFile = this.getAttribute('data-shortcut-file');
            fetch(shortcutsFile)
                .then(response => response.json())
                .then(shortcuts => {
                    let cursorPosition = this.selectionStart;
                    for (let shortcut in shortcuts) {
                        if (this.value.includes(shortcut)) {
                            this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                            this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                            break;
                        }
                    }
                })
                .catch(error => console.error('Error loading shortcuts:', error));
        });
        descriptionCell.appendChild(descriptionInput);
        row.appendChild(descriptionCell);

        // Tissue Pieces
        const tissueCell = document.createElement("td");
        const tissueInput = document.createElement("textarea");
        tissueInput.cols = 1;
        tissueInput.rows = 1;
        tissueInput.name = "tissue[]";
        tissueInput.value = '';
        tissueCell.appendChild(tissueInput);
        row.appendChild(tissueCell);

        // Cassette Number
        const cassetteNumberInput = document.createElement("input");
        cassetteNumberInput.type = "hidden";
        cassetteNumberInput.name = "cassetteNumber[]";
        cassetteNumberInput.value = sectionCode + '-' + last_value + '/' + lastTwoDigits;

        // fk_gross_id
        const fkGrossIdInput = document.createElement("input");
        fkGrossIdInput.type = "hidden";
        fkGrossIdInput.name = "fk_gross_id";
        fkGrossIdInput.value = fk_gross_id;

        row.appendChild(cassetteNumberInput);
        row.appendChild(fkGrossIdInput);

        tbody.appendChild(row);
        table.appendChild(tbody);

        fieldsContainer.appendChild(table);

        const saveButton = document.getElementById("saveButton");
        saveButton.style.display = "block";
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

document.getElementById("microDescriptionForm").addEventListener("submit", function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    
    fetch("update_micro_descriptions.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Micro Description', data);
        var labNumber = "<?php echo $LabNumber; ?>"; 
        window.location.href = `transcription.php?lab_number=${labNumber}`;
    })
    .catch(error => {
        console.error("Error:", error);
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
