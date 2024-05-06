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

print('
<style>
.histo-card {
  display: inline-block; /* Arrange cards horizontally */
  margin: 10px;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  width: 80%; /* Adjust width as needed for 4 columns per row */
  text-align: center; /* Center content within card */
}

.histo-table {
  width: 100%; /* Ensure table fills card width */
  border-collapse: collapse; /* Remove borders between cells */
}

.histo-table th,
.histo-table td {
  padding: 5px;
  border-bottom: 1px solid #ddd; /* Add bottom border for rows */
  max-width: 100px; /* Limit the width of table cells */
  overflow: hidden; /* Hide overflowing content */
  text-overflow: ellipsis; /* Show ellipsis for overflowed content */
  white-space: nowrap; /* Prevent wrapping */
}

.histo-table th:first-child,
.histo-table td:first-child { /* Style first column (name) */
  font-weight: bold;
  text-align: left; /* Align name to the left */
}
</style>
');

print('
<br><br>
<div id="cardGroup"></div>
');

print('<script>
const histo_gross_list = ' . json_encode($histo_gross_list) . ';

// Define the submitDateTime function
function submitDateTime() {
    var fromDate = new Date(document.getElementById("fromDateTime").value);
    var toDate = new Date(document.getElementById("toDateTime").value);
    var cardGroup = document.getElementById("cardGroup");
    
    // Clear existing card elements
    cardGroup.innerHTML = "";
  
	var filteredItems = histo_gross_list.filter(function(item) {
		var itemDate = new Date(item["Gross Create Date"]);
		// Set the time of fromDate to the start of the day (midnight)
		var fromDateStart = new Date(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
		// Set the time of toDate to the end of the day (just before midnight of the next day)
		var toDateEnd = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate() + 1) - 1;
		// Check if the items date falls within the range (inclusive)
		return itemDate >= fromDateStart && itemDate <= toDateEnd;
	});

    // Group items by Lab Number
    var groupedItems = {};
    filteredItems.forEach(function(item) {
        if (!groupedItems[item["Lab Number"]]) {
            groupedItems[item["Lab Number"]] = [];
        }
        groupedItems[item["Lab Number"]].push(item);
    });

    // Extract unique section codes
    var sectionSequence = [];
    filteredItems.forEach(function(item) {
        if (!sectionSequence.includes(item["section_code"])) {
            sectionSequence.push(item["section_code"]);
        }
    });
    sectionSequence.sort();

    // Extract and sort Lab numbers
    var labNumbers = Object.keys(groupedItems);
    labNumbers.sort();

    // Generate HTML markup for the table rows
    // labNumbers.forEach(function(labNumber) {
    //     if (groupedItems.hasOwnProperty(labNumber)) {
    //         let card = document.createElement("div");
    //         card.className = "histo-card"
    //         let cardContent = "<h3>Lab Number: " + labNumber + "</h3>";
    //         sectionSequence.forEach(function(code) {
    //             groupedItems[labNumber].forEach(function(item) {
    //                 if (item["section_code"] === code) {
    //                   cardContent += "<table>";
    //                   cardContent += "<thead><tr><th>" + item["section_code"] + "</th></tr></thead>";
    //                   cardContent += "<tbody>";
    //                   cardContent += "<tr>";
    //                   cardContent += "<td>" + item["tissue"] + "</td>";
    //                   cardContent += "</tr>";
    //                   cardContent += "</tbody>";
    //                   cardContent += "</table>";
                      
    //                 }
    //             });
    //         });
    //         card.innerHTML = cardContent;
    //         cardGroup.appendChild(card);
    //     }
    // });

    labNumbers.forEach(function(labNumber) {
      if (groupedItems.hasOwnProperty(labNumber)) {
          let card = document.createElement("div");
          card.className = "histo-card";
          let cardContent = "Lab Number: " + labNumber + "";
  
          // Construct the table with horizontal header
          cardContent += "<table>";
          cardContent += "<thead><tr>";
          sectionSequence.forEach(function(code) {
              cardContent += "<th>" + code + "</th>";
          });
          cardContent += "</tr></thead>";
          cardContent += "<tbody><tr>";
  
          // Populate the table data
          sectionSequence.forEach(function(code) {
              groupedItems[labNumber].forEach(function(item) {
                  if (item["section_code"] === code) {
                      cardContent += "<td>" + item["tissue"] + "</td>";
                  }
              });
          });
  
          cardContent += "</tr></tbody>";
          cardContent += "</table>";
          card.innerHTML = cardContent;
          // Set the HTML content of the table body
          document.getElementById("cardGroup").appendChild(card);
      }
  });
  printData();
}

// Add event listener to the button
document.getElementById("submitBtn").addEventListener("click", submitDateTime);
function printData() {
  var divToPrint=document.getElementById("cardGroup");
  newWin= window.open("");
  newWin.document.write(divToPrint.outerHTML);
  newWin.print();
  newWin.close();
}
</script>');

print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

print '</div></div>';
// End of page
llxFooter();
$db->close();
?>
