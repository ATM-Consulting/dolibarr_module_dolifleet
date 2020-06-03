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
 * \file    class/actions_dolifleet.class.php
 * \ingroup dolifleet
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsdoliFleet
 */
class ActionsdoliFleet
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		/*$error = 0; // Error counter
		$myvalue = 'test'; // A result value

		print_r($parameters);
		echo "action: " . $action;
		print_r($object);

		if (in_array('somecontext', explode(':', $parameters['context'])))
		{
		  // do something only for the context 'somecontext'
		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}*/
	}

	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if (!empty($parameters['object']) && is_object($parameters['object']) && get_class($parameters['object']) == "Societe" && $parameters['mode'] == 'add')
		{
			$this->results = $parameters['head'];
			$this->results[] = array(
				dol_buildpath('dolifleet/matrix_tab.php?socid='.$parameters['object']->id, 1),
				$langs->trans('rentalMatrix'),
				'matrix'
			);

			return 1;
		}
	}

	/**
	 * addSearchEntry Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user, $db;
		$langs->load('dolifleet@dolifleet');

		dol_include_once('/dolifleet/core/modules/moddoliFleet.class.php');
		$modDolifleet = new moddoliFleet($db);

		$arrayresult = array();
		if (empty($conf->global->DOLIFLEET_HIDE_QUICK_SEARCH) && $user->rights->dolifleet->read) {
			$str_search_vin = '&Listview_dolifleet_search_vin=' . urlencode($parameters['search_boxvalue']);
			$arrayresult['searchintovehiculevin'] = array(
				'position' => $modDolifleet->numero,
				'text' => img_object('', 'dolifleet@dolifleet') . ' VIN',
				'url' => dol_buildpath('/dolifleet/vehicule_list.php', 1) . '?search_by=Listview_dolifleet_search_vin'.$str_search_vin
			);

			$str_search_immat = '&Listview_dolifleet_search_immatriculation=' . urlencode($parameters['search_boxvalue']);
			$arrayresult['searchintovehiculeimmat'] = array(
				'position' => $modDolifleet->numero,
				'text' => img_object('', 'dolifleet@dolifleet') . ' Immat',
				'url' => dol_buildpath('/dolifleet/vehicule_list.php', 1) . '?search_by=Listview_dolifleet_search_immatriculation'.$str_search_immat
			);

		}

		$this->results = $arrayresult;

		return 0;
	}

	public function formBuilddocOptions($parameters, &$object, &$action, $hookmanager)
	{
//		var_dump($parameters);
	}
}
