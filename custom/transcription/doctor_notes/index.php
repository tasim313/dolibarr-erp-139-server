<?php 
include('connection.php');
include('common_function.php');

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


$title = $langs->trans("Transcription");
$help_url = '';
llxHeader('', $title, $help_url);
$loggedInUserId = $user->id;

$loggedInUsername = $user->login;

$doctor_instruction_list = get_histo_doctor_instruction_list();
$progress_instruction_list = get_histo_doctor_instruction_progress_list();
$complete_instruction_list = get_histo_doctor_instruction_done_list();
$on_hold_instruction_list = get_histo_doctor_instruction_on_hold_list();
$case_summary_list = get_histo_case_summary_list();

// Time complexity check

// function measure_execution_time($function_name) {
//     $start_time = microtime(true); // Start time
//     $result = call_user_func($function_name); // Call the function
//     $end_time = microtime(true); // End time
    
//     $execution_time = $end_time - $start_time;
//     echo "Execution time of {$function_name}: " . $execution_time . " seconds\n";
    
//     return $result;
// }

// // Measure execution times
// $doctor_instruction_list = measure_execution_time('get_histo_doctor_instruction_list');
// $progress_instruction_list = measure_execution_time('get_histo_doctor_instruction_progress_list');
// $complete_instruction_list = measure_execution_time('get_histo_doctor_instruction_done_list');
// $on_hold_instruction_list = measure_execution_time('get_histo_doctor_instruction_on_hold_list');
// $case_summary_list = measure_execution_time('get_histo_case_summary_list');


