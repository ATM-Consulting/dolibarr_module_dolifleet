<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/about.php
 * 	\ingroup	dolifleet
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
    $res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/dolifleet.lib.php';
dol_include_once('/dolifleet/class/vehiculeRentalMatrix.class.php');
dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');

$action = GETPOST('action');
$type = GETPOST('type');
$mark = GETPOST('mark');
$delay = GETPOST('delay');
$amount = GETPOST('amount');


// Translations
$langs->load('dolifleet@dolifleet');

// Access control
if (! $user->admin) {
    accessforbidden();
}

$object = new doliFleetVehiculeRentalMatrix($db);

/*
 * Actions
 */



$dictType = new dictionaryVehiculeType($db);
$dictMark = new dictionaryVehiculeMark($db);

$sql = "SELECT rowid  FROM ".$object->table_element;
$sql.= " WHERE fk_soc = 0";
$sql.= " ORDER BY fk_c_type_vh ASC, fk_c_mark_vh ASC";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);
else $num = $db->num_rows($resql);

/*
 * View
 */
$page_name = 'rentalMatrix';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = dolifleetAdminPrepareHead();
dol_fiche_head(
    $head,
    'matrix',
    $langs->trans('Module104087Name'),
    -1,
    'dolifleet@dolifleet'
);


print '<div class="div-table-responsive">';

print '<form id="RentalMatrixForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addRentalMatrix">';

print '<table class="border liste"  width="100%">';

// table header
print '<tr class="liste_titre">';
print '<td align="center">';
print $langs->trans('vehiculeType');
print '</td>';
print '<td align="center">';
print $langs->trans('vehiculeMark');
print '</td>';
print '<td align="center">';
print $langs->trans('vehiculeDelayExploit');
print '</td>';
print '<td align="center">';
print $langs->trans('VehiculeRental');
print '</td>';
print '<td align="center"></td>';
print '</tr>';

// new line
print '<tr class="oddeven">';
print '<td align="center">';
$TType = $dictType->getAllActiveArray('label');
print $form->selectarray('type', $TType, $type,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';
print '<td align="center">';
$TMark = $dictMark->getAllActiveArray('label');
print $form->selectarray('mark', $TMark, $mark,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';
print '<td align="center">';
print '<input type="number" name="delay" id="delay" step="1" value="'.$delay.'">&nbsp;'.$langs->trans('Months');
print '</td>';
print '<td align="center">';
print '<input type="number" name="amount" min="0" step="0.01" value="'.$amount.'">';
print '</td>';
print '<td align="center">';
print '<input class="button" type="submit" name="add" value="'.$langs->trans("Add").'">';
print '</td>';
print '</tr>';


print '</table>';

print '</form>';

print '<br />';

print '<table class="border liste"  width="100%">';

// table header
print '<tr class="liste_titre">';
print '<td align="center">';
print $langs->trans('vehiculeType');
print '</td>';
print '<td align="center">';
print $langs->trans('vehiculeMark');
print '</td>';
print '<td align="center">';
print $langs->trans('vehiculeDelayExploit');
print '</td>';
print '<td align="center">';
print $langs->trans('VehiculeRental');
print '</td>';
print '<td align="center"></td>';
print '</tr>';

if (empty($num))
{
	print '<tr class="oddeven">';
	print '<td align="center" colspan="5">';
	print $langs->trans('NodoliFleet');
	print '</td>';
	print '<tr>';
}
else
{
	while ($obj = $db->fetch_object($resql))
	{
		$matrixline = new doliFleetVehiculeRentalMatrix($db);
		$matrixline->fetch($obj->rowid);

		print '<tr class="oddeven">';
		print '<td align="center">';
		print $dictType->getValueFromId($matrixline->fk_c_type_vh);
		print '</td>';
		print '<td align="center">';
		print $dictMark->getValueFromId($matrixline->fk_c_mark_vh);
		print '</td>';
		print '<td align="center">';
		print $matrixline->delay.' '.$langs->trans('Months');
		print '</td>';
		print '<td align="center">';
		print price($matrixline->amount_ht);
		print '</td>';
		print '<td align="center"></td>';
		print '</tr>';
	}
}


print '</table>';

print '</div>';

dol_fiche_end();

llxFooter();
$db->close();
