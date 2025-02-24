<?php
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';

if (!$user->rights->commande->lire) {
	accessforbidden();
}

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('ordersindex'));

// Load translation files required by the page
$langs->loadLangs(array('orders', 'bills'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// Maximum elements of the tables
$maxDraftCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;


/*
 * View
 */

$commandestatic = new Commande($db);
$companystatic = new Societe($db);
$form = new Form($db);
$formfile = new FormFile($db);
$help_url = "EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
 
llxHeader("", $langs->trans("Orders"), $help_url);


print load_fiche_titre($langs->trans("Patient Worksheet Status Summary"), '', 'order');

 
print '<div class="fichecenter"><div class="fichethirdleft">';
 
$tmp = getCustomerOrderPieChart($socid);
if ($tmp) {
	 print $tmp;
	 print '<br>';
}
 
 
/*
 * Draft orders
 */

if (!empty($conf->commande->enabled)) {
	 $sql = "SELECT c.rowid, c.ref, s.nom as name, s.rowid as socid";
	 $sql .= ", s.client";
	 $sql .= ", s.code_client";
	 $sql .= ", s.canvas";
	 $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	 $sql .= ", ".MAIN_DB_PREFIX."societe as s";
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	 }
	 $sql .= " WHERE c.fk_soc = s.rowid";
	 $sql .= " AND c.entity IN (".getEntity('commande').")";
	 $sql .= " AND c.fk_statut = 0";
	 if ($socid) {
		 $sql .= " AND c.fk_soc = ".((int) $socid);
	 }
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	 }
 
	 $resql = $db->query($sql);
	 if ($resql) {
		 print '<div class="div-table-responsive-no-min">';
		 print '<table class="noborder centpercent">';
		 print '<tr class="liste_titre">';
		 print '<th colspan="2">'.$langs->trans("DraftOrders").'</th></tr>';
		 $langs->load("orders");
		 $num = $db->num_rows($resql);
		 if ($num) {
			 $i = 0;
			 while ($i < $num) {
				 $obj = $db->fetch_object($resql);
 
				 $commandestatic->id = $obj->rowid;
				 $commandestatic->ref = $obj->ref;
 
				 $companystatic->id = $obj->socid;
				 $companystatic->name = $obj->name;
				 $companystatic->client = $obj->client;
				 $companystatic->code_client = $obj->code_client;
				 $companystatic->canvas = $obj->canvas;
 
				 print '<tr class="oddeven">';
				 print '<td class="nowrap">';
				 print $commandestatic->getNomUrl(1);
				 print "</td>";
				 print '<td class="nowrap">';
				 print $companystatic->getNomUrl(1, 'company', 16);
				 print '</td></tr>';
				 $i++;
			 }
		 } else {
			 print '<tr class="oddeven"><td colspan="3">'.$langs->trans("NoOrder").'</td></tr>';
		 }
		 print "</table></div><br>";
	 }
}
 
 
print '</div><div class="fichetwothirdright">';
 
 
$max = 5;
 
/*
 * Lattest modified orders
 */

$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut, c.facture, c.date_cloture as datec, c.tms as datem,";
$sql .= " s.nom as name, s.rowid as socid";
$sql .= ", s.client";
$sql .= ", s.code_client";
$sql .= ", s.canvas";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (empty($user->rights->societe->client->voir) && !$socid) {
	 $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('commande').")";
//$sql.= " AND c.fk_statut > 2";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (empty($user->rights->societe->client->voir) && !$socid) {
    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);
 
$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastModifiedOrders", $max).'</th></tr>';
 
	$num = $db->num_rows($resql);
	if ($num) {
	 $i = 0;
	 while ($i < $num) {
			 $obj = $db->fetch_object($resql);
 
			 print '<tr class="oddeven">';
			 print '<td width="20%" class="nowrap">';
 
			 $commandestatic->id = $obj->rowid;
			 $commandestatic->ref = $obj->ref;
 
			 $companystatic->id = $obj->socid;
			 $companystatic->name = $obj->name;
			 $companystatic->client = $obj->client;
			 $companystatic->code_client = $obj->code_client;
			 $companystatic->canvas = $obj->canvas;
 
			 print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			 print '<td width="96" class="nobordernopadding nowrap">';
			 print $commandestatic->getNomUrl(1);
			 print '</td>';
 
			 print '<td width="16" class="nobordernopadding nowrap">';
			 print '&nbsp;';
			 print '</td>';
 
			 print '<td width="16" class="nobordernopadding hideonsmartphone right">';
			 $filename = dol_sanitizeFileName($obj->ref);
			 $filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
			 $urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			 print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
			 print '</td></tr></table>';
 
			 print '</td>';
 
			 print '<td class="nowrap">';
			 print $companystatic->getNomUrl(1, 'company', 16);
			 print '</td>';
			 print '<td>'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';
			 print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, $obj->facture, 3).'</td>';
			 print '</tr>';
			 $i++;
		 }
	 }
	 print "</table></div><br>";
 } else {
	 dol_print_error($db);
}
 
$max = 10;
 
/*
 * Orders to process
*/

