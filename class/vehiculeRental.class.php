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

class dolifleetVehiculeRental extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule_rental';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule_rental';

	public $fk_vehicule;

	public $date_start;

	public $date_end;

	public $total_ht;

	public $fk_soc;

	public $fk_proposaldet;

	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:doliFleetVehicule:dolifleet/class/vehicule.class.php',
			'label' => 'doliFleetVehicule',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
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

		'total_ht' => array(
			'type' => 'price',
			'label' => 'totalHT',
			'enabled' => 1,
			'visible' => 1,
			'position' => 80
		),

		'fk_soc' => array(
			'type' => 'integer:Societe:societe/class/societe.class.php',
			'enabled' => 1,
			'visible' => 0,
			'position' => 90,
			'index' => 1,
		),

		'fk_proposaldet' => array(
			'type' => 'integer',
			'enabled' => 1,
			'visible' => 0,
			'position' => 100,
			'index' => 1,
		),
	);

	public function __construct($db)
	{
		parent::__construct($db);

		$this->init();
	}
}
