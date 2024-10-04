<?php

include("../connection.php");
include("../gross_common_function.php");

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

$title = $langs->trans("Gross Abbrevations Insert");
$help_url = '';
llxHeader('', $title, $help_url);

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- CKEditor 5 CDN -->
     <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        textarea {
            height: 200px; /* Set height for CKEditor container */
            resize: vertical; /* Allow vertical resizing */
        }

        button {
            background-color: #007bff; /* Blue color */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        button:disabled {
            background-color: #d6d6d6; /* Light gray when disabled */
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <form id="abbreviationForm" method="post" action="save_abbreviation.php">
        <div>
            <label for="abbreviation_key">Abbreviation Key:</label>
            <input type="text" id="abbreviation_key" name="abbreviation_key" required>
        </div>
        <div>
            <label for="abbreviation_full_text">Abbreviation Full Text:</label>
            <!-- CKEditor container -->
            <textarea id="editor-container"></textarea>
            <input type="hidden" id="abbreviation_full_text" name="abbreviation_full_text">
        </div>
        <input type="hidden" name="fk_user_id" value="<?php echo htmlspecialchars($loggedInUserId); ?>">
        <button type="submit">Submit</button>
    </form>

    <script>
        let editorInstance;

        // Initialize CKEditor 5
        ClassicEditor
            .create(document.querySelector('#editor-container'))
            .then(editor => {
                editorInstance = editor; // Store the editor instance
            })
            .catch(error => {
                console.error(error);
            });

        // Add form submit event listener
        document.getElementById('abbreviationForm').addEventListener('submit', function(event) {
            // Get the editor content
            const editorContent = editorInstance.getData();

            // Update hidden input field with editor content
            document.getElementById('abbreviation_full_text').value = editorContent;

            // If the CKEditor content is empty, show an alert and prevent form submission
            if (!editorContent.trim()) {
                alert("Abbreviation Full Text cannot be empty.");
                event.preventDefault(); // Prevent form submission
            }
        });
    </script>
</body>
</html>