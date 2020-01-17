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
if (!GETPOST('cancel'))
{
	$type = GETPOST('fk_c_type_vh');
	$mark = GETPOST('fk_c_mark_vh');
	$delay = GETPOST('delay');
	$amount = GETPOST('amount_ht');
	$id = GETPOST('id');
}

$search_type = GETPOST('search_fk_c_type_vh');
$search_mark = GETPOST('search_fk_c_mark_vh');

if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha'))
{
	$search_type = '';
	$search_mark = '';
}


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
if ($action == 'addMatrixLine' || $action == 'editMatrixLine')
{
	if (!GETPOST('cancel'))
	{
		$object->setValues($_REQUEST);
		$ret = $object->create($user);
		if ($ret < 0 || !empty($object->errors))
		{
			setEventMessages('', $object->errors, "errors");
			if ($action == 'editMatrixLine') $action = 'edit';
		}
		else
		{
			setEventMessage('RecordSaved');
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		}
	}
}
if ($action == 'confirm_delMatrixLine')
{
	$object->id = $id;
	$ret = $object->delete($user);
}


$dictType = new dictionaryVehiculeType($db);
$TType = $dictType->getAllActiveArray('label');

$dictMark = new dictionaryVehiculeMark($db);
$TMark = $dictMark->getAllActiveArray('label');

$sql = "SELECT rowid  FROM ".MAIN_DB_PREFIX.$object->table_element;
$sql.= " WHERE fk_soc = 0";
if (!empty($search_type) && $search_type != -1) $sql.= " AND fk_c_type_vh = ".$search_type;
if (!empty($search_mark) && $search_mark != -1) $sql.= " AND fk_c_mark_vh = ".$search_mark;
$sql.= " ORDER BY fk_c_type_vh ASC, fk_c_mark_vh ASC, delay ASC";

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

$formconfirm = getFormConfirmdoliFleetVehicule($form, $object, $action);
if (!empty($formconfirm)) print $formconfirm;

print '<div class="div-table-responsive">';

print '<form id="NewRentalMatrixForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addMatrixLine">';

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
print '<tr class="oddeven">'
;
print '<td align="center">';
print $form->selectarray('fk_c_type_vh', $TType, $type,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';

print '<td align="center">';
print $form->selectarray('fk_c_mark_vh', $TMark, $mark,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';

print '<td align="center">';
print '<input type="number" name="delay" id="delay" step="1" value="'.$delay.'">&nbsp;'.$langs->trans('Months');
print '</td>';

print '<td align="center">';
print '<input type="number" name="amount_ht" min="0" step="0.01" value="'.$amount.'">';
print '</td>';

print '<td align="center">';
print '<input class="button" type="submit" name="add" value="'.$langs->trans("Add").'">';
print '</td>';

print '</tr>';


print '</table>';

print '</form>';

print '<br />';

print '<form id="NewRentalMatrixForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="border liste"  width="100%">';

// table filters
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">';
print $form->selectarray('search_fk_c_type_vh', $TType, $search_type,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';
print '<td class="liste_titre">';
print $form->selectarray('search_fk_c_mark_vh', $TMark, $search_mark,1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</td>';
print '<td class="liste_titre">';
print '</td>';
print '<td class="liste_titre">';
print '</td>';
print '<td class="liste_titre" align="center">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

// table header
print '<tr class="liste_titre">';
print '<td>';
print $langs->trans('vehiculeType');
print '</td>';
print '<td>';
print $langs->trans('vehiculeMark');
print '</td>';
print '<td>';
print $langs->trans('vehiculeDelayExploit');
print '</td>';
print '<td>';
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

		if ($action == "edit" && $id == $matrixline->id)
		{
			print '<tr class="oddeven">';
			print '<td>';
			print '<input type="hidden" name="id" value="'.$matrixline->id.'">';
			print '<input type="hidden" name="action" value="editMatrixLine">';
			print $form->selectarray('fk_c_type_vh', $TType, $matrixline->fk_c_type_vh,1, 0, 0, '', 0, 0, 0, '', '', 1);
			print '</td>';
			print '<td>';
			print $form->selectarray('fk_c_mark_vh', $TMark, $matrixline->fk_c_mark_vh,1, 0, 0, '', 0, 0, 0, '', '', 1);
			print '</td>';
			print '<td>';
			print '<input type="number" name="delay" id="delay" step="1" value="'.$matrixline->delay.'">&nbsp;'.$langs->trans('Months');
			print '</td>';
			print '<td>';
			print '<input type="number" name="amount_ht" min="0" step="0.01" value="'.$matrixline->amount_ht.'">';
			print '</td>';
			// actions
			print '<td align="center">';
			print '<input class="button" type="submit" name="add" value="'.$langs->trans("Modify").'">';
			print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</td>';
			print '</tr>';
		}
		else
		{
			print '<tr class="oddeven">';
			print '<td>';
			print $dictType->getValueFromId($matrixline->fk_c_type_vh);
			print '</td>';
			print '<td>';
			print $dictMark->getValueFromId($matrixline->fk_c_mark_vh);
			print '</td>';
			print '<td>';
			print $matrixline->delay.' '.$langs->trans('Months');
			print '</td>';
			print '<td>';
			print price($matrixline->amount_ht);
			print '</td>';
			// actions
			print '<td align="center">';
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$matrixline->id.'">'.img_edit().'</a>';
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=delMatrixLine&id='.$matrixline->id.'">'.img_delete().'</a>';
			print '</td>';
			print '</tr>';
		}


	}
}


print '</table>';

print '</form>';

print '</div>';

dol_fiche_end();

llxFooter();
$db->close();