if (!empty($conf->commande->enabled)) {
	 $sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut, c.facture, c.date_commande as date, s.nom as name, s.rowid as socid";
	 $sql .= ", s.client";
	 $sql .= ", s.code_client";
	 $sql .= ", s.canvas";
	 $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	 $sql .= ", ".MAIN_DB_PREFIX."societe as s";
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	 }
	 $sql .= " WHERE c.fk_soc = s.rowid";
	 $sql .= " AND c.entity IN (".getEntity('commande').")";
	 $sql .= " AND c.fk_statut = ".Commande::STATUS_VALIDATED;
	 if ($socid) {
		 $sql .= " AND c.fk_soc = ".((int) $socid);
	 }
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	 }
	 $sql .= " ORDER BY c.rowid DESC";
 
	 $resql = $db->query($sql);
	 if ($resql) {
		 $num = $db->num_rows($resql);
 
		 print '<div class="div-table-responsive-no-min">';
		 print '<table class="noborder centpercent">';
		 print '<tr class="liste_titre">';
		 print '<th colspan="4">'.$langs->trans("OrdersToProcess").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_VALIDATED.'"><span class="badge">'.$num.'</span></a></th></tr>';
 
		 if ($num) {
			 $i = 0;
			 while ($i < $num && $i < $max) {
				 $obj = $db->fetch_object($resql);
				 print '<tr class="oddeven">';
				 print '<td class="nowrap" width="20%">';
 
				 $commandestatic->id = $obj->rowid;
				 $commandestatic->ref = $obj->ref;
 
				 $companystatic->id = $obj->socid;
				 $companystatic->name = $obj->name;
				 $companystatic->client = $obj->client;
				 $companystatic->code_client = $obj->code_client;
				 $companystatic->canvas = $obj->canvas;
 
				 print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				 print '<td width="96" class="nobordernopadding nowrap">';
				 print $commandestatic->getNomUrl(1);
				 print '</td>';
 
				 print '<td width="16" class="nobordernopadding nowrap">';
				 print '&nbsp;';
				 print '</td>';
 
				 print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				 $filename = dol_sanitizeFileName($obj->ref);
				 $filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				 $urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				 print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				 print '</td></tr></table>';
 
				 print '</td>';
 
				 print '<td class="nowrap">';
				 print $companystatic->getNomUrl(1, 'company', 24);
				 print '</td>';
 
				 print '<td class="right">'.dol_print_date($db->jdate($obj->date), 'day').'</td>'."\n";
 
				 print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, $obj->facture, 3).'</td>';
 
				 print '</tr>';
				 $i++;
			 }
			 if ($i < $num) {
				 print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
			 }
		 }
 
		 print "</table></div><br>";
	 } else {
		 dol_print_error($db);
	 }
}
 
/*
 * Orders that are in process
*/

if (!empty($conf->commande->enabled)) {
	 $sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut, c.facture, c.date_commande as date, s.nom as name, s.rowid as socid";
	 $sql .= ", s.client";
	 $sql .= ", s.code_client";
	 $sql .= ", s.canvas";
	 $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	 $sql .= ", ".MAIN_DB_PREFIX."societe as s";
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	 }
	 $sql .= " WHERE c.fk_soc = s.rowid";
	 $sql .= " AND c.entity IN (".getEntity('commande').")";
	 $sql .= " AND c.fk_statut = ".((int) Commande::STATUS_ACCEPTED);
	 if ($socid) {
		 $sql .= " AND c.fk_soc = ".((int) $socid);
	 }
	 if (empty($user->rights->societe->client->voir) && !$socid) {
		 $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	 }
	 $sql .= " ORDER BY c.rowid DESC";
 
	 $resql = $db->query($sql);
	 if ($resql) {
		 $num = $db->num_rows($resql);
 
		 print '<div class="div-table-responsive-no-min">';
		 print '<table class="noborder centpercent">';
		 print '<tr class="liste_titre">';
		 print '<th colspan="4">'.$langs->trans("OnProcessOrders").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_ACCEPTED.'"><span class="badge">'.$num.'</span></a></th></tr>';
 
		 if ($num) {
			 $i = 0;
			 while ($i < $num && $i < $max) {
				 $obj = $db->fetch_object($resql);
				 print '<tr class="oddeven">';
				 print '<td width="20%" class="nowrap">';
 
				 $commandestatic->id = $obj->rowid;
				 $commandestatic->ref = $obj->ref;
 
				 $companystatic->id = $obj->socid;
				 $companystatic->name = $obj->name;
				 $companystatic->client = $obj->client;
				 $companystatic->code_client = $obj->code_client;
				 $companystatic->canvas = $obj->canvas;
 
				 print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				 print '<td width="96" class="nobordernopadding nowrap">';
				 print $commandestatic->getNomUrl(1);
				 print '</td>';
 
				 print '<td width="16" class="nobordernopadding nowrap">';
				 print '&nbsp;';
				 print '</td>';
 
				 print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				 $filename = dol_sanitizeFileName($obj->ref);
				 $filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				 $urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				 print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				 print '</td></tr></table>';
 
				 print '</td>';
 
				 print '<td>';
				 print $companystatic->getNomUrl(1, 'company');
				 print '</td>';
 
				 print '<td class="right">'.dol_print_date($db->jdate($obj->date), 'day').'</td>'."\n";
 
				 print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, $obj->facture, 3).'</td>';
 
				 print '</tr>';
				 $i++;
			 }
			 if ($i < $num) {
				 print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
			 }
		 }
		 print "</table></div><br>";
	 } else {
		 dol_print_error($db);
	 }
}
 
 
print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardOrders', $parameters, $object); // Note that $action and $object may have been modified by hook
 
// End of page
llxFooter();
$db->close(); 