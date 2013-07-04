<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/
/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 * Generated from xml/schema/CRM/HRJob/HRJob.xml
 * DO NOT EDIT.  Generated by GenCode.php
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_HRJob_DAO_HRJob extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_hrjob';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the keys used in $_fields for each field.
   *
   * @var array
   * @static
   */
  static $_fieldKeys = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = true;
  /**
   * Unique HRJob ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Contact ID
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Internal name for the job (for HR)
   *
   * @var string
   */
  public $position;
  /**
   * Negotiated name for the job
   *
   * @var string
   */
  public $title;
  /**
   *
   * @var boolean
   */
  public $is_tied_to_funding;
  /**
   * Contract for employment, internship, etc.
   *
   * @var string
   */
  public $contract_type;
  /**
   * Junior manager, senior manager, etc.
   *
   * @var string
   */
  public $seniority;
  /**
   * .
   *
   * @var enum('Temporary', 'Permanent')
   */
  public $period_type;
  /**
   * First day of the job
   *
   * @var date
   */
  public $period_start_date;
  /**
   * Last day of the job
   *
   * @var date
   */
  public $period_end_date;
  /**
   * FK to Contact ID
   *
   * @var int unsigned
   */
  public $manager_contact_id;
  /**
   * Is this the primary?
   *
   * @var boolean
   */
  public $is_primary;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_hrjob
   */
  function __construct()
  {
    $this->__table = 'civicrm_hrjob';
    parent::__construct();
  }
  /**
   * return foreign keys and entity references
   *
   * @static
   * @access public
   * @return array of CRM_Core_EntityReference
   */
  static function getReferenceColumns()
  {
    if (!self::$_links) {
      self::$_links = array(
        new CRM_Core_EntityReference(self::getTableName() , 'contact_id', 'civicrm_contact', 'id') ,
        new CRM_Core_EntityReference(self::getTableName() , 'manager_contact_id', 'civicrm_contact', 'id') ,
      );
    }
    return self::$_links;
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'position' => array(
          'name' => 'position',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Position') ,
          'maxlength' => 127,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title') ,
          'maxlength' => 127,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_tied_to_funding' => array(
          'name' => 'is_tied_to_funding',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'contract_type' => array(
          'name' => 'contract_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contract Type') ,
          'maxlength' => 63,
          'size' => CRM_Utils_Type::BIG,
          'pseudoconstant' => array(
            'optionGroupName' => 'hrjob_contract_type',
          )
        ) ,
        'seniority' => array(
          'name' => 'seniority',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Seniority') ,
          'maxlength' => 63,
          'size' => CRM_Utils_Type::BIG,
          'pseudoconstant' => array(
            'optionGroupName' => 'hrjob_seniority',
          )
        ) ,
        'period_type' => array(
          'name' => 'period_type',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Period Type') ,
          'enumValues' => 'Temporary, Permanent',
        ) ,
        'period_start_date' => array(
          'name' => 'period_start_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Job Start Date') ,
        ) ,
        'period_end_date' => array(
          'name' => 'period_end_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Job End Date') ,
        ) ,
        'manager_contact_id' => array(
          'name' => 'manager_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'is_primary' => array(
          'name' => 'is_primary',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the arary key used for that
   * field in self::$_fields.
   *
   * @access public
   * @return array
   */
  static function &fieldKeys()
  {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'id',
        'contact_id' => 'contact_id',
        'position' => 'position',
        'title' => 'title',
        'is_tied_to_funding' => 'is_tied_to_funding',
        'contract_type' => 'contract_type',
        'seniority' => 'seniority',
        'period_type' => 'period_type',
        'period_start_date' => 'period_start_date',
        'period_end_date' => 'period_end_date',
        'manager_contact_id' => 'manager_contact_id',
        'is_primary' => 'is_primary',
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['hrjob'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['hrjob'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_hrjob table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'period_type',
    );
    return $enums;
  }
  /**
   * returns a ts()-translated enum value for display purposes
   *
   * @param string $field  the enum field in question
   * @param string $value  the enum value up for translation
   *
   * @return string  the display value of the enum
   */
  static function tsEnum($field, $value)
  {
    static $translations = null;
    if (!$translations) {
      $translations = array(
        'period_type' => array(
          'Temporary' => ts('Temporary') ,
          'Permanent' => ts('Permanent') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_hrjob
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = & CRM_HRJob_DAO_HRJob::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_HRJob_DAO_HRJob::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
