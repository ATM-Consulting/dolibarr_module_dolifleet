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

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('dolifleet/class/vehicule.class.php');
dol_include_once('dolifleet/lib/dolifleet.lib.php');

if(empty($user->rights->dolifleet->read)) accessforbidden();

$langs->load('dolifleet@dolifleet');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$vin = GETPOST('vin');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'vehiculecard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$object = new doliFleetVehicule($db);

if (!empty($id) || !empty($ref)) $object->fetch($id, true, $ref);
if (!empty($vin)) $object->fetchBy($vin,'vin', false);

$hookmanager->initHooks(array($contextpage, 'globalcard'));


if ($object->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);

    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
    $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}

// Initialize array of search criterias
//$search_all=trim(GETPOST("search_all",'alpha'));
//$search=array();
//foreach($object->fields as $key => $val)
//{
//    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
//}

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

    if ($cancel)
    {
        if (! empty($backtopage))
        {
            header("Location: ".$backtopage);
            exit;
        }
        $action='';
    }

    // For object linked
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once




    $error = 0;
	switch ($action) {
		case 'add':
		case 'update':
			$object->setValues($_REQUEST); // Set standard attributes

            if ($object->isextrafieldmanaged)
            {
                $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
                if ($ret < 0) $error++;
            }

//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($object->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}

			// ...

			if ($error > 0)
			{
				$action = 'edit';
				break;
			}

			$res = $object->save($user);

            if ($res < 0)
            {
                setEventMessage($object->errors, 'errors');
                if (empty($object->id)) $action = 'create';
                else $action = 'edit';
                break;
            }
            else
            {
                header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
                exit;
            }
        case 'update_extras':

            $object->oldcopy = dol_clone($object);

            // Fill array 'array_options' with data from update form
            $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
            if ($ret < 0) $error++;

            if (! $error)
            {
                $result = $object->insertExtraFields('DOLIFLEET_MODIFY');
                if ($result < 0)
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }
            }

            if ($error) $action = 'edit_extras';
            else
            {
                header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
                exit;
            }
            break;
		case 'confirm_clone':
			$object->cloneObject($user);

			header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_modif':
		case 'confirm_reopen':
			if (!empty($user->rights->dolifleet->write)) $object->setDraft($user);

			break;
		case 'confirm_validate':
			if (!empty($user->rights->dolifleet->write)) $object->setValid($user);

			header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_delete':
			if (!empty($user->rights->dolifleet->delete)) $object->delete($user);

			header('Location: '.dol_buildpath('/dolifleet/vehicule_list.php', 1));
			exit;

		// link from llx_element_element
		case 'dellink':
			$object->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
			exit;

		case 'addActivity':
			$type = GETPOST('activityTypes', 'int');
			$date_start = dol_mktime(0, 0, 0, GETPOST('activityDate_startmonth'), GETPOST('activityDate_startday'), GETPOST('activityDate_startyear'));
			$date_end = dol_mktime(23, 59, 59, GETPOST('activityDate_endmonth'), GETPOST('activityDate_endday'), GETPOST('activityDate_endyear'));
			$error = 0;

			$ret = $object->addActivity($type, $date_start, $date_end);
			if ($ret < 0)
			{
				setEventMessage($langs->trans($object->error), 'errors');
			}
			else
			{
				setEventMessage($langs->trans('ActivityAdded'));
			}

			header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_delActivity':
			$activityId = GETPOST('act_id', 'int');

			$ret = $object->delActivity($user, $activityId);
			if ($ret < 0)
			{
				setEventMessage($object->error, "errors");
			}

			header('Location: '.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$object->id);
			exit;
	}
}


/**
 * View
 */
$form = new Form($db);

