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

$loggedInUsername = $user->login;

$isGrossManagement = false;
$doctors = get_doctor_list();
$assistants = get_gross_assistant_list();
$pending_list = get_pending_gross_list();
$receptions = get_reception_assign_doctor_pending_gross_list();
$pending_gross_total = get_pending_gross_value();
$today_completed_gross_total = get_complete_gross_value();
$managements = get_gross_management_list();
foreach ($managements as $management) {
    if ($management['username'] == $loggedInUsername) {
        print("<style>");
        print('.row {
            display: flex;
            justify-content: space-between;
        }');
        print('.table-container {
            width: 48%; /* Adjust the width of each table container */
            overflow: auto; /* Add scrolling if needed */
            margin: 0 1%; /* Add some margin between the tables */
        }');
        print('.table-container table {
            width: 100%;
            border-collapse: collapse;
        }');
        print('.table-container th, .table-container td {
            border: 1px solid #ddd;
            padding: 8px;
        }');
        print('.table-container th {
            background-color: #f2f2f2;
        }');

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

        print('#pendingTableAssign {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
          }
          
          #pendingTableAssign td, #pendingTableAssign th {
            border: 1px solid #ddd;
            padding: 8px;
          }
          
          #pendingTableAssign tr:nth-child(even){
            background-color: #f2f2f2;
          }
          
          #pendingTableAssign tr:hover {
            background-color: #ddd;
          }
          
          #pendingTableAssign th {
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
        print("<div class='container'>");
        print("<div class='row'>");
        print('<div class="table-container">');
        print('<h2>Doctors List</h2>');
        print('<table id="customers">');
        print('<tr><th>Name</th><th>Login Name</th></tr>');
        
        foreach ($doctors as $doctor) {
            print('<tr><td>' . $doctor['doctor_name'] . '</td><td>' . $doctor['doctor_username'] . '</td></tr>');
        }
        print('</table>');
        print('</div>');
        print('<div class="table-container">');
        print('<h2>Gross Assistants List</h2>');
        print('<table id="customers">');
        print('<tr><th>Name</th><th>Login Name</th></tr>');
        foreach ($assistants as $assistant) {
            print('<tr><td>' . $assistant['assistants_name'] . '</td><td>' . $assistant['username'] . '</td></tr>');
        }
        print('</table>');
        print('</div>');
        
        print('</div>');
        print("<div class='row'>");
        print('<div class="table-container">');
        print('<h2>Gross Summary</h2>');
        print('<table id="customers">');
        print('<tr><th>Pending Gross</th><th>Completed Gross</th></tr>');
        
        print('
            <tr><td>' .$pending_gross_total. '</td>
            <td>' . $today_completed_gross_total . '</td>
            </tr>');
      
        print('</table>');
        
        print('</div>');
        print("</div>");
        print("<br><br>");
        print("<div class='row'>");
        print('<div class="table-container">');
        print('<h2>Pending Gross List</h2>');
        print('<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for lab numbers...">');
        print('<table id="pendingTable">');
        print('<tr><th>Lab Number</th><th>Patient Code</th><th>Received Date</th><th>Referr</th><th>Action</th></tr>');
        foreach ($pending_list as $list) {
            print('
            <tr><td>' . $list['lab_number'] . '</td>
            <td>' . $list['patient_code'] . '</td>
            <td>' . $list['received_date'] . '</td>
            <td>' . $list['referr'] . '</td>
            <td><a href="gross_assign_user_create.php?lab_number=' . $list['lab_number'] . '"><button>View</button></a></td>
            </tr>');
        }
        print('</table>');
        
        print('</div>');

        print('<div class="table-container">');
        print('<h2>Doctor Assign From Reception Pending Gross List</h2>');
        print('<input type="text" id="searchInputAssign" onkeyup="searchTableAssign()" placeholder="Search for lab numbers...">');
        print('<table id="pendingTableAssign">');
        print('<tr><th>Lab Number</th><th>Patient Code</th><th>Received Date</th><th>Referr</th><th>Doctor</th><th>Action</th></tr>');
        foreach ($receptions as $list) {
            print('
            <tr><td>' . $list['lab_number'] . '</td>
            <td>' . $list['patient_code'] . '</td>
            <td>' . $list['received_date'] . '</td>
            <td>' . $list['referr'] . '</td>
            <td>' . $list['assign_doctor'] . '</td>
            <td><a href="gross_assign_user_create.php?lab_number=' . $list['lab_number'] . '"><button>View</button></a></td>
            </tr>');
        }
        print('</table>');
        
        print('</div>');

        print("</div>");


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
        function searchTableAssign() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('searchInputAssign');
            filter = input.value.toUpperCase();
            table = document.getElementById('pendingTableAssign');
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
        
        break;
    }
}
?>
