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
 * 	\file		admin/dolifleet.php
 * 	\ingroup	dolifleet
 * 	\brief		This file is an example module setup page
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
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->loadLangs(array('dolifleet@dolifleet', 'admin', 'other'));

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if ($code == "DOLIFLEET_MOTRICE_TYPES")
	{
		if (dolibarr_set_const($db, $code, serialize(GETPOST($code)), 'chaine', 0, '', $conf->entity) > 0)
		{
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			dol_print_error($db);
		}
	}
	elseif (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "doliFleetSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = dolifleetAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104087Name"),
    -1,
    "dolifleet@dolifleet"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';


if(!function_exists('setup_print_title')){
    print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
    exit;
}

setup_print_title("Parameters");

print '<tr>';
print '<td>'.$langs->trans('DOLIFLEET_MOTRICE_TYPES').'</td>';
print '<td></td>';
print '<td><form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="action" value="set_DOLIFLEET_MOTRICE_TYPES">';
dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
$dict = new dictionaryVehiculeType($db);
$TType = $dict->getAllActiveArray('label');
print $form->multiselectarray('DOLIFLEET_MOTRICE_TYPES', $TType, unserialize($conf->global->DOLIFLEET_MOTRICE_TYPES));
print '<input class="button" type="submit" value="'.$langs->trans('Save').'">';
print '</form></td>';
print '</tr>';

// Example with a yes / no select
//setup_print_on_off('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc');

// Example with imput
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'));

// Example with color
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc', array('type'=>'color'), 'input', 'ParamHelp');

// Example with placeholder
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array(),'textarea');


print '</table>';

dol_fiche_end(-1);

llxFooter();

$db->close();
