<?php 
include('connection.php');
include('gross_common_function.php');
include('../transcription/common_function.php');

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
dol_include_once('/gross/class/gross.class.php');
dol_include_once('/gross/lib/gross_gross.lib.php');

$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$fk_gross_id = $_GET['fk_gross_id'];

$lab_number = get_lab_number($fk_gross_id);
$LabNumber = $lab_number;
if ($lab_number !== null) {
    $last_value = substr($lab_number, 8);
} else {
    echo 'Error: Lab number not found';
}

$abbreviations = get_abbreviations_list();



if (strpos($lab_number, 'HPL') === 0) {
    $re_gross_lab_number = substr($lab_number, 3);  // Remove the first 3 characters ("HPL")
} else {
    $re_gross_lab_number = $lab_number;
}

$re_gross_request = get_re_gross_request_list($re_gross_lab_number);

// Check if the request list is empty
$is_empty = empty($re_gross_request);  // True if empty, false otherwise
$doctors = get_doctor_list();
$assistants = get_gross_assistant_list();

print('<style>
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

.field-group input[name="requires_slide_for_block[]"] {
    flex: 0.2; /* Smaller size for requires_slide_for_block input */
    min-width: 10px; /* Minimum width for smaller input */
    margin-right: 10px;
}

.regross-button {
    background-color: red; /* Button color */
    color: white;          /* Text color */
    border: none;         /* Remove border */
    padding: 8px 16px;    /* Adjust padding for a better appearance */
    cursor: pointer;       /* Change cursor to pointer */
    font-size: 12px;      /* Font size */
    border-radius: 5px;   /* Rounded corners */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add subtle shadow */
    transition: background-color 0.3s, transform 0.2s; /* Transition for hover effect */
}



@media screen and (max-width: 600px) {
.col-25, .col-75, input[type=submit] {
width: 100%;
margin-top: 0;
}
}
</style>');

$LabNumberWithoutPrefix = substr($LabNumber, 3);
$patient_information = get_patient_details_information($LabNumberWithoutPrefix);
print('<div class="sticky">');
print('<h1>Patient Information</h1>');
foreach ($patient_information as $list) {
    $gender = '';
    if ($list['Gender'] == '1') {
        $gender = 'Male';
    } elseif ($list['Gender'] == '2') {
        $gender = 'Female';
    } else {
        $gender = 'Other';
    }
    print('
    <table class="fixed-table">
	<thead>
    <tr>
        <th>Name </th> 
        <th>Patient Code </th>
        <th>Date of Birth </th>
        <th>Age </th>
        <th>Gender </th>
        <th>Lab Number </th>
        <th></th>
    </tr>
    <thead>
    <tr>
    <td><input type="text" name="name[]" value="' . $list['name'] . '" readonly></td> 
    <td><input type="text" name="patient_code[]" value="' . $list['patient_code'] . '" readonly></td> 
    <td><input type="text" name="date_of_birth[]" value="' . $list['date_of_birth'] . '" readonly></td>
    <td><input type="text" name="age[]" value="' . $list['Age'] . '" readonly></td>     
    <td><input type="text" name="gender[]" value="' . $gender . '" readonly></td> 
    <td><input type="text" name="labnumber" value="' . $LabNumber . '" readonly></td> 
    <td><button type="button" 
        class="btn btn-primary" style="background-color: rgb(118, 145, 225); color: white; height: 45px; width: 100px;" 
        onclick="redirectToReport()">Preview</button></td> 
    </tr>
    </table>'
    );
}
print('</div>');
print('<br><br>');
$specimens = get_gross_specimen_description($fk_gross_id);

print('<form method="post" action="update_gross_specimens.php">');
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
echo '<input type="submit" value="Save">';
echo '</form>';


$sections = get_gross_specimen_section($fk_gross_id);
$specimen_count_value = number_of_specimen($fk_gross_id);
$alphabet_string = numberToAlphabet($specimen_count_value); 
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

print("<div class='container'>");
print('<button type="button" id="re-gross-info" class="regross-button"  style="display: none;" title="Re-Gross Information">Re-Gross</button>');
echo '<div><br></div>';
echo('<!-- Re-Gross Form -->
<form id="regross-form" action="re_gross/regross_info.php" method="post" style="display: none;">

    <!-- Hidden field for lab number -->
    <input type="hidden" id="lab_number" name="lab_number" value="' . htmlspecialchars($LabNumber) . '">

    <!-- Select field for Doctor Name -->
    <label for="doctor_name">Doctor:</label>
    <select id="doctor_name" name="doctor_name" required>
        <option value="">Select Doctor</option>
    ');

foreach ($doctors as $doctor) {
    $selected = ($doctor["doctor_username"] == $loggedInUsername) ? "selected" : "";
    // Concatenate properly with escaped quotes
    echo "<option value=\"" . htmlspecialchars($doctor["doctor_username"]) . "\" $selected>" . htmlspecialchars($doctor["doctor_username"]) . "</option>";
}

echo('</select>'); // Close the Doctor select

echo("<label for='gross_station_type'>Gross Station</label>
    <select name='gross_station_type' id='gross_station_type' required>
        <option value=''></option>
        <option value='One' " . (isset($_SESSION['gross_station_type']) && $_SESSION['gross_station_type'] === 'One' ? 'selected' : '') . ">One</option>
        <option value='Two' " . (isset($_SESSION['gross_station_type']) && $_SESSION['gross_station_type'] === 'Two' ? 'selected' : '') . ">Two</option>
    </select>");

echo('
    <label for="gross_assistant_name">Gross Assistant Name:</label>
    <select id="gross_assistant_name" name="gross_assistant_name" required>
        <option value="">Select Assistant</option>
    ');

foreach ($assistants as $assistant) {
    $selected = ($assistant["username"] == $loggedInUsername) ? "selected" : "";
    // Concatenate properly with escaped quotes
    echo "<option value=\"" . htmlspecialchars($assistant["username"]) . "\" $selected>" . htmlspecialchars($assistant["username"]) . "</option>";
}

echo('
    </select>
    <!-- Submit button -->
    <button type="submit">Save Re-Gross</button>
    <br>
</form>');

echo '<div><br></div>';
// Generate regross section buttons
for ($i = 1; $i <= $specimen_count_value; $i++) {
    $specimenLetter = chr($i + 64); 
    $regross_button_id = "generate-regross-" . $i;
    // Button for regross section code generation
    echo '<button type="button" id="' . $regross_button_id . '" data-specimen-letter="' . $specimenLetter . '"
    class="regross-button"  style="display: none;" title="Generate regross section Code">
    ' . $specimenLetter . '&nbsp;&nbsp;GRSC</button>';
    echo '<br><br>';
}
// Regross section form container
print('
<form id="regross_section_form" method="post" action="gross_regross_section_generate.php">
    <div id="regross-fields-container"> 
    </div>
    <br>
    <button id="regrossSaveButton" style="display:none;">Save Regross</button>
</form>');
print("</div>");   

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
print('<form method="post" action="update_gross_summary.php" id="auto-submit-form">');
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


?>

<!-- Include Quill's CSS and JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<!-- <script>
        // Abbreviations dictionary
        const abbreviations = {
            "acic": "acute and chronic inflammatory cells",
            "aic": "acute inflammatory cells",
            "ant ling rm": "anterior lingual resection margin",
            "ant brm": "Anterior buccal resection margin",
            "post ling rm": "Posterior Lingual Resection margin",
            "apf": "additional pathologic findings",
            "afb": "Acid fast bacilli",
            "asi": "Acute suppurative inflammation",
            "aie": "Acute inflammatory exudate",
            "a&p": "Antero – posteriorly",
            "a&m": "available material",
            "avm": "arteriovenous malformation",
            "avn": "Avascular necrosis",
            "b&g": "background",
            "bxx": "biopsy"
        };
        // Initialize Quill editor for each textarea
        document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.editor').forEach((element, index) => {
                        const editor = new Quill(element, {
                            theme: 'snow',
                            modules: {
                                toolbar: [
                                    // [{ 'header': '1' }, { 'header': '2' }],
                                    // [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                    // ['bold', 'italic', 'underline'],
                                    // ['link', 'image'],
                                    // [{ 'align': [] }],
                                    // [{ 'color': [] }, { 'background': [] }]
                                ]
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
                        document.querySelector(`#editor_${index}`).addEventListener('keydown', function(event) {
                            if (event.ctrlKey && event.key === 'a') { // Example: Ctrl + A for abbreviation
                                event.preventDefault();
                                const selection = window.getSelection().toString().trim().toLowerCase();
                                if (selection) {
                                        const abbreviation = abbreviations[selection];
                                        if (abbreviation) {
                                            replaceSelectedText(editor, abbreviation);
                                        } else {
                                            alert('No abbreviation found for the selected text.');
                                        }
                                }
                            }
                        });
                });
        });
        // Function to replace selected text in Quill editor
        function replaceSelectedText(editor, replacementText) {
            const range = editor.getSelection();
            if (range) {
                editor.deleteText(range.index, range.length);
                editor.insertText(range.index, replacementText);
                editor.setSelection(range.index + replacementText.length, 0);
            }
        }
</script> -->


<script>
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

                        if (event.ctrlKey && event.key === 's') {
                            event.preventDefault(); // Prevent default behavior of Enter key
                            this.closest('form').submit(); // Submit the form containing the textarea
                        }
                    });
                });
            })
            .catch(error => console.error('Error loading shortcuts:', error));
    });

