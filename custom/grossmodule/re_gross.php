<?php 
include('connection.php');
include('gross_common_function.php');
include('../histolab/histo_common_function.php');

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

llxHeader("", $langs->trans("Gross"));

print load_fiche_titre($langs->trans("Re-Gross Area"), '',);


$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);
$doctor_instruction_list = get_histo_doctor_instruction_list();
$complete_instruction_list = get_histo_doctor_instruction_complete_list();
$assistants = get_gross_assistant_list();
$doctors = get_doctor_list();
$assistants = get_gross_assistant_list();

$fk_gross_id = get_gross_instance($LabNumber);

print("
    <style>
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

    </style>
");


echo('
<div class="tab-container">

    <div class="tabs">
      <button style="border:none" class="tablink" onclick="openTab(event, \'ReGrossPendingInstructions\')">
       <i class="fas fa-hourglass-half" style="font-size: 35px;"></i> ReGross Pending List</button>
      <button style="border:none" class="tablink" onclick="openTab(event, \'Completed\')">
       <i class="fas fa-check-circle" style="font-size: 35px;"></i>ReGross Complete List</button>
    </div>
    
    <div id="ReGrossPendingInstructions" class="tabcontent">

         <!-- Search box for Lab Number -->
        <div>
            <input type="text" id="searchLabNumberInput" placeholder="Search by Lab Number" style="margin-bottom: 15px;">
            <button id="searchLabNumberButton" class="btn">Search</button>
        </div>
        
            <table id="inprogres_InstructionTable" border="1" style="border: none;">
                <thead style="border: none;">
                        <tr style="border: none;">
                            <th style="border: none;"></th>
                            <th style="border: none;"></th>
                            <th style="border: none;"></th>
                            <th style="border: none;"></th>
                            <th style="border: none;"></th>
                            <th style="border: none;">
                                <button id="submitInprogressStatusChanges" class="btn" style="margin-bottom: 10px;">Submit</button>
                            </th>
                        </tr>
                     
                        <tr>
                            <th>Date</th>
                            <th>Lab Number</th>
                            <th>Section</th>
                            <th>Instruction</th>
                            <th>ReGross Request Doctor Name</th>
                            <th>Doctor</th>
                            <th>Gross Assistant</th>
                            <th>Status</th>
                        </tr>
                </thead>
                <tbody></tbody>
            </table>

            <!-- Message for search results -->
            <p id="searchResultMessage" style="display:none; color: red;"></p>
                    <script>
                        const inprogress_instruction_list = ' . json_encode($doctor_instruction_list) . ';
                        const loggedInUserId = ' . json_encode($loggedInUserId) . ';
                        const doctor_list = '.json_encode($doctors).';
                        const assistant_list = ' .json_encode($assistants).';
                        
                        let inprogres_statusChanges = {};

                        // Define the values to exclude
                        const inprogres_excludedSections = ["Gross", "Transcription", "Frontdesk", "Screening"];
                        const inprogres_excludedStatusNames = ["Diagnosis Completed", "Final Screening Start", "Screening Done",
                            "Waiting - Study", "Waiting - Patient History / Investigation", "Slides Prepared",
                            "Regross Completed", "Recut or Special Stain Completed", "Regross Slides Prepared", "IHC-Block-Markers-completed",
                            "M/R/C Completed", "Deeper Cut Completed", "Serial Sections Completed", "Block D/C & R/C Completed", "Special Stain AFB Completed",
                            "Special Stain GMS Completed", "Special Stain PAS Completed", "Special Stain PAS with Diastase Completed", "Special Stain Fite Faraco Completed",
                            "Special Stain Brown-Brenn Completed", "Special Stain Congo-Red Completed", "Special Stain others Completed", "In-Progress", 
                            "On-Hold", "R/C Completed", "Special Stain others requested","IHC-Block-Markers-requested", "M/R/C requested",
                            "Deeper Cut requested", "Serial Sections requested", "Block D/C & R/C requested", "Special Stain AFB requested", "Special Stain PAS requested",
                            "Special Stain GMS requested", "Special Stain PAS requested", "Special Stain PAS with Diastase requested", "Special Stain Fite Faraco requested",
                            "Special Stain Brown-Brenn requested", "Special Stain Congo-Red requested", "Special Stain Bone Decalcification requested", "R/C requested",
                            "SBO Ready", "Bones Slide Ready"
                        ];

                        const statusPairsWithIds = {
                            "Re-gross Requested": { completed: "Regross Completed", id: 7 }
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
                                
                                let doctorOptions = \'<option value="">Select Doctor</option>\';
                                doctor_list.forEach(doctor => {
                                    const selected = (doctor["doctor_username"] === item["doctor_username"]) ? "selected" : "";
                                    doctorOptions += `<option value="${doctor.doctor_username}" ${selected}>${doctor.doctor_username}</option>`;
                                });

                                let assistantOptions = \'<option value="">Select Gross Assistant</option>\';
                                assistant_list.forEach(assistant => {
                                    const selected = (assistant["username"] === item["username"]) ? "selected" : "";
                                    assistantOptions += `<option value="${assistant.username}" ${selected}>${assistant.username}</option>`;
                                });
                                
                                return `
                                    <tr>
                                        <td>${formatDateTime(item["TrackCreateTime"])}</td>
                                        <td>${item["Lab Number"]}</td>
                                        <td>${item["Description"]}</td>
                                        <td>${item["Status Name"]}</td>
                                        <td>${item["User Name"]}</td>
                                        <td>
                                            <select>
                                                ${doctorOptions}
                                            </select>
                                        </td>
                                        <td>
                                            <select>
                                                ${assistantOptions}
                                            </select>
                                        </td>
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
                                    // Clear changes after successful save
                                    inprogres_statusChanges = {};

                                    // Redirect to the page with fk_gross_id if available
                                    if (data.fk_gross_id) {
                                        window.location.href = `gross_update.php?fk_gross_id=${data.fk_gross_id}`;
                                    } else {
                                        window.location.reload(); // Fallback to reload the current page if fk_gross_id is not present
                                    }
                                } else {
                                    alert(data.message || "Failed to save status changes.");
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

    <div id="Completed" class="tabcontent">
        <table id="CompletedInstructionTable" border="1" style="border: none;">
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
            const completed_excludedStatusNames = [
                "Special Stain others requested", "IHC-Block-Markers-requested", "R/C requested", 
                "Waiting - Study", "Waiting - Patient History / Investigation", "M/R/C requested", 
                "Deeper Cut requested", "Serial Sections requested", "Block D/C & R/C requested", 
                "Special Stain AFB requested", "Special Stain GMS requested", 
                "Special Stain PAS with Diastase requested", "Special Stain Brown-Brenn requested", 
                "Special Stain Congo-Red requested", "Special Stain Bone Decalcification requested", 
                "Special Stain Fite Faraco requested", "Re-gross Requested", "Screening Done", 
                "Final Screening Start", "In-Progress", "Special Stain PAS requested", "Diagnosis Completed", 
                "Slides Prepared", "Serial Sections Completed", "Special Stain Congo-Red Completed", "R/C Completed",
                "M/R/C Completed", "IHC-Block-Markers-completed", "Block D/C & R/C Completed","Special Stain Brown-Brenn Completed",
                "Special Stain others Completed", "Special Stain Fite Faraco Completed", "Special Stain AFB Completed", 
                "Special Stain PAS with Diastase Completed", "Deeper Cut Completed", "Special Stain GMS Completed", "Special Stain PAS Completed"
            
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
            }

            // Initialize the table and pagination
            renderTable();
            renderPaginationControls();
        </script>
    </div>

</div>


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

</script>

');

?>

<script>
    // Insert table rows
    document.querySelector("#inprogres_InstructionTable tbody").innerHTML = inprogres_generateTableRows(inprogres_filteredDoctorInstructionList);

    // Search functionality
    document.getElementById("searchLabNumberButton").addEventListener("click", function () {
        const searchValue = document.getElementById("searchLabNumberInput").value.trim();
        const filteredData = inprogres_filteredDoctorInstructionList.filter(item => item["Lab Number"] === searchValue);

        if (filteredData.length > 0) {
            document.querySelector("#inprogres_InstructionTable tbody").innerHTML = inprogres_generateTableRows(filteredData);
            document.getElementById("searchResultMessage").style.display = "none";
        } else {
            document.getElementById("searchResultMessage").textContent = "Lab Number not found.";
            document.getElementById("searchResultMessage").style.display = "block";
            document.querySelector("#inprogres_InstructionTable tbody").innerHTML = "";
        }
    });
</script>