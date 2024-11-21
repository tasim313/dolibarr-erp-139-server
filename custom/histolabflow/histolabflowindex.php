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

// Fetch gross related info for all lab numbers in one query
$gross_info_status = getGrossDetailsByLabNumbers($labNumberRefs);


if (empty($labNumber_list)) {
    echo "No lab numbers found.";
} else {
    foreach ($labNumber_list as $lab_number) {
        // Fetch associated order and tracking status data
        $lab_track_status = array_filter($lab_track_statuses, function($status) use ($lab_number) {
            return $status['labno'] === $lab_number['ref'];
        });

        $order_status_data = array_filter($order_status_datas, function($status) use ($lab_number) {
            return $status['ref'] === $lab_number['ref'];
        });

        $gross_status_data = array_filter($gross_info_status, function($status) use ($lab_number) {
            // Ensure both the lab_number and gross_lab_number have the same format, including 'HPL' prefix if needed
            $formattedLabNumber = (strpos($lab_number['ref'], 'HPL') === 0) ? $lab_number['ref'] : 'HPL' . $lab_number['ref'];

            // Remove any potential whitespace issues by trimming the values
            return trim($status['gross_lab_number']) === trim($formattedLabNumber);
        });

        // Now add the lab information to $labData without patient information
        $labInfo = [
            'lab_number' => $lab_number['ref'],
            'order_status' => !empty($order_status_data) ? $order_status_data : 'No status available',
            'track_status' => !empty($lab_track_status) ? $lab_track_status : 'No status available',
            'gross_status' => !empty($gross_status_data) ? $gross_status_data : 'No gross status available'
        ];

        // Add the lab information to $labData
        $labData[] = $labInfo;
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
                    <label for="patientAgeFilterTypeFilter" class="form-label">Patient Age</label>
                    <select id="patientAgeFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="patientGenderFilterTypeFilter" class="form-label">Patient Gender</label>
                    <select id="patientGenderFilterTypeFilter" class="form-select">
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
                    <label for="attendentNameFilterTypeFilter" class="form-label">Attendent Name</label>
                    <select id="attendentNameFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="attendentRelationFilterTypeFilter" class="form-label">Attendent Relation</label>
                    <select id="attendentRelationFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="attendentNumberFilterTypeFilter" class="form-label">Attendent Number</label>
                    <select id="attendentNumberFilterTypeFilter" class="form-select">
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
                <div class="col-12 col-md-4 mb-3">
                    <label for="sectionFilterTypeFilter" class="form-label">Section</label>
                    <select id="sectionFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="worksheetStatusNameFilterTypeFilter" class="form-label">Worksheet Status Name</label>
                    <select id="worksheetStatusNameFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="worksheetCreateTimeFilterTypeFilter" class="form-label">Worksheet Create Time</label>
                    <select id="worksheetCreateTimeFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label for="worksheetTrackUserNameFilterTypeFilter" class="form-label">Worksheet Track User Name</label>
                    <select id="worksheetTrackUserNameFilterTypeFilter" class="form-select">
                        <option value="">All</option> <!-- Default option -->
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Lab Data Table -->
    <div style="margin-top: 20px;">
		<table id="labDataTable" class="table, table-bordered, table-striped, table-hover">
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
                    <th>Gross Station</th>
                    <th>Gross Doctor</th>
                    <th>Gross Assistant Name</th>
                    <th>Gross Create User</th>
                    <th>Gross Created Date</th>
                    <th>Section</th>
                    <th>WorkSheet Status Name</th>
                    <th>Track Date & Time</th>
                    <th>Track User</th>
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
        const sectionSelect = document.getElementById('sectionFilterTypeFilter');
        const worksheetStatusNameSelect = document.getElementById('worksheetStatusNameFilterTypeFilter');
        const worksheetCreateTimeSelect = document.getElementById('worksheetCreateTimeFilterTypeFilter');
        const worksheetTrackUserNameSelect = document.getElementById('worksheetTrackUserNameFilterTypeFilter');
        const patientAgeSelect = document.getElementById('patientAgeFilterTypeFilter');
        const patientGenderSelect = document.getElementById('patientGenderFilterTypeFilter');
        const attendantNameSelect = document.getElementById('attendentNameFilterTypeFilter');
        const attendantRelationSelect = document.getElementById('attendentRelationFilterTypeFilter');
        const attendantNumberSelect = document.getElementById('attendentNumberFilterTypeFilter');
        
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
        const sections = new Set();
        const worksheetStatusName = new Set();
        const worksheetCreateTime = new Set();
        const worksheetTrackUserName = new Set();
        const patientAge = new Set();
        const patientGender = new Set();
        const attendantName = new Set();
        const attendant_relation = new Set();
        const attendantNumber = new Set();

        labData.forEach(item => {
            labNumbers.add(item.lab_number);
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
                    patientNames.add(firstOrder.nom || "N/A");
                    patientCodes.add(firstOrder.code_client || "N/A");
                    phones.add(firstOrder.phone || "N/A");
                    addresses.add(firstOrder.address || "N/A");
                    patientAge.add(firstOrder.age || "N/A");
                    patientGender.add(getGender(firstOrder.sex) || "N/A");
                    attendantName.add(firstOrder.attendant_name || "N/A");
                    attendant_relation.add(firstOrder.attendant_relation || "N/A");
                    attendantNumber.add(firstOrder.fax || "N/A");
                }
            }
            if (item.track_status && typeof item.track_status === 'object') {
                Object.keys(item.track_status).forEach((key, index) => {
                    const statusItem = item.track_status[key];
                    // Add dynamic track_status filter values to sets
                    sections.add(statusItem.section || "N/A");
                    worksheetStatusName.add(statusItem.WSStatusName || "N/A");
                    worksheetCreateTime.add(statusItem.WSStatusCreateTime || "N/A");
                    worksheetTrackUserName.add(statusItem.TrackUserName || "N/A");
                });
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

        patientAge.forEach(age =>{
            const option = document.createElement('option');
            option.value = age;
            option.textContent = age;
            patientAgeSelect.appendChild(option);
        });
        patientGender.forEach(sex =>{
            const option = document.createElement('option');
            option.value = sex;
            option.textContent = sex;
            patientGenderSelect.appendChild(option);
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

        attendantName.forEach(attendant_name =>{
            const option = document.createElement('option');
            option.value = attendant_name;
            option.textContent = attendant_name;
            attendantNameSelect.appendChild(option);
        })

        attendant_relation.forEach(attendant_relation =>{
            const option = document.createElement('option');
            option.value = attendant_relation;
            option.textContent = attendant_relation;
            attendantRelationSelect.appendChild(option);
        })

        attendantNumber.forEach(fax =>{
            const option = document.createElement('option');
            option.value = fax;
            option.textContent = fax;
            attendantNumberSelect.appendChild(option);
        })

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
            const formattedDate = formatTrackCreateTime(date);
            const option = document.createElement('option');
            option.value = date;  
            option.textContent = formattedDate;  // Use the formatted date for display
            createDateSelect.appendChild(option);
        });

        deliveryDates.forEach(date => {
            const formattedDate = formatTrackCreateTime(date);
            const option = document.createElement('option');
            option.value = date;
            option.textContent = formattedDate;  // Use the formatted date for display
            deliveryDateSelect.appendChild(option);
        });

        statuses.forEach(status => {
            const option = document.createElement('option');
            option.value = status;
            option.textContent = status;
            statusSelect.appendChild(option);
        });

        // Populate dynamic track_status filters
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section;
            option.textContent = section;
            // Assuming you have a section select in your HTML
            sectionSelect.appendChild(option);
        });

        worksheetStatusName.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            // Assuming you have a WS Status Name select in your HTML
            worksheetStatusNameSelect.appendChild(option);
        });

        worksheetCreateTime.forEach(createTime => {
            const formattedDate = formatTrackCreateTime(createTime);
            const option = document.createElement('option');
            option.value = createTime;
            option.textContent = formattedDate;
            // Assuming you have a WS Status Create Time select in your HTML
            worksheetCreateTimeSelect.appendChild(option);
        });

        worksheetTrackUserName.forEach(userName => {
            const option = document.createElement('option');
            option.value = userName;
            option.textContent = userName;
            // Assuming you have a Track User Name select in your HTML
            worksheetTrackUserNameSelect.appendChild(option);
        });
    }

    function formatTrackCreateTime(trackCreateTime) {
        // Check if TrackCreateTime is provided
        if (!trackCreateTime) {
            return "Not Provided Date";
        }

        // Convert the timestamp string into ISO format
        // Assuming 'trackCreateTime' is in the format 'Y-m-d H:i:s.u'
        let isoFormattedTime = trackCreateTime.replace(' ', 'T'); // Convert to 'YYYY-MM-DDTHH:mm:ss'
        isoFormattedTime = isoFormattedTime + 'Z'; // Add 'Z' for UTC time (Optional: Adjust if needed)

        // Create a Date object from the ISO formatted time
        let date = new Date(isoFormattedTime);

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
            second: "numeric",
            hour12: true,
            timeZone: "Asia/Dhaka"  // Specify the Asia/Dhaka timezone
        };

        // Create the formatter
        let formatter = new Intl.DateTimeFormat("en-GB", options);

        // Format the date and return
        return formatter.format(date);
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

    function getBatchLabel(batchNumber) {
        const batchNames = [
            "First Batch", 
            "Second Batch", 
            "Third Batch", 
            "Fourth Batch", 
            "Fifth Batch",
            "Sixth Batch",
            "Seventh Batch",
            "Eighth Batch"
        ]; // Extend this array if more batches are needed
    
        return batchNames[batchNumber - 1] || `Batch ${batchNumber}`;
    }

    function getGender(sex) {
        let gender;

        // Ensure sex is a number
        sex = Number(sex);  // Convert to number to avoid any type mismatch

        if (sex === 1) {
            gender = 'Male';
        } else if (sex === 2) {
            gender = 'Female';
        } else {
            gender = 'Other';
        }

        return gender;
    }

    // Display the data after applying the filter
    function displayLabData(filteredData) {
            const tbody = document.getElementById('labDataTable').getElementsByTagName('tbody')[0];
            const thead = document.getElementById('labDataTable').getElementsByTagName('thead')[0];
    
            tbody.innerHTML = ''; // Clear existing data
    
            // First, clear existing headers in the <thead>
            const headerRow = thead.getElementsByTagName('tr')[0];
            headerRow.innerHTML = ''; // Clear existing headers
    
            // Add the static headers first (you may already have these columns in your table)
            headerRow.innerHTML = `
                <th>Lab Number</th>
                <th>Patient Name</th>
                <th>Patient Age</th>
                <th>Patient Date Of Birth</th>
                <th>Patient Gender</th>
                <th>Patient Code</th>
                <th>Patient Phone</th>
                <th>Attendant Name</th>
                <th>Attendant Relation</th>
                <th>Attendant Number</th>
                <th>Patient Address</th>
                <th>Customer Support</th>
                <th>Test Price</th>
                <th>Total Test Price</th>
                <th>Test Type</th>
                <th>Sample Received Date</th>
                <th>Invoice</th>
                <th>Invoice Total Amount</th>
                <th>Paid Amount</th>
                <th>Due Amount</th>
                <th>Payment Term Code</th>
                <th>Payment Mode Code</th>
                <th>Bank Name</th>
                <th>Bank Bic</th>
                <th>Bank Iban</th>
                <th>Discount Percentage</th>
                <th>Discount Value</th>
                <th>Specimen Name</th>
                <th>Report Delivery Date</th>
                <th>Status</th>
                <th>Gross Station</th>
                <th>Gross Doctor</th>
                <th>Gross Assistant Name</th>
                <th>Gross Create User</th>
                <th>Gross Created Date</th>
                <th>Batch</th>
                <th>Transcriptionist</th>
                <th>Transcription Date</th>
            `;
    
            // Loop through filteredData
            filteredData.forEach(item => {
                const row = document.createElement('tr');
                
                // Default values
                let userName = 'N/A';
                let amountHT = 'N/A';
                let dateCreation = 'Not Provided Date';
                let dateLivraison = 'Not Provided Date';
                let multicurrencyTotalHT = 'N/A';
                let status = 'Unknown';
                let section = 'Not Provided';
                let WSStatusName = 'Not Provided';
                let TrackUserName = 'Not Provided';
                let grossStationType = 'Not Provided';
                let grossDoctor = 'Not Provided';
                let grossAssistant = 'Not Provided';
                let grossCreateUser = 'Not Provided';
                let grossCreatedDate = 'Not Provided';
                let batch = 'Not Provided';
                let microCreateUser = 'Not Provided';
                let microcreateDate = 'Not Provided'; 
                let testType = 'Not Provided';
                let invoice = 'Not Provided';
                let invoice_total_amount = 'Not Provided';
                let invoice_already_paid = 'Not Provided';
                let invoice_remaining_amount_due = 'Not Provided';
                let payment_mode_code = 'Not Provided';
                let payment_term_code = 'Not Provided';
                let bank_name = 'Not Provided';
                let bank_bic = 'Not Provided';
                let bank_iban = 'Not Provided';
                let discount_percentage = 'Not Provided';
                let discount_value = 'Not Provided';
                let specimen_name = 'Not Provided';
                let patient_name = 'Not Provided';
                let patient_code = 'Not Provided';
                let patient_phone = 'Not Provided';
                let attendant_number = 'Not Provided';
                let attendant_name = 'Not Provided';
                let attendant_relation = 'Not Provided';
                let address = 'Not Provided';
                let patient_age = 'Not Provided';
                let patient_sex = 'Not Provided';
                let patient_date_of_birth = 'Not Provided';


                if (item.gross_status){
                    if (Array.isArray(item.gross_status)){
                        if(item.gross_status.length > 0){
                            const firstGross = item.gross_status[0];
                            grossStationType = firstGross.gross_station_type || grossStationType;
                            grossDoctor = firstGross.gross_doctor_name || grossDoctor;
                            grossAssistant = firstGross.gross_assistant_name || grossAssistant;
                            grossCreateUser = firstGross.gross_created_by || grossCreateUser;
                            grossCreatedDate = firstGross.gross_create_date || grossCreatedDate;
                            batch = firstGross.batch || batch;
                            microcreateDate = firstGross.micro_created_date || microcreateDate;
                            microCreateUser = firstGross.micro_created_user || microCreateUser;
                        }
                    }else if (typeof item.gross_status === 'object') {
                                // If it's an object, retrieve the first key dynamically
                                const grossStatusKey = Object.keys(item.gross_status)[0];
                                if (grossStatusKey) {
                                    const firstGross = item.gross_status[grossStatusKey];
                                    grossStationType = firstGross.gross_station_type || grossStationType;
                                    grossDoctor = firstGross.gross_doctor_name || grossDoctor;
                                    grossAssistant = firstGross.gross_assistant_name || grossAssistant;
                                    grossCreateUser = firstGross.gross_created_by || grossCreateUser;
                                    grossCreatedDate = firstGross.gross_create_date || grossCreatedDate;
                                    batch = firstGross.batch || batch;
                                    microcreateDate = firstGross.micro_created_date || microcreateDate;
                                    microCreateUser = firstGross.micro_created_user || microCreateUser;
                                }
                    }
                }
                
                // Check and handle order_status (for order details)
                if (item.order_status) {
                    if (Array.isArray(item.order_status)) {
                        // If it's an array, get values from the first element if it exists
                        if (item.order_status.length > 0) {
                                const firstOrder = item.order_status[0];
                                userName = firstOrder.username || userName;
                                amountHT = firstOrder.amount_ht || amountHT;
                                dateCreation = firstOrder.date_creation || dateCreation;
                                dateLivraison = firstOrder.date_livraison || dateLivraison;
                                multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                                status = getStatusLabel(firstOrder.status); // Reassignment now valid
                                testType = firstOrder.test_type|| testType;
                                invoice = firstOrder.invoice_ref || invoice;
                                invoice_total_amount = firstOrder.total_amount || invoice_total_amount;
                                invoice_already_paid = firstOrder.already_paid || invoice_already_paid;
                                invoice_remaining_amount_due = firstOrder.remaining_amount_due || invoice_remaining_amount_due;
                                payment_mode_code = firstOrder.payment_mode_code || payment_mode_code;
                                payment_term_code = firstOrder.payment_term_code || payment_term_code;
                                bank_name = firstOrder.bank_name || bank_name;
                                bank_bic = firstOrder.bank_bic || bank_bic;
                                bank_iban = firstOrder.bank_iban || bank_iban;
                                discount_percentage = firstOrder.line_discount_percentage || discount_percentage;
                                discount_value = firstOrder.line_discount_value || discount_value;
                                specimen_name = firstOrder.line_descriptions || specimen_name;
                                patient_name = firstOrder.nom || patient_name;
                                patient_code = firstOrder.code_client || patient_code;
                                patient_phone = firstOrder.phone || patient_phone;
                                attendant_name = firstOrder.attendant_name || attendant_name;
                                attendant_relation = firstOrder.attendant_relation || attendant_relation;
                                attendant_number = firstOrder.fax || attendant_number;
                                address = firstOrder.address || address;
                                patient_age = firstOrder.age || patient_age;
                                patient_sex = getGender(firstOrder.sex);
                                patient_date_of_birth = firstOrder.date_of_birth || patient_date_of_birth;
                            }
                        } else if (typeof item.order_status === 'object') {
                            // Handle when order_status is an object
                            const statusKey = Object.keys(item.order_status)[0];
                            if (statusKey) {
                                const firstOrder = item.order_status[statusKey];
                                userName = firstOrder.username || userName;
                                amountHT = firstOrder.amount_ht || amountHT;
                                dateCreation = firstOrder.date_creation || dateCreation;
                                dateLivraison = firstOrder.date_livraison || dateLivraison;
                                multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                                status = getStatusLabel(firstOrder.status); // Reassignment now valid
                                testType = firstOrder.test_type || testType;
                                invoice = firstOrder.invoice_ref || invoice;
                                invoice_total_amount = firstOrder.total_amount || invoice_total_amount;
                                invoice_already_paid = firstOrder.already_paid || invoice_already_paid;
                                invoice_remaining_amount_due = firstOrder.remaining_amount_due || invoice_remaining_amount_due;
                                payment_mode_code = firstOrder.payment_mode_code || payment_mode_code;
                                payment_term_code = firstOrder.payment_term_code || payment_term_code;
                                bank_name = firstOrder.bank_name || bank_name;
                                bank_bic = firstOrder.bank_bic || bank_bic;
                                bank_iban = firstOrder.bank_iban || bank_iban;
                                discount_percentage = firstOrder.line_discount_percentage || discount_percentage;
                                discount_value = firstOrder.line_discount_value || discount_value;
                                specimen_name = firstOrder.line_descriptions || specimen_name;
                                patient_name = firstOrder.nom || patient_name;
                                patient_code = firstOrder.code_client || patient_code;
                                patient_phone = firstOrder.phone || patient_phone;
                                attendant_name = firstOrder.attendant_name || attendant_name;
                                attendant_relation = firstOrder.attendant_relation || attendant_relation;
                                attendant_number = firstOrder.fax || attendant_number;
                                address = firstOrder.address || address;
                                patient_age = firstOrder.age || patient_age;
                                patient_sex = getGender(firstOrder.sex);
                                patient_date_of_birth = firstOrder.date_of_birth || patient_date_of_birth;
                            }
                        }
                }
                
                // Create base data cells for the row using order_status values
                row.innerHTML = `
                    <td>${item.lab_number}</td>
                    <td>${patient_name}</td>
                    <td>${patient_age}</td>
                    <td>${patient_date_of_birth}</td>
                    <td>${patient_sex}</td>
                    <td>${patient_code}</td>
                    <td>${patient_phone}</td>
                    <td>${attendant_name}</td>
                    <td>${attendant_relation}</td>
                    <td>${attendant_number}</td>
                    <td>${address}</td>
                    <td>${userName}</td>
                    <td>${amountHT}</td>
                    <td>${multicurrencyTotalHT}</td>
                    <td>${testType}</td>
                    <td>${formatTrackCreateTime(dateCreation)}</td>
                    <td>${invoice}</td>
                    <td>${invoice_total_amount}</td>
                    <td>${invoice_already_paid}</td>
                    <td>${invoice_remaining_amount_due}</td>
                    <td>${payment_mode_code}</td>
                    <td>${payment_term_code}</td>
                    <td>${bank_name}</td>
                    <td>${bank_bic}</td>
                    <td>${bank_iban}</td>
                    <td>${discount_percentage}</td>
                    <td>${discount_value}</td>
                    <td>${specimen_name}</td>
                    <td>${formatTrackCreateTime(dateLivraison)}</td>
                    <td>${status}</td>
                    <td>${grossStationType}</td>
                    <td>${grossDoctor}</td>
                    <td>${grossAssistant}</td>
                    <td>${grossCreateUser}</td>
                    <td>${formatTrackCreateTime(grossCreatedDate)}</td>
                    <td>${getBatchLabel(batch)}</td>
                    <td>${microCreateUser}</td>
                    <td>${formatTrackCreateTime(microcreateDate)}</td>
                `;
                
                // Check if track_status exists and loop through its keys
                if (item.track_status && typeof item.track_status === 'object') {
                    Object.keys(item.track_status).forEach((key) => {
                        const statusItem = item.track_status[key];

                        // Define the titles you are interested in
                        const titles = ["Slides Prepared", "Screening Done", "Finalized", "Report Ready"];
                        
                        // Check if the WSStatusName matches one of the titles
                        if (titles.includes(statusItem.WSStatusName)) {
                            const title = statusItem.WSStatusName;

                            // Format the date
                            const dhakaDateTime = new Date(statusItem.create_time);

                            // Format the date and time using Intl.DateTimeFormat
                            const formattedDateTime = dhakaDateTime.toLocaleString('en-US', {
                                timeZone: 'Asia/Dhaka',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });

                            // Create the dynamic header for this title if it doesn't already exist
                            const titleHeader = `<th>${title}</th>`;
                            const createTimeHeader = `<th>${title} Create Time</th>`;
                            const userNameHeader = `<th>${title} User</th>`;

                            if (!headerRow.innerHTML.includes(titleHeader)) {
                                headerRow.innerHTML += titleHeader;
                                headerRow.innerHTML += createTimeHeader;
                                headerRow.innerHTML += userNameHeader;
                            }

                            // Add the data for this title in the corresponding row
                            row.innerHTML += `
                                <td>${statusItem.WSStatusName || 'N/A'}</td>
                                <td>${formattedDateTime}</td>
                                <td>${statusItem.TrackUserName || 'N/A'}</td>
                            `;
                        }
                    });
                }

                
                // Append the row to the tbody
                tbody.appendChild(row);
            });
    }

    // Apply filters based on the selected filter options
    function applyFilters() {
            const filters = {
                labNumber: document.getElementById('labNumberTypeFilter').value.toLowerCase().trim(),
                patientName: document.getElementById('patientNameFilterTypeFilter').value.toLowerCase().trim(),
                patientCode: document.getElementById('patientCodeFilterTypeFilter').value.toLowerCase().trim(),
                phone: document.getElementById('phoneFilterTypeFilter').value.toLowerCase().trim(),
                address: document.getElementById('addressFilterTypeFilter').value.toLowerCase().trim(),
                customerSupport: document.getElementById('customersupportFilterTypeFilter').value.toLowerCase().trim(),
                amount: document.getElementById('amountFilterTypeFilter').value.toLowerCase().trim(),
                totalAmount: document.getElementById('totalamountFilterTypeFilter').value.toLowerCase().trim(),
                createDate: document.getElementById('createdateFilterTypeFilter').value.toLowerCase().trim(),
                deliveryDate: document.getElementById('deliverydateTimeFilterTypeFilter').value.toLowerCase().trim(),
                status: document.getElementById('statusFilterTypeFilter').value.toLowerCase().trim(),
                section: document.getElementById('sectionFilterTypeFilter').value.toLowerCase().trim(),
                worksheetStatusName: document.getElementById('worksheetStatusNameFilterTypeFilter').value.toLowerCase().trim(),
                worksheetCreateTime: document.getElementById('worksheetCreateTimeFilterTypeFilter').value.toLowerCase().trim(),
                worksheetTrackUserName: document.getElementById('worksheetTrackUserNameFilterTypeFilter').value.toLowerCase().trim(),
                patientAge: document.getElementById('patientAgeFilterTypeFilter').value.toLowerCase().trim(),
                patientGender: document.getElementById('patientGenderFilterTypeFilter').value.toLowerCase().trim(),
                attendantName: document.getElementById('attendentNameFilterTypeFilter').value.toLowerCase().trim(),
                attendant_relation: document.getElementById('attendentRelationFilterTypeFilter').value.toLowerCase().trim(),
                attendantNumber: document.getElementById('attendentNumberFilterTypeFilter').value.toLowerCase().trim()
            };

            const filteredData = labData.filter(item => {
                const orderStatusArray = Array.isArray(item.order_status) ? item.order_status : [item.order_status];

                return orderStatusArray.some(order => {
                    // Default values
                    let userName = 'Not Provided';
                    let amountHT = 'Not Provided';
                    let dateCreation = 'Not Provided Date';
                    let dateLivraison = 'Not Provided Date';
                    let multicurrencyTotalHT = 'Not Provided';
                    let status = 'Unknown';
                    let section = 'Not Provided';
                    let worksheetStatusName = 'Not Provided';
                    let worksheetCreateTime = 'Not Provided';
                    let worksheetTrackUserName = 'Not Provided';
                    let patientName = 'Not Provided';
                    let patientCode = 'Not Provided';
                    let phone = 'Not Provided';
                    let address = 'Not Provided';
                    let patientAge = 'Not Provided';
                    let patientGender = 'Not Provided';
                    let attendantName = 'Not Provided';
                    let attendant_relation = 'Not Provided';
                    let attendantNumber = 'Not Provided';

                    // Handle order_status array
                    if (order) {
                        if (Array.isArray(order)) {
                            // If it's an array, get values from the first element if it exists
                            if (order.length > 0) {
                                const firstOrder = order[0];
                                userName = firstOrder.UserName || userName;
                                amountHT = firstOrder.amount_ht || amountHT;
                                dateCreation = firstOrder.date_creation || dateCreation;
                                dateLivraison = firstOrder.date_livraison || dateLivraison;
                                multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                                status = getStatusLabel(firstOrder.status) || status;
                                patientName = firstOrder.nom || patientName;
                                patientCode = firstOrder.code_client || patientCode;
                                phone = firstOrder.phone || phone;
                                address = firstOrder.address  || address;
                                patientAge = firstOrder.age || patientAge;
                                patientGender = getGender(firstOrder.sex) || patientGender;
                                attendantName = firstOrder.attendant_name || attendantName;
                                attendant_relation = firstOrder.attendant_relation || attendant_relation;
                                attendantNumber = firstOrder.fax || attendantNumber;
                            }
                        } else if (typeof order === 'object') {
                            // If it's an object, retrieve the first key dynamically
                            const statusKey = Object.keys(order)[0];
                            if (statusKey) {
                                const firstOrder = order[statusKey];
                                userName = firstOrder.UserName || userName;
                                amountHT = firstOrder.amount_ht || amountHT;
                                dateCreation = firstOrder.date_creation || dateCreation;
                                dateLivraison = firstOrder.date_livraison || dateLivraison;
                                multicurrencyTotalHT = firstOrder.multicurrency_total_ht || multicurrencyTotalHT;
                                status = getStatusLabel(firstOrder.status) || status;
                                patientName = firstOrder.nom || patientName;
                                patientCode = firstOrder.code_client || patientCode;
                                phone = firstOrder.phone || phone;
                                address = firstOrder.address  || address;
                                patientAge = firstOrder.age || patientAge;
                                patientGender = getGender(firstOrder.sex) || patientGender;
                                attendantName = firstOrder.attendant_name || attendantName;
                                attendant_relation = firstOrder.attendant_relation || attendant_relation;
                                attendantNumber = firstOrder.fax || attendantNumber;
                            }
                        }
                    }

                    // Handle track_status filtering
                    if (item.track_status && typeof item.track_status === 'object') {
                        Object.keys(item.track_status).forEach((key) => {
                            const statusItem = item.track_status[key];
                            // Update section, worksheetStatusName, worksheetCreateTime, and worksheetTrackUserName with actual values
                            section = statusItem.section || section;
                            worksheetStatusName = statusItem.WSStatusName || worksheetStatusName;
                            worksheetCreateTime = statusItem.WSStatusCreateTime || worksheetCreateTime;
                            worksheetTrackUserName = statusItem.TrackUserName || worksheetTrackUserName;
                        });
                    }

                    // Apply the filters based on user input
                    return (
                        (!filters.labNumber || (item.lab_number || "").toLowerCase().includes(filters.labNumber)) &&
                        (!filters.patientName || (patientName || "").toLowerCase().includes(filters.patientName)) &&
                        (!filters.patientAge || (patientAge || "").toLowerCase().includes(filters.patientAge)) &&
                        (!filters.patientGender || (patientGender || "").toLowerCase() === filters.patientGender) &&
                        (!filters.patientCode || (patientCode || "").toLowerCase().includes(filters.patientCode)) &&
                        (!filters.phone || (phone || "").toLowerCase().includes(filters.phone)) &&
                        (!filters.attendantName || (attendantName || "").toLowerCase() === filters.attendantName) &&
                        (!filters.attendant_relation || (attendant_relation || "").toLowerCase() === filters.attendant_relation) &&
                        (!filters.attendantNumber || (attendantNumber || "").toLowerCase().includes(filters.attendantNumber)) &&
                        (!filters.address || (address || "").toLowerCase().includes(filters.address)) &&
                        (!filters.customerSupport || (userName || "N/A").toLowerCase().includes(filters.customerSupport)) &&
                        (!filters.amount || (amountHT || "N/A").toLowerCase().includes(filters.amount)) &&
                        (!filters.totalAmount || multicurrencyTotalHT === filters.totalAmount) &&
                        (!filters.createDate || (dateCreation || "N/A").toLowerCase().includes(filters.createDate)) &&
                        (!filters.deliveryDate || (dateLivraison || "N/A").toLowerCase().includes(filters.deliveryDate)) &&
                        (!filters.status || status.toLowerCase().includes(filters.status)) &&
                        (!filters.section || section.toLowerCase().includes(filters.section)) &&
                        (!filters.worksheetStatusName || worksheetStatusName.toLowerCase().includes(filters.worksheetStatusName)) &&
                        (!filters.worksheetCreateTime || worksheetCreateTime.toLowerCase().includes(filters.worksheetCreateTime)) &&
                        (!filters.worksheetTrackUserName || worksheetTrackUserName.toLowerCase().includes(filters.worksheetTrackUserName))
                    );
                });
            });

            displayLabData(filteredData);
    }


    // Event listener to apply filters when a filter changes
    document.querySelectorAll('.form-select').forEach(selectElement => {
        selectElement.addEventListener('change', applyFilters);
    });

    // Event listener for the search input
    document.getElementById('searchInput').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase();
        const filteredData = labData.filter(item => {
            return item.lab_number.toLowerCase().includes(searchValue) ||
                item.name.toLowerCase().includes(searchValue) ||
                item.patient_code.toLowerCase().includes(searchValue) ||
                item.phone.toLowerCase().includes(searchValue) ||
                item.address.toLowerCase().includes(searchValue);
        });
        displayLabData(filteredData);
    });

    // Initialize the page
    populateFilters();
    displayLabData(labData);
</script>


</body>
</html>