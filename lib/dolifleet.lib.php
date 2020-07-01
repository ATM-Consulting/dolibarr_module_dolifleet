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

    $head[$h][0] = dol_buildpath("/dolifleet/admin/rental_matrix.php", 1);
    $head[$h][1] = $langs->trans("rentalMatrix");
    $head[$h][2] = 'matrix';
    $h++;

    $head[$h][0] = dol_buildpath("/dolifleet/admin/vehicule_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;

	if (!empty($conf->multicompany->enabled))
	{
		$head[$h][0] = dol_buildpath("/dolifleet/admin/multicompany_sharing.php", 1);
		$head[$h][1] = $langs->trans("multicompanySharing");
		$head[$h][2] = 'multicompanySharing';
		$h++;
	}

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
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleetVehicule');

	return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	dolifleetRentalProposal	$object		Object company shown
 * @return 	array				Array of tabs
 */
function rental_proposal_prepare_head(dolifleetRentalProposal $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("doliFleetRentalProposalCard");
    $head[$h][2] = 'card';
    $h++;

	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@dolifleet:/dolifleet/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'dolifleetRentalProposal');

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
    elseif ($action === 'validate' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmValidateRentalProposalBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidateRentalProposalTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'accept' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmAcceptRentalProposalBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptRentalProposalTitle'), $body, 'confirm_accept', '', 0, 1);
    }
    elseif ($action === 'close' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmCloseRentalProposalBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloseRentalProposalTitle'), $body, 'confirm_close', '', 0, 1);
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
    elseif ($action === 'deleteRental' && !empty($user->rights->dolifleet->delete))
    {
        $body = $langs->trans('ConfirmDeleteRentalBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmClonedoliFleetVehiculeBody', $object->immatriculation);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmClonedoliFleetVehiculeTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action === 'delActivity' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmDelActivitydoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&act_id='.GETPOST('act_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delActivity', '', 0, 1);
	}
	elseif ($action === 'unlinkVehicule' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&linkVehicule_id='.GETPOST('linkVehicule_id'), $langs->trans('ConfirmUnlinkVehiculedoliFleetVehiculeTitle'), $body, 'confirm_unlinkVehicule', '', 0, 1);
	}
	elseif ($action === 'delRental' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmDelRentaldoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&rent_id='.GETPOST('rent_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delRental', '', 0, 1);
	}
	elseif ($action === 'delOperation' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmDelOperationdoliFleetVehiculeBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&ope_id='.GETPOST('ope_id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delOperation', '', 0, 1);
	}
	elseif ($action === 'delMatrixLine' && !empty($user->rights->dolifleet->write))
	{
		$body = $langs->trans('ConfirmDeldoliFleetLineBody');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id='.GETPOST('id'), $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delMatrixLine', '', 0, 1);
	}

    return $formconfirm;
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeActivities($object, $fromcard = false)
{
	global $langs, $db, $form;
	print load_fiche_titre($langs->trans('VehiculeActivities'), '', '');

	print '<form id="activityForm" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="addActivity">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<table class="border" width="100%">'."\n";
	print '<tr class="liste_titre">
					<td align="center">'.$langs->trans('ActivityType').'</td>
					<td align="center">'.$langs->trans('DateStart').'</td>
					<td align="center">'.$langs->trans('DateEnd').'</td>
					<td></td>
					</tr>';

	$date_start = $date_end = '';
	if ($fromcard)
	{
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$ret = $object->getActivities($date_start, $date_end);
	if ($ret == 0)
	{
		print '<tr><td align="center" colspan="4">'.$langs->trans('NodoliFleetActivity').'</td></tr>';
	}
	else if ($ret > 0)
	{
		/** @var doliFleetVehiculeActivity $activity */
		foreach ($object->activities as $activity)
		{
			print '<tr>';
			print '<td align="center">'.$activity->getType().'</td>';
			print '<td align="center">'.dol_print_date($activity->date_start, "%d/%m/%Y").'</td>';
			print '<td align="center">'.(!empty($activity->date_end) ? dol_print_date($activity->date_end, "%d/%m/%Y") : '').'</td>';
			print '<td align="center">';
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delActivity&act_id='.$activity->id.'">'.img_delete().'</a>';
			print '</td>';
			print '</tr>';
		}
	}

	// ligne nouvelle activité
	print '<tr id="newActivity">';
	print '<td align="center">';

	$dict = new dictionaryVehiculeActivityType($db);
	$TTypeActivity =  $dict->getAllActiveArray('label');
	print $form->selectArray('activityTypes', $TTypeActivity, GETPOST('activityTypes'), 1);

	print '</td>';

	print '<td align="center">';
	print $form->selectDate('', 'activityDate_start');
	print '</td>';

	print '<td align="center">';
	print $form->selectDate('', 'activityDate_end');
	print '</td>';

	print '<td align="center">';
	print '<input class="button" type="submit" name="addActivity" value="'.$langs->trans("Add").'">';
	print '</td>';

	print '</tr>';

	print '</table>';

	print '</form>';
}

/**
 * @param doliFleetVehicule $object
 */
function printLinkedVehicules($object, $fromcard = false)
{
	global $langs, $db, $form, $conf;

	print load_fiche_titre($langs->trans('LinkedVehicules'), '', '');

	print '<form id="vehiculeLinkedForm" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
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

	$date_start = $date_end = '';
	if ($fromcard)
	{
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$object->getLinkedVehicules($date_start, $date_end);
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
	$sql .= " AND v.fk_soc = ".$object->fk_soc;
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
	print '<input class="button" type="submit" name="linkVehicule" value="'.$langs->trans("Add").'">';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</form>';
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeRental($object, $fromcard = false, $external = false)
{
	global $langs, $form;

	$title = $langs->trans('VehiculeRentals');
	if ($external) $title.= ' '.$langs->trans('Customer');

	print load_fiche_titre($title, '', '');

	print '<form id="vehiculeLinkedForm" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="addVehiculeRental">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<table class="border" width="100%">'."\n";
	print '<tr class="liste_titre">';
	print '<td align="center">'.$langs->trans('DateStart').'</td>';
	print '<td align="center">'.$langs->trans('DateEnd').'</td>';
	print '<td align="center">'.$langs->trans('TotalHT').'</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$date_start = $date_end = '';
	if ($fromcard)
	{
		$date_start = dol_now();
		$date_end = strtotime("+3 month", $date_start);
	}

	$object->getRentals($date_start, $date_end, $external);
	if (empty($object->rentals))
	{
		print '<tr>';
		print '<td align="center" colspan="4">'.$langs->trans('NodoliFleet').'</td>';
		print '</tr>';
	}
	else
	{

		foreach ($object->rentals as $rent)
		{
			print '<tr>';

			print '<td align="center">';
			print dol_print_date($rent->date_start, "%d/%m/%Y");
			print '</td>';

			print '<td align="center">';
			print dol_print_date($rent->date_end, "%d/%m/%Y");
			print '</td>';

			print '<td align="center">';
			print price($rent->total_ht);
			print '</td>';

			print '<td align="center">';
			if (!$external) print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delRental&rent_id='.$rent->id.'">'.img_delete().'</a>';
			print '</td>';

			print '</tr>';
		}
	}

	if (!$external)
	{
		// new line
		print '<tr>';

		print '<td align="center">';
		print $form->selectDate('', 'RentalDate_start');
		print '</td>';

		print '<td align="center">';
		print $form->selectDate('', 'RentalDate_end');
		print '</td>';

		print '<td align="center">';
		print '<input type="number" name="RentalTotal_HT" min="0" step="0.01" value="'.GETPOST('RentalTotal_HT').'">';
		print '</td>';

		print '<td align="center">';
		print '<input class="button" type="submit" name="addRental" value="'.$langs->trans("Add").'">';
		print '</td>';

		print '</tr>';
	}


	print '</table>';

	print '</form>';
}

/**
 * @param doliFleetVehicule $object
 */
function printVehiculeOpérations($object)
{
	global $langs, $form;

	print load_fiche_titre($langs->trans('VehiculeOperations'), '', '');

	print '<form id="vehiculeLinkedForm" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="addVehiculeOperation">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<table class="border" width="100%">'."\n";
	print '<tr class="liste_titre">';
	print '<td align="center">'.$langs->trans('VehiculeOperation').'</td>';
	print '<td align="center">'.$langs->trans('KM').'</td>';
	print '<td align="center">'.$langs->trans('VehiculeOperationDelay').'</td>';
	print '<td align="center">'.$langs->trans('VehiculeOperationLastDateDone').'</td>';
	print '<td align="center">'.$langs->trans('VehiculeOperationLastKmDone').'</td>';
	print '<td align="center"></td>';
	print '</tr>';

	$res = $object->getOperations();
	if ($res < 0) {
		setEventMessage($object->error,'errors');
	}
	if (empty($object->operations))
	{
		print '<tr><td align="center" colspan="6">'.$langs->trans('NodoliFleet').'</td></tr>';
	}
	else
	{
		foreach ($object->operations as $operation)
		{
			print '<tr>';
			print '<td align="center">'.$operation->getName().'</td>';
			print '<td align="center">'.(!empty($operation->km) ? price2num($operation->km) : '').'</td>';
			print '<td align="center">'.(!empty($operation->delai_from_last_op) ? $operation->delai_from_last_op.' '.$langs->trans('Months') : '').'</td>';
			print '<td align="center">';
			if (!empty($operation->date_done)) {
				print dol_print_date($operation->date_done, "%d/%m/%Y");
			}
			print '</td>';
			print '<td align="center">'.(!empty($operation->km_done)?$operation->km_done:'').'</td>';
			print '<td align="center">';
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delOperation&ope_id='.$operation->id.'">'.img_delete().'</a>';
			print '</td>';
			print '</tr>';
		}
	}

	// new line
	print '<tr>';

	print '<td align="center">';
	print $form->select_produits(GETPOST('productid'), 'productid', '', 20, 0, 1, 2, '', 2);
	print '</td>';

	print '<td align="center">';
	print '<input type="number" name="km" id="km" step="1" value="'.GETPOST('km').'">';
	print '</td>';

	print '<td align="center">';
	print '<input type="number" name="delay" id="delay" step="1" value="'.GETPOST('delay').'">&nbsp;'.$langs->trans('Months');
	print '</td>';

	print '<td align="center" colspan="3">';
	print '<input class="button" type="submit" name="addRental" value="'.$langs->trans("Add").'">';
	print '</td>';

	print '</tr>';

	print '</table>';

	print '</form>';
}

function printBannerVehicleCard($vehicle){

    global $db, $langs;

    $linkback = '<a href="' .dol_buildpath('/dolifleet/vehicule_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref='<div class="refidno">';
    if (! empty($vehicle->immatriculation)) $morehtmlref.= '<br>'.$langs->trans('immatriculation').': '.$vehicle->immatriculation;

    // marque
    dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');
    $dict = new dictionaryVehiculeMark($db);
    $morehtmlref.= '<br>'.$langs->trans('vehiculeMark').': '.$dict->getValueFromId($vehicle->fk_vehicule_mark);

    // type de véhicule
    dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
    $dict = new dictionaryVehiculeType($db);
    $morehtmlref.= '<br>'.$langs->trans('vehiculeType').': '.$dict->getValueFromId($vehicle->fk_vehicule_type);

    // client
    $vehicle->fetch_thirdparty();
    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$vehicle->thirdparty->getNomUrl(1, 'customer');
    /*
    // Ref bis
    $morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->write, 'string', '', 0, 1);
    $morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->write, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
    */
    $morehtmlref.='</div>';

    $vehicle->ref = $vehicle->vin;
    dol_banner_tab($vehicle, 'vin', $linkback, 1, 'vin', 'ref', $morehtmlref, '', 0, '', '');
}
