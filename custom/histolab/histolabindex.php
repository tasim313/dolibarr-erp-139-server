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
</style>");

print('<div class="container">
<input type="date" id="fromDateTime" class="input-field" placeholder="From">
<input type="date" id="toDateTime" class="input-field" placeholder="To">
<button id="submitBtn" class="btn">Submit</button>
</div>');

function filter_histo_gross_list($list, $fromDate, $toDate) {
    $filteredList = [];
    foreach ($list as $item) {
        // Extract date part from Gross Create Date and compare it with selected date range
        $grossCreateDate = date('Y-m-d', strtotime($item['Gross Create Date']));
        if ($grossCreateDate >= $fromDate && $grossCreateDate <= $toDate) {
            $filteredList[] = $item;
        }
    }
    return $filteredList;
}

$histo_gross_list = get_histo_gross_specimen_list();

print("<table>");
print("<tr><th>Lab Number</th><th>Gross Create Date</th><th>Section Code</th><th>Cassettes Numbers</th><th>Tissue</th></tr>");

// Filter the $histo_gross_list based on the selected date range
if (isset($_GET['fromDate']) && isset($_GET['toDate'])) {
    $fromDate = $_GET['fromDate'];
    $toDate = $_GET['toDate'];
	echo "From: $fromDate<br>";
    echo "To: $toDate<br>";
    $histo_gross_list = filter_histo_gross_list($histo_gross_list, $fromDate, $toDate);
}

foreach ($histo_gross_list as $list) {
	print('
	<tr><td>' . $list['Lab Number'] . 
	'</td><td>' . $list['Gross Create Date'] . 
	'</td><td>' . $list['section_code'] . 
	'</td><td>' . $list['cassettes_numbers'] . '</td>
	 <td>' . $list['tissue'] . '</td>
	</tr>
	');
}
print("</table>");


print('<script>
const histo_gross_list = ' . json_encode($histo_gross_list) . ';

// Define the submitDateTime function
function submitDateTime() {
    var fromDate = new Date(document.getElementById("fromDateTime").value);
    var toDate = new Date(document.getElementById("toDateTime").value);
    console.log("histo list", histo_gross_list);
    var dateValues = histo_gross_list.map(function(item) {
        return {
            "Lab Number": item["Lab Number"],
            "section_code": item["section_code"],
            "tissue": item["tissue"],
            "cassettes_numbers": item["cassettes_numbers"]
        };
    });

    var filteredItems = [];

    // Iterate over each item
    dateValues.forEach(function(item) {
        // Extract the date value
        var dateValue = item["Gross Create Date"];
        
        // Check if dateValue is a valid string
        if (typeof dateValue === "string") {
            // Extract the date part only (ignoring the time)
            var dateParts = dateValue.split(" ")[0];
            var currentDate = new Date(dateParts);

            // Compare the date with the "From" and "To" dates
            if (currentDate >= fromDate && currentDate <= toDate) {
                // Add the item to the filtered array
                filteredItems.push(item);
            } else {
                // Display "Sorry, date not match" if date not in range
                console.log("Sorry, date not match");
            }
        }
    });

    // Output the filtered items
    console.log(filteredItems);
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
