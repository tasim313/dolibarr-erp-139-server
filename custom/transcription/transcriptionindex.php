<?php

// Load Dolibarr environment
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');
include('preliminary_report/preliminary_report_function.php');
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
$langs->loadLangs(array("transcription@transcription"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->transcription->myobject->read) {
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

llxHeader("", $langs->trans("TranscriptionArea"));

$loggedInUserId = $user->id;
$loggedInUsername = $user->login;

$userGroupNames = getUserGroupNames($loggedInUserId);

$preliminaryLabNumbers = json_encode(get_preliminary_report_labnumber_list());

$hasTranscriptionist = false;
$hasConsultants = false;

foreach ($userGroupNames as $group) {
    if ($group['group'] === 'Transcription') {
        $hasTranscriptionist = true;
    } elseif ($group['group'] === 'Consultants') {
        $hasConsultants = true;
    }
}

// Access control using switch statement
switch (true) {
  case $hasTranscriptionist:
      // Transcription  has access, continue with the page content...
      break;
  default:
      echo "<h1>Access Denied</h1>";
      echo "<p>You are not authorized to view this page.</p>";
      exit; // Terminate script execution
}

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













$NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;



print '</div></div>';

// End of page
llxFooter();
$db->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transcription</title>
  <link href="../grossmodule/bootstrap-3.4.1-dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Add Bootstrap and jQuery JS -->
  <script src="../../grossmodule/jquery/jquery.min.js"></script>
  <script src="../../grossmodule/bootstrap-3.4.1-dist/js/bootstrap.min.js"></script>
  <style>
    body {
      background-color: #f5f5f5;
      font-family: 'Arial', sans-serif;
    }
    
    .page-header {
      border-bottom: 1px solid #e0e0e0;
      margin: 30px 0 40px;
      padding-bottom: 15px;
    }
    
    .page-title {
      color: #2c3e50;
      font-weight: 600;
      font-size: 28px;
      margin-bottom: 5px;
    }
    
    .page-subtitle {
      color: #7f8c8d;
      font-size: 16px;
      font-weight: 400;
    }
    
    .report-card {
      background: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      transition: all 0.3s ease;
    }
    
    .report-card:hover {
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #e0e0e0;
      padding: 20px;
      font-size: 18px;
      font-weight: 600;
      color: #2c3e50;
    }
    
    .preliminary .card-header {
      border-left: 4px solid #3498db;
    }
    
    .final .card-header {
      border-left: 4px solid #2ecc71;
    }
    
    .card-body {
      padding: 25px;
    }
    
    .form-label {
      font-weight: 600;
      color: #34495e;
      font-size: 14px;
      margin-bottom: 8px;
    }
    
    .form-control {
      height: 42px;
      border-radius: 3px;
      border: 1px solid #ddd;
      font-size: 14px;
      padding: 10px 15px;
    }
    
    .form-control:focus {
      border-color: #3498db;
      box-shadow: none;
    }
    
    .btn {
      font-weight: 600;
      padding: 10px 20px;
      font-size: 14px;
      border-radius: 3px;
      transition: all 0.3s;
    }
    
    .btn-outline-primary {
      background-color: #3498db;
      color: #fff;
    }
    
    
    .btn-outline-success {
      background-color: #2ecc71;
      color: #fff;
    }
    
   
    .message-box {
      padding: 15px;
      margin-top: 20px;
      border-radius: 3px;
      font-size: 14px;
      display: none;
    }
    
    .success-message {
      background-color: #e8f8f5;
      border-left: 4px solid #2ecc71;
      color: #27ae60;
    }
    
    .error-message {
      background-color: #fdedec;
      border-left: 4px solid #e74c3c;
      color: #c0392b;
    }
    
    @media (max-width: 768px) {
      .page-header {
        margin: 20px 0 30px;
      }
      
      .page-title {
        font-size: 24px;
      }
      
      .card-header {
        padding: 15px;
        font-size: 16px;
      }
      
      .card-body {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="page-header">
      <h1 class="page-title">Transcription</h1>
      <p class="page-subtitle">Access and Manage Histopathology Report</p>
    </div>
    
    <div class="row">
      <!-- Preliminary Report -->
      <div class="col-md-6 preliminary">
        <div class="report-card">
          <div class="card-header">
            Preliminary Report
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="labNumber" class="form-label">Lab Number</label>
              <input type="text" class="form-control" id="labNumber" placeholder="Enter lab number">
            </div>
            <button class="btn btn-outline-primary" id="searchBtn">Submit</button>
            <div id="message" class="message-box">
              <span id="messageText"></span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Final Report -->
      <div class="col-md-6 final">
        <div class="report-card">
          <div class="card-header">
            Final Report
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="finallabNumber" class="form-label">Lab Number</label>
              <input type="text" class="form-control" id="finallabNumber" placeholder="Enter lab number">
            </div>
            <button class="btn btn-outline-success" id="finalsearchBtn">Submit</button>
            <div id="finalMessage" class="message-box">
              <span id="finalMessageText"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>

    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('labNumber').focus();
    });

    // Press Enter triggers search for Preliminary Report
    document.getElementById('labNumber').addEventListener('keydown', function(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('searchBtn').click();
      }
    });

    // Press Enter triggers search for Final Report
    document.getElementById('finallabNumber').addEventListener('keydown', function(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('finalsearchBtn').click();
      }
    });

    // Simple script to show messages
    document.getElementById('searchBtn').addEventListener('click', function() {
      const labNumber = document.getElementById('labNumber').value;
      const messageBox = document.getElementById('message');
      const messageText = document.getElementById('messageText');
      
      if (labNumber) {
        messageText.textContent = `Processing request for lab number: ${labNumber}`;
        messageBox.style.display = 'block';
        messageBox.className = 'message-box success-message';
      } else {
        messageText.textContent = 'Please enter a valid lab number';
        messageBox.style.display = 'block';
        messageBox.className = 'message-box error-message';
      }
    });
    
    document.getElementById('finalsearchBtn').addEventListener('click', function() {
      const labNumber = document.getElementById('finallabNumber').value;
      const messageBox = document.getElementById('finalMessage');
      const messageText = document.getElementById('finalMessageText');
      
      if (labNumber) {
        messageText.textContent = `Processing request for lab number: ${labNumber}`;
        messageBox.style.display = 'block';
        messageBox.className = 'message-box success-message';
      } else {
        messageText.textContent = 'Please enter a valid lab number';
        messageBox.style.display = 'block';
        messageBox.className = 'message-box error-message';
      }
    });
  </script>
  
  <!-- Preliminary Report  -->
  <script>
    const preliminaryLabNumbers = <?php echo $preliminaryLabNumbers; ?>;

    const hplLabNumbers = [];
    const ihcLabNumbers = [];

    preliminaryLabNumbers.forEach(item => {
      if (item.test_type === 'HPL') {
        hplLabNumbers.push(item.lab_number.trim().toLowerCase());
      } else if (item.test_type === 'IHC') {
        ihcLabNumbers.push(item.lab_number.trim().toLowerCase());
      }
    });

    function handleLabNumberSearch() {
      const labNumber = document.getElementById('labNumber').value.trim().toLowerCase();

      if (labNumber) {
        if (hplLabNumbers.includes(labNumber)) {
          window.location.href = 'preliminary_report/hpl/index.php?LabNumber=' + encodeURIComponent("HPL" + labNumber);
        } else if (ihcLabNumbers.includes(labNumber)) {
          window.location.href = 'preliminary_report/ihc/index.php?LabNumber=' + encodeURIComponent(labNumber);
        } else {
          document.getElementById('message').innerHTML = 
            `<div class="alert alert-danger">This lab number does not match. Please enter a valid lab number.</div>`;
        }
      } else {
        document.getElementById('message').innerHTML = 
          `<div class="alert alert-danger">Please enter a lab number.</div>`;
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('searchBtn').addEventListener('click', handleLabNumberSearch);
      document.getElementById('labNumber').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
          handleLabNumberSearch();
        }
      });

      document.getElementById('labNumber').focus();
    });
  </script>

