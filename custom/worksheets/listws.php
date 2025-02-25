<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/commande/info.php
 *      \ingroup    commande
 *		\brief      Sale Order info page
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}

// require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
// if (!empty($conf->projet->enabled)) {
// 	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
// }

if (!$user->rights->commande->lire) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'bills'));

$socid = 0;
$comid = GETPOST("id", 'int');
$id = GETPOST("id", 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'commande', $comid, '');


// var_dump($user->id);

/*
 * View
 */

// $form = new Form($db);

$title = $langs->trans('Order')." - Reporting Tool";
$help_url = 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes|DE:Modul_Kundenaufträge';
llxHeader('', $title, $help_url);



readfile('listwsfiles/html.html');


// print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
echo "<input type='hidden' id='dol_login' value='".$_SESSION['dol_login']."'/>";
echo "<input type='hidden' id='dol_userid' value='".$user->id."'/>";
echo "<input type='hidden' id='dol_userapikey' value='".$user->api_key."'/>";
// var_dump($user->api_key);
