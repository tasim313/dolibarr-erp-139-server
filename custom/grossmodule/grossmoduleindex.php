<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       grossmodule/grossmoduleindex.php
 *	\ingroup    grossmodule
 *	\brief      Home page of grossmodule top menu
 */

include('connection.php');
include('gross_common_function.php');
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
$langs->loadLangs(array("grossmodule@grossmodule"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->grossmodule->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("GrossModuleArea"));

print load_fiche_titre($langs->trans(""), '', 'grossmodule.png@grossmodule');

print '<div class="fichecenter"><div class="fichethirdleft">';

$pending_gross = get_pending_gross_value();

$today_completed_gross = get_complete_gross_value();


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


print '<div class="fichecenter"><div class="fichethirdleft">';


print '</div></div>';

$loggedInUserId = $user->id;

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

$loggedInUsername = $user->login;

$assignments = getGrossAssignmentsByAssistantName($loggedInUsername);

$doctors = getGrossAssignmentsByDoctorName($loggedInUsername);

$total_assigned_task_pending = get_assigned_gross_value_pending($loggedInUsername);

$total_assigned_task_complete = get_assigned_gross_value_done($loggedInUsername);

$total_assigned_task_pending_by_doctor = get_assigned_gross_value_pending_by_doctor($loggedInUsername);

$total_assigned_task_complete_by_doctor = get_assigned_gross_value_done_by_doctor($loggedInUsername);

print('<div class="row">');
print('<div class="column">');
print('<h2>Total Gross Summary</h2> '); 
print ('<table id="customers">');
print('<tr>');
print('<th>Pending Gross</th>');
print('<th>Completed Gross</th>');
print('</tr>');
print('<tr>');
print('<td>' .$pending_gross. '</td>');
print('<td>'. $today_completed_gross . '</td>');
       
print('</tr>');
print('</table>');
print('</div>');
print('<div class="column">');
print('<h2>Gross Assign Summary</h2>');
print('<table id="customers">');
print('<tr>');
print('<th>Pending</th>');
print('<th>Finished</th>');
        
print('</tr>');
print('<tr>');
if ($hasGrossAssistants) {
   print('<td>' .$total_assigned_task_pending. '</td>');
   print('<td>' .$total_assigned_task_complete. '</td>');
}
if ($hasConsultants) {
  print('<td>' .$total_assigned_task_pending_by_doctor. '</td>');
  print('<td>' .$total_assigned_task_complete_by_doctor. '</td>');
}     
print('</tr>');
      
print('</table>');
print('</div>');
print('</div>');

if ($hasGrossAssistants) {
    print '<div class="row">';
    print('<div class="column">');
    print('<h2>Assigned List</h2>');
    print('<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for lab numbers...">');
    print('<table id="pendingTable">');
    print('<tr><th>Lab Number</th><th>Assistant Name</th><th>Doctor Name</th><th>Status</th><th>Action</th></tr>');
        
    foreach ($assignments as $assignment) {
        print('
        <tr><td>' . $assignment['lab_number'] . 
        '</td><td>' . $assignment['gross_assistant_name'] . 
        '</td><td>' . $assignment['gross_doctor_name'] . 
        '</td><td>' . $assignment['gross_status'] . '</td>
        <td><a href="gross_create_for_assign.php?lab_number=' . $assignment['lab_number'] . '&doctor_name=' .$assignment['gross_doctor_name']. '"><button>Start</button></a></td></tr>
        ');
    }
    print('</table>');
    print('</div>');
    print '</div>';
    
    
}

if ($hasConsultants) {
    print '<div class="row">';
    print('<div class="column">');
    print('<h2>Assigned List</h2>');
    print('<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for lab numbers...">');
    print('<table id="pendingTable">');
    print('<tr><th>Lab Number</th><th>Assistant Name</th><th>Doctor Name</th><th>Status</th><th>Action</th></tr>');
        
    foreach ($doctors as $doctor) {
        print('
        <tr><td>' . $doctor['lab_number'] . 
        '</td><td>' . $doctor['gross_assistant_name'] . 
        '</td><td>' . $doctor['gross_doctor_name'] . 
        '</td><td>' . $doctor['gross_status'] . '</td>
        <td><a href="gross_create.php?lab_number=' . $doctor['lab_number'] . '&assistant_name=' .$doctor['gross_assistant_name']. '"><button>Start</button></a></td></tr>
        ');
    }
    print('</table>');
    print('</div>');
    print '</div>';
}
print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


print('<div> </div>');

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

// End of page
llxFooter();
$db->close();
