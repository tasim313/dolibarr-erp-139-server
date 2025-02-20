<?php
include("../connection.php");
include('../../../grossmodule/gross_common_function.php');
include('../../../transcription/common_function.php');
include('../../../transcription/FNA/function.php');
include('../../common_function.php');


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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
echo '<script src="'.DOL_URL_ROOT.'/includes/ckeditor/ckeditor.js"></script>';

// Load translation files required by the page
$langs->loadLangs(array("cytology@cytology"));

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

llxHeader("", $langs->trans("CytologyArea"));



$loggedInUsername = $user->login;
$loggedInUserId = $user->id;

$isCytoAssistant = false;
$isDoctor = false;

$isAdmin = isUserAdmin($loggedInUserId);

$LabNumber = $_GET['LabNumber'];

$assistants = get_cyto_tech_list();
foreach ($assistants as $assistant) {
    if ($assistant['username'] == $loggedInUsername) {
        $isCytoAssistant = true;
        break;
    }
}

$doctors = get_doctor_list();
foreach ($doctors as $doctor) {
    if ($doctor['doctor_username'] == $loggedInUsername) {
        $isDoctor = true;
        break;
    }
}

// Access control using switch statement
switch (true) {
    case $isCytoAssistant:
        // cyto Assistant has access, continue with the page content...
        break;
    case $isDoctor:
        // Doctor has access, continue with the page content...
        break;
    case $isAdmin:
        // Admin has access, continue with the page content...
        break;
        
    default:
        echo "<h1>Access Denied</h1>";
        echo "<p>You are not authorized to view this page.</p>";
        exit; // Terminate script execution
}

