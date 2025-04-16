<?php
/* Copyright (C) 2025 Mostasim Mostasim Mahmud <mostasim.m@aikhanlab.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    deliverypoint/lib/deliverypoint.lib.php
 * \ingroup deliverypoint
 * \brief   Library files with common functions for DeliveryPoint
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function deliverypointAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("deliverypoint@deliverypoint");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/deliverypoint/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/deliverypoint/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/deliverypoint/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@deliverypoint:/deliverypoint/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@deliverypoint:/deliverypoint/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'deliverypoint@deliverypoint');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'deliverypoint@deliverypoint', 'remove');

	return $head;
}
