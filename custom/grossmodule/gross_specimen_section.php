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
$fk_gross_id = $GrossId;

$lab_number = get_lab_number($GrossId);

if ($lab_number !== null) {
    $last_value = substr($lab_number, 8);
} else {
    echo 'Error: Lab number not found';
}

$sections = get_gross_specimen_section($GrossId);

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
        // Check if lastSectionCodes for the current specimenLetter exists
        if (!lastSectionCodes.hasOwnProperty(specimenLetter)) {
            // If lastSectionCodes for the current specimenLetter doesn't exist, generate the first section code
            return specimenLetter + '1';
        } else {
            // Retrieve the last section code for the given specimenLetter
            const lastSectionCode = lastSectionCodes[specimenLetter];
            
            // Generate the next section code based on the last section code
            const lastSectionNumber = parseInt(lastSectionCode.replace(specimenLetter, ''), 10);
            const nextSectionNumber = isNaN(lastSectionNumber) ? 1 : lastSectionNumber + 1;
            return specimenLetter + nextSectionNumber;
        }
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


<form id="specimen_section_edit_from" method="post" action="update_gross_specimen_section_generate.php">
<div id="fields-container-edit"> 

</div>
<br>
<button id="editButton" style="display: none;">Update</button>
</form>


<style>
  .card-group {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 10px;
  }

  .card {
    flex: 0 0 calc(50% - 10px);
    margin-bottom: 10px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
  }

  .card-body {
    padding: 1rem;
  }

  .card-title {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
  }

  .card-text {
    margin-bottom: 1rem;
  }

  .text-body-secondary {
    color: #6c757d;
  }
</style>



<script>
  const newButtonClickCounts = {};
  let sections_edit = <?php echo json_encode($sections); ?>;

  
  // Check if the sections array is empty
  if (sections_edit.length === 0) {
    console.log('Value is empty');
  } else {
    // Create "Next" and "Previous" buttons
    const nextButton = document.createElement('button');
    nextButton.textContent = 'Next';
    nextButton.style.float = 'right'; // Align to the right side
    nextButton.style.marginBottom = '20px';
    nextButton.style.marginLeft = '10px';
    nextButton.style.backgroundColor = 'rgb(118, 145, 225)'; 
    nextButton.style.color = 'white';
    nextButton.style.padding = '8px 8px'; // Set padding
    nextButton.style.border = 'none'; // Remove border
    nextButton.style.borderRadius = '4px'; // Set border radius
    nextButton.style.cursor = 'pointer'; // Set cursor to pointer
    nextButton.style.transition = 'box-shadow 0.3s ease'; 
    nextButton.addEventListener('click', function(event) {
      event.preventDefault();
      window.location.href = "gross_summary_of_section.php?fk_gross_id=<?php echo $fk_gross_id; ?>";
    });

    const form = document.getElementById('specimen_section_edit_from');
    // Append buttons to the form
    form.prepend(nextButton);

    // Create a card group
    const cardGroup = document.createElement('div');
    cardGroup.classList.add('card-group')
    cardGroup.style.marginTop = '40px';
    
    // Append the card group to the form
    form.appendChild(cardGroup);

    // Loop through each section object in the sections array
    sections_edit.forEach(section => {

      const card = document.createElement('div');
      card.classList.add('card');

      const cardBody = document.createElement('div');
      cardBody.classList.add('card-body');
      
      // Create a new field set for each section
      const fieldSet = document.createElement('fieldset');
      
      // Create inputs for "Section Code", "Cassettes Numbers", and "Description"
      // If these properties are present in the section object

      if (section.hasOwnProperty('fk_gross_id')) {
        const fkGrossIdInput = document.createElement("input");
        fkGrossIdInput.type = "hidden";
        fkGrossIdInput.name = "fk_gross_id[]"; // Set the name attribute to identify the input
        fkGrossIdInput.value = section['fk_gross_id'];
        cardBody.appendChild(fkGrossIdInput);
      }

      if (section.hasOwnProperty('gross_specimen_section_id')) {
        const fkSpecimenSectionIdInput = document.createElement("input");
        fkSpecimenSectionIdInput.type = "hidden";
        fkSpecimenSectionIdInput.name = "gross_specimen_section_Id[]"; // Set the name attribute to identify the input
        fkSpecimenSectionIdInput.value = section['gross_specimen_section_id'];
        cardBody.appendChild(fkSpecimenSectionIdInput);
      }

      // Create card title
      if (section.hasOwnProperty('section_code')) {
        const sectionCodeLabel = document.createElement('label');
        sectionCodeLabel.textContent = 'Section Code: ';
        cardBody.appendChild(sectionCodeLabel);

        const sectionCodeInput = document.createElement('input');
        sectionCodeInput.type = 'text';
        sectionCodeInput.name = 'sectionCode[]';
        sectionCodeInput.value = section['section_code'];
        sectionCodeInput.readOnly = true;
        cardBody.appendChild(sectionCodeInput);
        cardBody.appendChild(document.createElement('br')); 
      }

      
      
      if (section.hasOwnProperty('cassettes_numbers')) {
        const cassettesNumbersLabel = document.createElement('label');
        cassettesNumbersLabel.textContent = 'Cassettes Numbers: ';
        cardBody.appendChild(cassettesNumbersLabel);

        const cassettesNumbersInput = document.createElement('input');
        cassettesNumbersInput.type = 'text';
        cassettesNumbersInput.name = 'cassetteNumber[]';
        cassettesNumbersInput.value = section['cassettes_numbers'];
        cassettesNumbersInput.readOnly = true;
        cardBody.appendChild(cassettesNumbersInput);
        cardBody.appendChild(document.createElement('br')); 
      }
      
      if (section.hasOwnProperty('specimen_section_description')) {
        const descriptionLabel = document.createElement('label');
        descriptionLabel.textContent = 'Description: ';
        cardBody.appendChild(descriptionLabel);

        const descriptionInput = document.createElement('input');
        descriptionInput.type = 'text';
        descriptionInput.name = 'specimen_section_description[]';
        descriptionInput.value = section['specimen_section_description'];
        cardBody.appendChild(descriptionInput);
        cardBody.appendChild(document.createElement('br')); 

        descriptionInput.addEventListener('keypress', function(event) {
          if (event.key === 'Enter') {
            // Prevent default form submission
            event.preventDefault();
            
            // Trigger form submission
            form.submit();
          }
        });
      }

       // Append card body to card
      card.appendChild(cardBody);

      // Append card to card group
      cardGroup.appendChild(card);
      
    });

    
  }
</script>