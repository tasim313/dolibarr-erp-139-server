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
print("<p> Specimen : " . $alphabet_string . "</p>");


echo '<form method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';

for ($i = 1; $i <= $specimen_count_value; $i++) {
    $specimenLetter = chr($i + 64); 
    echo '<label for="specimen' . $i . '">Specimen ' . $specimenLetter . ': </label>';
    echo '<input type="text" name="specimen' . $i . '" id="specimen' . $i . '" required>';
    echo '<br>';
}
print("<br>");
echo '<input type="submit" value="Generate Section Codes">';
print("<br><br><br>");
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   

    $sectionCodes = [];
    $cassetteNumbers = [];
    $descriptions = [];

    $allFieldsFilled = true;
    for ($i = 1; $i <= $specimen_count_value; $i++) {
        $specimenInput = $_POST['specimen' . $i];
        if (empty($specimenInput)) {
            $allFieldsFilled = false;
            break;
        }
    }

    if ($allFieldsFilled) {
        
        for ($i = 1; $i <= $specimen_count_value; $i++) {
            $specimenInput = $_POST['specimen' . $i];
            $specimenSectionDescription = isset($_POST['specimen_section_description']) ? $_POST['specimen_section_description'] : ''; 
            
            if (is_numeric($specimenInput) && $specimenInput > 0) {
                $specimenLetter = chr($i + 64);

                
                for ($j = 1; $j <= $specimenInput; $j++) {
                    $cassetteNumber = $specimenLetter . $j . '-' . $last_value . '/24';
                    $sectionCodes[] = $specimenLetter . $j;
                    $cassetteNumbers[] = $cassetteNumber;
                    $descriptions[] = $specimenSectionDescription; 
                }
            }
        }

        
        echo '<h2>Generated Section Codes:</h2>';
        echo '<form method="post" action="gross_specimen_section_create.php">';
        for ($k = 0; $k < count($sectionCodes); $k++) {
            $GrossId = $_GET['fk_gross_id'];
            echo '<input type="hidden" name="fk_gross_id[]" value="' . $GrossId . '">';
            echo 'Section Code: ' . $sectionCodes[$k] . '<br>';
            echo '<br>';
            echo ' Cassette Number: ' . $cassetteNumbers[$k] . '<br>';
            echo '<input type="hidden" name="sectionCode[]" value="' . $sectionCodes[$k] . '">';
            echo '<input type="hidden" name="cassetteNumber[]" value="' . $cassetteNumbers[$k] . '">';
            echo '<label for="specimen_section_description' . $k . '">Description: </label>';
            echo '<textarea name="specimen_section_description[]" id="specimen_section_description' . $k . '"required>' . $descriptions[$k] . '</textarea><br>'; 
            echo '<br><br>';
        }
        print("<br>");
        echo '<input type="submit" value="Next">';
        print("<br><br><br>");
        echo '</form>';
        print('</div>');
    } else {
        echo '<p style="color: red;">Please fill in all required fields before submitting.</p>';
    }
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
