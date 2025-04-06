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
            width: 40%;
            margin-top: 6px;
        }

        .col-75 {
            float: left;
            width: 60%;
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

$specimens = get_gross_specimen_description($fk_gross_id);

$sections = get_gross_specimen_section($fk_gross_id);
$specimen_count_value = number_of_specimen($fk_gross_id);
$alphabet_string = numberToAlphabet($specimen_count_value); 

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



    // summary of section
    // Initialize counters and summary text
    $total_sections = count($sections); // Number of section codes
    $total_tissues = 0;
    $tissue_description = '';

    $summaries = get_gross_summary_of_section($fk_gross_id);



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
        <form action="../transcription/preliminary_report/hpl/insert_micro_description.php" method="POST" class="micro-description-insert-form">
            <?php foreach ($specimens_list as $index => $specimen) { ?>
                <div class="form-group">
                    <label for="specimen_<?php echo $index; ?>" class="bold-label">Specimen:</label>
                    <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars((string) $specimen['specimen']); ?></textarea>

                    <!-- Replace Quill editor with normal textarea -->
                    <textarea class="specimen-textarea" name="description[]"></textarea>
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
        <?php
    } else {
        foreach ($existingMicroDescriptions as $key => $existingDescription) {
            $formId = 'microDescriptionForm' . $key;
            ?>
            <form action="../transcription/preliminary_report/hpl/update_micro_descriptions.php" method="POST"  class="micro-description-form">
                <div class="form-group">
                    <label for="specimen" class="bold-label">Specimen:</label>
                    <textarea class="specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars($existingDescription['specimen']); ?></textarea>

                    <!-- Replace Quill editor with normal textarea -->
                    <textarea class="specimen-textarea" name="description[]"><?php 
                        $micro_pre_define_text = "Sections Show";
                        $descriptionValue = !empty($existingDescription['description']) ? 
                            htmlspecialchars($existingDescription['description']) : 
                            $micro_pre_define_text;
                        echo $descriptionValue; 
                    ?></textarea>

                    <!-- Hidden fields -->
                    <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($existingDescription['fk_gross_id']); ?>">
                    <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
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
    <form action="../transcription/preliminary_report/hpl/insert_diagnosis_description.php" id="diagnosisInsertDescriptionForm" method="POST" class="diagnosisInsertDescriptionForm">
        <?php foreach ($specimens_list as $index => $specimen) { ?>
            <div class="form-group">
                <label for="diagnosis_specimen_<?php echo $index; ?>" class="bold-label">Specimen:</label>
                <textarea class="diagnosis-specimen-textarea" name="specimen[]" readonly><?php echo htmlspecialchars((string) $specimen['specimen']); ?></textarea>

                <div class="row">
                    <div class="col-25">
                        <label for="title" class="bold-label" style="width: 80px;">Title:</label>
                    </div>
                    <div class="col-75">
                        <?php $titleValue = !empty($specimen['title']) ? htmlspecialchars($specimen['title']) : 'biopsy'; ?>
                        <input type="text" name="title[]" value="<?php echo $titleValue; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-25">
                        <label for="description" class="bold-label" style="width: 40px;">Description:</label>
                    </div>
                    <div class="col-75">
                        
                        <textarea name="description[]"></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-25">
                        <label for="comment" class="bold-label" style="width: 120px;">Comment:</label>
                    </div>
                    <div class="col-75">
                        
                        <textarea name="comment[]"><?php echo htmlspecialchars($comment); ?></textarea>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Hidden input fields -->
        <input type="hidden" name="lab_number[]" value="<?php echo htmlspecialchars($LabNumber); ?>">
        <input type="hidden" name="fk_gross_id[]" value="<?php echo htmlspecialchars($fk_gross_id); ?>">
        <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
        <input type="hidden" name="status[]" value="Done">

        <div class="grid">
            <br><button type="submit" class="btn btn-success">Save</button>
        </div>
    </form>
   
    <?php
}else {
    ?>
    <form action="../transcription/preliminary_report/hpl/update_diagnosis_descriptions.php" id="diagnosisDescriptionForm" method="POST">
        <?php foreach ($existingDiagnosisDescriptions as $index => $specimen): ?>
            <?php
                // Prepare fallback values if some fields are missing
                $description = $specimen['description'] ?? '';
                $title = $specimen['title'] ?? 'biopsy';
                $comment = $specimen['comment'] ?? '';
                $fk_gross_id = $specimen['fk_gross_id'] ?? '';
                $status = $specimen['status'] ?? '';
                $lab_number = $specimen['lab_number'] ?? '';
                $row_id = $specimen['row_id'] ?? '';
                $specimen_text = $specimen['specimen'] ?? '';
                $specimen_id = $specimen['specimen_id'] ?? '';
            ?>

            <!-- Specimen display -->
            <div class="row">
                <div class="col-25">
                    <label for="specimen">Specimen</label>
                </div>
                <div class="col-75">
                    <input type="hidden" name="specimen_id[]" value="<?= htmlspecialchars($specimen_id) ?>" readonly>
                    <input type="text" name="specimen[]" value="<?= htmlspecialchars($specimen_text) ?>" readonly>
                </div>
            </div>

            <!-- Title field -->
            <div class="row">
                <div class="col-25">
                    <label for="title" class="bold-label" style="width: 120px;">Title:</label>
                </div>
                <div class="col-75">
                    <input type="text" name="title[]" value="<?= htmlspecialchars($title) ?>">
                </div>
            </div>

            <!-- Description field with Quill editor -->
            <div class="row">
                <div class="col-25">
                    <label for="description" class="bold-label" style="width: 120px;">Description:</label>
                </div>
                <div class="col-75">
                    <textarea name="description[]"><?= htmlspecialchars($description) ?></textarea>
                </div>
            </div>

            <!-- Comment field with Quill editor -->
            <div class="row">
                <div class="col-25">
                    <label for="comment" class="bold-label" style="width: 120px;">Comment:</label>
                </div>
                <div class="col-75">
                    <textarea name="comment[]"><?= htmlspecialchars($comment) ?></textarea>
                </div>
            </div>

            <!-- Hidden fields for additional metadata -->
            <input type="hidden" name="fk_gross_id[]" value="<?= htmlspecialchars($fk_gross_id) ?>">
            <input type="hidden" name="created_user[]" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
            <input type="hidden" name="status[]" value="<?= htmlspecialchars($status) ?>">
            <input type="hidden" name="lab_number[]" value="<?= htmlspecialchars($lab_number) ?>">
            <input type="hidden" name="row_id[]" value="<?= htmlspecialchars($row_id) ?>">

        <?php endforeach; ?>

        <div class="grid">
            <button id="diagnosisDescriptionSaveButton" type="submit" name="submit" value="att_relation" class="btn btn-primary">Save</button>
        </div>
    </form>
    <?php
}

?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

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


    // document.addEventListener('DOMContentLoaded', function() {
    //     // No Quill initialization needed anymore

    //     // Update form submission handler to use textarea values instead of Quill content
    //     document.querySelectorAll("form[id^='microDescriptionForm']").forEach(function(form) {
    //         form.addEventListener("submit", function(event) {
    //             event.preventDefault();
                
    //             const formData = new FormData(this);
                
    //             fetch("../transcription/preliminary_report/hpl/update_micro_descriptions.php", {
    //                 method: "POST",
    //                 body: formData
    //             })
    //             .then(response => response.text())
    //             .then(data => {
    //                 window.location.reload();
    //             })
    //             .catch(error => {
    //                 console.error("Error:", error);
    //             });
    //         });
    //     });
    // });
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

    
</script>