$title=$langs->trans('doliFleet');
llxHeader('', $title);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans('NewdoliFleet'), '', 'dolifleet@dolifleet');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head(array(), '');

    print '<table class="border centpercent">'."\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>'."\n";

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans('Create')).'">';
    print '&nbsp; ';
    print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans('Cancel')).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
    print '</div>';

    print '</form>';
}
else
{
    if (empty($object->id))
    {
        $langs->load('errors');
        print $langs->trans('ErrorRecordNotFound');
    }
    else
    {
        if (!empty($object->id) && $action === 'edit')
        {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

            $head = vehicule_prepare_head($object);
            $picto = 'dolifleet@dolifleet';
            dol_fiche_head($head, 'card', $langs->trans('doliFleet'), 0, $picto);

            print '<table class="border centpercent">'."\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

            print '</table>';

            dol_fiche_end();

            print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
            print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
            print '</div>';

            print '</form>';
        }
        elseif ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
        {
            $head = vehicule_prepare_head($object);
            $picto = 'dolifleet@dolifleet';
            dol_fiche_head($head, 'card', $langs->trans('doliFleet'), -1, $picto);

            $formconfirm = getFormConfirmdoliFleetVehicule($form, $object, $action);
            if (!empty($formconfirm)) print $formconfirm;


            $linkback = '<a href="' .dol_buildpath('/dolifleet/vehicule_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

            $morehtmlref='<div class="refidno">';
            /*
            // Ref bis
            $morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->write, 'string', '', 0, 1);
            $morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->write, 'string', '', null, null, '', 1);
            // Thirdparty
            $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
            */
            $morehtmlref.='</div>';


            $morehtmlstatus.=''; //$object->getLibStatut(2); // pas besoin fait doublon
            $object->ref = $object->vin;
            dol_banner_tab($object, 'vin', $linkback, 1, 'vin', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

            print '<div class="fichecenter">';

            print '<div class="fichehalfleft">'; // Auto close by commonfields_view.tpl.php
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">'."\n";

            // Common attributes
            //$keyforbreak='fieldkeytoswithonsecondcolumn';
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
            print '</table>';

			// Activités véhicule

			print load_fiche_titre($langs->trans('VehiculeActivities'), '', '');

			print '<form id="activityForm" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
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

			$ret = $object->getActivities();
			if ($ret == 0)
			{
				print '<tr><td colspan="3">'.$langs->trans('NodoliFleetActivity').'</td></tr>';
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

			dol_include_once('/dolifleet/class/dictionaryVehiculeActivityType.class.php');
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
			print '<input type="submit" name="addActivity" value="'.$langs->trans("Add").'">';
			print '</td>';

			print '</tr>';

			print '</table>';

			print '</form>';

			print '</div></div>'; // Fin fichehalfright & ficheaddleft
            print '</div>'; // Fin fichecenter

            print '<div class="clearboth"></div><br />';

			print '<div class="fichecenter">';

			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';
			print load_fiche_titre($langs->trans('LinkedVehicules'), '', '');

			printLinkedVehicules($object);
			print '</div>';

			print '<div class="fichehalfright">lol right';
			print '<div class="underbanner clearboth"></div>';

			print '</div></div>';

            print '<div class="tabsAction">'."\n";
            $parameters=array();
            $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {

                // Modify
                if (!empty($user->rights->dolifleet->write))
                {

					// Modify
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("doliFleetModify").'</a></div>'."\n";

					// Clone
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone">'.$langs->trans("doliFleetClone").'</a></div>'."\n";

                    // Activer
                    if ($object->status === doliFleetVehicule::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans('doliFleetActivate').'</a></div>'."\n";

                    // Désactiver
                    if ($object->status === doliFleetVehicule::STATUS_ACTIVE) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('doliFleetUnactivate').'</a></div>'."\n";

                }
                else
                {

					// Modify
					if ($object->status !== doliFleetVehicule::STATUS_ACTIVE) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("doliFleetModify").'</a></div>'."\n";

					// Clone
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("doliFleetClone").'</a></div>'."\n";


                    // Activer
                    if ($object->status === doliFleetVehicule::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('doliFleetActivate').'</a></div>'."\n";

                    // Désactiver
					if ($object->status === doliFleetVehicule::STATUS_ACTIVE) print '<div class="inline-block divButAction"><a class="butAction" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('doliFleetUnactivate').'</a></div>'."\n";

                }

                if (!empty($user->rights->dolifleet->delete))
                {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("doliFleetDelete").'</a></div>'."\n";
                }
                else
                {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("doliFleetDelete").'</a></div>'."\n";
                }
            }
            print '</div>'."\n";

            print '<div class="fichecenter"><div class="fichehalfleft">';
            $linktoelem = $form->showLinkToObjectBlock($object, null, array($object->element));
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

            print '</div><div class="fichehalfright"><div class="ficheaddleft">';

            // List of actions on element
            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
            $formactions = new FormActions($db);
            $somethingshown = $formactions->showactions($object, $object->element, $socid, 1);

            print '</div></div></div>';

            dol_fiche_end(-1);
        }
    }
}


llxFooter();
$db->close();
