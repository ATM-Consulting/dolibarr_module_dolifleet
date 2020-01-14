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
 *	\file		lib/dolifleet.lib.php
 *	\ingroup	dolifleet
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function dolifleetAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('dolifleet@dolifleet');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/dolifleet/admin/dolifleet_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/dolifleet/admin/vehicule_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;

    $head[$h][0] = dol_buildpath("/dolifleet/admin/dolifleet_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleet');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	doliFleetVehicule	$object		Object company shown
 * @return 	array				Array of tabs
 */
function vehicule_prepare_head(doliFleetVehicule $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("doliFleetVehiculeCard");
    $head[$h][2] = 'card';
    $h++;

	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleet');

	return $head;
}

/**
 * @param Form      $form       Form object
 * @param doliFleet  $object     doliFleet object
 * @param string    $action     Triggered action
 * @return string
 */
function getFormConfirmdoliFleetVehicule($form, $object, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmActivatedoliFleetVehiculeBody', $object->immatriculation);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmActivatedoliFleetVehiculeTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'modif' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmReopendoliFleetVehiculeBody', $object->immatriculation);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopendoliFleetVehiculeTitle'), $body, 'confirm_modif', '', 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->dolifleet->delete))
    {
        $body = $langs->trans('ConfirmDeletedoliFleetVehiculeBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmClonedoliFleetVehiculeBody', $object->immatriculation);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmClonedoliFleetVehiculeTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action === 'delActivity' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmDelActivitydoliFleetVehiculeBody', $object->immatriculation);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&act_id='.GETPOST('act_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delActivity', '', 0, 1);
	}
	elseif ($action === 'unlinkVehicule' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeBody', $object->immatriculation);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&linkVehicule_id='.GETPOST('linkVehicule_id'), $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeTitle'), $body, 'confirm_unlinkVehicule', '', 0, 1);
	}

    return $formconfirm;
}

/**
 * @param doliFleetVehicule $object
 */
function printLinkedVehicules($object)
{
	global $langs, $db, $form, $conf;

	print load_fiche_titre($langs->trans('LinkedVehicules'), '', '');

	print '<form id="vehiculeLinkedForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="addVehiculeLink">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<table class="border" width="100%">'."\n";
	print '<tr class="liste_titre">';
	print '<td align="center">Immatriculation</td>';
	print '<td align="center">'.$langs->trans('DateStart').'</td>';
	print '<td align="center">'.$langs->trans('DateEnd').'</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$object->getLinkedVehicules();
	if (empty($object->linkedVehicules))
	{
		print '<tr><td align="center" colspan="4">'.$langs->trans('NodoliFleet').'</td></tr>';
	}
	else
	{
		foreach ($object->linkedVehicules as $vehiculelink)
		{
			$veh = new doliFleetVehicule($db);
			print '<tr>';
			print '<td align="center">';

			$veh->fetch($vehiculelink->fk_other_vehicule);

			print $veh->getLinkUrl(0, '', 'immatriculation');
			print '</td>';
			print '<td align="center">'.dol_print_date($vehiculelink->date_start, "%d/%m/%Y").'</td>';
			print '<td align="center">'.(!empty($vehiculelink->date_end) ? dol_print_date($vehiculelink->date_end, "%d/%m/%Y") : '').'</td>';
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=unlinkVehicule&linkVehicule_id='.$vehiculelink->id.'"><span class="fas fa-unlink"></span></a> </td>';
			print '</tr>';
		}
	}

	// new link
	print '<tr">';
	$sql = "SELECT v.rowid, v.immatriculation, vt.label FROM ".MAIN_DB_PREFIX."dolifleet_vehicule as v";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_dolifleet_vehicule_type as vt ON vt.rowid = v.fk_vehicule_type";
	$sql.= " WHERE v.status = 1";
	$DOLIFLEET_MOTRICE_TYPES = unserialize($conf->global->DOLIFLEET_MOTRICE_TYPES);
	if (!empty($DOLIFLEET_MOTRICE_TYPES))
	{
		if (in_array($object->fk_vehicule_type, $DOLIFLEET_MOTRICE_TYPES))
			$sql.= " AND v.fk_vehicule_type NOT IN (".implode(', ', $DOLIFLEET_MOTRICE_TYPES).")";
		else
			$sql.= " AND v.fk_vehicule_type IN (".implode(', ', $DOLIFLEET_MOTRICE_TYPES).")";
	}
	else
	{
		// a minima on ne peut lier 2 véhicules de même nature
		$sql.= " AND v.fk_vehicule_type <> ".$object->fk_vehicule_type;
	}
	$resql = $db->query($sql);
	$Tab = array();
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$Tab[$obj->rowid] = $obj->label.' - '.$obj->immatriculation;
		}
	}

	print '<td align="center">';
	print $form->selectarray('linkVehicule_id', $Tab, GETPOST('linkVehicule_id'),1, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td>';
	print '<td align="center">';
	print $form->selectDate('', 'linkDate_start');
	print '</td>';

	print '<td align="center">';
	print $form->selectDate('', 'linkDate_end');
	print '</td>';

	print '<td align="center">';
	print '<input type="submit" name="linkVehicule" value="'.$langs->trans("Add").'">';
	print '</td>';
	print '<td align="center"></td>';
	print '</tr>';

	print '</table>';

	print '</form>';
}

function printVehiculeRental($object)
{
	global $langs, $db, $form, $conf;

	print load_fiche_titre($langs->trans('VehiculeRentals'), '', '');

}
