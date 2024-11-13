<?php
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');

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

echo('
<div class="container">

 <!-- Search Container -->
    <div class="search-container row mb-4">
        <input type="search" id="searchInput" class="form-control" placeholder="Search" />
        <button type="button" class="btn btn-primary">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <!-- Date Filter -->
    <div class="search-container row mb-4">
        <form method="get" class="search-container row mb-4">
            <label for="start_date" class="form-label" style="font-size: 1em;">Start Date:</label>
            <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" required>
            <label for="end_date" class="form-label" style="font-size: 1em;">End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" required>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
    </div>
</div>

<!-- Full-screen Spinner Overlay -->
<div id="loadingOverlay" class="overlay d-none">
    <button class="btn btn-primary" type="button" disabled>
        <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
        Loading...
    </button>
</div>

<style>
    /* Overlay styling to center spinner */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999; /* Ensures overlay is above all content */
    }
</style>

<script>
    // Show spinner overlay on form submission
    function showSpinner() {
        document.getElementById("loadingOverlay").classList.remove("d-none");
    }
</script>

');

// Get start and end dates from the form submission
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Check if both dates are provided
if ($startDate && $endDate) {
    $labNumber_list = labNumber_list($startDate, $endDate);
} else {
    
    $labNumber_list = labNumber_list(); // Set empty if dates are not provided
}

$labData = [];

// Hide the spinner after loading data
echo '<script>document.getElementById("loadingOverlay").style.display = "none";</script>';

$labNumberRefs = array_map(function ($lab) {
    return $lab['ref']; // Extract lab numbers
}, $labNumber_list);

// Fetch order status data and track statuses for all lab numbers
$order_status_datas = get_order_status_data($labNumberRefs);

// Fetch track statuses for all lab numbers in one query
$lab_track_statuses = get_tracking_data($labNumberRefs);


