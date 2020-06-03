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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('dolifleet/class/rentalProposal.class.php');
dol_include_once('dolifleet/class/vehicule.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeActivityType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('dolifleet/lib/dolifleet.lib.php');

if(empty($user->rights->dolifleet->rentalproposal->read)) accessforbidden();

$langs->load('dolifleet@dolifleet');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$lineid = GETPOST('lineid', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'dolifleetrentalproposalcard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$object = new dolifleetRentalProposal($db);

if (!empty($id) || !empty($ref)) $object->fetch($id, true, $ref);

$hookmanager->initHooks(array('dolifleetrentalproposalcard', 'globalcard'));


if ($object->isextrafieldmanaged)
{
	$extrafields = new ExtraFields($db);

	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}

$permissiontoadd = $user->rights->dolifleet->rentalproposal->write;
$upload_dir = $conf->dolifleet->multidir_output[$conf->entity];

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

	// Action to build doc
	// $action must be defined
	// $id must be defined
	// $object must be defined and must have a method generateDocument().
	// $permissiontoadd must be defined
	// $upload_dir must be defined (example $conf->projet->dir_output . "/";)
	// $hidedetails, $hidedesc, $hideref and $moreparams may have been set or not.
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

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

			if ($error > 0)
			{
				$action = 'edit';
				break;
			}

			$res = $object->save($user);
			if ($res <= 0)
			{
				setEventMessages('', $object->errors, 'errors');
				if (empty($object->id)) $action = 'create';
				else $action = 'edit';
			}
			else
			{
				header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
				exit;
			}
		case 'update_extras':

			$object->oldcopy = dol_clone($object);

			// Fill array 'array_options' with data from update form
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
			if ($ret < 0) $error++;

			if (! $error)
			{
				$result = $object->insertExtraFields('DOLIFLEETRENTALPROPOSAL_MODIFY');
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			if ($error) $action = 'edit_extras';
			else
			{
				header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
				exit;
			}
			break;
		case 'confirm_clone':
			$object->cloneObject($user);

			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
			exit;

		case 'modif':
		case 'reopen':
			if (!empty($user->rights->dolifleet->rentalproposal->write)) $object->setDraft($user);

			break;

		case 'confirm_validate':
			if (!empty($user->rights->dolifleet->rentalproposal->validate)) $object->setValid($user);

			if (!empty($object->errors)) setEventMessages('', $object->errors, "errors");

			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_accept':
			if (!empty($user->rights->dolifleet->rentalproposal->validate)) $object->setAccepted($user);

			if (!empty($object->errors)) setEventMessages('', $object->errors, "errors");

			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_close':
			if (!empty($user->rights->dolifleet->rentalproposal->validate)) $object->setClosed($user);

			if (!empty($object->errors)) setEventMessages('', $object->errors, "errors");

			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_delete':
			if (!empty($user->rights->dolifleet->rentalproposal->delete)) $object->delete($user);

			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_list.php', 1));
			exit;

		// link from llx_element_element
		case 'dellink':
			$object->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
			exit;

		case 'updateLine':
			if (!GETPOST('cancel'))
			{
				$line = new dolifleetRentalProposalDet($db);
				$line->fetch($lineid);

				$line->setValues($_REQUEST);
				$line->id = $lineid;

				$ret = $line->create($user);
				if ($ret < 0)
				{
					setEventMessages('', array_merge($line->errors, array($line->error)), 'errors');
					$action = 'editline';
				}
				else
				{
					header('Location: '.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$object->id);
					exit;
				}
			}
			break;

	}
}


/**
 * View
 */
$form = new Form($db);
$formfile = new FormFile($db);
$dictTypeAct = new dictionaryVehiculeActivityType($db);
$dictTypeVeh = new dictionaryVehiculeType($db);

$title=$langs->trans('dolifleetRentalProposal');
llxHeader('', $title);

if ($action == 'create')
{
	print load_fiche_titre($langs->trans('NewdolifleetRentalProposal'), '', 'dolifleet@dolifleet');

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

			$head = rental_proposal_prepare_head($object);
			$picto = 'dolifleet@dolifleet';
			dol_fiche_head($head, 'card', $langs->trans('dolifleetRentalProposal'), 0, $picto);

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
			$head = rental_proposal_prepare_head($object);
			$picto = 'dolifleet@dolifleet';
			dol_fiche_head($head, 'card', $langs->trans('dolifleetRentalProposal'), -1, $picto);

			$formconfirm = getFormConfirmdoliFleetVehicule($form, $object, $action);
			if (!empty($formconfirm)) print $formconfirm;


			$linkback = '<a href="' .dol_buildpath('/dolifleet/rental_proposal_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

			$morehtmlref='<div class="refidno">';
			/*
			// Ref bis
			$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->rentalproposal->write, 'string', '', 0, 1);
			$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->dolifleet->rentalproposal->write, 'string', '', null, null, '', 1);
			// Thirdparty
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
			*/
			$morehtmlref.='</div>';


			$morehtmlstatus.=''; //$object->getLibStatut(2); // pas besoin fait doublon
			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

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

			print '<table class="border tableforfield centpercent">';

			$object->fetchLines();
			// Amount HT
			print '<tr><td class="titlefieldmiddle">'.$langs->trans('AmountHT').'</td>';

			$total_ht = 0;
			$subtotals = array();

			if (!empty($object->lines))
			{
				foreach ($object->lines as $l) {
					$total_ht+= $l->total_ht;
					$subtotals[$l->activity_type]['total'] += $l->total_ht;
					$subtotals[$l->activity_type][$l->fk_vehicule_type] += $l->total_ht;
				}
			}

			print '<td class="nowrap">'.price($total_ht, '', $langs, 0, - 1, - 1, $conf->currency).'</td>';
			print '</tr>';

			print '</table>';

			print '</div></div>'; // Fin fichehalfright & ficheaddleft
			print '</div>'; // Fin fichecenter

			print '<div class="clearboth"></div><br />';

			if (!empty($object->lines))
			{
				print '<div class="fichecenter">';

				print '<div class="div-table-responsive-no-min">';

				if ($action == "editline" && $object->status == dolifleetRentalProposal::STATUS_DRAFT)
				{
					print '<form id="editRentalLines" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="updateLine">';
					print '<input type="hidden" name="id" value="'.$object->id.'">';
					print '<input type="hidden" name="lineid" value="'.$lineid.'">';
				}

				print '<table id="tablelines" class="noborder noshadow" width="100%">';

				print '<tr class="liste_titre nodrag nodrop">';
				print '<td class="linecolimmat">'.$langs->trans('Immatriculation').'</td>';
				print '<td class="linecoldate">'.$langs->trans('date_customer_exploit').'</td>';
				print '<td class="linecoldescription">'.$langs->trans('Description').'</td>';
				print '<td class="linecolht right">'.$langs->trans('TotalHT').'</td>';
				print '<td class="linecoledit"></td>';
				print '</tr>';

				$typeAct = $typeVeh = 0;

				foreach ($object->lines as $line)
				{

					$modeEdit = ($action == 'editline' && $lineid == $line->id);

					if ($typeAct !== $line->activity_type)
					{
						$activityLabel = $dictTypeAct->getValueFromId($line->activity_type);
						print '<tr>';
						print '<td colspan="3" align="center" style="background-color: #adadad">';
						print $activityLabel;
						print '</td>';
						print '<td class="linecolht right" style="background-color: #adadad">'.$langs->trans('Total').' '.$activityLabel.' : '.price($subtotals[$line->activity_type]['total']).'</td>';
						print '<td style="background-color: #adadad"></td>';
						print '</tr>';
						$typeAct = $line->activity_type;
						$typeVeh = 0;
					}

					if ($typeVeh !== $line->fk_vehicule_type)
					{
						$VtypeLabel = $dictTypeVeh->getValueFromId($line->fk_vehicule_type);
						print '<tr><td colspan="3" align="center" style="background-color: #d4d4d4">';
						print $VtypeLabel;
						print '</td>';
						print '<td class="linecolht right" style="background-color: #d4d4d4">'.$langs->trans('Total').' '.$VtypeLabel.' : '.price($subtotals[$line->activity_type][$line->fk_vehicule_type]).'</td>';
						print '<td style="background-color: #d4d4d4"></td>';
						print '</tr>';
						$typeVeh = $line->fk_vehicule_type;
					}

					print '<tr id="row-'.$line->id.'"  class="nodrag nodrop">';
					print '<td class="linecolimmat">';
					$vehicule = new doliFleetVehicule($db);
					$vehicule->fetch($line->fk_vehicule);
					print $vehicule->getLinkUrl(0, '', 'immatriculation');
					print '</td>';
					print '<td class="linecoldate">'.dol_print_date($vehicule->date_customer_exploit).'</td>';
					print '<td class="linecoldescription">'.(!$modeEdit ? $line->showOutputField($line->fields['description'], 'description', $line->description) : $line->showInputField($line->fields['description'], 'description', $line->description)).'</td>';
					print '<td class="linecolht right">'.(!$modeEdit ? price($line->total_ht) : $line->showInputField($line->fields['total_ht'], 'total_ht', $line->total_ht)).'</td>';
					print '<td class="linecoledit">';
					if ($modeEdit && $object->status == dolifleetRentalProposal::STATUS_DRAFT)
					{
						print '<input class="button" type="submit" name="save" value="'.$langs->trans('Save').'">';
						print '<input class="button" type="submit" name="cancel" value="'.$langs->trans('Cancel').'">';
					}
					else
					{
						if ($object->status == dolifleetRentalProposal::STATUS_DRAFT) print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editline&lineid='.$line->id.'">'.img_edit().'</a>';
					}
					print '</td>';
					print '</tr>';
				}

				print '</table>';

				if ($action == "editline" && $object->status == dolifleetRentalProposal::STATUS_DRAFT) print '</form>';

				print '</div>';

				print '</div>'; // Fin fichecenter

				print '<div class="clearboth"></div><br />';
			}

			print '<div class="tabsAction">'."\n";
			$parameters=array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

			if (empty($reshook))
			{
				// Send
				//        print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";

				// Modify
				if (!empty($user->rights->dolifleet->rentalproposal->validate))
				{
					// Valid
					if ($object->status === dolifleetRentalProposal::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('doliFleetValid').'</a></div>'."\n";

					if ($object->status === dolifleetRentalProposal::STATUS_INPROGRESS )
					{
						// Reopen
						if ($object->fk_first_valid == $user->id) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('doliFleetReopen').'</a></div>'."\n";
						else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("OnlyUserWhoValidatedCanReopen")).'">'.$langs->trans('doliFleetReopen').'</a></div>'."\n";

						// Accept
						if ($object->fk_first_valid != $user->id) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=accept">'.$langs->trans('doliFleetAccept').'</a></div>'."\n";
						else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserMustBeDifferentFromValider")).'">'.$langs->trans('doliFleetAccept').'</a></div>'."\n";
					}

					// Close
					if ($object->status === dolifleetRentalProposal::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close">'.$langs->trans('doliFleetClose').'</a></div>'."\n";
				}
				else
				{
					// Valid
					if ($object->status === dolifleetRentalProposal::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('doliFleetValid').'</a></div>'."\n";

					if ($object->status === dolifleetRentalProposal::STATUS_INPROGRESS ) {
						// Reopen
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('doliFleetReopen') . '</a></div>' . "\n";

						// Accept
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">'.$langs->trans('doliFleetAccept').'</a></div>'."\n";
					}

					if ($object->status === dolifleetRentalProposal::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">'.$langs->trans('doliFleetClose').'</a></div>'."\n";
				}

				if ($object->status != dolifleetRentalProposal::STATUS_CLOSED)
				{
					if (!empty($user->rights->dolifleet->rentalproposal->delete))
					{
						print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deleteRental">'.$langs->trans("doliFleetDelete").'</a></div>'."\n";
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("doliFleetDelete").'</a></div>'."\n";
					}
				}
				else
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("CanNotDeleteClosedProposal")).'">'.$langs->trans("doliFleetDelete").'</a></div>'."\n";
				}

			}
			print '</div>'."\n";

			print '<div class="fichecenter"><div class="fichehalfleft">';

			print '<a name="builddoc"></a>'; // ancre
			// Documents
			$propalref = dol_sanitizeFileName($object->ref);
			$relativepath = $propalref.'/'.$propalref.'.pdf';
			$filedir = $conf->dolifleet->multidir_output[$object->entity].'/'.$propalref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->rights->dolifleet->rentalproposal->read;
			$delallowed = $user->rights->dolifleet->rentalproposal->write;
			print $formfile->showdocuments('dolifleet:rentalproposal', $propalref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, '', $object);

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