function filter_data($data) {
    $filtered_data = [];

    foreach ($data as $row) {
        if ($row['status_name'] === 'Screening Done') {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && 
                    in_array($inner_row['status_name'], ['Finalized', 'Diagnosis Completed', 'Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $filtered_data[] = $row;
            }
        }
    }

    return $filtered_data;
}

$filtered_data = filter_data($case_summary_list);



function finalized_filter_data($data) {
    $finalized_filtered_data = [];

    foreach ($data as $row) {
        if ($row['status_name'] === 'Finalized') {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && 
                    in_array($inner_row['status_name'], ['Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $finalized_filtered_data[] = $row;
            }
        }
    }

    return $finalized_filtered_data;
}

$finalized_filtered_data = finalized_filter_data($case_summary_list);


function unseen_filter_data($data) {
    $unseen_filtered_data = [];

    foreach ($data as $row) {
        // Check if the status name is one of the specified ones
        if (in_array($row['status_name'], ['Gross Completed', 'Gross Entry Done', 'Slides Prepared'])) {
            // Check if the Lab Number should be excluded
            $exclude_lab_number = false;

            foreach ($data as $inner_row) {
                if ($inner_row['labno'] === $row['labno'] && in_array($inner_row['status_name'], ['Screening Done', 'Finalized', 'Diagnosis Completed', 'Report Ready', 'Report Delivered'])) {
                    $exclude_lab_number = true;
                    break;
                }
            }

            if (!$exclude_lab_number) {
                $unseen_filtered_data[] = $row;
            }
        }
    }

    return $unseen_filtered_data;
}

// Assuming $case_summary_list is your dataset
$unseen_filtered_data = unseen_filter_data($case_summary_list);

print('<link href="../bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">');
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
    border: 1px solid #ccc;
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
    border: 1px solid #ccc;
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
          <button style="border:none" class="tablink btn btn-primary btn-lg" onclick="openTab(event, \'DoctorRelatedInstructions\')">
           <i class="fas fa-user-md" style="font-size: 35px;"></i>Doctor Notes</button>
           <button style="border:none" class="tablink btn-success btn-lg" onclick="openTab(event, \'CaseStatus\')">
           <i class="fas fas fa-bell" style="font-size: 35px;"></i>Case Status</button>
           <button style="border:none" class="tablink btn btn-info btn-lg" onclick="openTab(event, \'ReportCompleteStatus\')">
           <i class="fas fa-check-circle" style="font-size: 35px;"></i>Report Complete Status</button>
        </div>

        <!-- Tab Content for Doctor Related Instructions -->
        <div id="DoctorRelatedInstructions" class="tabcontent">
              <!-- Sub-tab Links -->
              <div class="sub-tabs">
                <div class="sub-tab-links">
                  <button style="border:none" class="sub-tablink btn-info" onclick="openSubTab(event, \'List\')">
                  <i class="fas fa-list" style="font-size: 25px;"></i><b>&nbspList</b></button>
                  <button style="border:none" class="sub-tablink btn-success" onclick="openSubTab(event, \'InProgress\')">
                  <i class="fas fa-spinner" style="font-size: 35px;"></i>In Progress</button>
                  <button style="border:none" class="sub-tablink btn-secondary" onclick="openSubTab(event, \'Completed\')">
                  <i class="fas fa-check-circle" style="font-size: 35px;"></i>Done</button>
                  <button style="border:none" class="sub-tablink btn btn-warning" onclick="openSubTab(event, \'OnHold\')">
                    <i class="fas fa-pause-circle" style="font-size: 35px;"></i>On Hold
                  </button>
                </div>
                
                <!-- Sub-tab contents -->
                <div id="List" class="subtabcontent">
                    <table id="doctorInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="submitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Lab Number</th>
                                <th>Instructions</th>
                                <th>Patient History</th>
                                <th>Doctor Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <script>
                        const doctorInstructionList = ' . json_encode($doctor_instruction_list) . ';
                        console.log("doctor Instructions : ", doctorInstructionList);
                        const loggedInUserId = ' . json_encode($loggedInUserId) . ';

                        let statusChanges = {};

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        function groupByLabNumber(data) {
                            const groupedData = {};
                            data.forEach(item => {
                                const labNumber = item["Lab Number"];
                                if (!groupedData[labNumber]) {
                                    groupedData[labNumber] = [];
                                }
                                groupedData[labNumber].push(item);
                            });
                            return groupedData;
                        }

                        // Function to filter and display the data
                        function generateFilteredTableRows(groupedData) {
                            let rows = "";
                            Object.keys(groupedData).forEach(labNumber => {
                                const descriptions = groupedData[labNumber].map(item => item["Description"]);

                                // If "Transcription" is found, show all descriptions for that lab number
                                if (descriptions.includes("Transcription")) {
                                    groupedData[labNumber].forEach(item => {
                                        if (!["IT Space", "4", "Self", "Transcription"].includes(item["Description"])) {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="In-Progress">In-Progress</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        }
                                    });
                                } 
                                // Filter out descriptions that include "IT Space," "4," or "Self"
                                else {
                                    groupedData[labNumber].forEach(item => {
                                        if (!["IT Space", "4", "Self"].includes(item["Description"])) {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="In-Progress">In-Progress</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        }
                                    });
                                }
                            });
                            return rows;
                        }

                        // Define the trackStatusChange function
                        function trackStatusChange(trackId, labNumber, statusValue) {
                            if (!statusValue) {
                                delete statusChanges[trackId];
                            } else {
                                statusChanges[trackId] = {
                                    labNumber: labNumber,
                                    status: statusValue
                                };
                            }

                            console.log("Status Changes:", statusChanges); // For debugging
                        }


                       // Add the event listener for the select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            const groupedData = groupByLabNumber(doctorInstructionList);
                            document.querySelector("#doctorInstructionTable tbody").innerHTML = generateFilteredTableRows(groupedData);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    const statusValue = this.value;
                                    trackStatusChange(trackId, labNumber, statusValue);
                                });
                            });
                        });


                        // Submit the status changes
                        document.getElementById("submitStatusChanges").addEventListener("click", function() {
                            fetch("patient_history_status.php", {
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
                     <table id="progressInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="progress_submitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
                            </div>
                            <tr>
                                <th>Lab Number</th>
                                <th>Instructions</th>
                                <th>Patient History</th>
                                <th>Doctor Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <script>
                        const progressInstructionList = ' . json_encode($progress_instruction_list) . ';
                        console.log("progress Instructions : ", progressInstructionList);
                        

                        let progress_statusChanges = {};

                        // Function to format date and time
                        function formatDateTime(dateTimeStr) {
                            const date = new Date(dateTimeStr);
                            const optionsDate = { day: "numeric", month: "long", year: "numeric" };
                            const optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
                            const formattedDate = date.toLocaleDateString("en-GB", optionsDate);
                            const formattedTime = date.toLocaleTimeString("en-GB", optionsTime);
                            return `${formattedDate} ${formattedTime}`;
                        }

                        function groupByLabNumber(data) {
                            const groupedData = {};
                            data.forEach(item => {
                                const labNumber = item["Lab Number"];
                                if (!groupedData[labNumber]) {
                                    groupedData[labNumber] = [];
                                }
                                groupedData[labNumber].push(item);
                            });
                            return groupedData;
                        }

                        // Function to filter and display the data
                        function progress_generateFilteredTableRows(groupedData) {
                                let rows = "";
                                Object.keys(groupedData).forEach(labNumber => {
                                    const descriptions = groupedData[labNumber].map(item => item["Description"]);
                                    
                                    // If "Transcription" is found, show all descriptions for that lab number
                                    if (descriptions.includes("Transcription")) {
                                        groupedData[labNumber].forEach(item => {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="In-Progress">In-Progress</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                    } 
                                    // If any of "IT Space," "4," or "Self" is found, skip this lab number entirely
                                    else if (!descriptions.some(desc => ["IT Space", "4", "Self"].includes(desc))) {
                                        groupedData[labNumber].forEach(item => {
                                            rows += `
                                                <tr>
                                                    <td>${item["Lab Number"]}</td>
                                                    <td>${item["Description"]}</td>
                                                    <td>${item["Status Name"]}</td>
                                                    <td>${item["User Name"]}</td>
                                                    <td>${formatDateTime(item.TrackCreateTime)}</td>
                                                    <td>
                                                        <select data-track-id="${item.track_id}" data-lab-number="${item["Lab Number"]}">
                                                            <option value="">Select</option>
                                                            <option value="On-Hold">On-Hold</option>
                                                            <option value="Done">Done</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                    }
                                });
                                return rows;
                        }

                        // Define the trackStatusChange function
                        function progress_trackStatusChange(trackId, labNumber, statusValue) {
                            if (!statusValue) {
                                delete progress_statusChanges[trackId];
                            } else {
                                statusChanges[trackId] = {
                                    labNumber: labNumber,
                                    status: statusValue
                                };
                            }

                            console.log("Status Changes:", progress_statusChanges); // For debugging
                        }


                       // Add the event listener for the select elements
                        document.addEventListener("DOMContentLoaded", () => {
                            const groupedData = groupByLabNumber(progressInstructionList);
                            document.querySelector("#progressInstructionTable tbody").innerHTML = progress_generateFilteredTableRows(groupedData);

                            document.querySelectorAll("select").forEach(selectElement => {
                                selectElement.addEventListener("change", function() {
                                    const trackId = this.getAttribute("data-track-id");
                                    const labNumber = this.getAttribute("data-lab-number");
                                    const statusValue = this.value;
                                    trackStatusChange(trackId, labNumber, statusValue);
                                });
                            });
                        });


                        // Submit the status changes
                        document.getElementById("progress_submitStatusChanges").addEventListener("click", function() {
                            fetch("patient_history_status.php", {
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

                <div id="Completed" class="subtabcontent">
                        <table id="CompletedInstructionTable" class="table">
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

                            

                            // Filter the list based on the conditions
                            const completed_filteredDoctorInstructionList = completed_instruction_list.filter(item =>
                                (item["Section"]) &&
                                (item["Status Name"])
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
                                    controls += `<button class="pagination-btn btn btn-primary" data-page="${i}">${i}</button>`;
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
                   <table id="onHoldInstructionTable" class="table">
                        <thead>
                            <div style="display: flex; justify-content: flex-end;">
                                <button id="onHoldsubmitStatusChanges" class="btn btn-primary" style="margin-bottom: 10px;">Submit</button>  
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

                        

                        // Filter the list based on the conditions
                        const onHoldfilteredDoctorInstructionList = onHold_instruction_list.filter(item =>
                            (item["Section"]) &&
                            (item["Status Name"])
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
                            fetch("patient_history_status.php", {
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
        </div>
         
        <div id="CaseStatus" class="tabcontent">
                    
            <div class="sub-tabs">
                <div class="sub-tab-links">
                  <button style="border:none" class="sub-tablink btn-primary" onclick="openSubTab(event, \'ScreeningDoneList\')">
                  <i class="fas fa-microscope" style="font-size: 25px;"></i>&nbsp;&nbsp;Screening Done List</button>
                  <button style="border:none" class="sub-tablink btn-success" onclick="openSubTab(event, \'FinalizedList\')">
                  <i class="fa fa-gavel" style="font-size: 35px;"></i>Finalized List</button>
                  <button style="border:none" class="sub-tablink btn btn-danger" onclick="openSubTab(event, \'UnSeen\')">
                  <i class="fas fa-eye-slash" style="font-size: 35px;"></i>UnSeen</button>
                  <button style="border:none" class="sub-tablink btn btn-info" onclick="openSubTab(event, \'ReportEnteryCompleted\')">
                  <i class="fas fa-clipboard-check" style="font-size: 35px;"></i>Report Entery Completed</button>
                </div>

                <!-- Sub-tab contents -->
                <div id="ScreeningDoneList" class="subtabcontent">
                            <!-- Filter Controls -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="form-group">
                                                <label for="status-filter">Status:</label>
                                                <select id="status-filter" class="form-control">
                                                <option value="">All</option>
                                                <!-- Options will be populated dynamically -->
                                                </select>
                                            </div>

                                            <div class="form-group" style="display: none;">
                                                <label for="labroom-filter">Lab Room Status:</label>
                                                <select id="labroom-filter" class="form-control">
                                                <option value="">All</option>
                                                <!-- Options will be populated dynamically -->
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="date-filter">Date:</label>
                                                <input type="date" id="date-filter" class="form-control">
                                            </div>

                                            <button id="apply-filter" class="btn btn-primary ml-3">Apply Filter</button>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div id="screening-done-count-display-container" class="mb-4"></div>
                            <div id="list-tab-content-container"></div>
                </div>            
                        
                    
                <div id="FinalizedList" class="subtabcontent">
                        <!-- Filter Controls -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <!-- Status Filter -->
                                <div class="form-group">
                                    <label for="final-status-filter">Status:</label>
                                    <select id="final-status-filter" class="form-control">
                                    <option value="">All</option>
                                    <!-- Options will be populated dynamically -->
                                    </select>
                                </div>

                                <!-- Lab Room Status Filter (Initially hidden) -->
                                <div class="form-group" style="display: none;">
                                    <label for="labroom-filter">Lab Room Status:</label>
                                    <select id="labroom-filter" class="form-control">
                                    <option value="">All</option>
                                    <!-- Options will be populated dynamically -->
                                    </select>
                                </div>

                                <!-- Date Filter -->
                                <div class="form-group">
                                    <label for="final-date-filter">Date:</label>
                                    <input type="date" id="final-date-filter" class="form-control">
                                </div>

                                <!-- Apply Filter Button -->
                                <button id="final-apply-filter" class="btn btn-primary ml-3">Apply Filter</button>
                            </div>
                        </div>

                    <!-- Data Table -->
                    <div id="final-screening-done-count-display-container" class="mb-4"></div>
                    <div id="final-list-tab-content-container"></div>
                </div>

                <div id="UnSeen" class="subtabcontent">
                        <!-- Filter Controls -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                            <!-- Status Filter -->
                            <div class="form-group">
                                <label for="unseen-status-filter">Status:</label>
                                <select id="unseen-status-filter" class="form-control">
                                <option value="">All</option>
                                <!-- Options will be populated dynamically -->
                                </select>
                            </div>

                            <!-- Lab Room Status Filter (Initially hidden) -->
                            <div class="form-group" style="display: none;">
                                <label for="labroom-filter">Lab Room Status:</label>
                                <select id="labroom-filter" class="form-control">
                                <option value="">All</option>
                                <!-- Options will be populated dynamically -->
                                </select>
                            </div>

                            <!-- Date Filter -->
                            <div class="form-group">
                                <label for="unseen-date-filter">Date:</label>
                                <input type="date" id="unseen-date-filter" class="form-control">
                            </div>

                            <!-- Apply Filter Button -->
                            <button id="unseen-apply-filter" class="btn btn-primary ml-3 mt-2 mt-md-0">Apply Filter</button>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div id="unseen-count-display-container" class="mb-4"></div>
                        <div id="unseen-list-tab-content-container"></div>
                </div>


                 <div id="ReportEnteryCompleted" class="subtabcontent">
                     <h3>Completed List</h3>
                 </div>
                

             </div>
                
                   
        </div>
       
        <div id="ReportCompleteStatus" class="tabcontent">
                    <div class="container">
                        <input type="date" id="fromDateTime" class="input-field" placeholder="From">
                        <input type="date" id="toDateTime" class="input-field" placeholder="To">
                        <button id="submitBtn" class="btn">Submit</button>
                    </div>
                   
        </div>
</div>';

echo '
<script>
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
      }

      // Set the default tab to be open
      document.getElementsByClassName("tablink")[0].click();

      // Set the default sub-tab to be open
      // Ensure no sub-tab is active by default
      window.onload = function() {
        var subtabcontent = document.getElementsByClassName("subtabcontent");
        for (var i = 0; i < subtabcontent.length; i++) {
            subtabcontent[i].style.display = "none";
        }
      };
</script>';


?>

<script>
    // Function to count unique labno values
    function countUniqueLabnos(data) {
        const labnoSet = new Set();
        data.forEach(item => {
            if (item['labno']) {
                labnoSet.add(item['labno']);
            }
        });
        return labnoSet.size;
    }
</script>

<script>
        const case_summary_list = <?php echo json_encode($filtered_data); ?> 

        // Initialize filter options
        function generateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            case_summary_list.forEach(item => {
                statusNames.add(item['status_name']);
                labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is not in your data
            });

            populateSelectOptions('status-filter', Array.from(statusNames));
            populateSelectOptions('labroom-filter', Array.from(labRoomStatuses));
        }


        function populateSelectOptions(selectId, options) {
            const selectElement = document.getElementById(selectId);
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.text = option;
                selectElement.add(opt);
            });
        }

        // Apply filters
        document.getElementById('apply-filter').addEventListener('click', () => {
            const selectedStatus = document.getElementById('status-filter').value;
            const selectedLabRoomStatus = document.getElementById('labroom-filter').value;
            const selectedDate = document.getElementById('date-filter').value;

            const filteredData = case_summary_list.filter(item => {
                const matchesStatus = selectedStatus ? item['status_name'] === selectedStatus : true;
                const matchesLabRoom = selectedLabRoomStatus ? item['LabRoomStatus'] === selectedLabRoomStatus : true;
                const matchesDate = selectedDate ? new Date(item['create_time']).toISOString().split('T')[0] === selectedDate : true;
                return matchesStatus && matchesLabRoom && matchesDate;
            });

            
            // Get count of unique lab numbers
            const uniqueLabnoCount = countUniqueLabnos(filteredData);

            displayListData(filteredData, uniqueLabnoCount);
        });

        // Display the data in a table format
        function displayListData(data, uniqueLabnoCount) {
                const countDisplayContainer = document.getElementById('screening-done-count-display-container');
                const listDetailsContainer = document.getElementById('list-tab-content-container');
                listDetailsContainer.innerHTML = ''; // Clear existing details

                const total_case_summary_list = countUniqueLabnos(case_summary_list);
                
                // Display the total count of unique lab numbers
                if (countDisplayContainer) {
                    // Check if uniqueLabnoCount is undefined or null
                    if (uniqueLabnoCount == null) {
                        // Ensure total_case_summary_list is defined and used properly
                        countDisplayContainer.innerHTML = `<p>Total Screening Done: ${total_case_summary_list || 0}</p>`;
                    } else {
                        // Show the unique lab number count
                        countDisplayContainer.innerHTML = `<p>Total Screening Done: ${uniqueLabnoCount}</p>`;
                    }
                } else {
                    console.error('Count display container not found.');
                }

                
                const listTable = document.createElement('table');
                listTable.classList.add('table');
                listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
                listTable.style.width = '100%'; // Optional: To make the table use full width

                // Create table header
                const headerRow = document.createElement('tr');
                ['Lab Number', 'Status Name', 'Date', 'Doctor'].forEach(headerText => {
                    const th = document.createElement('th');
                    th.textContent = headerText;
                    th.style.border = '1px solid black'; // Add border to header cells
                    th.style.padding = '8px'; // Optional: Add padding to cells
                    headerRow.appendChild(th);
                });
                listTable.appendChild(headerRow);

                // Populate table rows
                data.forEach(item => {
                    const row = document.createElement('tr');
                    ['labno', 'status_name', 'create_time', 'user_name'].forEach(key => {
                        const td = document.createElement('td');
                        if (key === 'create_time') {
                            const date = new Date(item[key]);
                            const options = { day: 'numeric', month: 'short', year: 'numeric' };
                            td.textContent = date.toLocaleDateString('en-GB', options);
                        } else {
                            td.textContent = item[key] || 'N/A';
                        }
                        td.style.border = '1px solid black'; // Add border to data cells
                        td.style.padding = '8px'; // Optional: Add padding to cells
                        row.appendChild(td);
                    });
                    listTable.appendChild(row);
                });

                listDetailsContainer.appendChild(listTable);
        }

       

        // Initialize the page
        generateFilterOptions();
        displayListData(case_summary_list);
</script>


<script>
        // Sample Data
        const finalCaseSummaryList = <?php echo json_encode($finalized_filtered_data); ?>;

        // Initialize filter options
        function finalGenerateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            finalCaseSummaryList.forEach(item => {
                statusNames.add(item['status_name']);
                labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is not in your data
            });

            populateFinalSelectOptions('final-status-filter', Array.from(statusNames));
            populateFinalSelectOptions('labroom-filter', Array.from(labRoomStatuses));
        }

        // Populate select options
        function populateFinalSelectOptions(selectId, options) {
            const selectElement = document.getElementById(selectId);
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.text = option;
                selectElement.add(opt);
            });
        }

        // Apply filters
        document.getElementById('final-apply-filter').addEventListener('click', () => {
            const selectedStatus = document.getElementById('final-status-filter').value;
            const selectedLabRoomStatus = document.getElementById('labroom-filter').value;
            const selectedDate = document.getElementById('final-date-filter').value;

            const filteredData = finalCaseSummaryList.filter(item => {
                const matchesStatus = selectedStatus ? item['status_name'] === selectedStatus : true;
                const matchesLabRoom = selectedLabRoomStatus ? item['LabRoomStatus'] === selectedLabRoomStatus : true;
                const matchesDate = selectedDate ? new Date(item['create_time']).toISOString().split('T')[0] === selectedDate : true;
                return matchesStatus && matchesLabRoom && matchesDate;
            });

            // Get count of unique lab numbers
            const FinalizeduniqueLabnoCount = countUniqueLabnos(filteredData);

            renderFinalizedListTable(filteredData, FinalizeduniqueLabnoCount);
        });

        // Display the data in a table format
        function renderFinalizedListTable(data, FinalizeduniqueLabnoCount) {
            const countDisplayContainer = document.getElementById('final-screening-done-count-display-container');
            const listDetailsContainer = document.getElementById('final-list-tab-content-container');
            listDetailsContainer.innerHTML = ''; // Clear existing details

            const total_case_summary_list = countUniqueLabnos(finalCaseSummaryList);

            // Display the total count of unique lab numbers
            if (countDisplayContainer) {
                // Check if FinalizeduniqueLabnoCount is undefined or null
                if (FinalizeduniqueLabnoCount == null) {
                    // Ensure total_case_summary_list is defined and used properly
                    countDisplayContainer.innerHTML = `<p>Total Screening Done: ${total_case_summary_list || 0}</p>`;
                } else {
                    // Show the unique lab number count
                    countDisplayContainer.innerHTML = `<p>Total Screening Done: ${FinalizeduniqueLabnoCount}</p>`;
                }
            } else {
                console.error('Count display container not found.');
            }

            const listTable = document.createElement('table');
            listTable.classList.add('table');
            listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
            listTable.style.width = '100%'; // Optional: To make the table use full width

            // Create table header
            const headerRow = document.createElement('tr');
            ['Lab Number', 'Status Name', 'Date', 'Doctor'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.style.border = '1px solid black'; // Add border to header cells
                th.style.padding = '8px'; // Optional: Add padding to cells
                headerRow.appendChild(th);
            });
            listTable.appendChild(headerRow);

            // Populate table rows
            data.forEach(item => {
                const row = document.createElement('tr');
                ['labno', 'status_name', 'create_time', 'user_name'].forEach(key => {
                    const td = document.createElement('td');
                    if (key === 'create_time') {
                        const date = new Date(item[key]);
                        const options = { day: 'numeric', month: 'short', year: 'numeric' };
                        td.textContent = date.toLocaleDateString('en-GB', options);
                    } else {
                        td.textContent = item[key] || 'N/A';
                    }
                    td.style.border = '1px solid black'; // Add border to data cells
                    td.style.padding = '8px'; // Optional: Add padding to cells
                    row.appendChild(td);
                });
                listTable.appendChild(row);
            });

            listDetailsContainer.appendChild(listTable);
        }

        // Initialize the page
        finalGenerateFilterOptions();
        renderFinalizedListTable(finalCaseSummaryList);
</script>


<script>
        // Sample Data
        const unSeenCaseSummaryList = <?php echo json_encode($unseen_filtered_data); ?>;

        // Initialize filter options
        function unSeenGenerateFilterOptions() {
            const statusNames = new Set();
            const labRoomStatuses = new Set();

            unSeenCaseSummaryList.forEach(item => {
                statusNames.add(item['status_name']);
                if (item['LabRoomStatus']) {
                    labRoomStatuses.add(item['LabRoomStatus']); // Adjust if 'LabRoomStatus' is present in your data
                }
            });

            populateSelectOptions('unseen-status-filter', Array.from(statusNames));
            populateSelectOptions('labroom-filter', Array.from(labRoomStatuses));
        }

        // Populate select options
        function populateSelectOptions(selectId, options) {
            const selectElement = document.getElementById(selectId);
            selectElement.innerHTML = ''; // Clear existing options
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.text = option;
                selectElement.add(opt);
            });
        }

        // Apply filters
        document.getElementById('unseen-apply-filter').addEventListener('click', () => {
            const selectedStatus = document.getElementById('unseen-status-filter').value;
            const selectedLabRoomStatus = document.getElementById('labroom-filter').value;
            const selectedDate = document.getElementById('unseen-date-filter').value;

            const filteredData = unSeenCaseSummaryList.filter(item => {
                const matchesStatus = selectedStatus ? item['status_name'] === selectedStatus : true;
                const matchesLabRoom = selectedLabRoomStatus ? item['LabRoomStatus'] === selectedLabRoomStatus : true;
                const matchesDate = selectedDate ? new Date(item['create_time']).toISOString().split('T')[0] === selectedDate : true;
                return matchesStatus && matchesLabRoom && matchesDate;
            });
            
            // Get count of unique lab numbers
            const unseenuniqueLabnoCount = countUniqueLabnos(filteredData);
            renderunSeenListTable(filteredData, unseenuniqueLabnoCount);
        });

        // Display the data in a table format
        function renderunSeenListTable(data, unseenuniqueLabnoCount) {
            const countDisplayContainer = document.getElementById('unseen-count-display-container');
            const listDetailsContainer = document.getElementById('unseen-list-tab-content-container');
            listDetailsContainer.innerHTML = ''; // Clear existing details

            const total_case_summary_list = countUniqueLabnos(unSeenCaseSummaryList);

            // Display the total count of unique lab numbers
            if (countDisplayContainer) {
                // Check if unseenuniqueLabnoCount is undefined or null
                if (unseenuniqueLabnoCount == null) {
                    // Ensure total_case_summary_list is defined and used properly
                    countDisplayContainer.innerHTML = `<p>Total UnSeen: ${total_case_summary_list || 0}</p>`;
                } else {
                    // Show the unique lab number count
                    countDisplayContainer.innerHTML = `<p>Total UnSeen: ${unseenuniqueLabnoCount}</p>`;
                }
            } else {
                console.error('Count display container not found.');
            }

            const listTable = document.createElement('table');
            listTable.classList.add('table');
            listTable.style.borderCollapse = 'collapse'; // Optional: To remove space between borders
            listTable.style.width = '100%'; // Optional: To make the table use full width

            // Create table header
            const headerRow = document.createElement('tr');
            ['Lab Number', 'Status Name', 'Date', 'User'].forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                th.style.border = '1px solid black'; // Add border to header cells
                th.style.padding = '8px'; // Optional: Add padding to cells
                headerRow.appendChild(th);
            });
            listTable.appendChild(headerRow);

            // Populate table rows
            data.forEach(item => {
                const row = document.createElement('tr');
                ['labno', 'status_name', 'create_time', 'user_name'].forEach(key => {
                    const td = document.createElement('td');
                    if (key === 'create_time') {
                        const date = new Date(item[key]);
                        const options = { day: 'numeric', month: 'short', year: 'numeric' };
                        td.textContent = date.toLocaleDateString('en-GB', options);
                    } else {
                        td.textContent = item[key] || 'N/A';
                    }
                    td.style.border = '1px solid black'; // Add border to data cells
                    td.style.padding = '8px'; // Optional: Add padding to cells
                    row.appendChild(td);
                });
                listTable.appendChild(row);
            });

            listDetailsContainer.appendChild(listTable);
        }

        // Initialize the page
        unSeenGenerateFilterOptions();
        renderunSeenListTable(unSeenCaseSummaryList);
</script>