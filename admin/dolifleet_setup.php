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
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

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

if ($action == 'set')
{
	$ret = addDocumentModel($value, 'rentalproposal', $label, $scandir);
}

if ($action == 'setdoc')
{

	if (dolibarr_set_const($db, "DOLIFLEET_RENTALPROPOSAL_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->DOLIFLEET_RENTALPROPOSAL_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, 'rentalproposal');
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, 'rentalproposal', $label, $scandir);
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
print '<input class="butAction" type="submit" value="'.$langs->trans('Save').'">';
print '</form></td>';
print '</tr>';

setup_print_input_form_part('DOLIFLEET_DEFAULT_RENTAL_AMOUNT', $langs->trans('DOLIFLEET_DEFAULT_RENTAL_AMOUNT'));

if (empty($conf->global->DOLIFLEET_DELAY_SEARCH_OPERATIONS))
{
	dolibarr_set_const($db, 'DOLIFLEET_DELAY_SEARCH_OPERATIONS', 12, 'chaine', 0, '', $conf->entity);
}
setup_print_input_form_part('DOLIFLEET_DELAY_SEARCH_OPERATIONS');

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

/*
 *  Document templates generators
 *  Copy of invoice document code
 */
print '<br>';
print load_fiche_titre($langs->trans("RentalproposalPDFModules"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'rentalproposal'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
$activatedModels = array();

foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/dolifleet".$valdir);

		if (is_dir($dir))
		{
			$handle=opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					$filelist[]=$file;
				}
				closedir($handle);
				arsort($filelist);

				foreach($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

							if ($modulequalified)
							{
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								}
								else
								{
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("SetAsDefault"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print '<td class="center">';
								if ($conf->global->DOLIFLEET_RENTALPROPOSAL_ADDON_PDF == $name)
								{
									print img_picto($langs->trans("Default"), 'on');
								}
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';

dol_fiche_end(-1);

llxFooter();

$db->close();