$host = $_SERVER['HTTP_HOST'];
$homeUrl = "http://" . $host . "/custom/cytology/cytologyindex.php";
$reportUrl = "http://" . $host . "/custom/transcription/FNA/fna_report.php?LabNumber=" . urlencode($LabNumber) . "&username=" . urlencode($loggedInUsername);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-3.4.1-dist/js/bootstrap.min.js">
</head>
<body>
<div class="container">
        <h3>Cyto Lab WorkFlow</h3>
            <ul class="nav nav-tabs">
                <li><a href="../index.php">Home</a></li>
                <li class="active"><a href="./mfc.php">MFC</a></li>
                <li><a href="./special_instruction.php" class="tab">Special Instructions</a></li>
                <li><a href="./slide_prepared.php" class="tab">Slide Prepared</a></li>
                <li><a href="./new_slide_centrifuge.php" class="tab">New Slide (Centrifuge)</a></li>
                <li><a href="./sbo.php">SBO(Slide Block Order)</a></li>
                <li><a href="../recall.php">Re-Call</a></li>
                <li><a href="./doctor_instruction.php">Doctor's Instructions</a></li>
                <li><a href="./cancel_information.php">Cancel Information</a></li>
                <li><a href="./postpone_information.php">Postpone</a></li>
            </ul>
        
   
            <?php
                // Fetch MFC list for the specific Lab Number
                $mfc_list = get_mfc_create_list($LabNumber);

                // Check if any data is available for the lab number
                if (!empty($mfc_list) && isset($mfc_list[0]['description']) && !empty($mfc_list[0]['description'])) {
                    // If description data exists, use it
                    $description_data = $mfc_list[0]['description'];
                    
                    $data_available = true; // Flag to indicate data is available
                } else {
                    // If no data or description is empty
                    $description_data = ''; // Default empty string for description
                    $data_available = false; // Flag to indicate no data is available
                }

                date_default_timezone_set('Asia/Dhaka');
                function formatDateTime($datetime) {
                    return date("d F, Y h:i A", strtotime($datetime));
                }
            ?>

            <?php if (!empty($mfc_list)): // If lab number exists, show update form ?>
                <?php $mfc_data = $mfc_list[0]; ?> 

                <div class="container">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">MFC Update</h3></div>
                        <div class="panel-body">
                            <form method="POST" action="../edit/update_mfc.php">
                                
                                <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($LabNumber); ?>">

                                <div class="form-group">
                                    <label for="previous_description">Previous Description:</label>
                                    <div id="previous_description" class="form-control" style="min-height: 150px; overflow-y: auto; background-color: #f5f5f5; padding: 10px;">
                                        <?php 
                                            $previous_data = json_decode($mfc_data['previous_description'], true); 
                                            if (!empty($previous_data)) {
                                                foreach ($previous_data as $entry) {
                                                    echo "<strong>Old Description:</strong> " . $entry['old_description'] . "";
                                                    echo "<strong>Created By:</strong> " . htmlspecialchars($entry['created_user']) . "<br>";
                                                    echo "<strong>Updated By:</strong> " . htmlspecialchars($entry['updated_user']) . "<br>";
                                                    echo "<strong>Created Time:</strong> " . formatDateTime($entry['created_time']) . "<br>";
                                                    echo "<strong>Updated Time:</strong> " . formatDateTime($entry['updated_time']) . "<br>";
                                                    echo "<hr>"; // Add a separator between entries
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label for="description">Description:</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars(trim($mfc_data['description'])); ?></textarea>
                                </div>

                                <input type="hidden" name="updated_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">

                                <button type="submit" class="btn btn-warning">
                                    Submit
                                </button>

                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($data_available): // If description data is available ?>
                <div class="container" style="margin-top:30px;">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">MFC New Entry</h3></div>
                        <div class="panel-body">
                            <form method="POST" action="../insert/insert_mfc.php">
                                
                                <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($LabNumber); ?>">
                                
                                <div class="form-group">
                                    <label for="description">Description:</label>  
                                    <textarea id="description" name="description" class="form-control" rows="10" required><?php echo htmlspecialchars($description_data); ?></textarea>
                                </div>

                                <input type="hidden" name="created_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                                
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                                
                            </form>
                        </div>
                    </div>
                </div>
                <script>
                    // Initialize Dolibarr's WYSIWYG editor for description if data is available
                    initHtmlTextArea('description');
                </script>
            <?php else: // If no description data, show CKEditor initialization with default content ?>
                <div class="container" style="margin-top:30px;">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">MFC New Entry</h3></div>
                        <div class="panel-body">
                            <form method="POST" action="../insert/insert_mfc.php">
                                
                                <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($LabNumber); ?>">
                                
                                <div class="form-group">
                                    <label for="description">Description:</label>  
                                    <textarea id="description" name="description" class="form-control" rows="10" required></textarea>
                                </div>

                                <input type="hidden" name="created_user" value="<?php echo htmlspecialchars($loggedInUsername); ?>">
                                
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                                
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>



       
</div>
</body>
</html>

<script>
    $(document).ready(function() {

    // Function to clean up empty <p> tags and unnecessary white spaces
    function cleanHtmlContent(content) {
        // Remove empty <p> tags (including those with only spaces)
        content = content.replace(/<p>\s*<\/p>/g, '');

        // Trim overall content
        return content.trim();
    }

    // Initialize CKEditor
    CKEDITOR.replace('description');
        // Check if mfc data is available and pass it to CKEditor after initialization
        <?php if (!empty($mfc_list)): ?>
            CKEDITOR.replace('description');
            // Directly use the HTML content without escaping it
            let formattedDescription = `<?php echo $mfc_data['description']; ?>`;
            CKEDITOR.instances.description.setData(formattedDescription);
        <?php elseif ($data_available): ?>
            CKEDITOR.replace('description');
            let formattedDescription = `<?php echo $description_data; ?>`;
            CKEDITOR.instances.description.setData(formattedDescription);
        <?php else: ?>
            // Default content for new entries
            let defaultContent = `
                <p>Quantity: </p>
                <p>Color: </p>
                <p>Appearance: </p>
                <p>Sediment: </p>
                <p>Clot: </p>
            `;
            defaultContent = cleanHtmlContent(defaultContent);
            CKEDITOR.instances.description.setData(defaultContent);
        <?php endif; ?>

        // Ensure read-only mode for previous_description
        if ($('textarea#previous_description').length) {
            $('textarea#previous_description').attr('readonly', 'readonly');
            $('textarea#previous_description').prop('disabled', true); 
            initHtmlTextArea('previous_description'); // Initialize Dolibarr's WYSIWYG Editor for previous description
        }
    });
</script>





<?php 
    $NBMAX = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
    $max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

    print '</div></div>';

    // End of page
    llxFooter();
    $db->close();
?>