</script>

<script>
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

                        if (event.ctrlKey && event.key === 's') {
                            event.preventDefault(); // Prevent default behavior of Enter key
                            this.closest('form').submit(); // Submit the form containing the textarea
                        }
                    });
                });
            })
            .catch(error => console.error('Error loading shortcuts:', error));
    });
</script>

<script>
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

                        if (event.ctrlKey && event.key === 's') {
                            event.preventDefault(); // Prevent default behavior of Enter key
                            this.closest('form').submit(); // Submit the form containing the textarea
                        }
                    });
                });
            })
            .catch(error => console.error('Error loading shortcuts:', error));
    });
</script>

<!-- <script>

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
        sectionCodeLabel.textContent = 'Section Code: ' + sectionCode;
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
        // fieldSet.appendChild(cassetteNumberLabel);
        fieldSet.appendChild(cassetteNumberInput);
     
        const tissueLabel = document.createElement("label");
        tissueLabel.textContent = "Tissue Pieces In  " + sectionCode 
        const tissueInput = document.createElement("input");
        tissueInput.type = "text"; // Use "text" for Cassette Number input
        tissueInput.name = "tissue[]"; // Assign unique name based on count
        tissueInput.value = '';
        fieldSet.appendChild(tissueLabel);
        fieldSet.appendChild(tissueInput);

        // Create the label and input for Description
        // const descriptionLabel = document.createElement("label");
        // descriptionLabel.textContent = "Description:";
        
        const saveButton = document.getElementById("saveButton");
        saveButton.style.display = "block";
        
        // fieldSet.appendChild(descriptionLabel);
        fieldsContainer.appendChild(fieldSet);
    }
