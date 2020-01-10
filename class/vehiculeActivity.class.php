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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class doliFleetVehiculeActivity extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule_activity';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule_activity';

	/** @var int $fk_vehicule Object link to vehicule */
	public $fk_vehicule;

	/** @var int $fk_type Object type */
	public $fk_type;

	public $date_start;

	public $date_end;

	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:doliFleetVehicule:dolifleet/class/vehicule.class.php',
			'label' => 'Type',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
			'index' => 1,
		),

		'fk_type' => array(
			'type' => 'sellist:c_dolifleet_vehicule_activity_type:label:rowid::active=1',
			'label' => 'vehiculeMark',
			'visible' => 1,
			'enabled' => 1,
			'position' => 50,
			'index' => 1,
		),

		'date_start' => array(
			'type' => 'date',
			'label' => 'date_start',
			'enabled' => 1,
			'visible' => 1,
			'position' => 70,
			'searchall' => 1,
		),

		'date_end' => array(
			'type' => 'date',
			'label' => 'date_end',
			'enabled' => 1,
			'visible' => 1,
			'position' => 70,
			'searchall' => 1,
		),
	);

	/**
	 * doliFleetVehiculeActivity constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->entity = $conf->entity;
	}

	public function getType()
	{
		dol_include_once('/dolifleet/class/dictionaryVehiculeActivityType.class.php');
		$dict = new dictionaryVehiculeActivityType($this->db);

		if (!empty($this->fk_type))
		{
			return $dict->getValueFromId($this->fk_type);
		}

		return '';
	}
}
