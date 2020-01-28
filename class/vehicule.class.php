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

class doliFleetVehicule extends SeedObject
{

    /**
     * Draft status
     */
    const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_ACTIVE = 1;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'doliFleetVehiculeStatusShortDraft'
		,self::STATUS_ACTIVE => 'doliFleetVehiculeStatusShortActivated'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'dolifleet_vehicule';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'dolifleet_vehicule';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /**
     *  'type' is the field format.
     *  'label' the translation key.
     *  'enabled' is a condition when the field must be managed.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'default' is a default value for creation (can still be replaced by the global setup of default values)
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'position' is the sort order of field.
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
     *  'css' is the CSS style to use on field. For example: 'maxwidth200'
     *  'help' is a string visible as a tooltip on field
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
     */

    public $fields = array(

        'vin' => array(
            'type' => 'varchar(50)',
            'length' => 50,
            'label' => 'VIN',
            'enabled' => 1,
            'visible' => 1,
            'notnull' => 1,
            'showoncombobox' => 1,
            'index' => 1,
            'position' => 10,
            'searchall' => 1,
            'comment' => 'Vehicule international number'
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
				self::STATUS_DRAFT => 'doliFleetVehiculeStatusShortDraft'
				,self::STATUS_ACTIVE => 'doliFleetVehiculeStatusShortActivated'
            )
        ),

		'fk_vehicule_type' => array(
			'type' => 'sellist:c_dolifleet_vehicule_type:label:rowid::active=1',
			'label' => 'vehiculeType',
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'enabled' => 1,
			'position' => 40,
			'index' => 1,
		),

		'fk_vehicule_mark' => array(
			'type' => 'sellist:c_dolifleet_vehicule_mark:label:rowid::active=1',
			'label' => 'vehiculeMark',
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'enabled' => 1,
			'position' => 50,
			'index' => 1,
		),

        'immatriculation' => array(
            'type' => 'varchar(20)',
            'label' => 'immatriculation',
            'enabled' => 1,
            'visible' => 1,
			'notnull' =>1,
            'position' => 60,
            'searchall' => 1,
            'css' => 'minwidth200',
            'showoncombobox' => 1
        ),

        'date_immat' => array(
			'type' => 'date',
			'label' => 'immatriculation_date',
			'enabled' => 1,
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'position' => 70,
			'searchall' => 1,
        ),

        'fk_soc' => array(
            'type' => 'integer:Societe:societe/class/societe.class.php',
            'label' => 'ThirdParty',
            'visible' => 1,
			'notnull' =>1,
			'default' => 0,
            'enabled' => 1,
            'position' => 80,
            'index' => 1,
            'help' => 'LinkToThirparty'
        ),

        'date_customer_exploit' => array(
			'type' => 'date',
			'label' => 'date_customer_exploit',
            'visible' => 1,
            'enabled' => 1,
            'position' => 90,
            'index' => 1,
            'help' => 'date_customer_exploit_help'
        ),

        'km' => array(
			'type' => 'double',
			'label' => 'kilometrage',
			'visible' => 1,
			'notnull' =>1,
			'default' => 0,
			'enabled' => 1,
			'position' => 100
        ),

        'km_date' => array(
			'type' => 'date',
			'label' => 'km_date',
			'visible' => 1,
			'enabled' => 1,
			'position' => 110
        ),

        'fk_contract_type' => array(
			'type' => 'sellist:c_dolifleet_contract_type:label:rowid::active=1',
			'label' => 'contractType',
			'visible' => 1,
			'enabled' => 1,
			'position' => 120,
			'index' => 1,
        ),

        'date_end_contract' => array(
			'type' => 'date',
			'label' => 'date_end_contract',
			'visible' => 1,
			'enabled' => 1,
			'position' => 130
        ),

//        'description' => array(
//            'type' => 'text', // or html for WYSWYG
//            'label' => 'Description',
//            'enabled' => 1,
//            'visible' => -1, //  un bug sur la version 9.0 de Dolibarr necessite de mettre -1 pour ne pas apparaitre sur les listes au lieu de la valeur 3
//            'position' => 60
//        ),

//        'fk_user_valid' =>array(
//            'type' => 'integer',
//            'label' => 'UserValidation',
//            'enabled' => 1,
//            'visible' => -1,
//            'position' => 512
//        ),