if (empty($labNumber_list)) {
    echo "No lab numbers found.";
} else {
    foreach ($labNumber_list as $lab_number) {
        // Get patient information
        $patient_information = get_patient_information($lab_number['ref']);
        
        // Fetch associated order and tracking status data
        $lab_track_status = array_filter($lab_track_statuses, function($status) use ($lab_number) {
            return $status['labno'] === $lab_number['ref'];
        });

        $order_status_data = array_filter($order_status_datas, function($status) use ($lab_number) {
            return $status['ref'] === $lab_number['ref'];
        });


        // Now check if $patient_information is not empty
        if (!empty($patient_information)) {
            foreach ($patient_information as $patient) {
                // Format lab information with patient data and track status
                $labInfo = [
                    'lab_number' => $lab_number['ref'],
                    'name' => $patient['name'],
                    'patient_code' => $patient['patient_code'],
                    'address' => $patient['address'] ?: 'Not provided',
                    'phone' => $patient['phone'] ?: 'Not provided',
                    'fax' => $patient['fax'] ?: 'Not provided',
                    'order_status' => !empty($order_status_data) ? $order_status_data : 'No status available',
                    'track_status' => !empty($lab_track_status) ? $lab_track_status : 'No status available'
                ];

                // Add the lab information to $labData
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
                <div class="col-12 col-md-4 mb-3">
                    <label for="customersupportFilterTypeFilter" class="form-label">Customer Support</label>
                    <select id="customersupportFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="amountFilterTypeFilter" class="form-label">Amount</label>
                    <select id="amountFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="totalamountFilterTypeFilter" class="form-label">Total Amount</label>
                    <select id="totalamountFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="createdateFilterTypeFilter" class="form-label">Create Date Time</label>
                    <select id="createdateFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="deliverydateTimeFilterTypeFilter" class="form-label">Delivery Date Time</label>
                    <select id="deliverydateTimeFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="statusFilterTypeFilter" class="form-label">Status</label>
                    <select id="statusFilterTypeFilter" class="form-select">
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
                    <th>Customer Support</th>
                    <th>Amount</th>
                    <th>Total Amount</th>
                    <th>Create Date Time</th>
                    <th>Delivery Date Time</th>
                    <th>Status</th>
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

    function showSpinner() {
        document.getElementById("loadingOverlay").classList.remove("d-none");
        document.getElementById("shadowSpinner").style.display = 'block';
    }

    function hideSpinner() {
        document.getElementById("loadingOverlay").classList.add("d-none");
        document.getElementById("shadowSpinner").style.display = 'none';
    }

    // Function to populate the lab numbers in the dropdown
    // function populateFilters() {
    //     const labNumberSelect = document.getElementById('labNumberTypeFilter');
    //     const patientNameSelect = document.getElementById('patientNameFilterTypeFilter');
    //     const patientCodeSelect = document.getElementById('patientCodeFilterTypeFilter');
    //     const phoneSelect = document.getElementById('phoneFilterTypeFilter');
    //     const addressSelect = document.getElementById('addressFilterTypeFilter');
    //     const customerSupportSelect = document.getElementById('customersupportFilterTypeFilter');
    //     const amountSelect = document.getElementById('amountFilterTypeFilter');
    //     const totalAmountSelect = document.getElementById('totalamountFilterTypeFilter')
    //     const createDateSelect = document.getElementById('createdateFilterTypeFilter')
    //     const deliveryDateSelect = document.getElementById('deliverydateTimeFilterTypeFilter')
    //     const statusSelect = document.getElementById('statusFilterTypeFilter')
        
    //     // Create a set of unique values for each filter
    //     const labNumbers = new Set();
    //     const patientNames = new Set();
    //     const patientCodes = new Set();
    //     const phones = new Set();
    //     const addresses = new Set();
    //     const customerSupports = new Set();
    //     const amounts = new Set();
    //     const totalAmounts = new Set();
    //     const createDates  = new Set(); 
    //     const deliveryDates = new Set();
    //     const statuses = new Set();

    //     labData.forEach(item => {
    //         labNumbers.add(item.lab_number);
    //         patientNames.add(item.name);
    //         patientCodes.add(item.patient_code);
    //         phones.add(item.phone);
    //         addresses.add(item.address);
    //         if (item.order_status) {
    //             // Ensure that order_status is an array and has at least one element
    //             const firstOrder = Array.isArray(item.order_status) ? item.order_status[0] : (item.order_status && Object.values(item.order_status)[0]);

    //             // Check if firstOrder exists before accessing its properties
    //             if (firstOrder) {
    //                 customerSupports.add(firstOrder.UserName || "N/A");
    //                 amounts.add(firstOrder.amount_ht || "N/A");
    //                 totalAmounts.add(firstOrder.multicurrency_total_ht || "N/A");
    //                 createDates.add(firstOrder.date_creation || "N/A");
    //                 deliveryDates.add(firstOrder.date_livraison || "N/A");
    //                 statuses.add(getStatusLabel(firstOrder.status));
    //             }
    //         }
    //     });

    //     // Populate each select option with unique values
    //     labNumbers.forEach(number => {
    //         const option = document.createElement('option');
    //         option.value = number;
    //         option.textContent = number;
    //         labNumberSelect.appendChild(option);
    //     });

    //     patientNames.forEach(name => {
    //         const option = document.createElement('option');
    //         option.value = name;
    //         option.textContent = name;
    //         patientNameSelect.appendChild(option);
    //     });

    //     patientCodes.forEach(code => {
    //         const option = document.createElement('option');
    //         option.value = code;
    //         option.textContent = code;
    //         patientCodeSelect.appendChild(option);
    //     });

    //     phones.forEach(phone => {
    //         const option = document.createElement('option');
    //         option.value = phone;
    //         option.textContent = phone;
    //         phoneSelect.appendChild(option);
    //     });

    //     addresses.forEach(address => {
    //         const option = document.createElement('option');
    //         option.value = address;
    //         option.textContent = address;
    //         addressSelect.appendChild(option);
    //     });

    //     customerSupports.forEach(user => {
    //         const option = document.createElement('option');
    //         option.value = user;
    //         option.textContent = user;
    //         customerSupportSelect.appendChild(option);
    //     });

    //     amounts.forEach(amount => {
    //         const option = document.createElement('option');
    //         option.value = amount;
    //         option.textContent = amount;
    //         amountSelect.appendChild(option);
    //     });

    //     totalAmounts.forEach(total => {
    //         const option = document.createElement('option');
    //         option.value = total;
    //         option.textContent = total;
    //         totalAmountSelect.appendChild(option);
    //     });

    //     createDates.forEach(date => {
    //         const option = document.createElement('option');
    //         option.value = date;
    //         option.textContent = date;
    //         createDateSelect.appendChild(option);
    //     });

    //     deliveryDates.forEach(date => {
    //         const option = document.createElement('option');
    //         option.value = date;
    //         option.textContent = date;
    //         deliveryDateSelect.appendChild(option);
    //     });

    //     statuses.forEach(status => {
    //         const option = document.createElement('option');
    //         option.value = status;
    //         option.textContent = status;
    //         statusSelect.appendChild(option);
    //     });
    // }

    function populateFilters() {
    const labNumberSelect = document.getElementById('labNumberTypeFilter');
    const patientNameSelect = document.getElementById('patientNameFilterTypeFilter');
    const patientCodeSelect = document.getElementById('patientCodeFilterTypeFilter');
    const phoneSelect = document.getElementById('phoneFilterTypeFilter');
    const addressSelect = document.getElementById('addressFilterTypeFilter');
    const customerSupportSelect = document.getElementById('customersupportFilterTypeFilter');
    const amountSelect = document.getElementById('amountFilterTypeFilter');
    const totalAmountSelect = document.getElementById('totalamountFilterTypeFilter');
    const createDateSelect = document.getElementById('createdateFilterTypeFilter');
    const deliveryDateSelect = document.getElementById('deliverydateTimeFilterTypeFilter');
    const statusSelect = document.getElementById('statusFilterTypeFilter');
    
    // Create a set of unique values for each filter
    const labNumbers = new Set();
    const patientNames = new Set();
    const patientCodes = new Set();
    const phones = new Set();
    const addresses = new Set();
    const customerSupports = new Set();
    const amounts = new Set();
    const totalAmounts = new Set();
    const createDates  = new Set(); 
    const deliveryDates = new Set();
    const statuses = new Set();

    labData.forEach(item => {
        labNumbers.add(item.lab_number);
        patientNames.add(item.name);
        patientCodes.add(item.patient_code);
        phones.add(item.phone);
        addresses.add(item.address);
        
        if (item.order_status) {
            // Ensure that order_status is an array and has at least one element
            const firstOrder = Array.isArray(item.order_status) ? item.order_status[0] : (item.order_status && Object.values(item.order_status)[0]);

            // Check if firstOrder exists before accessing its properties
            if (firstOrder && firstOrder.status) {
                customerSupports.add(firstOrder.UserName || "N/A");
                amounts.add(firstOrder.amount_ht || "N/A");
                totalAmounts.add(firstOrder.multicurrency_total_ht || "N/A");
                createDates.add(firstOrder.date_creation || "N/A");
                deliveryDates.add(firstOrder.date_livraison || "N/A");
                statuses.add(getStatusLabel(firstOrder.status));
            }
        }
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

    customerSupports.forEach(user => {
        const option = document.createElement('option');
        option.value = user;
        option.textContent = user;
        customerSupportSelect.appendChild(option);
    });

    amounts.forEach(amount => {
        const option = document.createElement('option');
        option.value = amount;
        option.textContent = amount;
        amountSelect.appendChild(option);
    });

    totalAmounts.forEach(total => {
        const option = document.createElement('option');
        option.value = total;
        option.textContent = total;
        totalAmountSelect.appendChild(option);
    });

    createDates.forEach(date => {
        const option = document.createElement('option');
        option.value = date;
        option.textContent = date;
        createDateSelect.appendChild(option);
    });

    deliveryDates.forEach(date => {
        const option = document.createElement('option');
        option.value = date;
        option.textContent = date;
        deliveryDateSelect.appendChild(option);
    });

    statuses.forEach(status => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = status;
        statusSelect.appendChild(option);
    });
}


    function formatTrackCreateTime(trackCreateTime) {
			// Check if TrackCreateTime is provided
			if (!trackCreateTime) {
				return "Not Provided Date";
			}

			// Create a Date object from TrackCreateTime
			let date = new Date(trackCreateTime);

			// Check if the date is invalid
			if (isNaN(date)) {
				return "Not Provided Date";
			}

			// Format options for date and time
			let options = {
				year: "numeric",
				month: "long",
				day: "numeric",
				hour: "numeric",
				minute: "numeric",
				hour12: true,
				timeZone: "Asia/Dhaka"
			};

			// Format date to "4 November 2024 4:30 PM" in the Asia/Dhaka timezone
			return date.toLocaleString("en-GB", options);
	}

    // Function to map status values to labels
    function getStatusLabel(status) {
        switch (status) {
            case '1':
                return 'Validated';
            case '0':
                return 'Draft';
            case '-1':
                return 'Cancel';
            case '3':
                return 'Delivered';
            default:
                return 'Unknown';
        }
    }

    // Function to display the lab data in the table
    function displayLabData(data) {
            const tableBody = document.getElementById('labDataTable').getElementsByTagName('tbody')[0];
            tableBody.innerHTML = ''; // Clear previous data

            data.forEach(item => {
                const row = tableBody.insertRow();
                
                // Initialize default values for each property
                let userName = 'N/A';
                let amountHT = 'N/A';
                let dateCreation = 'N/A';
                let dateLivraison = 'N/A';
                let multicurrencyTotalHT = 'N/A';
                let status = 'N/A';

                // Check if item.order_status exists and is an object or array
                if (item.order_status) {
                    if (Array.isArray(item.order_status)) {
                        // If it's an array, get values from the first element if it exists
                        if (item.order_status.length > 0) {
                            const firstOrder = item.order_status[0];
                            userName = firstOrder.UserName || userName;
                            amountHT = firstOrder.amount_ht || amountHT;
                            dateCreation = firstOrder.date_creation || dateCreation;
                            dateLivraison = firstOrder.date_livraison || dateLivraison;
                            multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                            status = getStatusLabel(firstOrder.status);
                        }
                    } else if (typeof item.order_status === 'object') {
                        // If it's an object, retrieve the first key dynamically
                        const statusKey = Object.keys(item.order_status)[0];
                        if (statusKey) {
                            const firstOrder = item.order_status[statusKey];
                            userName = firstOrder.UserName || userName;
                            amountHT = firstOrder.amount_ht || amountHT;
                            dateCreation = firstOrder.date_creation || dateCreation;
                            dateLivraison = firstOrder.date_livraison || dateLivraison;
                            multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                            status = getStatusLabel(firstOrder.status);
                        }
                    }
                }

                // Insert extracted data into table rows
                row.innerHTML = `
                    <td>${item.lab_number}</td>
                    <td>${item.name}</td>
                    <td>${item.patient_code}</td>
                    <td>${item.phone}</td>
                    <td>${item.address}</td>
                    <td>${userName}</td>
                    <td>${amountHT}</td>
                    <td>${multicurrencyTotalHT}</td>
                    <td>${formatTrackCreateTime(dateCreation)}</td>
                    <td>${formatTrackCreateTime(dateLivraison)}</td>
                    <td>${status}</td>
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

    // Filtering logic for the dropdowns (to be used for each filter change event)
    const filterElements = [
        'labNumberTypeFilter', 'patientNameFilterTypeFilter', 'patientCodeFilterTypeFilter', 
        'phoneFilterTypeFilter', 'addressFilterTypeFilter', 'customersupportFilterTypeFilter', 
        'amountFilterTypeFilter', 'totalamountFilterTypeFilter', 'createdateFilterTypeFilter', 
        'deliverydateTimeFilterTypeFilter', 'statusFilterTypeFilter'
    ];

    // filterElements.forEach(elementId => {
    //     document.getElementById(elementId).addEventListener('change', function() {
    //         const selectedLabNumber = document.getElementById('labNumberTypeFilter').value.toLowerCase();
    //         const selectedPatientName = document.getElementById('patientNameFilterTypeFilter').value.toLowerCase();
    //         const selectedPatientCode = document.getElementById('patientCodeFilterTypeFilter').value.toLowerCase();
    //         const selectedPhone = document.getElementById('phoneFilterTypeFilter').value.toLowerCase();
    //         const selectedAddress = document.getElementById('addressFilterTypeFilter').value.toLowerCase();
    //         const selectedCustomerSupport = document.getElementById('customersupportFilterTypeFilter').value.toLowerCase();
    //         const selectedAmount = document.getElementById('amountFilterTypeFilter').value.toLowerCase();
    //         const selectedTotalAmount = document.getElementById('totalamountFilterTypeFilter').value.toLowerCase();
    //         const selectedCreateDate = document.getElementById('createdateFilterTypeFilter').value.toLowerCase();
    //         const selectedDeliveryDate = document.getElementById('deliverydateTimeFilterTypeFilter').value.toLowerCase();
    //         const selectedStatus = document.getElementById('statusFilterTypeFilter').value.toLowerCase();

    //         const filteredData = labData.filter(item => {
    //             // Ensure order_status exists before accessing its properties
    //             const hasOrderStatus = item.order_status && Array.isArray(item.order_status) && item.order_status.length > 0;
    //             const orderStatus = hasOrderStatus ? item.order_status[0] : {};
    //             // Filter condition: match dropdown selections
    //             return (selectedLabNumber === '' || item.lab_number.toLowerCase().includes(selectedLabNumber)) &&
    //                     (selectedPatientName === '' || item.name.toLowerCase().includes(selectedPatientName)) &&
    //                     (selectedPatientCode === '' || item.patient_code.toLowerCase().includes(selectedPatientCode)) &&
    //                     (selectedPhone === '' || item.phone.toLowerCase().includes(selectedPhone)) &&
    //                     (selectedAddress === '' || item.address.toLowerCase().includes(selectedAddress)) &&
    //                     (selectedCustomerSupport === '' || (hasOrderStatus && orderStatus.UserName && orderStatus.UserName.toLowerCase().includes(selectedCustomerSupport))) &&
    //                     (selectedAmount === '' || (hasOrderStatus && orderStatus.amount_ht && orderStatus.amount_ht.toString().toLowerCase().includes(selectedAmount))) &&
    //                     (selectedTotalAmount === '' || (hasOrderStatus && orderStatus.multicurrency_total_ht && orderStatus.multicurrency_total_ht.toString().toLowerCase().includes(selectedTotalAmount))) &&
    //                     (selectedCreateDate === '' || (hasOrderStatus && orderStatus.date_creation && orderStatus.date_creation.toLowerCase().includes(selectedCreateDate))) &&
    //                     (selectedDeliveryDate === '' || (hasOrderStatus && orderStatus.date_livraison && orderStatus.date_livraison.toLowerCase().includes(selectedDeliveryDate))) &&
    //                     (selectedStatus === '' || (hasOrderStatus && getStatusLabel(orderStatus.status) && getStatusLabel(orderStatus.status).toLowerCase().includes(selectedStatus)));
    //             });

    //         displayLabData(filteredData); // Update table with filtered data
    //     });
    // });
    filterElements.forEach(elementId => {
    document.getElementById(elementId).addEventListener('change', function() {
        // Retrieve filter values
        const selectedLabNumber = document.getElementById('labNumberTypeFilter').value.toLowerCase().trim();
        const selectedPatientName = document.getElementById('patientNameFilterTypeFilter').value.toLowerCase().trim();
        const selectedPatientCode = document.getElementById('patientCodeFilterTypeFilter').value.toLowerCase().trim();
        const selectedPhone = document.getElementById('phoneFilterTypeFilter').value.toLowerCase().trim();
        const selectedAddress = document.getElementById('addressFilterTypeFilter').value.toLowerCase().trim();
        const selectedCustomerSupport = document.getElementById('customersupportFilterTypeFilter').value.toLowerCase().trim();
        const selectedAmount = document.getElementById('amountFilterTypeFilter').value.toLowerCase().trim();
        const selectedTotalAmount = document.getElementById('totalamountFilterTypeFilter').value.toLowerCase().trim();
        const selectedCreateDate = document.getElementById('createdateFilterTypeFilter').value.toLowerCase().trim();
        const selectedDeliveryDate = document.getElementById('deliverydateTimeFilterTypeFilter').value.toLowerCase().trim();
        const selectedStatus = document.getElementById('statusFilterTypeFilter').value.toLowerCase().trim();

        // Log the selected filter values for debugging
        console.log("Selected Filters:");
        console.log("Lab Number:", selectedLabNumber);
        console.log("Patient Name:", selectedPatientName);
        console.log("Patient Code:", selectedPatientCode);
        console.log("Phone:", selectedPhone);
        console.log("Address:", selectedAddress);
        console.log("Customer Support:", selectedCustomerSupport);
        console.log("Amount:", selectedAmount);
        console.log("Total Amount:", selectedTotalAmount);
        console.log("Create Date:", selectedCreateDate);
        console.log("Delivery Date:", selectedDeliveryDate);
        console.log("Status:", selectedStatus);

        const filteredData = labData.filter(item => {
            // Loop through the order_status array (if it exists) to check filter conditions
            const hasOrderStatus = item.order_status && Array.isArray(item.order_status) && item.order_status.length > 0;
            let isMatching = false;

            if (hasOrderStatus) {
                // Iterate over each order status object in the array
                for (let i = 0; i < item.order_status.length; i++) {
                    const order = item.order_status[i];

                    // Ensure order has required properties before checking
                    if (order && order.status) {
                        const orderCustomerSupport = order.UserName || "N/A";
                        const orderAmount = order.amount_ht || "N/A";
                        const orderTotalAmount = order.multicurrency_total_ht || "N/A";
                        const orderCreateDate = order.date_creation || "N/A";
                        const orderDeliveryDate = order.date_livraison || "N/A";
                        const orderStatus = getStatusLabel(order.status) || "N/A";

                        // Check against selected filter values
                        const isCustomerSupportMatch = selectedCustomerSupport === '' || orderCustomerSupport.toLowerCase().includes(selectedCustomerSupport.toLowerCase());
                        const isAmountMatch = selectedAmount === '' || orderAmount.toString().toLowerCase().includes(selectedAmount.toLowerCase());
                        const isTotalAmountMatch = selectedTotalAmount === '' || orderTotalAmount.toString().replace('.', '').toLowerCase().includes(selectedTotalAmount.replace('.', '').toLowerCase());
                        const isCreateDateMatch = selectedCreateDate === '' || orderCreateDate.toLowerCase().includes(selectedCreateDate.toLowerCase());
                        const isDeliveryDateMatch = selectedDeliveryDate === '' || orderDeliveryDate.toLowerCase().includes(selectedDeliveryDate.toLowerCase());
                        const isStatusMatch = selectedStatus === '' || orderStatus.toLowerCase().includes(selectedStatus.toLowerCase());

                        // If all filter conditions are met for this order status, set isMatching to true
                        if (isCustomerSupportMatch && isAmountMatch && isTotalAmountMatch && isCreateDateMatch && isDeliveryDateMatch && isStatusMatch) {
                            isMatching = true;  // Item matches at least one valid order status
                            break; // No need to check further orders, since one match is enough
                        }
                    }
                }
            }

            // Filter condition for other attributes (lab number, patient name, etc.)
            const isOtherAttributesMatch =
                (selectedLabNumber === '' || item.lab_number.toLowerCase().includes(selectedLabNumber)) &&
                (selectedPatientName === '' || item.name.toLowerCase().includes(selectedPatientName)) &&
                (selectedPatientCode === '' || item.patient_code.toLowerCase().includes(selectedPatientCode)) &&
                (selectedPhone === '' || item.phone.toLowerCase().includes(selectedPhone)) &&
                (selectedAddress === '' || item.address.toLowerCase().includes(selectedAddress));

            // Return the item if both conditions (order_status match and other attributes match) are true
            return isOtherAttributesMatch && isMatching;
        });

        console.log("Filtered Data:", filteredData);

        displayLabData(filteredData); // Update table with filtered data
    });
});


</script>


</body>
</html>