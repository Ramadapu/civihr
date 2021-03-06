<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 *
 * Generated from xml/schema/CRM/HRRecruitment/HRVacancy.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:2ac1b1508fe4cd43c104c72051ccf9f8)
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
/**
 * CRM_HRRecruitment_DAO_HRVacancy constructor.
 */
class CRM_HRRecruitment_DAO_HRVacancy extends CRM_Core_DAO {
  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  static $_tableName = 'civicrm_hrvacancy';
  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var boolean
   */
  static $_log = true;
  /**
   * Unique Recruitment Vacancy ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Salary offered in vacancy
   *
   * @var string
   */
  public $salary;
  /**
   * Job Position offered in vacancy
   *
   * @var string
   */
  public $position;
  /**
   * Description of vacancy
   *
   * @var longtext
   */
  public $description;
  /**
   *
   * @var longtext
   */
  public $benefits;
  /**
   * Requirements of vacancy
   *
   * @var longtext
   */
  public $requirements;
  /**
   * Location of vacancy
   *
   * @var string
   */
  public $location;
  /**
   * Whether the Vacancy has template
   *
   * @var boolean
   */
  public $is_template;
  /**
   * Status of Vacancy
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Vacancy Start Date
   *
   * @var datetime
   */
  public $start_date;
  /**
   * Vacancy End Date
   *
   * @var datetime
   */
  public $end_date;
  /**
   * Vacancy Created Date
   *
   * @var datetime
   */
  public $created_date;
  /**
   * FK to civicrm_contact, who created this vacancy
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * Class constructor.
   */
  function __construct() {
    $this->__table = 'civicrm_hrvacancy';
    parent::__construct();
  }
  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'created_id', 'civicrm_contact', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => 'Unique Recruitment Vacancy ID',
          'required' => true,
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'salary' => array(
          'name' => 'salary',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Salary') ,
          'description' => 'Salary offered in vacancy',
          'maxlength' => 127,
          'size' => CRM_Utils_Type::HUGE,
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'position' => array(
          'name' => 'position',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Position') ,
          'description' => 'Job Position offered in vacancy',
          'maxlength' => 127,
          'size' => CRM_Utils_Type::HUGE,
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'description' => array(
          'name' => 'description',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Description') ,
          'description' => 'Description of vacancy',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'benefits' => array(
          'name' => 'benefits',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Benefits') ,
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'requirements' => array(
          'name' => 'requirements',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Requirements') ,
          'description' => 'Requirements of vacancy',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'location' => array(
          'name' => 'location',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Location') ,
          'description' => 'Location of vacancy',
          'maxlength' => 254,
          'size' => CRM_Utils_Type::HUGE,
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
          'pseudoconstant' => array(
            'optionGroupName' => 'hrjc_location',
            'optionEditPath' => 'civicrm/admin/options/hrjc_location',
          )
        ) ,
        'is_template' => array(
          'name' => 'is_template',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'description' => 'Whether the Vacancy has template',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Status') ,
          'description' => 'Status of Vacancy',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
          'pseudoconstant' => array(
            'optionGroupName' => 'vacancy_status',
            'optionEditPath' => 'civicrm/admin/options/vacancy_status',
          )
        ) ,
        'start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Start Date') ,
          'description' => 'Vacancy Start Date',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('End Date') ,
          'description' => 'Vacancy End Date',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Created Date') ,
          'description' => 'Vacancy Created Date',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
        ) ,
        'created_id' => array(
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => 'FK to civicrm_contact, who created this vacancy',
          'table_name' => 'civicrm_hrvacancy',
          'entity' => 'HRVacancy',
          'bao' => 'CRM_HRRecruitment_DAO_HRVacancy',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
      );
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }
  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }
  /**
   * Returns the names of this table
   *
   * @return string
   */
  static function getTableName() {
    return self::$_tableName;
  }
  /**
   * Returns if this table needs to be logged
   *
   * @return boolean
   */
  function getLog() {
    return self::$_log;
  }
  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &import($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'hrvacancy', $prefix, array());
    return $r;
  }
  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'hrvacancy', $prefix, array());
    return $r;
  }
  /**
   * Returns the list of indices
   */
  public static function indices($localize = TRUE) {
    $indices = array(
      'index_location' => array(
        'name' => 'index_location',
        'field' => array(
          0 => 'location',
        ) ,
        'localizable' => false,
        'sig' => 'civicrm_hrvacancy::0::location',
      ) ,
    );
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }
}