<!-- Final Report  -->
  <script>
    document.getElementById('finalsearchBtn').addEventListener('click', function () {
          const userInput = document.getElementById('finallabNumber').value.trim();
          const labNumber = 'HPL' + userInput;
          const messageBox = document.getElementById('finalMessage');
          const messageText = document.getElementById('finalMessageText');

          if (!userInput) {
            messageText.textContent = 'Please enter a valid lab number';
            messageBox.className = 'message-box error-message';
            messageBox.style.display = 'block';
            return;
          }

          fetch('get_final_lab_result.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ lab_number: userInput })
          })
            .then(res => res.json())
            .then(data => {
              if (data.status === 'transcript') {
                const item = data.data[0]; // Auto-redirect to the first matching transcript
                if (item) {
                  window.location.href = `transcription.php?lab_number=${item.lab_number}`;
                } else {
                  messageText.textContent = 'No transcript data available.';
                  messageBox.className = 'message-box error-message';
                  messageBox.style.display = 'block';
                }
              } else if (data.status === 'gross') {
                const item = data.data;
                window.location.href = `micro_description_create.php?fk_gross_id=${item.gross_id}&user=${item.gross_assistant_name}`;
              } else {
                messageText.textContent = 'No matching lab number found';
                messageBox.className = 'message-box error-message';
                messageBox.style.display = 'block';
              }
            })
            .catch(err => {
              messageText.textContent = 'Server error';
              messageBox.className = 'message-box error-message';
              messageBox.style.display = 'block';
            });
    });
  </script>
</body>
</html>