</script> -->

<style>
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
    button[type=submit] {
            background-color: rgb(118, 145, 225);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: center;
            transition: box-shadow 0.3s ease;
        }
    button[type=submit]:hover {
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); 
    }

    .container {
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
    }

    .col-25 {
    float: left;
    width: 25%;
    margin-top: 6px;
    }

    .col-75 {
    float: left;
    width: 75%;
    margin-top: 6px;
    }


    .row::after {
    content: "";
    display: table;
    clear: both;
    }


    @media screen and (max-width: 600px) {
    .col-25, .col-75, input[type=submit] {
        width: 100%;
        margin-top: 0;
    }
    }
</style>

<script type="text/javascript">
        function redirectToReport() {
            var labNumber = "<?php echo $lab_number; ?>";
            // window.location.href = "hpl_report.php?lab_number=" + labNumber;
            window.open("hpl_report.php?lab_number=" + labNumber, "_blank");
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

                        // Debug: Log current text and caret position
                        console.log("Text before caret:", text.substring(0, caretPosition));
                        console.log("Caret position:", caretPosition);

                        // Find the word before the caret position
                        const textBeforeCaret = text.substring(0, caretPosition);
                        const words = textBeforeCaret.trim().split(/\s+/);
                        const lastWord = words[words.length - 1]; // Get the last word in its original case

                        // Debug: Log the last word
                        console.log("Last word typed:", lastWord);

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
                            // Debug: Log abbreviation found
                            console.log("Abbreviation found:", fullAbbreviation);

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

        // Create the  input for Requires Slide for Block (New Field)
        const slideForBlockInput = document.createElement("input");
        slideForBlockInput.type = "text"; // Use "text" for Requires Slide for Block input
        slideForBlockInput.name = "requires_slide_for_block[]"; // Ensure it's an array to capture multiple values
        slideForBlockInput.placeholder = "Enter how many slides need"; // Optional placeholder text
        fieldSet.appendChild(slideForBlockInput);


        const saveButton = document.getElementById("saveButton");
        saveButton.style.display = "block";
        
        // fieldSet.appendChild(descriptionLabel);
        fieldsContainer.appendChild(fieldSet);
        console.log("Field Container: ", fieldSet)
    }
