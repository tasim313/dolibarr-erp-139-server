<?php 
include('connection.php');
include('common_function.php');
include('../grossmodule/gross_common_function.php');

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

$report_delivery_date_list = get_report_delivery_date_list();

// Array to store categorized entries
$categorizedEntries = array(
    '8AM  -  9AM' => array(),
    '9AM  -  10AM' => array(),
    '10AM -  11AM' => array(),
    '11AM -  12PM' => array(),
    '12PM -  1PM' => array(),
    '1PM  -  2PM' => array(),
    "2PM  -  3PM" => array(),
    '3PM  -  4PM' => array(),
    '4PM  -  5PM' => array(),
    '5PM  -  6PM' => array(),
    '6PM  -  7PM' => array(),
    '7PM  -  8PM' => array(),
    '8PM  -  9PM' => array(),
    '9PM  -  10PM' => array(),
    '10PM -  11PM' => array()
);


foreach ($report_delivery_date_list as $list) {
    $dateString = $list['date_livraison'];
    $date = new DateTime($dateString);
    $formattedDate = $date->format('F d, Y h:i A');

    // Check if the formatted date matches today's date
    if ($date->format('Y-m-d') === date('Y-m-d')) {
        // Determine the time slot based on the hour of delivery time
        $hour = $date->format('H');
        $timeSlot = '';

        if ($hour >= 8 && $hour < 9) {
            $timeSlot = '8AM - 9AM';
        } elseif ($hour >= 9 && $hour < 10) {
            $timeSlot = '9AM - 10AM';
        } elseif ($hour >= 10 && $hour < 11) {
            $timeSlot = '10AM - 11AM';
        } elseif ($hour >= 11 && $hour < 12) {
            $timeSlot = '11AM - 12PM';
        } elseif ($hour >= 12 && $hour < 13) {
            $timeSlot = '12PM - 1PM';
        } elseif ($hour >= 13 && $hour < 14) {
            $timeSlot = '1PM - 2PM';
        } elseif ($hour >= 14 && $hour < 15) {
            $timeSlot = '2PM - 3PM';
        } elseif ($hour >= 15 && $hour < 16) {
            $timeSlot = '3PM - 4PM';
        } elseif ($hour >= 16 && $hour < 17) {
            $timeSlot = '4PM - 5PM';
        } elseif ($hour >= 17 && $hour < 18) {
            $timeSlot = '5PM - 6PM';
        } elseif ($hour >= 18 && $hour < 19) {
            $timeSlot = '6PM - 7PM';
        } elseif ($hour >= 19 && $hour < 20) {
            $timeSlot = '7PM - 8PM';
        } elseif ($hour >= 20 && $hour < 21) {
            $timeSlot = '8PM - 9PM';
        } elseif ($hour >= 21 && $hour < 22) {
            $timeSlot = '9PM - 10PM';
        } elseif ($hour >= 22 && $hour < 23) {
            $timeSlot = '10PM - 11PM';
        }
        // Add the entry to the corresponding time slot
        if (!empty($timeSlot)) {
            $categorizedEntries[$timeSlot][] = array(
                'Lab Number' => $list['ref'],
                'Received Date' => $list['date_commande'],
                'Delivery Time' => $formattedDate
            );
        }
    }
}

?>



<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorized Delivery Entries</title>
</head>
<body>
    <h1>Categorized Delivery Entries</h1>
    <?php foreach ($categorizedEntries as $timeSlot => $entries): ?>
        <?php if (!empty($entries)): ?>
            <h2><?php echo $timeSlot; ?></h2>
            <ul>
                <?php foreach ($entries as $entry): ?>
                    <li>
                        Lab Number: <?php echo $entry['Lab Number']; ?><br>
                        Received Date: <?php echo $entry['Received Date']; ?><br>
                        Delivery Time: <?php echo $entry['Delivery Time']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html> -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorized Delivery Entries</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Categorized Delivery Entries</h1>
    <?php foreach ($categorizedEntries as $timeSlot => $entries): ?>
        <?php if (!empty($entries)): ?>
            <h2><?php echo $timeSlot; ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Lab Number</th>
                        <th>Received Date</th>
                        <th>Delivery Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?php echo $entry['Lab Number']; ?></td>
                            <td><?php echo $entry['Received Date']; ?></td>
                            <td><?php echo $entry['Delivery Time']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Total Entries: <?php echo count($entries); ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>

