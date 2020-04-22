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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('dolifleet/class/vehicule.class.php');
dol_include_once('dolifleet/class/dictionaryContractType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeType.class.php');
dol_include_once('dolifleet/class/dictionaryVehiculeMark.class.php');

if(empty($user->rights->dolifleet->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('dolifleet@dolifleet');

$fk_soc = GETPOST('fk_soc', 'int');
$search_by=GETPOST('search_by', 'alpha');
if (!empty($search_by)) {
	$sall=GETPOST('sall');
	if (!empty($sall)) {
		$_GET[$search_by]=$sall;
	}
}

$massaction = GETPOST('massaction', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$object = new doliFleetVehicule($db);
$dictCT = new dictionaryContractType($db);
$dictVT = new dictionaryVehiculeType($db);
$dictVM = new dictionaryVehiculeMark($db);

$hookmanager->initHooks(array('vehiculelist'));

if ($object->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);
    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
}

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
    $massaction = '';
}

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha'))
{
	unset($fk_soc);
}

if (empty($reshook))
{
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $langs->trans('doliFleetVehiculeList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->dolifleet->all->read)) $type = 'mine';

$formconfirm = '';

$parameters = array('formConfirm' => $formconfirm);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;


// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($object->fields);
$fieldList = 't.'.implode(', t.', $keys);
if (!empty($object->isextrafieldmanaged))
{
    $keys = array_keys($extralabels);
	if(!empty($keys)) {
		$fieldList .= ', et.' . implode(', et.', $keys);
	}
}

$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' t ';

if (!empty($object->isextrafieldmanaged) && array_keys($extralabels))
{
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields et ON (et.fk_object = t.rowid)';
}

$sql.= ' WHERE 1=1';
$sql.= ' AND t.entity IN ('.getEntity('dolifleet', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;
if (!empty($fk_soc) && $fk_soc > 0) $sql.= ' AND t.fk_soc = '.$fk_soc;
// Add where from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

//print $sql;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_dolifleet', 'GET');
$form = new Form($db);

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

// configuration listView

$TTitle = array();

foreach ($object->fields as $fieldKey => $infos)
{
	if (isset($infos['label']) && $infos['visible'] > 0) $TTitle[$fieldKey] = $langs->trans($infos['label']);
}

$TTitle['status'] = $langs->trans('Status');

if (!empty(array_keys($extralabels)))
{
	$TTitle = array_merge($TTitle, $extralabels);
}

$listViewConfig = array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'allow-fields-select' => true
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'list' => array(
		'title' => $langs->trans('doliFleetVehiculeList')
		,'image' => 'title_generic.png'
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('NodoliFleet')
		,'picto_search' => img_picto('', 'search.png', '', 0)
		,'massactions'=>array(
//			'yourmassactioncode'  => $langs->trans('YourMassActionLabel')
		)
	)
	,'subQuery' => array()
	,'link' => array()
	,'type' => array(
		'date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
		,'tms' => 'date'
		,'date_immat'=>'date'
		,'date_customer_exploit'=>'date'
		,'km_date'=>'date'
		,'date_end_contract'=>'date'
	)
	,'search' => array(
//		'date_creation' => array('search_type' => 'calendars', 'allow_is_null' => true)
//		,'tms' => array('search_type' => 'calendars', 'allow_is_null' => false)
//		,'label' => array('search_type' => true, 'table' => array('t', 't'), 'field' => array('label')) // input text de recherche sur plusieurs champs
		'vin' => array('search_type' => true, 'table' => 't', 'field' => 'vin')
		,'fk_vehicule_type' => array('search_type' => $dictVT->getAllActiveArray('label'))
		,'fk_vehicule_mark' => array('search_type' => $dictVM->getAllActiveArray('label'))
		,'immatriculation' => array('search_type' => true, 'table' => 't', 'field' => 'immatriculation')
		,'date_immat' => array('search_type' => 'calendars', 'allow_is_null' => false)
		,'fk_soc' => array('search_type' => 'override', 'override'=> $form->select_company($fk_soc, 'fk_soc'))
		,'date_customer_exploit' => array('search_type' => 'calendars', 'allow_is_null' => false)
		,'km' => array('search_type' => true, 'table' => 't', 'field' => 'km')
		,'km_date' => array('search_type' => 'calendars', 'allow_is_null' => false)
		,'fk_contract_type' => array('search_type' => $dictCT->getAllActiveArray('label'))
		,'date_end_contract' => array('search_type' => 'calendars', 'allow_is_null' => false)
		,'status' => array('search_type' => doliFleetVehicule::$TStatus, 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
	)
	,'translate' => array()
	,'hide' => array(
		'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
	)
	,'title'=>$TTitle
	,'eval'=>array(
		'vin' => '_getObjectNomUrl(\'@rowid@\', \'@val@\')'
		,'fk_vehicule_type' => '_getValueFromId("@val@", "dictionaryVehiculeType")'
		,'fk_vehicule_mark' => '_getValueFromId("@val@", "dictionaryVehiculeMark")'
		,'fk_soc'			=> '_getSocieteNomUrl("@val@")'
		,'fk_contract_type' => '_getValueFromId("@val@", "dictionaryContractType")'
		,'status' => 'doliFleetVehicule::LibStatut("@val@", 5)' // Si on a un fk_user dans notre requête
	)
);

if (!empty($extralabels))
{
	foreach ($extralabels as $k => $v)
	{
//		$listViewConfig['search'][$k] = array(
//			'search_type' => 'override'
//			,'override' => $extrafields->showInputField($k, GETPOST('Listview_dolifleet_search_'.$k), '', '', 'Listview_dolifleet_search_')
//		);
        $listViewConfig['eval'][$k] = '_evalEF("'.$k.'", "@val@")';
	}

}

$r = new Listview($db, 'dolifleet');

// Change view from hooks
$parameters=array(  'listViewConfig' => $listViewConfig);
$reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if ($reshook>0)
{
    $listViewConfig = $hookmanager->resArray;
}


echo $r->render($sql, $listViewConfig);

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
$db->close();

/**
 * TODO remove if unused
 */
function _getObjectNomUrl($id)
{
	global $db;

	$o = new doliFleetVehicule($db);
	$res = $o->fetch($id, false);
	if ($res > 0)
	{
		return $o->getNomUrl(1);
	}

	return '';
}

/**
 * TODO remove if unused
 */
function _getSocieteNomUrl($fk_soc)
{
	global $db;

	$soc = new Societe($db);
	if ($soc->fetch($fk_soc) > 0)
	{
		return $soc->getNomUrl(1);
	}

	return '';
}

function _getValueFromId($id, $dictionaryClassname)
{
	global $db;

	if (class_exists($dictionaryClassname))
	{
		$dict = new $dictionaryClassname($db);
		return $dict->getValueFromId($id, 'label');
	}
	else return '';
}

function _evalEF($key, $val)
{
	global $extrafields;

	return $extrafields->showOutputField($key, $val);
}
