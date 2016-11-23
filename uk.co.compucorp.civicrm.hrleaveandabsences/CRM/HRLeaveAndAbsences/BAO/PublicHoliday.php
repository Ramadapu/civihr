<?php

use CRM_HRLeaveAndAbsences_BAO_AbsencePeriod as AbsencePeriod;
use CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException as InvalidPublicHolidayException;
use CRM_HRLeaveAndAbsences_Queue_PublicHolidayLeaveRequestUpdates as PublicHolidayLeaveRequestUpdatesQueue;

class CRM_HRLeaveAndAbsences_BAO_PublicHoliday extends CRM_HRLeaveAndAbsences_DAO_PublicHoliday {

  /**
   * Create a new PublicHoliday based on array-data
   *
   * @param array $params
   *   key-value pairs
   * @return CRM_HRLeaveAndAbsences_DAO_PublicHoliday|NULL
   **/
  public static function create($params) {
    $entityName = 'PublicHoliday';
    $hook = empty($params['id']) ? 'create' : 'edit';

    self::validateParams($params);

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new self();
    $instance->copyValues($params);
    $transaction = new CRM_Core_Transaction();
    $instance->save();
    $transaction->commit();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Delete a PublicHoliday with given ID.
   *
   * @param int $id
   *
   * @throws RuntimeException
   */
  public static function del($id) {
    $publicHoliday = new CRM_HRLeaveAndAbsences_DAO_PublicHoliday();
    $publicHoliday->id = $id;
    $publicHoliday->find(true);

    if($publicHoliday->date && self::dateIsInThePast($publicHoliday->date)) {
      throw new RuntimeException('Past Public Holidays cannot be deleted');
    }

    $publicHoliday->delete();
  }

  /**
   * Return an array containing properties of Public Holiday with given ID.
   *
   * @param int $id
   *
   * @return array|NULL
   */
  public static function getValuesArray($id) {
    $result = civicrm_api3('PublicHoliday', 'get', ['id' => $id]);
    return !empty($result['values'][$id]) ? $result['values'][$id] : null;
  }

  /**
   * Validates all the params passed to the create method
   *
   * @param array $params
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   */
  private static function validateParams($params) {
    if(empty($params['title']) && empty($params['id'])) {
      throw new InvalidPublicHolidayException('Title value is required');
    }
    self::validateDate($params);
    self::checkIfDateIsUnique($params);
    self::validateIsActive($params);
    self::checkIfDateOverlapsAnAbsencePeriod($params);
  }

  /**
   * If there is no date specified but id exists then we skip the date validation.
   * Otherwise a date:
   * - cannot be empty
   * - must be a real date
   * - cannot be in the past
   *
   * @param array $params
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   *
   * @return bool
   */
  private static function validateDate($params) {
    // Skip date validation if we are editing an existing record and no new date is specified.
    if (!isset($params['date']) && !empty($params['id'])) {
      return true;
    }

    if (empty($params['date'])) {
      throw new InvalidPublicHolidayException('Date value is required');
    }

    $dateIsValid = CRM_HRLeaveAndAbsences_Validator_Date::isValid($params['date']);
    if(!$dateIsValid) {
      throw new InvalidPublicHolidayException('Date value should be valid');
    }

    $oldDate = self::getOldDate($params);
    if(strtotime($oldDate) != strtotime($params['date'])) {
      if(self::dateIsInThePast($oldDate)) {
        throw new InvalidPublicHolidayException('You cannot change the date of a past public holiday');
      }

      if(self::dateIsInThePast($params['date'])) {
        throw new InvalidPublicHolidayException('The date cannot be in the past');
      }
    }
  }

  /**
   * Check if there is no Public Holiday already existing with provided date.
   *
   * @param array $params
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   */
  private static function checkIfDateIsUnique($params) {
    // Skip date validation if we are editing an existing record and no new date is specified.
    if (!isset($params['date']) && !empty($params['id'])) {
      return;
    }
    // Check for Public Holiday already existing with given date.
    $duplicateDateParams = [
      'date' => $params['date'],
    ];
    if (!empty($params['id'])) {
      $duplicateDateParams['id'] = ['!=' => $params['id']];
    }
    $duplicateDate = civicrm_api3('PublicHoliday', 'getcount', $duplicateDateParams);
    if ($duplicateDate) {
      throw new InvalidPublicHolidayException('Another Public Holiday with the same date already exists');
    }
  }

  /**
   * Runs validation for the "Is Active" field. Basically, you cannot change its
   * value for a past public holiday
   *
   * @param array $params
   *   The params array passed to the create() method
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   */
  private static function validateIsActive($params) {
    if(empty($params['id'])) {
      return;
    }

    $publicHoliday = self::findById($params['id']);

    $isActiveChanged = array_key_exists('is_active', $params) &&
                       boolval($publicHoliday->is_active) != boolval($params['is_active']);

    if($isActiveChanged && self::dateIsInThePast($publicHoliday->date)) {
      throw new InvalidPublicHolidayException('You cannot disable/enable a past public holiday');
    }
  }

  /**
   * Returns the number of active Public Holidays between the given start and
   * end dates (inclusive).
   *
   * The end date can be null. In that case, it will count all the PublicHolidays
   * where the date is >= than start date
   *
   * @param string $startDate
   *   The start date of the period
   * @param string|null $endDate
   *   The end date of the period
   * @param bool $excludeWeekends
   *   When true it will not count Public Holidays that fall on a weekend. It's
   *   false by default
   *
   * @return int The Number of Public Holidays for the given Period
   */
  public static function getCountForPeriod($startDate, $endDate = null, $excludeWeekends = false) {
    $startDate = CRM_Utils_Date::processDate($startDate, null, false, 'Ymd');

    $queryParams = [
      1 => [$startDate, 'Date']
    ];

    $where = ' is_active = 1 AND date >= %1 ';

    if($endDate) {
      $endDate = CRM_Utils_Date::processDate($endDate, null, false, 'Ymd');
      $where .= ' AND date <= %2 ';
      $queryParams[2] = [$endDate, 'Date'];
    }

    // Weekends are Saturday and Sunday
    // So, to exclude them we return only the public holidays
    // between monday (2) and friday (6)
    if($excludeWeekends) {
      $where .= ' AND DAYOFWEEK(date) BETWEEN 2 AND 6 ';
    }

    $tableName = self::getTableName();

    $query = "
      SELECT COUNT(*) as public_holidays
      FROM {$tableName}
      WHERE $where
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $dao->fetch(true);

    return (int)$dao->public_holidays;
  }

  /**
   * Returns the number of Public Holidays in the Current Period
   *
   * @param bool $excludeWeekends
   *   If true, public holidays that falls on a weekend won't be counted. Default is false
   *
   * @return int
   */
  public static function getCountForCurrentPeriod($excludeWeekends = false) {
    $currentPeriod = AbsencePeriod::getCurrentPeriod();

    if(!$currentPeriod) {
      return 0;
    }

    return self::getCountForPeriod(
      $currentPeriod->start_date,
      $currentPeriod->end_date,
      $excludeWeekends
    );
  }

  /**
   * This method returns s list of active PublicHoliday instances between the
   * given start and end dates (inclusive).
   *
   * The end date can be null. In that case, it will return all the PublicHolidays
   * where the date is >= than start date
   *
   * @param string
   *   $startDate The start date of the period
   * @param string|null
   *   $endDate The end date of the period
   * @param bool $excludeWeekends
   *   When true it will not include Public Holidays that fall on a weekend. It's false by default
   *
   * @return CRM_HRLeaveAndAbsences_BAO_PublicHoliday[]
   */
  public static function getAllForPeriod($startDate, $endDate = null, $excludeWeekends = false) {
    $startDate = CRM_Utils_Date::processDate($startDate, null, false, 'Ymd');

    $queryParams = [
      1 => [$startDate, 'Date']
    ];

    $where = ' is_active = 1 AND date >= %1 ';

    if($endDate) {
      $endDate = CRM_Utils_Date::processDate($endDate, null, false, 'Ymd');
      $where .= ' AND date <= %2 ';
      $queryParams[2] = [$endDate, 'Date'];
    }

    // Weekends are Saturday and Sunday
    // So, to exclude them we return only the public holidays
    // between monday (2) and friday (6)
    if($excludeWeekends) {
      $where .= ' AND DAYOFWEEK(date) BETWEEN 2 AND 6 ';
    }

    $tableName = self::getTableName();

    $query = "
      SELECT *
      FROM {$tableName}
      WHERE {$where}
      ORDER BY date ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams, true, self::class);

    $publicHolidays = [];
    while($dao->fetch(true)) {
      $publicHolidays[] = clone $dao;
    }

    return $publicHolidays;
  }

