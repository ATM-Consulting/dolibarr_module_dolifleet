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

//    $head[$h][0] = dol_buildpath("/dolifleet/admin/dolifleet_setup.php", 1);
//    $head[$h][1] = $langs->trans("Parameters");
//    $head[$h][2] = 'settings';
//    $h++;
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
        $body = $langs->trans('ConfirmActivatedoliFleetVehiculeBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmActivatedoliFleetVehiculeTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'modif' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmReopendoliFleetVehiculeBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopendoliFleetVehiculeTitle'), $body, 'confirm_modif', '', 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->dolifleet->delete))
    {
        $body = $langs->trans('ConfirmDeletedoliFleetVehiculeBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeletedoliFleetVehiculeTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->dolifleet->write))
    {
        $body = $langs->trans('ConfirmClonedoliFleetVehiculeBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmClonedoliFleetVehiculeTitle'), $body, 'confirm_clone', '', 0, 1);
    }

    return $formconfirm;
}
