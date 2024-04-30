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


print("<style>");
print(' .container {
    margin: 20px;
    padding: 10px;
    border: 0px solid #ccc;
}');
print('* {
    box-sizing: border-box;
  }
  
  .row {
    display: flex;
    margin-left:-5px;
    margin-right:-5px;
  }
  
  .column {
    flex: 50%;
    padding: 5px;
  }
  
  table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    border: 1px solid #ddd;
  }
  
  th, td {
    text-align: left;
    padding: 16px;
  }
  
  tr:nth-child(even) {
    background-color: #f2f2f2;
  }');
print('.table-container {
    width: 48%; /* Adjust the width of each table container */
    overflow: auto; /* Add scrolling if needed */
    margin: 0 1%; /* Add some margin between the tables */
}');
print('.table-container {
    width: 48%; /* Adjust the width of each table container */
    overflow: auto; /* Add scrolling if needed */
    margin: 0 1%; /* Add some margin between the tables */
}
.table-container table {
    width: 100%;
    border-collapse: collapse;
}
.table-container th, .table-container td {
    border: 1px solid #ddd;
    padding: 8px;
}  
  
  ');

print('#customers {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  
  #customers td, #customers th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
  #customers tr:nth-child(even){
    background-color: #f2f2f2;
  }
  
  #customers tr:hover {
    background-color: #ddd;
  }
  
  #customers th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #046aaa;
    color: white;
}');
print('button {
    background-color: rgb(118, 145, 225);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
    transition: box-shadow 0.3s ease;
}');
print('#pendingTable {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  
  #pendingTable td, #pendingTable th {
    border: 1px solid #ddd;
    padding: 8px;
  }
  
  #pendingTable tr:nth-child(even){
    background-color: #f2f2f2;
  }
  
  #pendingTable tr:hover {
    background-color: #ddd;
  }
  
  #pendingTable th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #046aaa;
    color: white;
}');
print('#searchInput {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    box-sizing: border-box;
    border: 2px solid #ccc;
    border-radius: 4px;
    background-color: #f8f8f8;
    font-size: 16px;
    outline: none;
  }
  
  #searchInput:focus {
    border-color: #007bff; 
  }');
  print('#searchInputAssign {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    box-sizing: border-box;
    border: 2px solid #ccc;
    border-radius: 4px;
    background-color: #f8f8f8;
    font-size: 16px;
    outline: none;
  }
  
  #searchInputAssign:focus {
    border-color: #007bff; 
  }');

print("</style>");



$userGroupNames = getUserGroupNames($loggedInUserId);

$hasGrossAssistants = false;
$hasConsultants = false;

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Gross assistants') {
        $hasGrossAssistants = true;
    } elseif ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    }
}


list($total_gross_current_month, $total_gross_current_year) = get_total_gross_values_for_assistant($loggedInUsername);

list($total_gross_current_month_doctor, $total_gross_current_year_doctor) = get_total_gross_values_for_doctor($loggedInUsername);

// Access control using switch statement
switch (true) {
  case $hasGrossAssistants:
      // Gross Assistant has access, continue with the page content...
      break;
  case $hasConsultants:
      // Doctor has access, continue with the page content...
      break;
  default:
      echo "<h1>Access Denied</h1>";
      echo "<p>You are not authorized to view this page.</p>";
      exit; // Terminate script execution
}



print('<div class="row">');
print('<div class="column">');
print('<h2>Gross Complete Summary</h2>');
print('<table id="customers">');
print('<tr>');
print('<th>'. date("F") .'</th>');
print('<th>' . date('Y') . '</th>');
        
print('</tr>');
print('<tr>');
if ($hasGrossAssistants) {
   print('<td>' .$total_gross_current_month. '</td>');
   print('<td>' .$total_gross_current_year. '</td>');
}    
print('</tr>');
      
print('</table>');
print('</div>');
print('</div>');

$gross_list = get_gross_list_by_assistant($loggedInUsername);

$gross_list_by_doctor = get_gross_list_by_doctor($loggedInUsername);

if ($hasGrossAssistants) {
    print '<div class="row">';
    print('<div class="column">');
    print('<h2>Gross List</h2>');
    print('<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for lab numbers...">');
    print('<table id="pendingTable">');
    print('<tr>
	<th>Lab Number</th>
	<th>Patient Code</th>
	<th>Gross Station Type</th>
	<th>Assistant Name</th>
	<th>Doctor Name</th>
	<th>Created Date</th>
	<th>Action</th>
  <th>Report</th>
  </tr>');
        
    foreach ($gross_list as $list) {
		$dateString = $list['gross_create_date'];
        $date = new DateTime($dateString);
        $formattedDate = $date->format('d F l Y');
        print('
        <tr><td>' . $list['lab_number'] . 
        '</td><td>' . $list['patient_code'] . 
        '</td><td>' . $list['gross_station_type'] . 
        '</td><td>' . $list['gross_assistant_name'] . 
		'</td><td>' . $list['gross_doctor_name'] .
		'</td><td>' . $formattedDate .
		'</td>
		
        <td><a href="gross_update.php?fk_gross_id='.$list['gross_id'].'"><button>View</button></a></td>
        <td>
        <a href="hpl_report.php?lab_number='.$list['lab_number'].'"><button class="btn btn-primary">Preview</button>
        </td>
        </tr>
        ');
    }
    print('</table>');
    print('</div>');
    print '</div>';
    

}



print("<script>

function searchTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById('searchInput');
    filter = input.value.toUpperCase();
    table = document.getElementById('pendingTable');
    tr = table.getElementsByTagName('tr');

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName('td')[0]; 
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.focus(); 
    }
});
</script>");
?>