  /**
   * Returns if the given $date is in the past. That is, the date is less than
   * today at 00:00:00
   *
   * @param string $date
   *   A date string in any format supported by strtotime
   *
   * @return bool
   */
  private static function dateIsInThePast($date) {
    if(!$date) {
      return false;
    }
    $timestampToday = strtotime(date('Y-m-d 00:00:00'));

    return strtotime($date) < $timestampToday;
  }

  /**
   * Returns the old value for the date field of the Public Holiday being
   * updated.
   *
   * @param array $params
   *   The params array passed to the create() method
   *
   * @return string|null
   */
  private static function getOldDate($params) {
    if(empty($params['id'])) {
      return null;
    }

    $publicHoliday = self::findById($params['id']);

    return $publicHoliday->date;
  }

  private static function checkIfDateOverlapsAnAbsencePeriod($params) {
    if(!array_key_exists('date', $params)) {
      $date = self::getOldDate($params);
    } else {
      $date = $params['date'];
    }

    $period = AbsencePeriod::getPeriodOverlappingDate(new DateTime($date));

    if(is_null($period)) {
      throw new InvalidPublicHolidayException('The date cannot be outside the existing absence periods');
    }
  }

  /**
   * Returns all the Public Holidays in the future. That is, all where date is
   * >= today. Including Public Holidays that fall on weekends
   *
   * @return \CRM_HRLeaveAndAbsences_BAO_PublicHoliday[]
   */
  public static function getAllInFuture() {
    return self::getAllForPeriod(date('Ymd'));
  }

  /**
   * Process all the items on the PublicHolidayLeaveRequestUpdates Queue
   *
   * @return int
   *   The number of items processed
   */
  public static function processPublicHolidayLeaveRequestUpdatesQueue() {
    $numberOfItemsProcessed = 0;

    $queue = PublicHolidayLeaveRequestUpdatesQueue::getQueue();
    $runner = new CRM_Queue_Runner([
      'title' => ts('Public Holiday Leave Request Updates Runner'),
      'queue' => $queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
    ]);

    $continue = true;
    while($continue) {
      $result = $runner->runNext(false);
      $numberOfItemsProcessed++;
      if (!$result['is_continue']) {
        $continue = false; //all items in the queue are processed
      }
    }

    return $numberOfItemsProcessed;
  }
}
