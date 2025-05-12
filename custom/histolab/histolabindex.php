<?php
include('connection.php');
include('histo_common_function.php');
include('../grossmodule/gross_common_function.php');

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

print load_fiche_titre($langs->trans("Histo Lab Area"), '',);


$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$gross_created_user = $user->id;
$loggedInUsername = $user->login;

$isHistoTechs = false;

$histoTechs = get_histo_techs_user_list();
foreach ($histoTechs as $histoTech) {
    if ($histoTech['username'] == $loggedInUsername) {
        $isHistoTech = true;
        break;
    }
}

$isAdmin = isUserAdmin($loggedInUserId);

// Access control using switch statement
switch (true) {
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;

    case $isHistoTech:
        // Histo Techs has access, continue with the page content...
        break;
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

$histo_gross_list = get_histo_gross_specimen_list();
$doctor_instruction_list = get_histo_doctor_instruction_list();
$in_progress_instruction_list = get_histo_doctor_instruction_inprogress_list();
$complete_instruction_list = get_histo_doctor_instruction_complete_list();
$on_hold_instruction_list = get_histo_doctor_instruction_on_hold_list();
$bones_list = get_bones_not_ready_list();
$sbo_list = get_slide_block_order_list();
$sbo_complete_list = get_slide_block_order_ready_list();
$batch_name_cassettes_count = date_wise_batch_name_cassettes_count_list();
$bone_slide_ready_list = get_bone_slide_ready_list();
$regross_list = regross_cassettes_list($start_date, $end_date);

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

/* Style the tab container */
.tab-container {
    width: 100%;
}

/* Style the tabs */
.tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

/* Style the buttons inside the tab */
.tabs .tablink {
    background-color: #f1f1f1;
    border: 2px solid #ccc;
    outline: none;
    cursor: pointer;
    padding: 10px;
    margin: 0 5px;
    width: 100px;
    height: 100px;
    border-radius: 100%;
    text-align: center;
    font-size: 14px; /* Adjust font size for better fit */
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    white-space: pre-wrap; /* Allow text to wrap within the button */
    word-wrap: break-word; /* Break long words if necessary */
}

/* Change background color of buttons on hover */
.tabs .tablink:hover {
    background-color: #ddd;
}

/* Create an active/current tablink class */
.tabs .tablink.active {
    background-color: #ccc;
    border-color: #888;
}

/* Style the tab content */
.tabcontent {
    display: none;
}

/* Style for the sub-tabs container */
.sub-tabs {
    width: 100%;
    padding: 10px;
}

/* Style for the sub-tab links */
.sub-tab-links {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.sub-tab-links .sub-tablink {
    background-color: #f1f1f1;
    border: 2px solid #ccc;
    outline: none;
    cursor: pointer;
    padding: 10px;
    margin: 0 5px;
    width: 100px;
    height: 100px;
    border-radius: 100%;
    text-align: center;
    font-size: 14px; /* Adjust font size for better fit */
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    white-space: pre-wrap; /* Allow text to wrap within the button */
    word-wrap: break-word; /* Break long words if necessary */
}

.sub-tab-links .sub-tablink:hover {
    background-color: #ddd;
}

.sub-tab-links .sub-tablink.active {
    background-color: #ccc;
    border-color: #888;
}

/* Style for the sub-tab controls (inputs and button) */
.sub-tab-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px; /* Space between input fields and button */
}

/* Style for sub-tab content */
.subtabcontent {
    display: none; /* Hide all sub-tab contents by default */
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Style for the active sub-tab */
.sub-tablink.active {
    background-color: #ccc;
    border-color: #888;
}
.vertical-icon {
    display: inline-block;
    transform: rotate(90deg); /* Rotate 90 degrees to make it vertical */
    margin: 0; /* Adjust margin if needed */
    padding: 0; /* Adjust padding if needed */
}

/* Responsive styles */
@media (max-width: 768px) {
    .tabs {
        flex-wrap: wrap;
    }
    .tabs .tablink {
        width: 60px; /* Adjust size for smaller screens */
        height: 60px;
        font-size: 12px; /* Adjust font size for smaller screens */
        line-height: normal;
    }
}

</style>");

echo '<div class="tab-container">
            <!-- Tab Links -->
            <div class="tabs">
            <button style="border:none" class="tablink" onclick="openTab(event, \'GrossSectionInstructions\')">
                    <i class="fas fa-book" style="font-size: 35px;"></i> Gross Ledger</button>
            <button style="border:none" class="tablink" onclick="openTab(event, \'DoctorRelatedInstructions\')">
                <i class="fas fa-user-md" style="font-size: 35px;"></i>Doctor Notes</button>
            <button style="border:none" class="tablink" onclick="openTab(event, \'BoneRelatedInstructions\')">
                <i class="fas fa-bone vertical-icon" style="font-size: 35px;"></i>Bone Dcal</button>
            <button style="border:none" class="tablink" onclick="openTab(event, \'SBO\')">
                    <i class="fa fa-eraser" style="font-size: 35px;"></i> SBO
            </button>
            <button style="border:none" class="tablink" onclick="openTab(event, \'Re-Gross\')">
                    <i class="fas fa-book" style="font-size: 35px;"></i> Re-Gross Ledger
            </button>
            </div>

            <!-- Tab Content for Gross Section Instructions -->
            <div id="GrossSectionInstructions" class="tabcontent">
                    <div class="container">
                        <input type="date" id="fromDateTime" class="input-field" placeholder="From">
                        <input type="date" id="toDateTime" class="input-field" placeholder="To">
                        <button id="submitBtn" class="btn">Submit</button>&nbsp;&nbsp;
                        <button id="generatePdfBtn" class="btn">Generate PDF</button>
                    </div>
                    <!-- Table content goes here -->
                    <table class="table" id="histoGrossTable">
                        <tr></tr>
                        <tbody id="histoGrossTableBody">
                    </tbody>
                    </table>
                     
                    <!-- Hidden input for username -->
                    <input type="hidden" id="loggedInUsername" value="' . htmlspecialchars($loggedInUsername) . '">
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
                    <script>
                        const histo_gross_list = ' . json_encode($histo_gross_list) . ';
                        
                        // Function to generate table rows for submission
                        function generateTableRowsForSubmit(groupedItems, labNumbers, sectionSequence) {
                            let tableRows = ""; // Initialize the tableRows variable
                            labNumbers.forEach(function(labNumber) {
                                if (groupedItems.hasOwnProperty(labNumber)) {
                                    // Add Lab Number row with colon separator
                                    tableRows += "<tr><td colspan=\'6\'><strong>Lab Number: " + labNumber + "</strong></td></tr>";

                                    // Initialize an array to hold all section details for this lab number
                                    let sectionDetails = [];

                                    // Add section code, tissue, slide, cassettes numbers, doctor, and gross assistant details for each Lab Number
                                    sectionSequence.forEach(function(code) {
                                        groupedItems[labNumber].forEach(function(item) {
                                            if (item["section_code"] === code) {
                                                // Detailed row for each section
                                                tableRows += "<tr>";
                                                tableRows += "<td>" + item["section_code"] + "</td>";
                                                tableRows += "<td>Tissue: " + item["tissue"] + "</td>";
                                                tableRows += "<td>" + (item["cassettes_numbers"] || \'N/A\') + "</td>";
                                                tableRows += "<td>Slide: " + (item["requires_slide_for_block"] || \'N/A\') + "</td>";
                                                tableRows += "<td>Station : "+ (item["gross_station_type"] || \'N/A\') + "</td>";
                                                tableRows += "<td> " + (item["doctor"] || \'N/A\') + "</td>";
                                                tableRows += "<td>" + (item["assistant"] || \'N/A\') + "</td>";
                                                
                                                // Conditional formatting for the "Bone" field
                                                let boneValue = item["bone"];
                                                if (!boneValue || boneValue.toLowerCase() === "no" || boneValue.toLowerCase() === "false") {
                                                    tableRows += "<td style=\'color: red;\'><strong>●</strong></td>";
                                                } else if (boneValue.toLowerCase() === "yes" || boneValue.toLowerCase() === "true") {
                                                    tableRows += "<td style=\'color: blue;\'><strong>●</strong></td>";
                                                } else {
                                                    tableRows += "<td>Bone: " + (boneValue || \'N/A\') + "</td>";
                                                }
                                                
                                                let reGrossValue = item["re_gross"];
                                                if (!reGrossValue || reGrossValue.toLowerCase() === "no" || reGrossValue.toLowerCase() === "false") {
                                                    tableRows += "<td style=\'color: magenta;\'><strong>●</strong></td>";
                                                } else if (reGrossValue.toLowerCase() === "yes" ||reGrossValue.toLowerCase() === "true") {
                                                    tableRows += "<td style=\'color: navy blue;\'><strong>●</strong></td>";
                                                } else {
                                                    tableRows += "<td>Re Gross: " + (reGrossValue || \'N/A\') + "</td>";
                                                }

                                                
                                                // Format the gross create date
                                                if (item["Gross Create Date"]) {
                                                    const date = new Date(item["Gross Create Date"]);
                                                    tableRows += "<td>Gross Date: " + (isNaN(date.getTime()) ? \'Invalid Date\' : date.toLocaleDateString("en-US", { day: "numeric", month: "long", year: "numeric" })) + "</td>";
                                                } else {
                                                    tableRows += "<td>N/A</td>"; // Placeholder for missing date info
                                                }

                                                // Format the delivery date
                                                if (item["date_livraison"]) {
                                                    const delivery_date = new Date(item["date_livraison"]);
                                                    tableRows += "<td>Delivery Date: " + (isNaN(delivery_date.getTime()) ? \'Invalid Date\' : delivery_date.toLocaleDateString("en-US", { day: "numeric", month: "long", year: "numeric" })) + "</td>";
                                                } else {
                                                    tableRows += "<td>N/A</td>"; // Placeholder for missing date info
                                                }

                                                tableRows += "</tr>"; // Close the row
                                            }
                                        });
                                    });

                                    // Add a summary row if there are any section details collected
                                    if (sectionDetails.length > 0) {
                                        tableRows += "<tr><td colspan=\'6\'>" + sectionDetails.join(", ") + "</td></tr>";
                                    }
                                }
                            });

                            return tableRows; // Return the generated rows
                        }

                        // Define the function to generate table rows for PDF
                        
                        function generateUniqueDataStructure(histo_gross_list) {
                                const uniqueDataByDate = {}; // Main object to store unique data by date

                                histo_gross_list.forEach((item) => {
                                    const dateKey = item["Gross Create Date"].split(\' \')[0]; // Only use the date part (e.g., \'2024-11-09\')
                                    const batchName = item["batch"];
                                    const doctorName = item["doctor"];
                                    const assistantName = item["assistant"];
                                    const labNumber = item["Lab Number"];
                                    const sectionCode = item["section_code"];
                                    const tissue = item["tissue"];
                                    const slideForBlock = item["requires_slide_for_block"];
                                    const bone = item["bone"]; // Add bone property
                                    const reGross = item["re_gross"]; // Add re_gross property

                                    // Ensure the date does not exist before adding it
                                    if (!uniqueDataByDate[dateKey]) {
                                        uniqueDataByDate[dateKey] = {
                                            date: dateKey,
                                            batches: {} // Initialize batches object for each date
                                        };
                                    }

                                    // Ensure batch exists as a key within the date
                                    if (!uniqueDataByDate[dateKey].batches[batchName]) {
                                        uniqueDataByDate[dateKey].batches[batchName] = {
                                            batchName,
                                            doctors: {} // Initialize doctors object within each batch
                                        };
                                    }

                                    // Ensure doctor exists as a key within the batch
                                    if (!uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName]) {
                                        uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName] = {
                                            doctorName,
                                            assistants: {} // Initialize assistants object within each doctor
                                        };
                                    }

                                    // Ensure assistant exists as a key within the doctor
                                    if (!uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName].assistants[assistantName]) {
                                        uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName].assistants[assistantName] = {
                                            assistantName,
                                            labNumbers: {} // Initialize labNumbers object within each assistant
                                        };
                                    }

                                    // Ensure lab number exists as a unique entry within the assistant
                                    if (!uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName].assistants[assistantName].labNumbers[labNumber]) {
                                        uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName].assistants[assistantName].labNumbers[labNumber] = {
                                            labNumber,
                                            sections: [] // Initialize sections array within each lab number
                                        };
                                    }

                                    // Check if the section information is already added, and add it only if unique
                                    const sectionDetail = {
                                        sectionCode,
                                        tissue,
                                        requiresSlideForBlock: slideForBlock,
                                        bone, // Include bone property
                                        reGross // Include re_gross property
                                    };

                                    const existingSections = uniqueDataByDate[dateKey].batches[batchName].doctors[doctorName].assistants[assistantName].labNumbers[labNumber].sections;
                                    const sectionExists = existingSections.some(section => 
                                        section.sectionCode === sectionCode &&
                                        section.tissue === tissue &&
                                        section.requiresSlideForBlock === slideForBlock &&
                                        section.bone === bone &&
                                        section.reGross === reGross
                                    );

                                    // Add section details if not already present
                                    if (!sectionExists) {
                                        existingSections.push(sectionDetail);
                                    }
                                });

                                // Sort Lab Numbers and Sections in ascending order
                                Object.keys(uniqueDataByDate).forEach(dateKey => {
                                    const dateData = uniqueDataByDate[dateKey];

                                    // Sort batches by batch name (optional, if needed)
                                    Object.keys(dateData.batches).forEach(batchKey => {
                                        const batch = dateData.batches[batchKey];

                                        // Sort doctors by doctor name (optional, if needed)
                                        Object.keys(batch.doctors).forEach(doctorKey => {
                                            const doctor = batch.doctors[doctorKey];

                                            // Sort assistants by assistant name (optional, if needed)
                                            Object.keys(doctor.assistants).forEach(assistantKey => {
                                                const assistant = doctor.assistants[assistantKey];

                                                // Sort lab numbers in ascending order
                                                const labNumbers = assistant.labNumbers;
                                                const sortedLabNumbers = Object.keys(labNumbers).sort((a, b) => a - b); // Sort lab numbers numerically

                                                // Sort sections within each lab number in ascending order by section code
                                                sortedLabNumbers.forEach(labNumberKey => {
                                                    const labNumber = labNumbers[labNumberKey];

                                                    // Sort sections by section code
                                                    labNumber.sections.sort((a, b) => a.sectionCode.localeCompare(b.sectionCode));
                                                });
                                            });
                                        });
                                    });
                                });

                                return uniqueDataByDate;
                        }

                        function generateTableRowsForPdf() {
                                let tableRows = ""; // Initialize the tableRows variable

                                // Get the date values from the input fields
                                const fromDate = new Date(document.getElementById("fromDateTime").value);
                                const toDate = new Date(document.getElementById("toDateTime").value);
                                const fromDateStart = new Date(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
                                const toDateEnd = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate() + 1) - 1;

                                // Define batch names for display
                                const batchNames = [
                                    "I", "II", "III", "IV", 
                                    "V", "VI", "VII", "VIII", 
                                    "IX", "X"
                                ];

                                // Generate unique data structure
                                const uniqueDataByDate = generateUniqueDataStructure(histo_gross_list);

                                // Function to compare Lab Numbers and sort numerically for numeric and alphanumerically for mixed format
                                function compareLabNumbers(a, b) {
                                    // Check if both values are numeric
                                    const numericA = parseInt(a.replace(/[^0-9]/g, ""), 10); // Extract numbers from lab number
                                    const numericB = parseInt(b.replace(/[^0-9]/g, ""), 10); // Extract numbers from lab number

                                    // If both are numeric, sort by numeric value
                                    if (!isNaN(numericA) && !isNaN(numericB)) {
                                        return numericA - numericB;
                                    }

                                    // If one of them is a numeric value and the other is alphanumeric, sort numerically first
                                    if (!isNaN(numericA)) {
                                        return -1; // numeric values come first
                                    }
                                    if (!isNaN(numericB)) {
                                        return 1; // numeric values come first
                                    }

                                    // For alphanumeric sorting, compare by string values if needed (optional for your case)
                                    return a.localeCompare(b);
                                }

                                // Function to compare Section Codes (e.g., A1, A2, B1)
                                function compareSectionCodes(a, b) {
                                        // Ensure both a and b are strings
                                        const aStr = String(a);
                                        const bStr = String(b);

                                        const regex = /([A-Za-z]+)(\d+)/; // Regex to split letters and numbers
                                        const matchA = aStr.match(regex);
                                        const matchB = bStr.match(regex);

                                        // Check if both match the pattern
                                        if (matchA && matchB) {
                                            const letterA = matchA[1];
                                            const numberA = parseInt(matchA[2], 10);
                                            const letterB = matchB[1];
                                            const numberB = parseInt(matchB[2], 10);

                                            // First compare the letter part alphabetically
                                            if (letterA !== letterB) {
                                                return letterA.localeCompare(letterB);
                                            }

                                            // Then compare the number part numerically
                                            return numberA - numberB;
                                        }

                                    // If either doesn\'t\ match the expected pattern, return 0 (or handle as needed)
                                    return 0;
                                }

                                // Iterate through each date in the data
                                Object.keys(uniqueDataByDate).forEach(dateKey => {
                                    const dateData = uniqueDataByDate[dateKey];

                                    // Check if the date is within the selected range
                                    const dateObj = new Date(dateKey);
                                    if (dateObj >= fromDateStart && dateObj <= toDateEnd) {
                                        // Display the date in the table
                                        const formattedDate = dateObj.toLocaleDateString("en-US", {
                                            day: "numeric",
                                            month: "long",
                                            year: "numeric"
                                        });

                                        tableRows += `<tr><td colspan=\'6\'>${formattedDate}</td></tr>`;

                                        // Iterate through batches for this date
                                        Object.keys(dateData.batches).forEach(batchKey => {
                                            const batch = dateData.batches[batchKey];
                                            const batchName = batchNames[batchKey - 1] !== undefined && batchNames[batchKey - 1] !== null && batchNames[batchKey - 1] !== "" 
                                                            ? batchNames[batchKey - 1] 
                                                            : `Batch ${batchKey} ${batchNames[batchKey - 1] === undefined ? "(Batch is not Selected)" : ""}`;

                                            // Initialize section code count for this batch
                                            let sectionCodeCount = 0;

                                            // Iterate through doctors and assistants
                                            Object.keys(batch.doctors).forEach(doctorKey => {
                                                const doctor = batch.doctors[doctorKey];
                                                Object.keys(doctor.assistants).forEach(assistantKey => {
                                                    const assistant = doctor.assistants[assistantKey];

                                                    // Display the assistant name
                                                    tableRows += `<tr><td colspan=\'6\'>(${batchName})&nbsp;${doctorKey}&nbsp;${assistantKey}</td></tr>`;

                                                    // Iterate through lab numbers and sections
                                                    const labNumbers = assistant.labNumbers;
                                                    const sortedLabNumbers = Object.keys(labNumbers).sort(compareLabNumbers); // Use custom comparison for lab numbers

                                                    // Iterate through sorted lab numbers
                                                    sortedLabNumbers.forEach(labNumberKey => {
                                                        const labNumber = labNumbers[labNumberKey];

                                                        // Create a map to group sections by sectionCode
                                                        const sectionGroups = {};

                                                        // Group sections by sectionCode and tissue
                                                        labNumber.sections.forEach(section => {
                                                            // If the section has multiple occurrences, show \'(m)\' instead of \'(multiple)\'
                                                            const tissueDisplay = section.tissue === "multiple" ? "m" : section.tissue;
                                                            const sectionKey = `${section.sectionCode}(${tissueDisplay})`;

                                                            // Track occurrences of each section
                                                            if (!sectionGroups[sectionKey]) {
                                                                sectionGroups[sectionKey] = { 
                                                                    count: 0,
                                                                    requiresSlideForBlock: section.requiresSlideForBlock,
                                                                    bone: section.bone,
                                                                    reGross: section.reGross
                                                                };
                                                            }
                                                            sectionGroups[sectionKey].count++;

                                                            // If \'requiresSlideForBlock\' is missing, it should show as empty or a default value
                                                            if (!sectionGroups[sectionKey].requiresSlideForBlock) {
                                                                sectionGroups[sectionKey].requiresSlideForBlock = "";
                                                            }
                                                        });

                                                        // Count total section codes for the batch
                                                        sectionCodeCount += Object.keys(sectionGroups).length;

                                                        // Generate the horizontal display of sections with \'multiple\' for sections that appear more than once
                                                        const sectionDisplay = Object.keys(sectionGroups)
    .sort(compareSectionCodes) // Sort the section codes properly
    .map(sectionKey => { 
                                                            const count = sectionGroups[sectionKey].count;
                                                            const section = sectionGroups[sectionKey];
                                                            let additionalInfo = "";

                                                            // Check if \'bone\' is \'yes\' and append \'(b)\'
                                                            if (section.bone === "yes") {
                                                                additionalInfo += "(b)";
                                                            }

                                                            // Check if \'re_gross\' is \'yes\' and append \'(re)\'
                                                            if (section.reGross === "yes") {
                                                                additionalInfo += "(re)";
                                                            }
                                                            const slideInfo = sectionGroups[sectionKey].requiresSlideForBlock 
                                                                                ? `(Slide: ${sectionGroups[sectionKey].requiresSlideForBlock})`
                                                                                : \'\';
                                                            return `${sectionKey}${slideInfo}${additionalInfo}`;
                                                        }).join(", ");

                                                        // Sort sections within each lab number by section code
                                                        labNumber.sections.sort(compareSectionCodes); 

                                                        // Display lab number and sections in a horizontal format
                                                        tableRows += `<tr><td colspan=\'6\'><strong>${labNumberKey}</strong> - ${sectionDisplay}</td></tr>`;
                                                    });
                                                });
                                            });

                                            // After processing all doctors and assistants, display the total section codes for this batch
                                            tableRows += `<tr><td colspan=\'6\'>Total Blocks in (${batchName}): ${sectionCodeCount}<hr></td></tr>`;
                                        });
                                    }
                                });

                                return tableRows; // Return the generated rows
                        }

                        // Define the submitDateTime function
                        function submitDateTime() {
                            var fromDate = new Date(document.getElementById("fromDateTime").value);
                            var toDate = new Date(document.getElementById("toDateTime").value);  
                            var tableRows = "";

                            // Filter items by date range
                            var filteredItems = histo_gross_list.filter(function(item) {
                                var itemDate = new Date(item["Gross Create Date"]);
                                var fromDateStart = new Date(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
                                var toDateEnd = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate() + 1) - 1;
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

                            // Extract unique section codes and sort them according to custom order
                            var sectionSequence = [];
                            filteredItems.forEach(function(item) {
                                if (!sectionSequence.includes(item["section_code"])) {
                                    sectionSequence.push(item["section_code"]);
                                }
                            });

                            // Custom sort function for section codes
                            sectionSequence.sort(function(a, b) {
                                var regex = /^([A-Z])(\d*)$/;
                                var matchA = a.match(regex);
                                var matchB = b.match(regex);
                                if (matchA[1] === matchB[1]) {
                                    return (parseInt(matchA[2] || 0) || 0) - (parseInt(matchB[2] || 0) || 0);
                                } else {
                                    return matchA[1].localeCompare(matchB[1]);
                                }
                            });

                            // Extract and sort Lab numbers
                            var labNumbers = Object.keys(groupedItems);
                            labNumbers.sort();

                            // Generate table rows for submission
                            tableRows = generateTableRowsForSubmit(groupedItems, labNumbers, sectionSequence);

                            // Set the HTML content of the table body
                            document.getElementById("histoGrossTableBody").innerHTML = tableRows;
                        }

                        // Add event listener to the button
                        document.getElementById("submitBtn").addEventListener("click", submitDateTime);

                        // Event listener for the Generate PDF button
                        document.getElementById("generatePdfBtn").addEventListener("click", function() {
                            var tableData = generateTableRowsForPdf(); // Call the function for PDF generation
                            var today = new Date().toLocaleDateString();
                            var userName = document.getElementById("loggedInUsername").value;

                            var formData = new FormData();
                            formData.append("tableData", JSON.stringify(tableData));
                            formData.append("userName", userName);
                            formData.append("today", today);

                            var xhr = new XMLHttpRequest();
                            xhr.open("POST", "generate_pdf.php", true);
                            xhr.responseType = "blob"; 
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    var blob = new Blob([xhr.response], { type: "application/pdf" });
                                    var url = window.URL.createObjectURL(blob);
                                    window.open(url); 
                                } else {
                                    console.error("Failed to generate PDF:", xhr.statusText);
                                }
                            };
                            xhr.onerror = function() {
                                console.error("Request failed");
                            };
                            xhr.send(formData);
                        });
                    </script>
            </div>

            <!-- Tab Content for Doctor Related Instructions -->
            <div id="DoctorRelatedInstructions" class="tabcontent">
              <!-- Sub-tab Links -->
              <div class="sub-tabs">
                <div class="sub-tab-links">
                  <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'List\')">
                  <i class="fas fa-list" style="font-size: 25px;"></i><b>&nbspList</b></button>
                  <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'InProgress\')">
                  <i class="fas fa-spinner" style="font-size: 35px;"></i>In Progress</button>
                  <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'Completed\')">
                  <i class="fas fa-check-circle" style="font-size: 35px;"></i>Done</button>
                  <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'OnHold\')">
                    <i class="fas fa-pause-circle" style="font-size: 35px;"></i>On Hold
                  </button>
                </div>
            
                <!-- Sub-tab contents -->
                <div id="List" class="subtabcontent">
                    <table id="doctorInstructionTable" border="1">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="submitStatusChanges" class="btn" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Date</th>
                                <th>Lab Number</th>
                                <th>Section</th>
                                <th>Instruction</th>
                                <th>Doctor Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script>
                        const doctor_instruction_list = ' . json_encode($doctor_instruction_list) . ';
                        const loggedInUserId = ' . json_encode($loggedInUserId) . ';

                        let statusChanges = {};

                        // Define the values to exclude
                        const excludedSections = ["Gross", "Transcription", "Frontdesk", "Screening"];
                        const excludedStatusNames = ["Diagnosis Completed", "Final Screening Start", "Screening Done",
                            "Waiting - Study", "Waiting - Patient History / Investigation", "In-Progress", "On-Hold", "Slides Prepared",
                            "Regross Completed", "Recut or Special Stain Completed", "Regross Slides Prepared", "IHC-Block-Markers-completed",
                            "M/R/C Completed", "Deeper Cut Completed", "Serial Sections Completed", "Block D/C & R/C Completed", "Special Stain AFB Completed",
                            "Special Stain GMS Completed", "Special Stain PAS Completed", "Special Stain PAS with Diastase Completed", "Special Stain Fite Faraco Completed",
                            "Special Stain Brown-Brenn Completed", "Special Stain Congo-Red Completed", "Special Stain others Completed", 
                            "R/C Completed", "Re-gross Requested", "Wating Screening For Bones", "Wating Finalized For Bones"
                        ];

                        // Define the status name mappings
                        const statusNameMappings = {
                            "Special Stain others requested": "Special Stain others",
                            "IHC-Block-Markers-requested": "IHC",
                            "R/C requested": "R/C",
                            "M/R/C requested": "M/R/C",
                            "Deeper Cut requested": "Deeper Cut",
                            "Serial Sections requested": "Serial Sections",
                            "Block D/C & R/C requested": "Block D/C & R/C",
                            "Special Stain AFB requested": "Special Stain AFB",
                            "Special Stain GMS requested": "Special Stain GMS",
                            "Special Stain PAS with Diastase requested": "Special Stain PAS with Diastase",
                            "Special Stain Brown-Brenn requested": "Special Stain Brown-Brenn",
                            "Special Stain Congo-Red requested": "Special Stain Congo-Red",
                            "Special Stain Bone Decalcification requested": "Special Stain Bone Decalcification",
                            "Special Stain Fite Faraco requested": "Special Stain Fite Faraco",
                            "Re-gross Requested": "Re-gross",
                            "Special Stain PAS requested": "Special Stain PAS"
                        };

                        // Filter the list based on the conditions
                        const filteredDoctorInstructionList = doctor_instruction_list.filter(item =>
                            !excludedSections.includes(item["Section"]) &&
                            !excludedStatusNames.includes(item["Status Name"])
                        ).map(item => {
                            // Map the status name if it exists in the mapping
                            if (statusNameMappings[item["Status Name"]]) {
                                item["Status Name"] = statusNameMappings[item["Status Name"]];
                            }
                            return item;
                        });

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        // Function to generate table rows
                        function generateTableRows(data) {
                            return data.map(item => `
                                <tr>
                                    <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                    <td>${item["Lab Number"]}</td>
                                    <td>${item["Description"]}</td>
                                    <td>${item["Status Name"]}</td>
                                    <td>${item["User Name"]}</td>
                                    <td>
                                        <select data-track-id="${item["track_id"]}" data-lab-number="${item["Lab Number"]}">
                                            <option value="">Select</option>
                                            <option value="In-Progress">In-Progress</option>
                                            <option value="On-Hold">On-Hold</option>
                                        </select>
                                    </td>
                                </tr>
                            `).join("");
                        }

                        // Track status changes with unique track_id
                        function trackStatusChange(trackId, labNumber, status) {
                            statusChanges[trackId] = { labNumber: labNumber, status: status };
                        }

                        // Add event listeners to all select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            document.querySelector("#doctorInstructionTable tbody").innerHTML = generateTableRows(filteredDoctorInstructionList);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    trackStatusChange(trackId, labNumber, this.value);
                                });
                            });
                        });

                        // Submit the status changes
                        document.getElementById("submitStatusChanges").addEventListener("click", function() {
                            fetch("histo_lab_status.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    values: statusChanges
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Status changes saved successfully.");
                                    statusChanges = {}; // Clear the changes after successful save
                                    // Optionally, reload the page or refresh the table to reflect the changes
                                    window.location.reload();
                                } else {
                                    alert(data.message || "Failed to save status changes.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });
                    </script>
                </div>

                <div id="InProgress" class="subtabcontent">
                    <table id="inprogres_InstructionTable" border="1">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="submitInprogressStatusChanges" class="btn" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Date</th>
                                <th>Lab Number</th>
                                <th>Section</th>
                                <th>Instruction</th>
                                <th>Doctor Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script>
                        const inprogress_instruction_list = ' . json_encode($in_progress_instruction_list) . ';
                        
                        let inprogres_statusChanges = {};

                        // Define the values to exclude
                        const inprogres_excludedSections = ["Gross", "Transcription", "Frontdesk", "Screening"];
                        const inprogres_excludedStatusNames = ["Diagnosis Completed", "Final Screening Start", "Screening Done",
                            "Waiting - Study", "Waiting - Patient History / Investigation", "Slides Prepared",
                            "Regross Completed", "Recut or Special Stain Completed", "Regross Slides Prepared", "IHC-Block-Markers-completed",
                            "M/R/C Completed", "Deeper Cut Completed", "Serial Sections Completed", "Block D/C & R/C Completed", "Special Stain AFB Completed",
                            "Special Stain GMS Completed", "Special Stain PAS Completed", "Special Stain PAS with Diastase Completed", "Special Stain Fite Faraco Completed",
                            "Special Stain Brown-Brenn Completed", "Special Stain Congo-Red Completed", "Special Stain others Completed", "In-Progress", 
                            "On-Hold", "R/C Completed", "Re-gross Requested"
                        ];

                        const statusPairsWithIds = {
                            "Special Stain others requested": { completed: "Special Stain others Completed", id: 41 },
                            "IHC-Block-Markers-requested": { completed: "IHC-Block-Markers-completed", id: 45 },
                            "M/R/C requested": { completed: "M/R/C Completed", id: 19 },
                            "Deeper Cut requested": { completed: "Deeper Cut Completed", id: 21 },
                            "Serial Sections requested": { completed: "Serial Sections Completed", id: 23 },
                            "Block D/C & R/C requested": { completed: "Block D/C & R/C Completed", id: 25 },
                            "Special Stain AFB requested": { completed: "Special Stain AFB Completed", id: 27 },
                            "Special Stain GMS requested": { completed: "Special Stain GMS Completed", id: 29 },
                            "Special Stain PAS requested": { completed: "Special Stain PAS Completed", id: 31 },
                            "Special Stain PAS with Diastase requested": { completed: "Special Stain PAS with Diastase Completed", id: 33 },
                            "Special Stain Fite Faraco requested": { completed: "Special Stain Fite Faraco Completed", id: 35 },
                            "Special Stain Brown-Brenn requested": { completed: "Special Stain Brown-Brenn Completed", id: 37 },
                            "Special Stain Congo-Red requested": { completed: "Special Stain Congo-Red Completed", id: 39 },
                            "Special Stain Bone Decalcification requested": { completed: "Special Stain Bone Decalcification Completed", id: 43 },
                            "Re-gross Requested": { completed: "Regross Completed", id: 7 },
                            "R/C requested": { completed: "R/C Completed", id: 49 }
                        };

                        // Filter the list based on the conditions
                        const inprogres_filteredDoctorInstructionList = inprogress_instruction_list.filter(item => {
                            const requestedStatus = item["Status Name"];
                            const labNumber = item["Lab Number"];

                            // Exclude sections and statuses
                            if (inprogres_excludedSections.includes(item["Section"]) || 
                                inprogres_excludedStatusNames.includes(item["Status Name"])) {
                                return false;
                            }

                            // Check if there is a matching completed status
                            if (statusPairsWithIds[requestedStatus]) {
                                const completedStatus = statusPairsWithIds[requestedStatus].completed;
                                const completedStatusFound = inprogress_instruction_list.some(
                                    i => i["Lab Number"] === labNumber && i["Status Name"] === completedStatus
                                );

                                // Exclude if both requested and completed statuses are found
                                return !completedStatusFound;
                            }

                            return true;
                        });

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        function inprogres_generateTableRows(data) {
                            return data.map(item => {
                                const requestedStatus = item["Status Name"];
                                const labNumber = item["Lab Number"];
                                const trackId = item["track_id"];  // Ensure this is correctly assigned

                                let options = \'<option value="">Select</option>\';

                                if (statusPairsWithIds[requestedStatus]) {
                                    const statusInfo = statusPairsWithIds[requestedStatus];
                                    options += `<option value="${statusInfo["id"]}">${statusInfo["completed"]}</option>`;
                                } else {
                                    options += `
                                        <option value="Done">Done</option>
                                    `;
                                }

                                return `
                                    <tr>
                                        <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                        <td>${item["Lab Number"]}</td>
                                        <td>${item["Description"]}</td>
                                        <td>${item["Status Name"]}</td>
                                        <td>${item["User Name"]}</td>
                                        <td>
                                            <select data-lab-number="${item["Lab Number"]}" data-track-id="${trackId}">
                                                ${options}
                                            </select>
                                        </td>
                                    </tr>
                                `;
                            }).join("");
                        }

                        // Track status changes
                        function inprogres_trackStatusChange(labNumber, status, trackId) {
                            inprogres_statusChanges[labNumber] = { status: status, trackId: trackId };
                        }

                        // Add event listeners to all select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const labNumber = this.getAttribute("data-lab-number");
                                    const trackId = this.getAttribute("data-track-id");
                                    const selectedValue = this.value;
                                    if (selectedValue) {
                                        inprogres_trackStatusChange(labNumber, selectedValue, trackId);
                                    } else {
                                        delete inprogres_statusChanges[labNumber];
                                    }
                                });
                            });
                        });

                        // Submit the status changes
                        document.getElementById("submitInprogressStatusChanges").addEventListener("click", function () {
                            const statusData = Object.keys(inprogres_statusChanges).map(labNumber => ({
                                labNumber: labNumber,
                                status: inprogres_statusChanges[labNumber].status,
                                trackId: inprogres_statusChanges[labNumber].trackId // Ensure this is included
                            }));

                            console.log("Status Data to Send:", statusData); // Log to verify data

                            fetch("complete_lab_instruction.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    statusChanges: statusData
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    inprogres_statusChanges = {}; // Clear the changes after successful save
                                    window.location.reload();
                                } else {
                                    alert(data.message || `Failed to save status changes.`);
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });

                        // Insert table rows into the table body
                        document.querySelector("#inprogres_InstructionTable tbody").innerHTML = inprogres_generateTableRows(inprogres_filteredDoctorInstructionList);
                    </script>
                </div>
                
                <div id="Completed" class="subtabcontent">
                        <table id="CompletedInstructionTable" border="1">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Lab Number</th>
                                    <th>Instruction</th>
                                    <th>User Name</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <!-- Pagination Controls -->
                        <div id="paginationControls"></div>

                        <script>
                            const completed_instruction_list = ' . json_encode($complete_instruction_list) . ';
                            

                            let completed_statusChanges = {};

                            // Pagination variables
                            const rowsPerPage = 20; // Number of rows per page
                            let currentPage = 1;
                            let totalPages = 0;

                            // Define the values to exclude
                            const completed_excludedSections = ["Gross", "Transcription", "Frontdesk", "Screening"];
                            const completed_excludedStatusNames = ["Special Stain others requested", "IHC-Block-Markers-requested", "R/C requested",
                                "Waiting - Study", "Waiting - Patient History / Investigation", "M/R/C requested", "Deeper Cut requested", "Serial Sections requested",
                                "Block D/C & R/C requested", "Special Stain AFB requested", "Special Stain GMS requested", "Special Stain PAS with Diastase requested",
                                "Special Stain PAS with Diastase requested", "Special Stain Brown-Brenn requested", "Special Stain Brown-Brenn requested", "Special Stain Congo-Red requested", 
                                "Special Stain Bone Decalcification requested", "Special Stain Fite Faraco requested",
                                "Re-gross Requested", "Screening Done", "Final Screening Start", "In-Progress", "Special Stain PAS requested", "Regross Completed"
                            ];

                            // Filter the list based on the conditions
                            const completed_filteredDoctorInstructionList = completed_instruction_list.filter(item =>
                                !completed_excludedSections.includes(item["Section"]) &&
                                !completed_excludedStatusNames.includes(item["Status Name"])
                            );
                            
                            // Calculate total pages
                            totalPages = Math.ceil(completed_filteredDoctorInstructionList.length / rowsPerPage);

                            // Function to format date and time
                            function formatDateTime(dateTimeStr) {
                                const date = new Date(dateTimeStr);
                                const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                                const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                                const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                                const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                                return `${formattedDate} ${formattedTime}`;
                            }

                            // Function to generate table rows with pagination
                            function completed_generateTableRows(data, page = 1) {
                                const start = (page - 1) * rowsPerPage;
                                const end = start + rowsPerPage;
                                const paginatedData = data.slice(start, end);

                                return paginatedData.map(item => `
                                    <tr>
                                        <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                        <td>${item["Lab Number"]}</td>
                                        <td>${item["Status Name"]}</td>
                                        <td>${item["User Name"]}</td>
                                    </tr>
                                `).join("");
                            }

                            // Function to render pagination controls
                            function renderPaginationControls() {
                                let controls = "";

                                for (let i = 1; i <= totalPages; i++) {
                                    controls += `<button class="pagination-btn" data-page="${i}">${i}</button>`;
                                }

                                document.getElementById("paginationControls").innerHTML = controls;

                                // Add event listeners to pagination buttons
                                document.querySelectorAll(".pagination-btn").forEach(button => {
                                    button.addEventListener("click", function() {
                                        currentPage = parseInt(this.getAttribute("data-page"));
                                        renderTable();
                                    });
                                });
                            }

                            // Function to render the table and pagination
                            function renderTable() {
                                document.querySelector("#CompletedInstructionTable tbody").innerHTML = completed_generateTableRows(completed_filteredDoctorInstructionList, currentPage);

                                // Add event listeners to all select elements after rows are inserted
                                document.querySelectorAll("select").forEach(selectElement => {
                                    selectElement.addEventListener("change", function() {
                                        trackStatusChange(this.getAttribute("data-lab-number"), this.value);
                                    });
                                });
                            }

                            // Initialize the table and pagination
                            renderTable();
                            renderPaginationControls();
                        </script>
                </div>
               
                <div id="OnHold" class="subtabcontent">
                   <table id="onHoldInstructionTable" border="1">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="onHoldsubmitStatusChanges" class="btn" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Date</th>
                                <th>Lab Number</th>
                                <th>Section</th>
                                <th>Instruction</th>
                                <th>Doctor Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <script>
                        const onHold_instruction_list = ' . json_encode($on_hold_instruction_list) . ';
                        

                        let onHoldstatusChanges = {};

                        // Define the values to exclude
                        const onHoldexcludedSections = ["Gross", "Transcription", "Frontdesk", "Screening"];
                        const onHoldexcludedStatusNames = ["Diagnosis Completed", "Final Screening Start", "Screening Done",
                            "Waiting - Study", "Waiting - Patient History / Investigation", "In-Progress", "On-Hold", "Slides Prepared",
                            "Regross Completed", "Recut or Special Stain Completed", "Regross Slides Prepared", "IHC-Block-Markers-completed",
                            "M/R/C Completed", "Deeper Cut Completed", "Serial Sections Completed", "Block D/C & R/C Completed", "Special Stain AFB Completed",
                            "Special Stain GMS Completed", "Special Stain PAS Completed", "Special Stain PAS with Diastase Completed", "Special Stain Fite Faraco Completed",
                            "Special Stain Brown-Brenn Completed", "Special Stain Congo-Red Completed", "Special Stain others Completed", 
                            "R/C Completed", "Re-gross Requested"
                        ];

                        // Filter the list based on the conditions
                        const onHoldfilteredDoctorInstructionList = onHold_instruction_list.filter(item =>
                            !onHoldexcludedSections.includes(item["Section"]) &&
                            !onHoldexcludedStatusNames.includes(item["Status Name"])
                        );

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        // Function to generate table rows
                        function onHoldgenerateTableRows(data) {
                            return data.map(item => `
                                <tr>
                                    <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                    <td>${item["Lab Number"]}</td>
                                    <td>${item["Description"]}</td>
                                    <td>${item["Status Name"]}</td>
                                    <td>${item["User Name"]}</td>
                                    <td>
                                        <select data-track-id="${item["track_id"]}" data-lab-number="${item["Lab Number"]}">
                                            <option value="">Select</option>
                                            <option value="In-Progress">In-Progress</option>
                                        </select>
                                    </td>
                                </tr>
                            `).join("");
                        }

                        // Track status changes with unique track_id
                        function onHoldtrackStatusChange(trackId, labNumber, status) {
                            onHoldstatusChanges[trackId] = { labNumber: labNumber, status: status };
                        }

                        // Add event listeners to all select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            document.querySelector("#onHoldInstructionTable tbody").innerHTML = onHoldgenerateTableRows(onHoldfilteredDoctorInstructionList);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    trackStatusChange(trackId, labNumber, this.value);
                                });
                            });
                        });

                        // Submit the status changes
                        document.getElementById("onHoldsubmitStatusChanges").addEventListener("click", function() {
                            fetch("on_hold_instructions.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    loggedInUserId: loggedInUserId,
                                    values: statusChanges
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log("Status changes saved successfully.");
                                    statusChanges = {}; // Clear the changes after successful save
                                    // Optionally, reload the page or refresh the table to reflect the changes
                                    window.location.reload();
                                } else {
                                    alert(data.message || "Failed to save status changes.");
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });
                    </script>
                </div>

              </div>
            </div>';

            //  Tab Content for Bones Related Instructions 
            echo '<div id="BoneRelatedInstructions" class="tabcontent">';
                echo('<div class="sub-tabs">
                    <div class="sub-tab-links">
                    <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'BoneList\')">
                    <i class="fas fa-list" style="font-size: 25px;"></i><b>&nbspList</b></button>
                    <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'BoneCompleted\')">
                    <i class="fas fa-check-circle" style="font-size: 35px;"></i>Done</button>
                    </div>');
                        echo('<div id="BoneList" class="subtabcontent">');
                            // Check if the list has any data
                            if (!empty($bones_list)) {
                                // Create a table to display the bones list

                                echo '<input type="text" id="searchInput" placeholder="Search..." class="form-control" style="margin-bottom: 10px;">';

                                echo '<form id="updateStatusForm" >';
                                echo '<table class="table" border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">';
                                echo '<thead>';
                                echo '<tr>';
                                echo '<th>Lab Number</th>';
                                echo '<th>Doctor Name</th>';
                                echo '<th>Assistant Name</th>';
                                echo '<th>Section Code</th>';
                                echo '<th>Cassettes Numbers</th>';
                                echo '<th>Tissue</th>';
                                echo '<th>Decalcified</th>';
                                echo '<th>Slide Block Need</th>';
                                echo '<th>Status</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';
                            
                                // Loop through the bones list and populate the table
                                foreach ($bones_list as $bone) {
                                    echo '<tr data-labnumber="' . htmlspecialchars($bone['lab_number']) . '" data-statusname="' . htmlspecialchars($bone['status_name']) . '">';
                                    echo '<td>' . htmlspecialchars($bone['lab_number']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['gross_doctor_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['gross_assistant_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['section_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['cassettes_numbers']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['tissue']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['decalcified_bone']) . '</td>';
                                    echo '<td>' . htmlspecialchars($bone['requires_slide_for_block']) . '</td>';
                                    echo '<input type="hidden" name="id[]" value="' . htmlspecialchars($bone['id']) . '">';
                                
                                    // Create a choice field for Status
                                    echo '<td>';
                                    echo '<select name="status[]" >';
                                    echo '<option value=""' . ($bone['status_name'] == '' ? ' selected' : '') . '></option>';
                                    echo '<option value="Bones Ready"' . ($bone['status_name'] == 'Bones Ready' ? ' selected' : '') . '>Bones Slide Ready</option>';
                                    echo '</select>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            
                                echo '</tbody>';
                                echo '</table>';
                                echo '<button type="submit">Submit</button>'; // Submit button for the form
                                echo '</form>';
                            } else {
                                echo '<p>No bones are pending or not ready at the moment.</p>';
                            }
                        echo '</div>';
                        // Number of records per page
                        $recordsPerPage = 40;

                        // Total number of records
                        $totalRecords = count($bone_slide_ready_list);

                        // Calculate the total number of pages
                        $totalPages = ceil($totalRecords / $recordsPerPage);

                        // Get the current page number from URL, default to page 1
                        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

                        // Calculate the starting index for the query
                        $startIndex = ($currentPage - 1) * $recordsPerPage;

                        // Slice the array to get only the records for the current page
                        $pageRecords = array_slice($bone_slide_ready_list, $startIndex, $recordsPerPage);

                        // HTML for pagination
                        echo('<div id="BoneCompleted" class="subtabcontent">');
                                    // Search input field
                                    echo '<input type="text" id="boneSlideSearchInput" style="margin-bottom: 10px;" class="form-control mb-3" placeholder="Search by Lab Number, User Name, or Date Time...">';

                                        // Check if there is data to display
                                        if (!empty($pageRecords)) {
                                            // Start the Bootstrap table
                                            echo '<table id="boneSlideTable" class="table table-bordered table-striped">';
                                            echo '<thead>';
                                            echo '<tr>';
                                            echo '<th>Lab Number</th>';
                                            echo '<th>User Name</th>';
                                            echo '<th>Date Time</th>';
                                            echo '<th>Status Name</th>';
                                            echo '<th>Section</th>';
                                            echo '</tr>';
                                            echo '</thead>';
                                            echo '<tbody>';

                                            // Loop through the data and create table rows
                                            foreach ($pageRecords as $data) {
                                                $dateTime = new DateTime($data['TrackCreateTime'], new DateTimeZone('UTC')); // Assuming the original time is in UTC
                                                $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka')); // Convert to Asia/Dhaka timezone
                                                $formattedDate = $dateTime->format('j F, Y g:i A'); 
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($data['Lab Number']) . '</td>';
                                                echo '<td>' . htmlspecialchars($data['User Name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($formattedDate) . '</td>';
                                                echo '<td>' . htmlspecialchars($data['Status Name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($data['Section']) . '</td>';
                                                echo '</tr>';
                                            }

                                            echo '</tbody>';
                                            echo '</table>';
                                           
                                            // Pagination controls
                                            echo '<div class="pagination">';
                                            // Previous Button
                                            if ($currentPage > 1) {
                                                echo '<a href="?page=' . ($currentPage - 1) . '" class="prev-next">Prev</a>';
                                            }

                                            // Display page numbers
                                            for ($page = 1; $page <= $totalPages; $page++) {
                                                if ($page == $currentPage) {
                                                    echo '<span class="current-page">' . $page . '</span>';
                                                } else {
                                                    echo '<a href="?page=' . $page . '">' . $page . '</a>';
                                                }
                                            }

                                            // Next Button
                                            if ($currentPage < $totalPages) {
                                                echo '<a href="?page=' . ($currentPage + 1) . '" class="prev-next">Next</a>';
                                            }

                                            echo '</div>';
                                        } else {
                                            // Display a message if no data is available
                                            echo '<p id="noResultsMessage" style="display: none; color: red;">No data found.</p>';
                                        }

                        echo '</div>';
                echo '</div>';
            echo '</div>';

            
            echo '<div id="SBO" class="tabcontent">
            <!-- Sub-tab Links -->
            <div class="sub-tabs">
            <div class="sub-tab-links">
            <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'SBOList\')">
            <i class="fas fa-list" style="font-size: 25px;"></i><b>&nbspList</b>
            </button>
            <button style="border:none" class="sub-tablink" onclick="openSubTab(event, \'SBOCompleted\')">
            <i class="fas fa-check-circle" style="font-size: 35px;"></i>Done
            </button>
            </div>
            </div>';

    echo '<!-- Sub-tab contents -->
    <div id="SBOList" class="subtabcontent">';

    if (!empty($sbo_list)) {
    // Add form to submit selected statuses
    echo '<form action="sbo_status.php" method="POST">
    <input type="hidden" name="loggedInUserId" value="' . htmlspecialchars($loggedInUserId) . '">
    <table border="1" cellpadding="2">
    <thead>
    <tr>
    <th>SBO Lab Number</th>
    <th>Original Lab Number and Descriptions</th>
    <th>Delivery Date</th>
    <th>Status</th>
    </tr>
    </thead>
    <tbody>';

    foreach ($sbo_list as $order) {
    // Create a DateTime object and set the timezone
    $dateLivraison = new DateTime($order["date_livraison"]);
    $dateLivraison->setTimezone(new DateTimeZone('Asia/Dhaka'));
    echo '<tr>
    <td>' . htmlspecialchars($order["ref"]) . '</td>
    <td>' . htmlspecialchars($order["description"]) . '</td> 
    <td>' . $dateLivraison->format('j F, Y g:i a') . '</td>
    <td>
    <select name="status[' . htmlspecialchars($order["ref"]) . ']">
    <option value=""></option>
    <option value="51">SBO Ready</option>
    </select>
    </td>
    </tr>';
    }

    echo ' </tbody>
    </table>
    <div style="display: flex; justify-content: flex-end;">
    <input type="submit" value="Submit" class="btn" style="margin-bottom: 10px;">
    </div>
    </form>';
    } else {
    echo '<p>No Slide Block Orders available.</p>';
    }
    echo '</div>'; // Close List of SBO subtab content
    echo '
    <div id="SBOCompleted" class="subtabcontent">';
    if (!empty($sbo_complete_list)) {
    // Add form to submit selected statuses
    echo '
    <table border="1" cellpadding="1">
    <thead>
    <tr>
    <th>SBO Lab Number</th>
    <th>Status</th>
    <th>User Name</th>
    <th>Date</th>
    </tr>
    </thead>
    <tbody>';

    foreach ($sbo_complete_list as $order) {
    // Create a DateTime object and set the timezone
    $dateCreateTime = new DateTime($order["create_time"]);
    $dateCreateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
    echo '<tr>
    <td>' . htmlspecialchars($order["labno"]) . '</td>
    <td>' . htmlspecialchars($order["status_name"]) . '</td> 
    <td>' . htmlspecialchars($order["user_name"]) . '</td> 
    <td>' . $dateCreateTime->format('j F, Y g:i a') . '</td>
    </tr>';
    }

    echo ' </tbody>
    </table>
    ';
    } else {
    echo '<p>No Slide Block Orders available.</p>';
    }
    echo '</div>'; // Close complete List of SBO subtab content
    echo '</div>'; // Close tabcontent

    echo('
        <div id="Re-Gross" class="tabcontent">
            <div class="container">
                <input type="date" id="regrossfromDateTime" class="input-field" placeholder="From">
                <input type="date" id="regrosstoDateTime" class="input-field" placeholder="To">
                <button id="regrosssubmitBtn" class="btn">Submit</button>&nbsp;&nbsp;
                <button id="regrossgeneratePdfBtn" class="btn">Generate PDF</button>
            </div>
            
            
                <!-- Table content goes here -->
                <table id="histoReGrossTable">
                        
                </table>
           

            <input type="hidden" id="loggedInUsername" value="' . htmlspecialchars($loggedInUsername) . '">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
            <!-- jsPDF AutoTable plugin -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

        <div>

        <style>
                #histoReGrossTable {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-family: Arial, sans-serif;
                }

                #histoReGrossTable th,
                #histoReGrossTable td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                    font-size: 14px;
                }

                #histoReGrossTable th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }

                #histoReGrossTable tr:nth-child(even) {
                    background-color: #f9f9f9;
                }

                #histoReGrossTable tr:hover {
                    background-color: #e9e9e9;
                }

                .table-container {
                    overflow-x: auto;
                }
        </style>

        <script>
            document.getElementById("regrosssubmitBtn").addEventListener("click", function () {
                const startDate = document.getElementById("regrossfromDateTime").value;
                const endDate = document.getElementById("regrosstoDateTime").value;
                console.log("Sending dates:", startDate, endDate);

                if (!startDate || !endDate) {
                    alert("Please select both start and end dates.");
                    return;
                }

                fetch("regross_data_fetch.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": \'application/json\'
                    },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Received data:", data);
                    const tbody = document.getElementById(\'histoReGrossTable\');
                    tbody.innerHTML = ""; // Clear previous rows

                    if (data.length === 0) {
                        tbody.innerHTML = \'<tr><td colspan="6">No data found.</td></tr>\';
                        return;
                    }

                    const seenLabnos = new Set();

                    const formatDate = (datetimeString) => {
                        const date = new Date(datetimeString);
                        return new Intl.DateTimeFormat(\'en-GB\', {
                            timeZone: \'Asia/Dhaka\',
                            day: \'2-digit\',
                            month: \'long\',
                            year: \'numeric\',
                            hour: \'numeric\',
                            minute: \'2-digit\',
                            hour12: true
                        }).format(date);
                    };

                    data.forEach(row => {
                        const tr = document.createElement(\'tr\');

                        const showLabno = !seenLabnos.has(row.labno);
                        if (showLabno) {
                            seenLabnos.add(row.labno);
                        }

                        tr.innerHTML = `
                            <td>${showLabno ? row.labno : ""}</td>
                            <td>Cassettes:${row.cassettes_numbers}</td>
                            <td>Tissue:${row.tissue}</td>
                            <td>Bone:${row.bone}</td>
                            <td>BoneSlide:${row.boneslide}</td>
                            <td>Re-Gross:${row.re_gross}</td>
                            <td>Re-Gross Date: ${formatDate(row.create_time)}</td>
                            <td>UserName:${row.username}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(error => {
                    console.error(\'Error:\', error);
                    alert(\'Failed to fetch regross data.\');
                });
            });
          
            document.getElementById("regrossgeneratePdfBtn").addEventListener("click", function () {
                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF(\'p\', \'pt\', \'a4\');
                    const marginLeft = 40;
                    let currentY = 40;
                    let lastLabNo = "";

                    doc.setFontSize(14);
                    doc.setFont("helvetica", "bold");
                    doc.text("Re-Gross Report", marginLeft, currentY);
                    currentY += 20;

                    const table = document.getElementById(\'histoReGrossTable\');
                    const rows = table.querySelectorAll(\'tr\');

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(10);

                    rows.forEach(row => {
                        const cells = row.querySelectorAll("td");

                        const clean = (value) => value.replace(/^.*?:\s*/, "").trim(); // Remove existing label if any

                        const labNoRaw = cells[0] ? cells[0].innerText.trim() : "";
                        const labNo = labNoRaw && !/^no|null|undefined$/i.test(labNoRaw) ? labNoRaw : "";
                        const cassettes = cells[1] ? clean(cells[1].innerText) : "";
                        const tissue = cells[2] ? clean(cells[2].innerText) : "";
                        const bone = cells[3] ? clean(cells[3].innerText) : "";
                        const boneSlide = cells[4] ? clean(cells[4].innerText) : "";

                        let parts = [];

                        if (cassettes && !/^no|null|undefined$/i.test(cassettes)) {
                            parts.push(`Cassettes:${cassettes}`);
                        }
                        if (tissue && !/^no|null|undefined$/i.test(tissue)) {
                            parts.push(`Tissue:${tissue}`);
                        }
                        if (bone && !/^no|null|undefined$/i.test(bone)) {
                            parts.push(`Bone:${bone}`);
                        }
                        if (boneSlide && !/^no|null|undefined$/i.test(boneSlide)) {
                            parts.push(`BoneSlide:${boneSlide}`);
                        }

                        if (labNo) {
                            // New lab no block
                            lastLabNo = labNo;
                            if (currentY > 780) {
                                doc.addPage();
                                currentY = 40;
                            }
                            const line = `LabNo: ${labNo}` + (parts.length ? `, ${parts.join(", ")}` : "");
                            const splitText = doc.splitTextToSize(line, 520);
                            doc.text(splitText, marginLeft, currentY);
                            currentY += splitText.length * 14;
                        } else if (parts.length) {
                            // Same lab no, indented
                            if (currentY > 780) {
                                doc.addPage();
                                currentY = 40;
                            }
                            const line = `     ${parts.join(", ")}`;
                            const splitText = doc.splitTextToSize(line, 520);
                            doc.text(splitText, marginLeft, currentY);
                            currentY += splitText.length * 14;
                        }

                        // Skip row if no parts and no lab no
                    });

                    const now = new Date().toISOString().split("T")[0];
                    doc.save(`re-gross-report-${now}.pdf`);
                } catch (error) {
                    console.error("Error generating PDF:", error);
                }
            });
        </script>
        '
    );

echo  '</div>';


print '</div><div class="fichetwothirdright">';

print '</div><div class="fichetwothirdright">';


$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

print '</div></div>';
// End of page
llxFooter();
$db->close();

?>

<link rel="stylesheet" href="../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css">

<script>
    // Function to open the main tab
    function openTab(evt, tabName) {
        var i, tabcontent, tablink;

        // Hide all main tab contents
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Remove "active" class from all main tab links
        tablink = document.getElementsByClassName("tablink");
        for (i = 0; i < tablink.length; i++) {
            tablink[i].className = tablink[i].className.replace(" active", "");
        }

        // Show the clicked main tab content
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";

        // Store the active main tab in session storage
        sessionStorage.setItem("activeTab", tabName);
    }

    // Function to open the sub-tab
    function openSubTab(evt, subTabName) {
        var i, subtabcontent, subtablink;

        // Hide all sub-tab contents
        subtabcontent = document.getElementsByClassName("subtabcontent");
        for (i = 0; i < subtabcontent.length; i++) {
            subtabcontent[i].style.display = "none";
        }

        // Remove "active" class from all sub-tab links
        subtablink = document.getElementsByClassName("sub-tablink");
        for (i = 0; i < subtablink.length; i++) {
            subtablink[i].className = subtablink[i].className.replace(" active", "");
        }

        // Show the clicked sub-tab content
        document.getElementById(subTabName).style.display = "block";
        evt.currentTarget.className += " active";

        // Store the active sub-tab in session storage
        sessionStorage.setItem("activeSubTab", subTabName);
    }

    // Get the active tab and sub-tab from session storage and open them
    window.onload = function() {
        // Restore main tab
        var activeTab = sessionStorage.getItem("activeTab");
        if (activeTab) {
            document.getElementById(activeTab).style.display = "block";
            var tabButtons = document.getElementsByClassName("tablink");
            for (var i = 0; i < tabButtons.length; i++) {
                if (tabButtons[i].getAttribute("onclick").includes(activeTab)) {
                    tabButtons[i].className += " active";
                    break;
                }
            }

            // Restore sub-tab for the active main tab
            var activeSubTab = sessionStorage.getItem("activeSubTab");
            if (activeSubTab) {
                document.getElementById(activeSubTab).style.display = "block";
                var subTabButtons = document.getElementsByClassName("sub-tablink");
                for (var i = 0; i < subTabButtons.length; i++) {
                    if (subTabButtons[i].getAttribute("onclick").includes(activeSubTab)) {
                        subTabButtons[i].className += " active";
                        break;
                    }
                }
            }
        } else {
            // No active tab, so do not display any tab as active
            var tabButtons = document.getElementsByClassName("tablink");
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }
        }
    }
</script>

<!-- <script>
      function openTab(evt, tabName) {
        var i, tabcontent, tablink;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
          tabcontent[i].style.display = "none";
        }
        tablink = document.getElementsByClassName("tablink");
        for (i = 0; i < tablink.length; i++) {
          tablink[i].className = tablink[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";

        // Store the active main tab in session storage
        sessionStorage.setItem("activeTab", tabName);
      }

      function openSubTab(evt, subTabName) {
        var i, subtabcontent, subtablink;
        subtabcontent = document.getElementsByClassName("subtabcontent");
        for (i = 0; i < subtabcontent.length; i++) {
            subtabcontent[i].style.display = "none";
        }
        subtablink = document.getElementsByClassName("sub-tablink");
        for (i = 0; i < subtablink.length; i++) {
            subtablink[i].className = subtablink[i].className.replace(" active", "");
        }
        document.getElementById(subTabName).style.display = "block";
        evt.currentTarget.className += " active";

        // Store the active sub-tab in session storage
        sessionStorage.setItem("activeSubTab", subTabName);
      }

      // Set the default tab to be open
      document.getElementsByClassName("tablink")[0].click();

      // Set the default sub-tab to be open
     // Get the active tab and sub-tab from session storage and open them
        window.onload = function() {
        // Restore main tab
        var activeTab = sessionStorage.getItem("activeTab");
        if (activeTab) {
            document.getElementById(activeTab).style.display = "block";
            var tabButtons = document.getElementsByClassName("tablink");
            for (var i = 0; i < tabButtons.length; i++) {
                if (tabButtons[i].getAttribute("onclick").includes(activeTab)) {
                    tabButtons[i].className += " active";
                    break;
                }
            }

            // Restore sub-tab for the active main tab
            var activeSubTab = sessionStorage.getItem("activeSubTab");
            if (activeSubTab) {
                document.getElementById(activeSubTab).style.display = "block";
                var subTabButtons = document.getElementsByClassName("sub-tablink");
                for (var i = 0; i < subTabButtons.length; i++) {
                    if (subTabButtons[i].getAttribute("onclick").includes(activeSubTab)) {
                        subTabButtons[i].className += " active";
                        break;
                    }
                }
            }
        } else {
            // Default to the first main tab if no tab is stored
            document.getElementsByClassName("tablink")[0].click();
        }
    }

</script> -->


<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        // PHP variable passed to JavaScript
        const userId = '<?php echo $loggedInUserId; ?>';

        // Select the form and listen for the submit event
        const form = document.getElementById('updateStatusForm');
        
        form.addEventListener('submit', (e) => {
            // Prevent the default form submission
            e.preventDefault();
            
            // Create an array to store the lab numbers and status
            const boneStatusData = [];
            
            // Select all table rows inside the BoneRelatedInstructions div
            const rows = document.querySelectorAll('#BoneRelatedInstructions tbody tr');
            
            // Loop through each row
            rows.forEach(row => {
                // Get the labnumber from the row's data attribute
                let labnumber = row.getAttribute('data-labnumber');
                
                // Remove the first 3 characters if labnumber exists
                if (labnumber) {
                    labnumber = labnumber.substring(3); // Removes the first 3 characters
                }
                
                // Get the selected status value from the dropdown
                const statusSelect = row.querySelector('select[name="status[]"]');
                const statusName = statusSelect.value;
                
                // Only add the data if statusName is selected
                if (labnumber && statusName) {
                    boneStatusData.push({
                        labnumber: labnumber,
                        status: statusName,
                        user_id: userId // Include the loggedInUserId in the data
                    });
                }
            });

            // Check if there is any data to send
            if (boneStatusData.length > 0) {
                // Send the data to update_bone_status.php via AJAX using fetch
                fetch('bones/update_bone_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(boneStatusData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response from server:', data);
                    // Optionally, handle success messages or updates to the UI here
                    // alert('Statuses updated successfully!');
                    // location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the statuses.');
                });
            } else {
                alert('No lab numbers with statuses selected.');
            }
        });
    });
</script> -->



<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        const userId = '<?php echo $loggedInUserId; ?>';
        const form = document.getElementById('updateStatusForm');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const boneStatusData = [];
            const rows = document.querySelectorAll('#BoneRelatedInstructions tbody tr');
            
            rows.forEach(row => {
                let labnumber = row.getAttribute('data-labnumber');
                
                if (labnumber) {
                    labnumber = labnumber.substring(3); // Removes the first 3 characters
                }
                
                const statusSelect = row.querySelector('select[name="status[]"]');
                const statusName = statusSelect.value;
                const id = row.querySelector('input[name="id[]"]').value; // Get the hidden id field value
                
                if (labnumber && statusName && id) {
                    boneStatusData.push({
                        labnumber: labnumber,
                        status: statusName,
                        user_id: userId,
                        id: id // Add id to the payload
                    });
                }
            });

            if (boneStatusData.length > 0) {
                fetch('bones/update_bone_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(boneStatusData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response from server:', data);

                    if (data.status === 'success') {
                        alert('Statuses updated successfully!');
                        location.reload(); // Optionally reload the page to reflect changes
                    } else {
                        alert('Failed to update statuses: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the statuses.');
                });
            } else {
                alert('No lab numbers with statuses selected.');
            }
        });
    });
</script> -->


<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
    const userId = '<?php echo $loggedInUserId; ?>';
    const form = document.getElementById('updateStatusForm');

        if (form) {  // Check if the form exists
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const boneStatusData = [];
                const rows = document.querySelectorAll('#BoneRelatedInstructions tbody tr');
                
                rows.forEach(row => {
                    let labnumber = row.getAttribute('data-labnumber');
                    
                    if (labnumber) {
                        labnumber = labnumber.substring(3); // Removes the first 3 characters
                    }
                    
                    const statusSelect = row.querySelector('select[name="status[]"]');
                    const statusName = statusSelect.value;
                    const id = row.querySelector('input[name="id[]"]').value; // Get the hidden id field value
                    
                    if (labnumber && statusName && id) {
                        boneStatusData.push({
                            labnumber: labnumber,
                            status: statusName,
                            user_id: userId,
                            id: id // Add id to the payload
                        });
                    }
                });

                if (boneStatusData.length > 0) {
                    fetch('bones/update_bone_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(boneStatusData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Response from server:', data);

                        if (data.status === 'success') {
                            alert('Statuses updated successfully!');
                            location.reload(); // Optionally reload the page to reflect changes
                        } else {
                            alert('Failed to update statuses: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the statuses.');
                    });
                } else {
                    alert('No lab numbers with statuses selected.');
                }
            });
        } else {
            console.warn("Form with ID 'updateStatusForm' not found.");
        }
    });
</script> -->

<script>
    document.addEventListener('DOMContentLoaded', () => {
            const userId = '<?php echo $loggedInUserId; ?>';
            const form = document.getElementById('updateStatusForm');

            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    const boneStatusData = [];
                    const rows = document.querySelectorAll('#BoneList tbody tr'); // Fixed the table ID

                    rows.forEach(row => {
                        let labnumber = row.getAttribute('data-labnumber');
                        
                        if (labnumber) {
                            labnumber = labnumber.substring(3); // Removes the first 3 characters
                        }
                        
                        const statusSelect = row.querySelector('select[name="status[]"]');
                        const statusName = statusSelect.value;
                        const id = row.querySelector('input[name="id[]"]').value; // Get the hidden id field value
                        
                        if (labnumber && statusName && id) {
                            boneStatusData.push({
                                labnumber: labnumber,
                                status: statusName,
                                user_id: userId,
                                id: id // Add id to the payload
                            });
                        }
                    });

                    if (boneStatusData.length > 0) {
                        fetch('bones/update_bone_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(boneStatusData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Response from server:', data);

                            if (data.status === 'success') {
                                alert('Statuses updated successfully!');
                                location.reload(); // Optionally reload the page to reflect changes
                            } else {
                                alert('Failed to update statuses: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while updating the statuses.');
                        });
                    } else {
                        alert('No lab numbers with statuses selected.');
                    }
                });
            } else {
                console.warn("Form with ID 'updateStatusForm' not found.");
            }
    });
</script>

<script>
  // JavaScript for search functionality
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#BoneRelatedInstructions tbody tr');
    let found = false;

    rows.forEach(row => {
      const labNumber = row.cells[0].textContent.toLowerCase();
      const doctorName = row.cells[1].textContent.toLowerCase();
      const assistantName = row.cells[2].textContent.toLowerCase();
      const sectionCode = row.cells[3].textContent.toLowerCase();
      const cassettesNumbers = row.cells[4].textContent.toLowerCase();
      const tissue = row.cells[5].textContent.toLowerCase();

      // Check if any cell in the row matches the search filter
      if (labNumber.includes(filter) || doctorName.includes(filter) || assistantName.includes(filter) ||
          sectionCode.includes(filter) || cassettesNumbers.includes(filter) || tissue.includes(filter)) {
        row.style.display = '';
        found = true;
      } else {
        row.style.display = 'none';
      }
    });
  });
</script>


<style>
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 10px;
        gap: 5px; /* Space between pagination elements */
    }

    .pagination a, .pagination span {
        padding: 5px 10px;
        text-decoration: none;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        margin: 0 2px;
    }

    .pagination a:hover {
        background-color: #ddd;
    }

    .pagination .current-page {
        font-weight: bold;
        background-color: #007bff;
        color: white;
        border: 1px solid #007bff;
    }

    .pagination .prev-next {
        font-weight: bold;
        background-color: #f0f0f0;
        color: #007bff;
    }

    .pagination a, .pagination .prev-next {
        border-radius: 5px;
    }

    .pagination a:hover, .pagination .prev-next:hover {
        background-color: #ddd;
    }

</style>

<!-- JavaScript for Search Functionality -->
<script>
    // JavaScript for search functionality
    document.getElementById('boneSlideSearchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#boneSlideTable tbody tr');
        let matchFound = false;

        rows.forEach(row => {
            // Extract text from each cell and convert to lowercase
            const labNumber = row.cells[0].textContent.toLowerCase();
            const userName = row.cells[1].textContent.toLowerCase();
            const dateTime = row.cells[2].textContent.toLowerCase();
            const statusName = row.cells[3].textContent.toLowerCase();
            const section = row.cells[4].textContent.toLowerCase();

            // Check if any cell in the row matches the search filter
            if (
                labNumber.includes(filter) || 
                userName.includes(filter) || 
                dateTime.includes(filter) || 
                statusName.includes(filter) || 
                section.includes(filter)
            ) {
                row.style.display = ''; // Show the row
                matchFound = true; // Set matchFound to true
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });
    
        // Show or hide the "no results" message based on whether a match was found
        const noResultsMessage = document.getElementById('noResultsMessage');
        if (noResultsMessage) {
            noResultsMessage.style.display = matchFound ? 'none' : 'block';
        }
    });
</script>