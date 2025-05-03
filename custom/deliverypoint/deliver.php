<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

// Security check
if (empty($user->rights->facture->lire)) {
    accessforbidden();
}

$id = GETPOST('id', 'int');

// Process delivery action
if ($id > 0) {
    // Your delivery processing logic here
    setEventMessages($langs->trans("DeliveryProcessed"), null, 'mesgs');
}

// Redirect back to invoice
header("Location: ".DOL_URL_ROOT."/compta/facture/card.php?id=".$id);
exit;