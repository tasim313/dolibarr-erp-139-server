<?php
include('connection.php');
include('histo_common_function.php');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("histolab@histolab"));

$action = GETPOST('action', 'aZ09');

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Histo Lab Area"));

print load_fiche_titre($langs->trans("Histo Lab Area"), '', 'histolab.png@histolab');

print '<div class="fichecenter"><div class="fichethirdleft">';
print("<style>
.container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  float: left;
  background-color: #fff;
  border-radius: 1px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
  padding: 20px;
  width: 600px;
  margin-left: auto; 
  margin-right: auto;
}
.input-field {
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 10px;
  width: 180px;
  font-size: 16px;
  outline: none;
}
.input-field:focus {
  border-color: dodgerblue;
}
.btn {
  background-color: dodgerblue;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 10px 20px;
  cursor: pointer;
  font-size: 16px;
  outline: none;
}
.btn:hover {
  background-color: #007bff;
}
#histoGrossTable {
    width: 100%;
    border-collapse: collapse;
  }
  
  #histoGrossTable th, #histoGrossTable td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
  }
  
  #histoGrossTable th {
    background-color: #046aaa;
    color: white;
  }
</style>");

print('<div class="container">
<input type="date" id="fromDateTime" class="input-field" placeholder="From">
<input type="date" id="toDateTime" class="input-field" placeholder="To">
<button id="submitBtn" class="btn">Submit</button>
</div>');


$histo_gross_list = get_histo_gross_specimen_list();

print("<table id='histoGrossTable'>");
print("<tr></tr>");


print("<tbody id='histoGrossTableBody'>");
print("</tbody>");
print("</table>");


print('<script>
const histo_gross_list = ' . json_encode($histo_gross_list) . ';

// Define the submitDateTime function
function submitDateTime() {
    var fromDate = new Date(document.getElementById("fromDateTime").value);
    var toDate = new Date(document.getElementById("toDateTime").value);
    var tableRows = "";
    
    // Filter items by date range
    var filteredItems = histo_gross_list.filter(function(item) {
        var itemDate = new Date(item["Gross Create Date"]);
        return itemDate >= fromDate && itemDate <= toDate;
    });

    // Group items by Lab Number
    var groupedItems = {};
    filteredItems.forEach(function(item) {
        if (!groupedItems[item["Lab Number"]]) {
            groupedItems[item["Lab Number"]] = [];
        }
        groupedItems[item["Lab Number"]].push(item);
    });

    // Extract unique section codes and sort them alphabetically
    var sectionSequence = [];
    for (var labNumber in groupedItems) {
        groupedItems[labNumber].forEach(function(item) {
            if (!sectionSequence.includes(item["section_code"])) {
                sectionSequence.push(item["section_code"]);
            }
        });
    }
    sectionSequence.sort();

    // Generate HTML markup for the table rows
    for (var labNumber in groupedItems) {
        if (groupedItems.hasOwnProperty(labNumber)) {
            // Add Lab Number row
            tableRows += "<tr><td colspan=\'4\'><strong>Lab Number: " + labNumber + "</strong></td></tr>";
            // Add section code, tissue, and cassette numbers rows for each Lab Number
            sectionSequence.forEach(function(code) {
                groupedItems[labNumber].forEach(function(item) {
                    if (item["section_code"] === code) {
                        tableRows += "<tr>";
                        tableRows += "<td>Section Code : " + item["section_code"] + "</td>";
                        tableRows += "<td>Tissue : " + item["tissue"] + "</td>";
                        tableRows += "<td>Cassettes Numbers : " + item["cassettes_numbers"] + "</td>";
                        tableRows += "</tr>";
                    }
                });
            });
        }
    }

    // Set the HTML content of the table body
    document.getElementById("histoGrossTableBody").innerHTML = tableRows;
}

// Add event listener to the button
document.getElementById("submitBtn").addEventListener("click", submitDateTime);
</script>');



print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

print '</div></div>';
// End of page
llxFooter();
$db->close();
