<?php
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
include('../transcription/common_function.php');

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
$langs->loadLangs(array("histolabflow@histolabflow"));

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

llxHeader("", $langs->trans("A focus on HistoLab collaboration, task management, and data tracking."));

$loggedInUsername = $user->login;
$loggedInUserId = $user->id;



$isAdmin = isUserAdmin($loggedInUserId);


// Access control using switch statement
switch (true) {
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
    
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}


$labNumber_list = labNumber_list();
$labData = [];


if (empty($labNumber_list)) {
    echo "No lab numbers found.";
} else {
    foreach($labNumber_list as $lab_number) {
        // Get patient information
        $patient_information = get_patient_information($lab_number['ref']);
        
        // Get the lab track status for the current lab number
        $lab_track_status = get_labNumber_track_status_by_lab_number($lab_number['ref']);
        
        // Now check if $patient_information is not empty
        if (!empty($patient_information)) {
            // Loop through the patient information array (as get_patient_information() returns an array of patients)
            foreach ($patient_information as $patient) {
                // Initialize the data array with patient information
                $labInfo = [
                    'lab_number' => $lab_number['ref'],
                    'name' => $patient['name'],
                    'patient_code' => $patient['patient_code'],
                    'address' => $patient['address'] ?: 'Not provided',
                    'phone' => $patient['phone'] ?: 'Not provided',
                    'fax' => $patient['fax'] ?: 'Not provided'
                ];
                
                // If there is track status data for this lab number, add it to the $labInfo array
                if (!empty($lab_track_status)) {
                    $labInfo['TrackCreateTime'] = $lab_track_status[0]['TrackCreateTime'] ?: 'Not provided';
                    $labInfo['TrackUserName'] = $lab_track_status[0]['TrackUserName'] ?: 'Not provided';
                    $labInfo['WSStatusCreateTime'] = $lab_track_status[0]['WSStatusCreateTime'] ?: 'Not provided';
                    $labInfo['WSStatusName'] = $lab_track_status[0]['WSStatusName'] ?: 'Not provided';
                    $labInfo['section'] = $lab_track_status[0]['section'] ?: 'Not provided';
                    $labInfo['Status'] = $lab_track_status[0]['status'] ?: 'Not provided';
					$labInfo['date_creation'] = $lab_track_status[0]['date_creation'] ?: 'Not provided';
					$labInfo['date_commande'] = $lab_track_status[0]['date_commande'] ?: 'Not provided';
					$labInfo['UserName'] = $lab_track_status[0]['UserName'] ?: 'Not provided';
					$labInfo['amount_ht'] = $lab_track_status[0]['amount_ht'] ?: 'Not provided';
					$labInfo['date_creation'] = $lab_track_status[0]['date_creation'] ?: 'Not provided';
					$labInfo['date_livraison'] = $lab_track_status[0]['date_livraison'] ?: 'Not provided';
					$labInfo['multicurrency_total_ht'] = $lab_track_status[0]['multicurrency_total_ht'] ?: 'Not provided';
					
                }
                
                // Add the merged data into $labData array
                $labData[] = $labInfo;
            }
        } else {
            echo "No patient information found for lab number " . $lab_number['ref'] . "<br>";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Import the JavaScript file -->
	<link href="../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pagination { display: inline-block; }
        .pagination a { margin: 0 5px; cursor: pointer; }
        .pagination a.active { font-weight: bold; }
        .table th, .table td { text-align: center; }
		.search-container {
			display: flex;
			align-items: center; /* Align items vertically in the center */
		}
		.search-container .form-control {
			flex-grow: 1; /* Make the input field take up the remaining space */
			margin-right: 10px; /* Space between input and button */
		}
		.search-container .btn {
			padding: 0.5rem 1rem; /* Adjust button size */
		}
    </style>
</head>
<body>

<style>
	
</style>

<div class="container">
    <!-- Search Container -->
    <div class="search-container row mb-4">
        <input type="search" id="searchInput" class="form-control" placeholder="Search" />
        <button type="button" class="btn btn-primary">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <!-- Filter Options -->
	<div id="filterOptions" class="col-md-12">
            <h3>Filter Options</h3>
            <form id="filterForm" class="row g-1">
                <div class="col-12 col-md-4 mb-3">
                    <label for="labNumberTypeFilter" class="form-label">Lab Number</label>
                    <select id="labNumberTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="patientNameFilterTypeFilter" class="form-label">Patient Name</label>
                    <select id="patientNameFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="patientCodeFilterTypeFilter" class="form-label">Patient Code</label>
                    <select id="patientCodeFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="phoneFilterTypeFilter" class="form-label">Phone</label>
                    <select id="phoneFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="addressFilterTypeFilter" class="form-label">Address</label>
                    <select id="addressFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Lab Data Table -->
    <div style="margin-top: 20px;">
		<table id="labDataTable" class="table table-bordered">
			<thead>
				<tr>
					<th>Lab Number</th>
					<th>Patient Name</th>
					<th>Patient Code</th>
					<th>Phone</th>
					<th>Address</th>
				</tr>
			</thead>
			<tbody>
				<!-- Data will be dynamically populated here -->
			</tbody>
		</table>
	</div>
    
</div>

<!-- Include this in your JavaScript section -->
<script>
    // PHP passes the labData as a JSON-encoded object to JavaScript
    const labData = <?php echo json_encode($labData); ?>;
	console.log('lab data : ', labData);

    // Function to populate the lab numbers in the dropdown
    function populateFilters() {
        const labNumberSelect = document.getElementById('labNumberTypeFilter');
        const patientNameSelect = document.getElementById('patientNameFilterTypeFilter');
        const patientCodeSelect = document.getElementById('patientCodeFilterTypeFilter');
        const phoneSelect = document.getElementById('phoneFilterTypeFilter');
        const addressSelect = document.getElementById('addressFilterTypeFilter');
        
        // Create a set of unique values for each filter
        const labNumbers = new Set();
        const patientNames = new Set();
        const patientCodes = new Set();
        const phones = new Set();
        const addresses = new Set();

        labData.forEach(item => {
            labNumbers.add(item.lab_number);
            patientNames.add(item.name);
            patientCodes.add(item.patient_code);
            phones.add(item.phone);
            addresses.add(item.address);
        });

        // Populate each select option with unique values
        labNumbers.forEach(number => {
            const option = document.createElement('option');
            option.value = number;
            option.textContent = number;
            labNumberSelect.appendChild(option);
        });

        patientNames.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            patientNameSelect.appendChild(option);
        });

        patientCodes.forEach(code => {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = code;
            patientCodeSelect.appendChild(option);
        });

        phones.forEach(phone => {
            const option = document.createElement('option');
            option.value = phone;
            option.textContent = phone;
            phoneSelect.appendChild(option);
        });

        addresses.forEach(address => {
            const option = document.createElement('option');
            option.value = address;
            option.textContent = address;
            addressSelect.appendChild(option);
        });
    }

    // Function to display the lab data in the table
    function displayLabData(data) {
        const tableBody = document.getElementById('labDataTable').getElementsByTagName('tbody')[0];
        tableBody.innerHTML = ''; // Clear previous data

        data.forEach(item => {
            const row = tableBody.insertRow();
            row.innerHTML = `
                <td>${item.lab_number}</td>
                <td>${item.name}</td>
                <td>${item.patient_code}</td>
                <td>${item.phone}</td>
                <td>${item.address}</td>
            `;
        });
    }

    // Initial display of all lab data and populate filters
    displayLabData(labData);
    populateFilters();

    // Filtering logic based on search input and selected filter
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const filteredData = labData.filter(item => {
            return item.lab_number.toLowerCase().includes(searchValue) ||
                   item.name.toLowerCase().includes(searchValue) ||
                   item.patient_code.toLowerCase().includes(searchValue) ||
                   item.phone.toLowerCase().includes(searchValue) ||
                   item.address.toLowerCase().includes(searchValue);
        });
        displayLabData(filteredData); // Update table with filtered data
    });

    // Filtering logic for the dropdowns
    const filterElements = ['labNumberTypeFilter', 'patientNameFilterTypeFilter', 'patientCodeFilterTypeFilter', 'phoneFilterTypeFilter', 'addressFilterTypeFilter'];
    filterElements.forEach(elementId => {
        document.getElementById(elementId).addEventListener('change', function() {
            const selectedLabNumber = document.getElementById('labNumberTypeFilter').value.toLowerCase();
            const selectedPatientName = document.getElementById('patientNameFilterTypeFilter').value.toLowerCase();
            const selectedPatientCode = document.getElementById('patientCodeFilterTypeFilter').value.toLowerCase();
            const selectedPhone = document.getElementById('phoneFilterTypeFilter').value.toLowerCase();
            const selectedAddress = document.getElementById('addressFilterTypeFilter').value.toLowerCase();

            const filteredData = labData.filter(item => {
                return (selectedLabNumber === '' || item.lab_number.toLowerCase().includes(selectedLabNumber)) &&
                       (selectedPatientName === '' || item.name.toLowerCase().includes(selectedPatientName)) &&
                       (selectedPatientCode === '' || item.patient_code.toLowerCase().includes(selectedPatientCode)) &&
                       (selectedPhone === '' || item.phone.toLowerCase().includes(selectedPhone)) &&
                       (selectedAddress === '' || item.address.toLowerCase().includes(selectedAddress));
            });

            displayLabData(filteredData); // Update table with filtered data
        });
    });
</script>


</body>
</html>