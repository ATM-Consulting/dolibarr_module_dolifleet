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

class dolifleetRentalProposal extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_rental_proposal';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_rental_proposal';

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * validation in progress status
	 */
	const STATUS_INPROGRESS = 1;

	/**
	 * validated status
	 */
	const STATUS_VALIDATED = 2;

	/**
	 * validated status
	 */
	const STATUS_CLOSED = 3;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'doliFleetProposalStatusDraft'
		,self::STATUS_INPROGRESS => 'doliFleetProposalStatusInProgress'
		,self::STATUS_VALIDATED => 'doliFleetProposalStatusValidated'
		,self::STATUS_CLOSED => 'doliFleetProposalStatusCloture'
	);

	public $month;
	public $year;
	public $fk_soc;
	public $status;
	public $fk_first_valid;
	public $date_first_valid;
	public $fk_second_valid;
	public $date_second_valid;

	public $fields = array(
		'ref' => array(
			'type' => 'varchar(50)',
			'length' => 50,
			'label' => 'Ref',
			'enabled' => 1,
			'visible' => 1,
			'notnull' => 1,
			'showoncombobox' => 1,
			'index' => 1,
			'position' => 10,
			'searchall' => 1,
			'comment' => 'Reference of object'
		),

		'entity' => array(
			'type' => 'integer',
			'label' => 'Entity',
			'enabled' => 1,
			'visible' => 0,
			'default' => 1,
			'notnull' => 1,
			'index' => 1,
			'position' => 20
		),

		'month' => array(
			'type' => 'integer',
			'label' => 'Month',
			'visible' => 1,
			'enabled' => 1,
			'position' => 30,
			'index' => 1,
		),

		'year' => array(
			'type' => 'integer',
			'label' => 'Year',
			'visible' => 1,
			'enabled' => 1,
			'position' => 40,
			'index' => 1,
		),

		'fk_soc' => array(
			'type' => 'integer:Societe:societe/class/societe.class.php',
			'label' => 'ThirdParty',
			'enabled' => 1,
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'position' => 50,
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
			'position' => 60,
			'arrayofkeyval' => array(
				self::STATUS_DRAFT => 'doliFleetProposalStatusDraft'
				,self::STATUS_INPROGRESS => 'doliFleetProposalStatusInProgress'
				,self::STATUS_VALIDATED => 'doliFleetProposalStatusValidated'
				,self::STATUS_CLOSED => 'doliFleetProposalStatusCloture'
			)
		),

		'fk_first_valid' => array(
			'type' => 'integer:User:user/class/user.class.php',
			'enabled' => 1,
			'visible' => 0,
			'position' => 70,
			'index' => 1,
		),

		'date_first_valid' => array(
			'type' => 'date',
			'label' => 'date_valid',
			'enabled' => 0,
			'visible' => 0,
			'position' => 80,
			'searchall' => 1,
		),

		'fk_second_valid' => array(
			'type' => 'integer:User:user/class/user.class.php',
			'enabled' => 1,
			'visible' => 0,
			'position' => 90,
			'index' => 1,
		),

		'date_second_valid' => array(
			'type' => 'date',
			'label' => 'date_valid',
			'enabled' => 0,
			'visible' => 0,
			'position' => 100,
			'searchall' => 1,
		),



	);

	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;
	}

	public function save($user, $notrigger = false)
	{
		global $langs;

		// TODO check parameters
		$initLines = false;
		if (empty($this->id)) {
			$initLines = true;
		}

		$ret = $this->create($user, $notrigger);
		if ($ret > 0 && $initLines) {
			$ret = $this->initLines();
			$this->ref = '(PROV'.$this->id.')';
			$this->update($user, true);
		}

		return $ret;
	}

	/**
	 * @see cloneObject
	 * @return void
	 */
	public function clearUniqueFields()
	{
		$this->ref = 'Copy of '.$this->ref;
	}


	/**
	 * @param User $user User object
	 * @return int
	 */
	public function delete(User &$user, $notrigger = false)
	{
		$this->deleteObjectLinked();

		if ($this->status === self::STATUS_CLOSED) return 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."det WHERE fk_rental_proposal = ".$this->id;
		$this->db->query($sql);

		unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
		return parent::delete($user);
	}

	/**
	 * @return string
	 */
	public function getRef()
	{
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
		{
			return $this->getNextRef();
		}

		return $this->ref;
	}

	/**
	 * @return string
	 */
	private function getNextRef()
	{
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$codeTiers = '';
		$this->fetch_thirdparty();
		$codeTiers.=$this->thirdparty->code_client;

		$mask = !empty($conf->global->DOLIFLEETRENTALPROPOSAL_REF_MASK) ? $conf->global->DOLIFLEETRENTALPROPOSAL_REF_MASK : $this->year.'-'.($this->month < 10 ? '0' : '').$this->month.(!empty($codeTiers) ? '-'.$codeTiers : '').'-{000}';//'MM{yy}{mm}-{0000}';
		$ref = get_next_value($db, $mask, $this->table_element, 'ref', '', '', '', 'next',false);

		return $ref;
	}


	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setDraft($user)
	{
		if ($this->status === self::STATUS_INPROGRESS && $user->rights->dolifleet->rentalproposal->validate)
		{
			$this->status = self::STATUS_DRAFT;
			$this->withChild = false;
			$this->fk_first_valid = NULL;
			$this->date_first_valid = NULL;

			return $this->update($user);
		}

		return 0;
	}

	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setValid($user)
	{
		if ($this->status === self::STATUS_DRAFT && $user->rights->dolifleet->rentalproposal->validate)
		{
			// TODO determinate if auto generate
            $this->ref = $this->getRef();
//            $this->fk_user_valid = $user->id;
			$this->status = self::STATUS_INPROGRESS;
			$this->fk_first_valid = $user->id;
			$this->date_first_valid = dol_now();

			$this->withChild = false;

			return $this->update($user);
		}

		return 0;
	}

	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setAccepted($user)
	{
		if ($this->status === self::STATUS_INPROGRESS && $user->rights->dolifleet->rentalproposal->validate)
		{
			$this->status = self::STATUS_VALIDATED;
			$this->withChild = false;
			$this->fk_second_valid = $user->id;
			$this->date_second_valid = dol_now();

			return $this->update($user);
		}

		return 0;
	}

	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setRefused($user)
	{
		if ($this->status === self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_REFUSED;
			$this->withChild = false;

			return $this->update($user);
		}

		return 0;
	}

	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setClosed($user)
	{
		if ($this->status === self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_CLOSED;
			$this->withChild = false;

			$this->generateRentals();

			return $this->update($user);
		}

		return 0;
	}

	/**
	 * generate customer vehiculeRental for each lines
	 */
	public function generateRentals()
	{
		$this->date_start = strtotime("01-".$this->month."-".$this->year." 00:00:00");
		$this->date_end = strtotime(date("t-m-Y 23:59:59", $this->date_start));

		dol_include_once('/dolifleet/class/vehicule.class.php');
		$VehiculeStatic = new dolifleetVehicule($this->db);

		if (empty($this->lines)) $this->fetchLines();

		foreach ($this->lines as $line)
		{
			$VehiculeStatic->id = $line->fk_vehicule;
			$VehiculeStatic->addRental($this->date_start, $this->date_end, $line->total_ht, $this->fk_soc, $line->id);
		}

	}

	public function fetchLines()
	{
		$this->lines = array();

		$this->date_start = strtotime("01-".$this->month."-".$this->year." 00:00:00");
		$this->date_end = strtotime(date("t-m-Y 23:59:59", $this->date_start));

		$sql = "SELECT d.rowid, v.fk_vehicule_type, va.fk_type FROM ".MAIN_DB_PREFIX.$this->table_element."det as d";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolifleet_vehicule as v ON v.rowid = d.fk_vehicule";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolifleet_vehicule_activity as va ON va.fk_vehicule = v.rowid";
		$sql.= " WHERE d.fk_rental_proposal = ".$this->id;
		$sql.= " AND ((va.date_start <= '".$this->db->idate($this->date_end)."' AND va.date_end >= '".$this->db->idate($this->date_start)."') OR va.rowid IS NULL)";
		$sql.= " GROUP BY va.fk_type ASC, v.fk_vehicule_type ASC, d.rowid ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$line = new dolifleetRentalProposalDet($this->db);
					$line->fetch($obj->rowid);
					$line->id = $obj->rowid;
					$line->fk_vehicule_type = $obj->fk_vehicule_type;
					$line->activity_type = $obj->fk_type;

					$this->lines[] = $line;
				}
			}

			return $num;
		}
		else
		{
			$this->errors[] = $this->db->lasterror();
			return -1;
		}
	}


	public function initLines()
	{
		global $user;

		$this->lines = array();

		$this->date_start = strtotime("01-".$this->month."-".$this->year." 00:00:00");
		$this->date_end = strtotime(date("t-m-Y 23:59:59", $this->date_start));

		$sql = "SELECT DISTINCT v.rowid as v_id, vat.rowid as va_type FROM ".MAIN_DB_PREFIX."dolifleet_vehicule as v";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolifleet_vehicule_activity as va ON va.fk_vehicule = v.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_dolifleet_vehicule_activity_type as vat ON vat.rowid = va.fk_type";
		$sql.= " WHERE v.fk_soc = ".$this->fk_soc;
		$sql.= " AND v.status = 1";
		$sql.= " AND (va.fk_soc = ".$this->fk_soc;
		$sql.= " AND (va.date_start <= '".$this->db->idate($this->date_end)."' AND va.date_end >= '".$this->db->idate($this->date_start)."') OR vat.label IS NULL)";

		$sql.= " GROUP BY vat.rowid ASC, v.fk_vehicule_type ASC, v.rowid ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				while($obj = $this->db->fetch_object($resql))
				{
					$pdet = new dolifleetRentalProposalDet($this->db);
					$pdet->fk_vehicule = $obj->v_id;
					$pdet->fk_rental_proposal = $this->id;
					$pdet->fk_soc = $this->fk_soc;
					$pdet->getprice();

					$ret = $pdet->create($user);
					if ($ret < 0)
					{
						$this->errors = array_merge($pdet->errors, array($pdet->error));
					}

				}
			}
		}
		else
		{
			$this->errors[] = $this->db->lasterror();
		}

		if (!empty($this->errors)) return -1;
		else return 1;

	}


	/**
	 * @param int    $withpicto     Add picto into link
	 * @param string $moreparams    Add more parameters in the URL
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $moreparams = '')
	{
		global $langs;

		$result='';
		$label = '<u>' . $langs->trans("ShowdolifleetRentalProposal") . '</u>';
		//if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$link = '<a href="'.dol_buildpath('/dolifleet/rental_proposal_card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

		$linkend='</a>';

		$picto='generic';
//        $picto='dolifleetrentalproposal@dolifleetrentalproposal';

		if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';

		$result.=$link.$this->ref.$linkend;

		return $result;
	}

	/**
	 * @param int       $id             Identifiant
	 * @param null      $ref            Ref
	 * @param int       $withpicto      Add picto into link
	 * @param string    $moreparams     Add more parameters in the URL
	 * @return string
	 */
	public static function getStaticNomUrl($id, $ref = null, $withpicto = 0, $moreparams = '')
	{
		global $db;

		$object = new dolifleetRentalProposal($db);
		$object->fetch($id, false, $ref);

		return $object->getNomUrl($withpicto, $moreparams);
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

		if ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('doliFleetProposalStatusDraft'); $statusLabelShort=$langs->trans('doliFleetProposalStatusShortDraft'); }
		elseif ($status==self::STATUS_INPROGRESS) { $statusType='status1'; $statusLabel=$langs->trans('doliFleetProposalStatusInProgress'); $statusLabelShort=$langs->trans('doliFleetProposalStatusShortInProgress'); }
		elseif ($status==self::STATUS_VALIDATED) { $statusType='status4'; $statusLabel=$langs->trans('doliFleetProposalStatusValidated'); $statusLabelShort=$langs->trans('doliFleetProposalStatusValidated'); }
		elseif ($status==self::STATUS_CLOSED) { $statusType='status6'; $statusLabel=$langs->trans('doliFleetProposalStatusCloture'); $statusLabelShort=$langs->trans('doliFleetProposalStatusCloture'); }

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

	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $langs;

		$out = '';

		if ($key == 'month')
		{
			require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";
			$formother = new FormOther($this->db);
			$out.= $formother->select_month(
				$this->month
				,'month'
				,1
				,0
			);
		}
		elseif ($key == 'year')
		{
			require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";
			$formother = new FormOther($this->db);
			$out.= $formother->select_year(
				$this->year
				,'year'
				,0
				,10
				,0
			);
		}
		elseif ($key == 'ref')
		{
			$out.= (empty($this->id) && empty($this->ref) ? $langs->trans('Draft') : $this->ref) ;
		}
		else $out.= parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);

		return $out;
	}

	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $langs;

		$out = '';

		if ($key == 'month')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

			$montharray = monthArray($langs, 1);
			$out.= $montharray[$value];
		}
		else $out.= parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);

		return $out;
	}

}

