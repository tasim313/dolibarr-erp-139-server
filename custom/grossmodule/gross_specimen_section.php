<?php 

include("connection.php");
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/grossmodule/class/gross.class.php');
dol_include_once('/grossmodule/lib/grossmodule_gross.lib.php');


$title = $langs->trans("Gross");
$help_url = '';
llxHeader('', $title, $help_url);

$GrossId = $_GET['fk_gross_id'];

$lab_number = get_lab_number($GrossId);

if ($lab_number !== null) {
    $last_value = substr($lab_number, 8);
} else {
    echo 'Error: Lab number not found';
}


$specimen_count_value = number_of_specimen($GrossId);

$alphabet_string = numberToAlphabet($specimen_count_value); 
print("<div class='container'>");

for ($i = 1; $i <= $specimen_count_value; $i++) {
    $specimenLetter = chr($i + 64); 
    $button_id =  "add-more-" . $i ;
    echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
    echo '<button type="submit" id="' . $button_id . '" data-specimen-letter="' . $specimenLetter . '" onclick="handleButtonClick(this)">Generate Section Codes</button>';
    echo '<br><br>';
}


?>

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


<!-- This div Generate automatic Form By Using Script Tag -->
<form id='specimen_section_form' method="post" action="gross_specimen_section_create.php">
    <div id="fields-container"> 
    </div>
    <br>
    <button id="saveButton" style="display: none;">Save</button>
</form>
    
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

    function handleButtonClick(button) {
        const buttonId = button.id;
        const specimenIndex = button.id.split("-")[1]; 
        const specimenLetter = button.getAttribute('data-specimen-letter');
        buttonClickCounts[buttonId] = (buttonClickCounts[buttonId] || 0) + 1;
        const section_text = 'Section from the ';
        const specimen_count_value = <?php echo $specimen_count_value; ?>;
        const last_value = "<?php echo $last_value; ?>";
        const fk_gross_id = "<?php echo $GrossId;?>";
        const fieldsContainer = document.getElementById("fields-container");
        const addMoreButton = document.getElementById("<?php echo $button_id; ?>");
        const currentYear = new Date().getFullYear();
        const lastTwoDigits = currentYear.toString().slice(-2);

        // Create a new field set for each entry
        const fieldSet = document.createElement("fieldset");
        fieldSet.classList.add("field-group"); // Add a class for styling (optional)
        let sectionCodes = [];
        let cassetteNumbers = [];
        let descriptions = [];

        const fkGrossIdInput = document.createElement("input");
        fkGrossIdInput.type = "hidden";
        fkGrossIdInput.name = "fk_gross_id"; // Set the name attribute to identify the input
        fkGrossIdInput.value = "<?php echo $GrossId;?>";
        fieldSet.appendChild(fkGrossIdInput);
            
        // Create the label and input for Section Code
           
        const sectionCodeLabel = document.createElement("label");
        sectionCodeLabel.textContent = 'Section Code: ' + specimenLetter + buttonClickCounts[buttonId];
        const inputSectionCode = document.createElement("input");
        inputSectionCode.type = "hidden";
        inputSectionCode.name =  "sectionCode[]"; // Assign unique name based on count
        inputSectionCode.value = specimenLetter + buttonClickCounts[buttonId];
        fieldSet.appendChild(sectionCodeLabel);
        fieldSet.appendChild(inputSectionCode);

        // Create the label and input for cassetteNumbers
        const cassetteNumberLabel = document.createElement("label");
        cassetteNumberLabel.textContent = "Cassette Number: " + specimenLetter + buttonClickCounts[buttonId] + '-' + last_value + '/' + lastTwoDigits;
        const cassetteNumberInput = document.createElement("input");
        cassetteNumberInput.type = "hidden";
        cassetteNumberInput.name = "cassetteNumber[]"; // Assign unique name based on count
        cassetteNumberInput.value = specimenLetter + buttonClickCounts[buttonId] + '-' + last_value + '/' + lastTwoDigits;
        fieldSet.appendChild(cassetteNumberLabel);
        fieldSet.appendChild(cassetteNumberInput );

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