</script>


<!-- <script>
    var isEmpty = <?php echo json_encode($is_empty); ?>;
    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById('generate_regross_section_code');
        
        // Check if it's empty and show/hide the button
        if (!isEmpty) {
            button.style.display = 'block';  // Show button if the request is not empty
        } else {
            button.style.display = 'none';  // Hide button if the request is empty
        }
    });
</script> -->


<script>
    // Send the isEmpty variable from PHP to JavaScript
    var isEmpty = <?php echo json_encode($is_empty); ?>;

    
    document.addEventListener("DOMContentLoaded", function() {
        let regross_sections = <?php echo json_encode($sections); ?>;
        let lastRegrossSectionCodes = {};
        let lastRegrossCassetteNumbers = {};
        let lastRegrossTissues = {};

        console.log('Regross sections from PHP:', regross_sections); // Debug regross sections

        // Iterate over regross sections to find the last section code, cassette number, and tissue for each specimen
        regross_sections.forEach(function(section) {
            let specimenLetter = section.section_code.charAt(0);
            let sectionCode = section.section_code;
            let cassetteNumber = section.cassettes_numbers;
            let tissue = section.tissue;

            // Update the last values for each specimen
            lastRegrossSectionCodes[specimenLetter] = sectionCode;
            lastRegrossCassetteNumbers[specimenLetter] = cassetteNumber;
            lastRegrossTissues[specimenLetter] = tissue;
        });

        // Function to generate the next regross section code
        function generateNextRegrossSectionCode(specimenLetter) {
            let sectionCode = '';

            if (!lastRegrossSectionCodes[specimenLetter] || lastRegrossSectionCodes[specimenLetter] === '') {
                sectionCode = specimenLetter + '1';
            } else {
                const lastSectionNumber = parseInt(lastRegrossSectionCodes[specimenLetter].slice(1), 10);
                const nextSectionNumber = lastSectionNumber + 1;
                sectionCode = specimenLetter + nextSectionNumber;
            }

            lastRegrossSectionCodes[specimenLetter] = sectionCode;
            return sectionCode;
        }

        // Function to handle button clicks for regross
        function handleRegrossButtonClick(button) {
            const specimenLetter = button.getAttribute('data-specimen-letter');
            let sectionCode = generateNextRegrossSectionCode(specimenLetter);

            const fieldsContainer = document.getElementById("regross-fields-container");

            // Create a new field set for the regross section
            const fieldSet = document.createElement("fieldset");
            fieldSet.classList.add("field-group");

            // Hidden input for fk_gross_id
            const fkGrossIdInput = document.createElement("input");
            fkGrossIdInput.type = "hidden";
            fkGrossIdInput.name = "fk_gross_id";
            fkGrossIdInput.value = "<?php echo $fk_gross_id; ?>";
            fieldSet.appendChild(fkGrossIdInput);

            // Section Code label and input
            const sectionCodeLabel = document.createElement("label");
            sectionCodeLabel.textContent = sectionCode + ' :';
            const inputSectionCode = document.createElement("input");
            inputSectionCode.type = "hidden";
            inputSectionCode.name = "sectionCode[]";
            inputSectionCode.value = sectionCode;
            fieldSet.appendChild(sectionCodeLabel);
            fieldSet.appendChild(inputSectionCode);

            // Description input
            const descriptionInput = document.createElement("input");
            descriptionInput.type = "text";
            descriptionInput.name = "specimen_section_description[]";
            descriptionInput.value = 'Section from the ';
            fieldSet.appendChild(descriptionInput);

            // Cassette Number input
            const currentYear = new Date().getFullYear();
            const lastTwoDigits = currentYear.toString().slice(-2);
            const cassetteNumberLabel = document.createElement("label");
            cassetteNumberLabel.textContent = "Cassette Number: " + sectionCode + '-' + "<?php echo $last_value; ?>" + '/' + lastTwoDigits;
            const cassetteNumberInput = document.createElement("input");
            cassetteNumberInput.type = "hidden";
            cassetteNumberInput.name = "cassetteNumber[]";
            cassetteNumberInput.value = sectionCode + '-' + "<?php echo $last_value; ?>" + '/' + lastTwoDigits;
            fieldSet.appendChild(cassetteNumberInput);

            // Tissue input
            const tissueLabel = document.createElement("label");
            tissueLabel.textContent = "Tissue Pieces In " + sectionCode;
            const tissueInput = document.createElement("input");
            tissueInput.type = "text";
            tissueInput.name = "tissue[]";
            tissueInput.placeholder = "Tissue Pieces In " + sectionCode; 
            fieldSet.appendChild(tissueInput);

            // Bone selection as a checkbox
            const boneLabel = document.createElement("label");
            boneLabel.textContent = "Bone?";
            const boneInput = document.createElement("input");
            boneInput.type = "checkbox";
            boneInput.name = "bone[]";
            boneInput.value = sectionCode;
            fieldSet.appendChild(boneLabel);
            fieldSet.appendChild(boneInput);

            // Create the label and input for Requires Slide for Block (New Field)
            const slideForBlockLabel = document.createElement("label");
            slideForBlockLabel.textContent = "Requires Slide for Block:";
            const slideForBlockInput = document.createElement("input");
            slideForBlockInput.type = "text"; // Use "text" for Requires Slide for Block input
            slideForBlockInput.name = "requires_slide_for_block[]"; // Assign unique name based on count
            slideForBlockInput.placeholder = "Enter how many slide need"; // Optional placeholder text
            fieldSet.appendChild(slideForBlockInput);


            // Hidden input for re_gross with value 'yes'
            const reGrossInput = document.createElement("input");
            reGrossInput.type = "hidden";
            reGrossInput.name = "re_gross";
            reGrossInput.value = "yes"; // Set the value to 'yes'
            fieldSet.appendChild(reGrossInput);

            // Append the fieldSet to the container
            fieldsContainer.appendChild(fieldSet);

            // Show the save button
            document.getElementById("regrossSaveButton").style.display = "block";
        }

        // Check if isEmpty is false, then show the buttons
        if (!isEmpty) {
            console.log('Buttons will be displayed'); // Debugging button display
            document.querySelectorAll("[id^='generate-regross-']").forEach(function(button) {
                button.style.display = 'block'; // Display each button
                button.addEventListener("click", function(event) {
                    event.preventDefault(); // Prevent form submission
                    handleRegrossButtonClick(button);
                });
            });
        } else {
            console.log('isEmpty is true, no buttons will be displayed.');
        }

        // Add the event listener to the save button
        document.getElementById("regrossSaveButton").addEventListener("click", function(event) {
            event.preventDefault();
            document.getElementById("regross_section_form").submit(); // Submit the form
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById('re-gross-info');
        var form = document.getElementById('regross-form');
        var re_gross_button = document.getElementById('re-gross-info');
        
        // Check if it's empty and show/hide the button
        if (!isEmpty) {
            button.style.display = 'block';  // Show button if the request is not empty
            re_gross_button.style.display = 'block';
            
            // Set the lab number (assuming you have this value from your backend or script)
            var labNumber =  <?php echo json_encode($LabNumber); ?>; // Replace with dynamic lab number
            document.getElementById('lab_number').value = labNumber;
        } else {
            button.style.display = 'none';   // Hide button if the request is empty
            re_gross_button.style.display = 'none';
        }

        // Show the form when the button is clicked
        button.addEventListener('click', function() {
            form.style.display = 'block'; // Show the form
        });
    });
</script>