        'import_key' => array(
            'type' => 'varchar(14)',
            'label' => 'ImportId',
            'enabled' => 1,
            'visible' => -2,
            'notnull' => -1,
            'index' => 0,
            'position' => 1000
        ),

    );

    /** @var string $vin Object reference */
	public $vin;

    /** @var int $entity Object entity */
	public $entity;

	/** @var int $status Object status */
	public $status;

	public $fk_vehicule_type;
	public $fk_vehicule_mark;
	public $immatriculation;
	public $date_immat;
	public $fk_soc;
	public $date_customer_exploit;
	public $km;
	public $km_date;
	public $fk_contract_type;
	public $date_end_contract;



    /**
     * doliFleetVehicule constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
		global $conf;

        parent::__construct($db);

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;
    }

    /**
     * @param User $user User object
     * @return int
     */
    public function save($user, $notrigger = false)
    {
    	global $langs;

    	// TODO remake object field validation
		// vin type, marque, immat (format), dateMIC, tiers
    	if (empty($this->vin))
		{
			$this->errors[] = $langs->trans("ErrNoVinNumber");
		}

		$veh = new static($this->db);
		$ret = $veh->fetchBy($this->vin, 'vin', false);
		if ($ret > 0 && $veh->id != $this->id)
		{
			$this->errors[] = $langs->trans('ErrVinAlreadyUsed', html_entity_decode($veh->getNomUrl()));
		}

		if (empty($this->fk_vehicule_type)) $this->errors[] = $langs->trans('ErrInvalidVehiculeType');
		if (empty($this->fk_vehicule_mark)) $this->errors[] = $langs->trans('ErrInvalidVehiculeMark');

		if (empty($this->immatriculation)) $this->errors[] = $langs->trans('ErrEmptyVehiculeImmatriculation');

		if (empty($this->date_immat)) $this->errors[] = $langs->trans('ErrEmptyVehiculeImmatDate');

		if (empty($this->fk_soc) || $this->fk_soc == '-1') $this->errors[] = $langs->trans('ErrInvalidSocid');

		if (!empty($this->errors)) return -1;

//        if (!empty($this->is_clone))
//        {
//            // TODO determinate if auto generate
//            $this->ref = '(PROV'.$this->id.')';
//        }

        return $this->create($user, $notrigger);
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

        unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
        return parent::delete($user, $notrigger);
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

		$mask = !empty($conf->global->DOLIFLEET_REF_MASK) ? $conf->global->DOLIFLEET_REF_MASK : 'MM{yy}{mm}-{0000}';
		$ref = get_next_value($db, $mask, 'dolifleet', 'ref');

		return $ref;
    }


    /**
     * @param User  $user   User object
     * @return int
     */
    public function setDraft($user)
    {
        if ($this->status === self::STATUS_ACTIVE)
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
            $this->status = self::STATUS_ACTIVE;
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

    public function getActivities($date_start = '', $date_end = '')
	{
		$this->activities = array();

		dol_include_once('/dolifleet/class/vehiculeActivity.class.php');
		$act = new doliFleetVehiculeActivity($this->db);

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$act->table_element;
		$sql.= " WHERE fk_vehicule = ".$this->id;
		if (!empty($date_end))
			$sql.= " AND date_start < '".$this->db->idate($date_end)."'";
		if (!empty($date_start))
			$sql.= " AND date_end > '".$this->db->idate($date_start)."'";
		$sql.= " AND fk_soc = ".$this->fk_soc;
		$sql.= " ORDER BY date_start ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$act = new doliFleetVehiculeActivity($this->db);

					$ret = $act->fetch($obj->rowid);
					if ($ret > 0) $this->activities[$obj->rowid] = $act;
				}
			}

			return $num;
		}

		return -1;
	}

	/**
	 * @param int $type Activity Type
	 * @param $date_start
	 * @param $date_end
	 *
	 * @return int >0 OK <0 KO
	 */
	public function addActivity($type, $date_start, $date_end)
	{
		global $user;

		if (empty($type) || $type == '-1')
		{
			$this->error = "ErrNoActivityType";
			return -1;
		}

		dol_include_once("/dolifleet/class/vehiculeActivity.class.php");
		$act = new doliFleetVehiculeActivity($this->db);

		$act->fk_vehicule = $this->id;
		$act->fk_type = $type;
		$act->fk_soc = $this->fk_soc;
		$act->date_start = $date_start;
		$act->date_end = $date_end;

		$retDate = $act->verifyDates();
		if ($retDate)
		{
			return $act->create($user);
		}
		else
		{
			$this->error = $act->error;
			return -1;
		}
	}

	public function delActivity($user, $act_id)
	{
		global $db;

		dol_include_once("/dolifleet/class/vehiculeActivity.class.php");
		$act = new doliFleetVehiculeActivity($this->db);

		$ret = $act->fetch($act_id);

		if ($act->fk_vehicule != $this->id)
		{
			$this->error = "IllegalDeletion";
			return -1;
		}
		else
		{
			$ret = $act->delete($user);
			if ($ret > 0) return 1;
			else
			{
				$this->error = $act->error;
				return -1;
			}
		}
	}

	public function getLinkedVehicules($date_start = '', $date_end = '')
	{
		$sql = 'SELECT rowid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'dolifleet_vehicule_link';
		$sql .= " WHERE ";
		$sql .= " (fk_source = ".$this->id." OR fk_target = ".$this->id.")";
		if (!empty($date_end))
			$sql.= " AND date_start < '".$this->db->idate($date_end)."'";
		if (!empty($date_start))
			$sql.= " AND date_end > '".$this->db->idate($date_start)."'";
		$sql .= " ORDER BY date_start ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			dol_include_once('/dolifleet/class/vehiculeLink.class.php');

			$this->linkedVehicules = array();

			while ($obj = $this->db->fetch_object($resql))
			{
				$Vlink = new doliFleetVehiculeLink($this->db);
				$ret = $Vlink->fetch($obj->rowid);

				if ($Vlink->fk_source != $this->id) $Vlink->fk_other_vehicule = $Vlink->fk_source;
				else if ($Vlink->fk_target != $this->id) $Vlink->fk_other_vehicule = $Vlink->fk_target;

				if ($ret > 0) $this->linkedVehicules[$Vlink->date_start] = $Vlink;
			}
		}

	}

	// ajoute un lien entre véhicule de date à date
	public function addLink($id, $date_start, $date_end)
	{
		global $langs, $user;

		$this->vehicules = $this->errors = array();

		$vehiculeToLink = new static($this->db);
		$vehiculeToLink->fetch($id);

		$this->getLinkedVehicules($date_start, $date_end);
		if (!empty($this->linkedVehicules))
		{
			// le véhicule courant est déjà lié pour la période saisie
			foreach ($this->linkedVehicules as $v)
			{
				if (!in_array($v->fk_other_vehicule, array_keys($this->vehicules)))
				{
					$veh = new doliFleetVehicule($this->db);
					$veh->fetch($v->fk_other_vehicule);
					$this->vehicules[$v->fk_other_vehicule] = $veh;
				}

				$this->errors[] = $langs->trans(
					"ErrVehiculeAlreadyLinkedDates",
					'',
					html_entity_decode($this->vehicules[$v->fk_other_vehicule]->getLinkUrl(0,'','immatriculation')),
					dol_print_date($v->date_start, "%d/%m/%Y"),
					dol_print_date($v->date_end, "%d/%m/%Y"));
			}
			unset($v);
		}

		$vehiculeToLink->getLinkedVehicules($date_start, $date_end);
		if (!empty($vehiculeToLink->linkedVehicules))
		{
			// le véhicule courant est déjà lié pour la période saisie
			foreach ($vehiculeToLink->linkedVehicules as $v)
			{
				if (!in_array($v->fk_other_vehicule, array_keys($this->vehicules)))
				{
					$veh = new doliFleetVehicule($this->db);
					$veh->fetch($v->fk_other_vehicule);
					$this->vehicules[$v->fk_other_vehicule] = $veh;
				}

				$this->errors[] = $langs->trans(
					"ErrVehiculeAlreadyLinkedDates",
					html_entity_decode($vehiculeToLink->getLinkUrl(0,'','immatriculation')),
					html_entity_decode($this->vehicules[$v->fk_other_vehicule]->getLinkUrl(0,'','immatriculation')),
					dol_print_date($v->date_start, "%d/%m/%Y"),
					dol_print_date($v->date_end, "%d/%m/%Y"));
			}
		}

		if ($this->fk_soc != $vehiculeToLink->fk_soc)
		{
			$this->errors[] = $langs->trans('ErrVehiculeThirPartiesAreDifferent');
		}

		if (!empty($this->errors)) return -1;
		else
		{
			dol_include_once('/dolifleet/class/vehiculeLink.class.php');
			$Vlink = new doliFleetVehiculeLink($this->db);
			$Vlink->fk_source = $this->id;
			$Vlink->fk_soc_vehicule_source=$this->fk_soc;
			$Vlink->fk_target = $id;
			$Vlink->fk_soc_vehicule_target=$vehiculeToLink->fk_soc;
			$Vlink->date_start= $date_start;
			$Vlink->date_end = $date_end;

			$ret = $Vlink->create($user);
			if ($ret < 0)
			{
				$this->errors[] = $Vlink->error;
				return -2;
			}
		}

		return 1;
	}

	public function delLink($id)
	{
		global $user;

		dol_include_once('/dolifleet/class/vehiculeLink.class.php');
		$link = new doliFleetVehiculeLink($this->db);

		$ret = $link->fetch($id);

		if ($ret > 0 && $link->fk_source != $this->id && $link->fk_target != $this->id)
		{
			$this->errors[] = "IllegalDeletion";
			return -1;
		}

		$ret = $link->delete($user);
		if ($ret > 0) return 1;
		else
		{
			$this->errors[] = $link->error;
			return -1;
		}
	}

	public function getRentals($date_start = '', $date_end = '', $externalRental = false)
	{
		$this->rentals = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."dolifleet_vehicule_rental";
		$sql.= " WHERE fk_vehicule = ".$this->id;
		if (!empty($date_end))
			$sql.= " AND date_start < '".$this->db->idate($date_end)."'";
		if (!empty($date_start))
			$sql.= " AND date_end > '".$this->db->idate($date_start)."'";
		$sql.= " AND fk_soc ".($externalRental ? "<> 0 AND fk_soc IS NOT NULL" : "IS NULL");
		$sql.= " ORDER BY date_start ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				dol_include_once('/dolifleet/class/vehiculeRental.class.php');

				while ($obj = $this->db->fetch_object($resql))
				{
					$rent = new dolifleetVehiculeRental($this->db);
					$ret = $rent->fetch($obj->rowid);
					if ($ret > 0) $this->rentals[] = $rent;
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

	public function delRental($rent_id)
	{
		global $user;

		dol_include_once('/dolifleet/class/vehiculeRental.class.php');

		$rent = new dolifleetVehiculeRental($this->db);
		$rent->fetch($rent_id);

		if ($rent->fk_vehicule != $this->id)
		{
			$this->errors[] = "IllegalDeletion";
			return -1;
		}

		$ret = $rent->delete($user);
		if ($ret < 0)
		{
			$this->errors = array_merge($rent->errors, array($rent->error));
			return -1;
		}

		return 1;
	}

	public function addRental($date_start, $date_end, $amountHT, $fk_soc = 0, $fk_proposal_det = 0)
	{
		global $user, $langs;

		if (empty($amountHT))
		{
			$this->errors[] = $langs->trans('ErrEmptyAmountForRental');
			return -1;
		}

		$ret = $this->getRentals($date_start, $date_end, !empty($fk_soc));
		if ($ret > 0)
		{
			$this->errors[] = $langs->trans('ErrPeriodReservedForRental', dol_print_date($date_start, "%d/%m/%Y"), dol_print_date($date_end, "%d/%m/%Y"));
			return -1;
		}

		dol_include_once('/dolifleet/class/vehiculeRental.class.php');
		$rent = new dolifleetVehiculeRental($this->db);

		$rent->fk_vehicule = $this->id;
		$rent->date_start = $date_start;
		$rent->date_end = $date_end;
		$rent->total_ht = $amountHT;
		$rent->fk_soc = $fk_soc;
		$rent->fk_proposaldet = $fk_proposal_det;

		$ret = $rent->create($user);
		if ($ret < 0)
		{
			$this->errors = array_merge($rent->errors, array($rent->error));
			return -1;
		}

		return 1;
	}

	public function getOperations()
	{
		$this->operations = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element."_operation";
		$sql.= " WHERE fk_vehicule = ".$this->id;
		$sql.= " ORDER BY rank ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				dol_include_once('/dolifleet/class/vehiculeOperation.class.php');

				while ($obj = $this->db->fetch_object($resql))
				{
					$ope = new dolifleetVehiculeOperation($this->db);
					$ret = $ope->fetch($obj->rowid);
					if ($ret > 0) $this->operations[] = $ope;
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

	public function addOperation($productid, $km = 0, $delayInMonths = 0)
	{
		global $langs, $user;

		dol_include_once('/dolifleet/class/vehiculeOperation.class.php');
		$ope = new dolifleetVehiculeOperation($this->db);

		$ope->fk_vehicule = $this->id;
		$ope->fk_product = $productid;
		$ope->fk_soc_vehicule = $this->fk_soc;
		$ope->km = $km;
		$ope->delai_from_last_op = $delayInMonths;

		$ret = $ope->create($user);
		if($ret < 0)
		{
			$this->errors = array_merge($ope->errors, array($ope->error));
			return -1;
		}

		return $ret;
	}

	public function delOperation($ope_id)
	{
		global $user;

		dol_include_once('/dolifleet/class/vehiculeOperation.class.php');
		$ope = new dolifleetVehiculeOperation($this->db);
		$ope->fetch($ope_id);

		if ($ope->fk_vehicule != $this->id)
		{
			$this->errors[] = "IllegalDeletion";
			return -1;
		}

		$ret = $ope->delete($user);
		if ($ret < 0)
		{
			$this->errors = array_merge($ope->errors, array($ope->error));
			return -2;
		}

		return 1;

	}

    /**
     * @param int    $withpicto     Add picto into link
     * @param string $moreparams    Add more parameters in the URL
     * @return string
     */
    public function getNomUrl($withpicto = 0, $moreparams = '')
    {
		global $langs, $db;

        $result='';
        $label = '<u>' . $langs->trans("ShowdoliFleetVehicule") . '</u>';
        if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('VIN').':</b> '.$this->vin;
        if (! empty($this->immatriculation)) $label.= '<br><b>'.$langs->trans('immatriculation').':</b> '.$this->immatriculation;

        // marque
        dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');
        $dict = new dictionaryVehiculeMark($db);
        $label.= '<br><b>'.$langs->trans('vehiculeMark').':</b> '.$dict->getValueFromId($this->fk_vehicule_mark);

        // type de véhicule
        dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
        $dict = new dictionaryVehiculeType($db);
        $label.= '<br><b>'.$langs->trans('vehiculeType').':</b> '.$dict->getValueFromId($this->fk_vehicule_type);

        // client
        $this->fetch_thirdparty();
        $label.= '<br><b>'.$langs->trans('ThirdParty').':</b> '.$this->thirdparty->name;

        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $link = '<a href="'.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

        $linkend='</a>';

        $picto='generic';
//        $picto='dolifleet@dolifleet';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';

        $result.=$link.$this->vin.$linkend;

        return $result;
    }

    public function getLinkUrl($withpicto = 0, $moreparams = '', $fieldtodisplay = 'vin')
	{
		global $langs, $db;

		$result='';
		$label = '<u>' . $langs->trans("ShowdoliFleetVehicule") . '</u>';
		if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('VIN').':</b> '.$this->vin;
		if (! empty($this->immatriculation)) $label.= '<br><b>'.$langs->trans('immatriculation').':</b> '.$this->immatriculation;

		// marque
		dol_include_once('/dolifleet/class/dictionaryVehiculeMark.class.php');
		$dict = new dictionaryVehiculeMark($db);
		$label.= '<br><b>'.$langs->trans('vehiculeMark').':</b> '.$dict->getValueFromId($this->fk_vehicule_mark);

		// type de véhicule
		dol_include_once('/dolifleet/class/dictionaryVehiculeType.class.php');
		$dict = new dictionaryVehiculeType($db);
		$label.= '<br><b>'.$langs->trans('vehiculeType').':</b> '.$dict->getValueFromId($this->fk_vehicule_type);

		// client
		$this->fetch_thirdparty();
		$label.= '<br><b>'.$langs->trans('ThirdParty').':</b> '.$this->thirdparty->name;

		$linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$link = '<a href="'.dol_buildpath('/dolifleet/vehicule_card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

		$linkend='</a>';

		$picto='generic';
//        $picto='dolifleet@dolifleet';

		if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';

		$result.=$link.$this->{$fieldtodisplay}.$linkend;

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

		$object = new doliFleetVehicule($db);
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

        if ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('doliFleetVehiculeStatusDraft'); $statusLabelShort=$langs->trans('doliFleetVehiculeStatusShortDraft'); }
        elseif ($status==self::STATUS_ACTIVE) { $statusType='status4'; $statusLabel=$langs->trans('doliFleetVehiculeStatusActivated'); $statusLabelShort=$langs->trans('doliFleetVehiculeStatusShortValidate'); }

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
}
