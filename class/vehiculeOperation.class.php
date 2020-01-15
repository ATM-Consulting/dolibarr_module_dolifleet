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

class dolifleetVehiculeOperation extends SeedObject
{
	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * To Plan status
	 */
	const STATUS_TOPLAN = 1;

	/**
	 * Planned status
	 */
	 const STATUS_PLANNED = 2;

	/**
	 * Done status
	 */
	 const STATUS_DONE = 3;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'doliFleetOperationStatusShortDraft'
		,self::STATUS_TOPLAN => 'doliFleetOperationStatusShortToPlan'
		,self::STATUS_PLANNED => 'doliFleetOperationStatusShortPlanned'
		,self::STATUS_DONE => 'doliFleetOperationStatusShortDone'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule_operation';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule_operation';

	/** @var int $fk_vehicule Object link to vehicule */
	public $fk_vehicule;

	public $fk_product;

	public $status;

	public $km;

	public $delay_from_last_op;

	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:doliFleetVehicule:dolifleet/class/vehicule.class.php',
			'label' => 'doliFleetVehicule',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
			'index' => 1,
		),

		'fk_product' => array(
			'type' => 'integer:Product:product/class/product.class.php',
			'label' => 'VehiculeOperation',
			'visible' => 1,
			'enabled' => 1,
			'position' => 20,
			'index' => 1,
		),

		'status' => array(
			'type' => 'integer',
			'label' => 'Status',
			'enabled' => 1,
			'visible' => 0,
			'notnull' => 1,
			'default' => 0,
			'index' => 1,
			'position' => 30,
			'arrayofkeyval' => array(
				self::STATUS_DRAFT => 'doliFleetOperationStatusShortDraft'
				,self::STATUS_TOPLAN => 'doliFleetOperationStatusShortToPlan'
				,self::STATUS_PLANNED => 'doliFleetOperationStatusShortPlanned'
				,self::STATUS_DONE => 'doliFleetOperationStatusShortDone'
			)
		),

		'rank' => array(
			'type' => 'integer',
			'visible' => 0,
			'enabled' => 1,
			'position' => 40
		),

		'km' => array(
			'type' => 'double',
			'visible' => 1,
			'enabled' => 1,
			'position' => 50
		),

		'delai_from_last_op' => array(
			'type' => 'integer',
			'label' => 'VehiculeOperationDelai',
			'visible' => 1,
			'enabled' => 1,
			'comment' => 'delay from last operation in months'
		),

	);

	/**
	 * doliFleetVehiculeOperation constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->entity = $conf->entity;
	}

	/**
	 * @param int $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string
	 */
	public function getLibStatut($mode = 0)
	{
		return self::LibStatut($this->status, $mode);
	}

	/**
	 * @param int       $status   Status
	 * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string
	 */
	public static function LibStatut($status, $mode)
	{
		global $langs;

		$langs->load('dolifleet@dolifleet');
		$res = '';

		if ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('doliFleetOperationStatusDraft'); $statusLabelShort=$langs->trans('doliFleetOperationStatusShortDraft'); }
		elseif ($status==self::STATUS_TOPLAN) { $statusType='status6'; $statusLabel=$langs->trans('doliFleetOperationStatusToPlan'); $statusLabelShort=$langs->trans('doliFleetOperationStatusShortToPlan'); }
		elseif ($status==self::STATUS_PLANNED) { $statusType='status1'; $statusLabel=$langs->trans('doliFleetOperationStatusPlanned'); $statusLabelShort=$langs->trans('doliFleetOperationStatusShortPlanned'); }
		elseif ($status==self::STATUS_DONE) { $statusType='status4'; $statusLabel=$langs->trans('doliFleetOperationStatusDone'); $statusLabelShort=$langs->trans('doliFleetOperationStatusShortDone'); }

		if (function_exists('dolGetStatus'))
		{
			$res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
		}
		else
		{
			if ($mode == 0) $res = $statusLabel;
			elseif ($mode == 1) $res = $statusLabelShort;
			elseif ($mode == 2) $res = img_picto($statusLabel, $statusType).$statusLabelShort;
			elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
			elseif ($mode == 4) $res = img_picto($statusLabel, $statusType).$statusLabel;
			elseif ($mode == 5) $res = $statusLabelShort.img_picto($statusLabel, $statusType);
			elseif ($mode == 6) $res = $statusLabel.img_picto($statusLabel, $statusType);
		}

		return $res;
	}

	public function getName()
	{
		$ret = $this->fetch_product();
		if ($ret > 0) return $this->product->getNomUrl();
		else return '';
	}
}