class dolifleetRentalProposalDet extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_rental_proposaldet';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_rental_proposaldet';

	/** @var int $fk_vehicule Object link to vehicule */
	public $fk_vehicule;

	public $fk_rental_proposal;

	public $total_ht;

	public $description;

	public $fields = array(
		'fk_vehicule' => array(
			'type' => 'integer:doliFleetVehicule:dolifleet/class/vehicule.class.php',
			'label' => 'doliFleetVehicule',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
			'index' => 1,
		),

		'fk_rental_proposal' => array(
			'type' => 'integer:dolifleetRentalProposal:dolifleet/class/rentalProposal.class.php',
			'label' => 'Line',
			'visible' => 1,
			'enabled' => 1,
			'position' => 20,
			'index' => 1,
		),

		'total_ht' => array(
			'type' => 'price',
			'label' => 'totalHT',
			'enabled' => 1,
			'visible' => 1,
			'position' => 30
		),

		'description' => array(
			'type' => 'text', // or html for WYSWYG
			'label' => 'Description',
			'enabled' => 1,
			'visible' => -1, //  un bug sur la version 9.0 de Dolibarr necessite de mettre -1 pour ne pas apparaitre sur les listes au lieu de la valeur 3
			'position' => 40
		),
	);

	public function getprice()
	{
		global $langs, $conf, $cashVehiculeDates;

		if (empty($cashVehiculeDates) || !in_array($this->fk_vehicule, array_keys($cashVehiculeDates)))
		{
			dol_include_once('dolifleet/class/vehicule.class.php');
			$vehicule = new doliFleetVehicule($this->db);
			$vehicule->fetch($this->fk_vehicule);

			$cashVehiculeDates[$vehicule->id] = $vehicule->date_customer_exploit;
		}

		$this->total_ht = 0;

		// récupérer le montant hors taxe depuis la matrice du client
		$sql = "SELECT amount_ht FROM ".MAIN_DB_PREFIX."dolifleet_vehicule_rental_matrix";
		$sql.= " WHERE fk_soc = ".$this->fk_soc;
		$sql.= " AND delay >= PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT('".$this->db->idate($cashVehiculeDates[$this->fk_vehicule])."', '%Y%m'))";
		$sql.= " LIMIT 1";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->total_ht = (float) $obj->amount_ht;
				$this->description = $langs->trans('AmountIsFromThirdpartyMatrix');
			}
		}

		// ou depuis la matrice générale
		if (empty($this->total_ht))
		{
			$sql = "SELECT amount_ht FROM ".MAIN_DB_PREFIX."dolifleet_vehicule_rental_matrix";
			$sql.= " WHERE fk_soc = 0";
			$sql.= " AND delay >= PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'), DATE_FORMAT('".$this->db->idate($cashVehiculeDates[$this->fk_vehicule])."', '%Y%m'))";
			$sql.= " LIMIT 1";
			$resql = $this->db->query($sql);

			if ($resql)
			{
				if ($this->db->num_rows($resql))
				{
					$obj = $this->db->fetch_object($resql);
					$this->total_ht = (float) $obj->amount_ht;
					$this->description = $langs->trans('AmountIsFromGlobalMatrix');
				}
			}
		}

		// ou prix par défaut en conf
		if (empty($this->total_ht))
		{
			if (!empty($conf->global->DOLIFLEET_DEFAULT_RENTAL_AMOUNT))
			{
				$this->total_ht = (float) $conf->global->DOLIFLEET_DEFAULT_RENTAL_AMOUNT;
				$this->description = $langs->trans('AmountIsFromGlobalConf');
			}
		}

		if (!empty($this->total_ht)) return 1;
		else return 0;
	}
}
