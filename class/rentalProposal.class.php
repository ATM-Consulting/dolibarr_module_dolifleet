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
		,self::STATUS_CLOSED => 'doliFleetProposalStatusCloturé'
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
		'month' => array(
			'type' => 'integer',
			'label' => 'Month',
			'visible' => 1,
			'enabled' => 1,
			'position' => 10,
			'index' => 1,
		),

		'year' => array(
			'type' => 'integer',
			'label' => 'Year',
			'visible' => 1,
			'enabled' => 1,
			'position' => 20,
			'index' => 1,
		),

		'fk_soc' => array(
			'type' => 'integer:Societe:societe/class/societe.class.php',
			'label' => 'ThirdParty',
			'enabled' => 1,
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'position' => 30,
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
			'position' => 40,
			'arrayofkeyval' => array(
				self::STATUS_DRAFT => 'doliFleetProposalStatusDraft'
				,self::STATUS_INPROGRESS => 'doliFleetProposalStatusInProgress'
				,self::STATUS_VALIDATED => 'doliFleetProposalStatusValidated'
				,self::STATUS_CLOSED => 'doliFleetProposalStatusCloturé'
			)
		),

		'fk_first_valid' => array(
			'type' => 'integer:User:user/class/user.class.php',
			'enabled' => 1,
			'visible' => 0,
			'notnull' =>1,
			'default' => 0,
			'position' => 50,
			'index' => 1,
		),

		'date_first_valid' => array(
			'type' => 'date',
			'label' => 'date_valid',
			'enabled' => 0,
			'visible' => 0,
			'position' => 60,
			'searchall' => 1,
		),

		'fk_first_valid' => array(
			'type' => 'integer:User:user/class/user.class.php',
			'enabled' => 1,
			'visible' => 0,
			'notnull' =>1,
			'default' => 0,
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



	);

	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;
	}

	public function save($user)
	{
		global $langs;

		// TODO check parameters

		return $this->create($user);
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
	public function delete(User &$user)
	{
		$this->deleteObjectLinked();

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

		$mask = !empty($conf->global->DOLIFLEETRENTALPROPOSAL_REF_MASK) ? $conf->global->DOLIFLEETRENTALPROPOSAL_REF_MASK : 'MM{yy}{mm}-{0000}';
		$ref = get_next_value($db, $mask, 'dolifleetrentalproposal', 'ref');

		return $ref;
	}


	/**
	 * @param User  $user   User object
	 * @return int
	 */
	public function setDraft($user)
	{
		if ($this->status === self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_DRAFT;
			$this->withChild = false;

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
		if ($this->status === self::STATUS_DRAFT)
		{
			// TODO determinate if auto generate
//            $this->ref = $this->getRef();
//            $this->fk_user_valid = $user->id;
			$this->status = self::STATUS_VALIDATED;
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
		if ($this->status === self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_ACCEPTED;
			$this->withChild = false;

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
	public function setReopen($user)
	{
		if ($this->status === self::STATUS_ACCEPTED || $this->status === self::STATUS_REFUSED)
		{
			$this->status = self::STATUS_VALIDATED;
			$this->withChild = false;

			return $this->update($user);
		}

		return 0;
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
		if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$link = '<a href="'.dol_buildpath('/dolifleetrentalproposal/card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

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

		$langs->load('dolifleetrentalproposal@dolifleetrentalproposal');
		$res = '';

		if ($status==self::STATUS_CANCELED) { $statusType='status9'; $statusLabel=$langs->trans('dolifleetRentalProposalStatusCancel'); $statusLabelShort=$langs->trans('dolifleetRentalProposalStatusShortCancel'); }
		elseif ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('dolifleetRentalProposalStatusDraft'); $statusLabelShort=$langs->trans('dolifleetRentalProposalStatusShortDraft'); }
		elseif ($status==self::STATUS_VALIDATED) { $statusType='status1'; $statusLabel=$langs->trans('dolifleetRentalProposalStatusValidated'); $statusLabelShort=$langs->trans('dolifleetRentalProposalStatusShortValidate'); }
		elseif ($status==self::STATUS_REFUSED) { $statusType='status5'; $statusLabel=$langs->trans('dolifleetRentalProposalStatusRefused'); $statusLabelShort=$langs->trans('dolifleetRentalProposalStatusShortRefused'); }
		elseif ($status==self::STATUS_ACCEPTED) { $statusType='status6'; $statusLabel=$langs->trans('dolifleetRentalProposalStatusAccepted'); $statusLabelShort=$langs->trans('dolifleetRentalProposalStatusShortAccepted'); }

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
			$out.= $montharray[$val];
		}
		else $out.= parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);

		return $out;
	}


}
