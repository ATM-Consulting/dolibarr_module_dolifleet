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

abstract class dictionary extends SeedObject
{

	/** @var string $code Object reference */
	public $code;

	/** @var int $entity Object entity */
	public $entity;

	/** @var int $active Object active */
	public $active;

	/** @var int $label Object name */
	public $label;

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

		'code' => array(
			'type' => 'varchar(20)',
			'length' => 20,
			'label' => 'Code',
			'enabled' => 1,
			'visible' => 1,
			'notnull' => 1,
			'index' => 1,
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

		'active' => array(
			'type' => 'integer',
			'label' => 'Active',
			'enabled' => 1,
			'visible' => 0,
			'notnull' => 1,
			'default' => 0,
			'index' => 1,
			'position' => 30,
			'arrayofkeyval' => array(
				0 => 'Disabled',
				1 => 'Active'
			)
		),

		'label' => array(
			'type' => 'varchar(255)',
			'label' => 'Label',
			'enabled' => 1,
			'visible' => 1,
			'position' => 40,
			'searchall' => 1,
			'css' => 'minwidth200',
			'showoncombobox' => 1
		),

	);

	/**
	 * Dictionnary constructor.
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
	 * @param User $user User object
	 * @return int
	 */
	public function save($user)
	{
		return $this->create($user);
	}

	public function getAllActiveArray($field = '')
	{
		$Tab = array();

		$sql = 'SELECT rowid';
		if (!empty($field)) $sql.= ', '.$field;
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE active=1';
		$sql.= ' AND entity IN ('.getEntity('dolifleet').')';

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$Tab[$obj->rowid] = empty($field) ? $obj->rowid : $obj->{$field};
			}
			return $Tab;
		}
		else
		{
			return -1;
		}
	}

	public function getValueFromId($id, $field = 'label')
	{
		global $langs;

		$dict = new static($this->db);
		$ret = $dict->fetch($id);
		if ($ret > 0 && isset($dict->{$field}))
		{
			return $dict->{$field};
		}

		return '';
	}
}
