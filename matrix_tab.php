<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/dolifleet/lib/dolifleet.lib.php');
dol_include_once('/dolifleet/class/vehiculeRentalMatrix.class.php');
dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');

$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users", "dolifleet@dolifleet"));

$userCanRead = $user->rights->dolifleet->matrix->read;
$userCanCreate = $user->rights->dolifleet->matrix->write;
$userCanDelete = $user->rights->dolifleet->matrix->delete;

$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel		= GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');

$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : 0;
if ($user->socid) $socid = $user->socid;

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

$object = new Societe($db);
$matrix = new doliFleetVehiculeRentalMatrix($db);

$extrafields = new ExtraFields($db);

$hookmanager->initHooks(array('thirdpartymatrixcard', 'globalcard'));

if ($socid > 0) $object->fetch($socid);

/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'addMatrixLine' || $action == 'editMatrixLine')
	{
		if (!GETPOST('cancel'))
		{
			$matrix->setValues($_REQUEST);
			$ret = $matrix->create($user);
			if ($ret < 0 || !empty($matrix->errors))
			{
				setEventMessages('', $matrix->errors, "errors");
				if ($action == 'editMatrixLine') $action = 'edit';
			}
			else
			{
				setEventMessage('RecordSaved');
				header('Location: '.$_SERVER['PHP_SELF']."?socid=".$socid);
				exit;
			}
		}
	}

	if ($action == 'confirm_delMatrixLine')
	{
		$matrix->id = $id;
		$ret = $matrix->delete($user);
	}

	if ($action == 'importGeneral')
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$matrix->table_element;
		$sql.= " WHERE fk_soc = 0";

		$res = $db->query($sql);
		if ($res)
		{
			while ($obj = $db->fetch_object($res))
			{
				$mat = new doliFleetVehiculeRentalMatrix($db);
				$mat->fetch($obj->rowid);
				$mat->id = 0;
				$mat->fk_soc = $socid;
				$mat->create($user);
			}
		}
	}
}

/*
 *  View
 */

$form = new Form($db);

if ($socid > 0 && empty($object->id))
{
	$result = $object->fetch($socid);
	if ($result <= 0) dol_print_error('', $object->error);
}

$dictType = new dictionaryVehiculeType($db);
$TType = $dictType->getAllActiveArray('label');

$dictMark = new dictionaryVehiculeMark($db);
$TMark = $dictMark->getAllActiveArray('label');

$sql = "SELECT rowid  FROM ".MAIN_DB_PREFIX.$matrix->table_element;
$sql.= " WHERE fk_soc = ".$object->id;
if (!empty($search_type) && $search_type != -1) $sql.= " AND fk_c_type_vh = ".$search_type;
if (!empty($search_mark) && $search_mark != -1) $sql.= " AND fk_c_mark_vh = ".$search_mark;
$sql.= " ORDER BY fk_c_type_vh ASC, fk_c_mark_vh ASC, delay ASC";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);
else $num = $db->num_rows($resql);

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name." - ".$langs->trans('Card');
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$head = societe_prepare_head($object);

dol_fiche_head($head, 'matrix', $langs->trans("ThirdParty"), 0, 'company');

//$formconfirm = getFormConfirmdoliFleetVehicule($form, $matrix, $action);

if ($action === 'delMatrixLine' && $userCanCreate)
{
	$body = $langs->trans('ConfirmDeldoliFleetLineBody');
	$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&id='.$id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delMatrixLine', '', 0, 1);
}

if (!empty($formconfirm)) print $formconfirm;

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

dol_fiche_end();

print '<br>';

print '<div class="div-table-responsive">';

if (empty($num))
{
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX.$matrix->table_element;
	$sql.= " WHERE fk_soc = 0";
	$resql = $db->query($sql);
	if ($resql)
	{
		print '<form method="POST" name="import" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="socid" value="'.$object->id.'">';
		print '<input type="hidden" name="action" value="importGeneral">';
		print '<div class="warning">';
		print '<p>'.$langs->trans('NoMatrixDefined').'</p>';

		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0 && $userCanCreate)
		{
			print '<p>'.$langs->trans('CanImportMatrix');
			print '<br><input type="submit" class="button" value="'.$langs->trans('ImportConf').'"></p>';
		}

		print '</div>';
		print '</form>';
	}

}

if ($userCanCreate)
{
	print '<form id="NewRentalMatrixForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="addMatrixLine">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';
	print '<input type="hidden" name="fk_soc" value="'.$object->id.'">';

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
}

print '<form id="NewRentalMatrixForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="socid" value="'.$object->id.'">';
print '<input type="hidden" name="fk_soc" value="'.$object->id.'">';

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

		if ($action == "edit" && $id == $matrixline->id && $userCanCreate)
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
			if ($userCanCreate) print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&action=edit&id='.$matrixline->id.'">'.img_edit().'</a>';
			if ($userCanDelete) print '<a href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&action=delMatrixLine&id='.$matrixline->id.'">'.img_delete().'</a>';
			print '</td>';
			print '</tr>';
		}


	}
}


print '</table>';

print '</form>';

print '</div>';

llxFooter();
$db->close();
