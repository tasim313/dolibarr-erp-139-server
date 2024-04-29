<?php 
include('connection.php');
include('gross_common_function.php');

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
dol_include_once('/grossmodule/class/gross.class.php');
dol_include_once('/grossmodule/lib/grossmodule_gross.lib.php');

$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$fk_gross_id = $_GET['fk_gross_id'];

$lab_number = get_lab_number($fk_gross_id);
if ($lab_number !== null) {
    $last_value = substr($lab_number, 8);
} else {
    echo 'Error: Lab number not found';
}


print('<style>
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
</style>');



$specimens = get_gross_specimen_description($fk_gross_id);

print('<form method="post" action="update_gross_specimens.php">');
foreach ($specimens as $specimen) {
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
    echo '<textarea name="gross_description[]" cols="60" rows="10">' . htmlspecialchars($specimen['gross_description']) . '</textarea>';
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
}
echo '<input type="submit" value="Update">';
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
<button id="saveButton" style="display: none;">Save</button>
</form>');
print("</div>");
print('<div id="form-container">');
print('<form id="section-code-form" method="post" action="update_gross_specimen_section.php">');
foreach ($sections as $section) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="section_code">Section Code</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="hidden" name="gross_specimen_section_Id[]" value="' . htmlspecialchars($section['gross_specimen_section_id']) . '">';
    echo '<input type="text" name="sectionCode[]" value="' . htmlspecialchars($section['section_code']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="cassette_number">Cassette Number</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<input type="text" name="cassetteNumber[]" value="' . htmlspecialchars($section['cassettes_numbers']) . '" readonly>';
    echo '</div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="specimen_section_description">Description</label>';
    echo '</div>';
    echo '<div class="col-75">';
    echo '<textarea name="specimen_section_description[]">' . htmlspecialchars($section['specimen_section_description']) . '</textarea>';
    echo '</div>';
    echo '</div>';
}
echo '<input type="hidden" name="fk_gross_id[]" value="' . htmlspecialchars($fk_gross_id) . '">';
echo '<input type="submit" value="Update">';
echo '</form>';
print("</div>");

$summaries = get_gross_summary_of_section($fk_gross_id);
print('<form method="post" action="update_gross_summary.php">');
foreach ($summaries as $summary) {
    echo '<div class="row">';
    echo '<div class="col-25">';
    echo '<label for="summary">Summary</label>';
    echo '</div>';
    echo '<div class="col-75">';
    print('<textarea name="summary" id="summary">'. htmlspecialchars($summary['summary']) .'</textarea>');
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
echo '<input type="submit" value="Update">';
echo '</form>';


?>

<script>

document.addEventListener('DOMContentLoaded', function() {
  fetch('shortcuts.json')
      .then(response => response.json())
      .then(shortcuts => {
          document.querySelectorAll('textarea[name="gross_description[]"]').forEach(textarea => {
              textarea.addEventListener('input', function() {
                  let cursorPosition = this.selectionStart;
                  for (let shortcut in shortcuts) {
                      if (this.value.includes(shortcut)) {
                          this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                          this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                          break;
                      }
                  }
              });

              textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
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
          document.querySelectorAll('textarea[name="specimen_section_description[]"]').forEach(textarea => {
              textarea.addEventListener('input', function() {
                  let cursorPosition = this.selectionStart;
                  for (let shortcut in shortcuts) {
                      if (this.value.includes(shortcut)) {
                          this.value = this.value.replace(shortcut, shortcuts[shortcut]);
                          this.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                          break;
                      }
                  }
              });
            textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
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
fetch('shortcuts.json')
    .then(response => response.json())
    .then(shortcuts => {
        function handleShortcutInput(inputElement) {
            let inputValue = inputElement.value.toLowerCase();
            for (let shortcut in shortcuts) {
                if (inputValue.includes(shortcut)) {
                    inputElement.value = inputValue.replace(shortcut, shortcuts[shortcut]);
                    break; 
                }
            }
        }

        document.getElementById('summary').addEventListener('input', function() {
            let textarea = this;
            let cursorPosition = textarea.selectionStart;
            for (let shortcut in shortcuts) {
                if (textarea.value.includes(shortcut)) {
                    textarea.value = textarea.value.replace(shortcut, shortcuts[shortcut]);
                    textarea.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                    break; 
                }
            }
        });

        document.getElementById('shortcutInput').addEventListener('input', function() {
            handleShortcutInput(this);
        });
        // Listen for Enter key press event
        document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
                    }
                });
            });
        })
    .catch(error => console.error('Error loading shortcuts:', error));
</script>


<script>
fetch('shortcuts.json')
    .then(response => response.json())
    .then(shortcuts => {
        function handleShortcutInput(inputElement) {
            let inputValue = inputElement.value.toLowerCase();
            for (let shortcut in shortcuts) {
                if (inputValue.includes(shortcut)) {
                    inputElement.value = inputValue.replace(shortcut, shortcuts[shortcut]);
                    break; 
                }
            }
        }

        document.getElementById('ink_code').addEventListener('input', function() {
            let textarea = this;
            let cursorPosition = textarea.selectionStart;
            for (let shortcut in shortcuts) {
                if (textarea.value.includes(shortcut)) {
                    textarea.value = textarea.value.replace(shortcut, shortcuts[shortcut]);
                    textarea.selectionEnd = cursorPosition + (shortcuts[shortcut].length - shortcut.length);
                    break; 
                }
            }
        });

        document.getElementById('shortcutInput').addEventListener('input', function() {
            handleShortcutInput(this);
        });
        
        document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault(); // Prevent default behavior of Enter key
                        this.closest('form').submit(); // Submit the form containing the textarea
                    }
                });
            });
        })
    .catch(error => console.error('Error loading shortcuts:', error));
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

    // Iterate over each section to find the last section code for each specimen
    sections.forEach(function(section) {
        let specimenLetter = section.section_code.charAt(0); // Extract the specimen letter
        let sectionCode = section.section_code;
        
        // If the last section code for this specimen is not already set or the current section code is greater
        if (!lastSectionCodes[specimenLetter] || sectionCode > lastSectionCodes[specimenLetter]) {
            lastSectionCodes[specimenLetter] = sectionCode; // Update the last section code for this specimen
        }
    });

    function generateNextSectionCode(specimenLetter) {
        // Generate the next section code
        let sectionCode = '';
        if (lastSectionCodes[specimenLetter] === '') {
            // If it's the first section, generate it based on the specimen letter and button click count
            sectionCode = specimenLetter + '1';
        } else {
            // Otherwise, generate it sequentially based on the last section code
            const lastSectionNumber = parseInt(lastSectionCodes[specimenLetter].replace(specimenLetter, ''), 10);
            if (!isNaN(lastSectionNumber)) {
                sectionCode = specimenLetter + (lastSectionNumber + 1);
            } else {
                // Handle the case where lastSectionNumber is NaN (e.g., if lastSectionCode doesn't follow the expected format)
                console.error("Invalid last section code format:", lastSectionCodes[specimenLetter]);
                // You can provide a default behavior here, such as setting sectionCode to a predefined value
                // sectionCode = specimenLetter + "1";
            }
        }
        return sectionCode;
    }
    
    console.log("Last section codes:", lastSectionCodes);

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

        const fkGrossIdInput = document.createElement("input");
        fkGrossIdInput.type = "hidden";
        fkGrossIdInput.name = "fk_gross_id"; // Set the name attribute to identify the input
        fkGrossIdInput.value = "<?php echo $fk_gross_id;?>";
        fieldSet.appendChild(fkGrossIdInput);

        // Create the label and input for Section Code
        const sectionCodeLabel = document.createElement("label");
        sectionCodeLabel.textContent = 'Section Code: ' + sectionCode;
        const inputSectionCode = document.createElement("input");
        inputSectionCode.type = "text"; // Use "text" for Section Code input
        inputSectionCode.name =  "sectionCode[]"; // Assign unique name based on count
        inputSectionCode.value = sectionCode;
        fieldSet.appendChild(sectionCodeLabel);
        fieldSet.appendChild(inputSectionCode);

        // Create the label and input for cassetteNumbers
        const cassetteNumberLabel = document.createElement("label");
        cassetteNumberLabel.textContent = "Cassette Number: " + sectionCode + '-' + last_value + '/' + lastTwoDigits;
        const cassetteNumberInput = document.createElement("input");
        cassetteNumberInput.type = "text"; // Use "text" for Cassette Number input
        cassetteNumberInput.name = "cassetteNumber[]"; // Assign unique name based on count
        cassetteNumberInput.value = sectionCode + '-' + last_value + '/' + lastTwoDigits;
        fieldSet.appendChild(cassetteNumberLabel);
        fieldSet.appendChild(cassetteNumberInput);

        // Create the label and input for Description
        const descriptionLabel = document.createElement("label");
        descriptionLabel.textContent = "Description:";
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

        const saveButton = document.getElementById("saveButton");
        saveButton.style.display = "block";
        
        fieldSet.appendChild(descriptionLabel);
        fieldSet.appendChild(descriptionInput);
        fieldsContainer.appendChild(fieldSet);
    }
</script>



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