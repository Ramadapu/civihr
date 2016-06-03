<?php

class CRM_HRLeaveAndAbsences_BAO_PublicHoliday extends CRM_HRLeaveAndAbsences_DAO_PublicHoliday {

  /**
   * Create a new PublicHoliday based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_HRLeaveAndAbsences_DAO_PublicHoliday|NULL
   **/
  public static function create($params) {
    $className = 'CRM_HRLeaveAndAbsences_DAO_PublicHoliday';
    $entityName = 'PublicHoliday';
    $hook = empty($params['id']) ? 'create' : 'edit';

    self::validateParams($params);

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $transaction = new CRM_Core_Transaction();
    $instance->save();
    $transaction->commit();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Validates all the params passed to the create method
   *
   * @param array $params
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   */
  private static function validateParams($params)
  {
    if(empty($params['title'])) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException(
        'Title value is required'
      );
    }
    self::validateDate($params);
  }

  /**
   * Checks if date value in the $params array is valid.
   *
   * A date cannot be empty and must be a real date.
   *
   * @param array $params
   *
   * @throws \CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException
   */
  private static function validateDate($params)
  {
    if(empty($params['date'])) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException(
        'Date value is required'
      );
    }

    $dateIsValid = CRM_HRLeaveAndAbsences_Validator_Date::isValid($params['date']);
    if(!$dateIsValid) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidPublicHolidayException(
        'Date value should be valid'
      );
    }
  }

  /**
   * Returns the number of active Public Holidays between the given
   * start and end dates (inclusive)
   *
   * @param $startDate The start date of the period
   * @param $endDate The end date of the period
   * @param bool $excludeWeekends When true it will not count Public Holidays that fall on a weekend. It's false by default
   *
   * @return int The Number of Public Holidays for the given Period
   */
  public static function getNumberOfPublicHolidaysForPeriod($startDate, $endDate, $excludeWeekends = false)
  {
    $startDate = CRM_Utils_Date::processDate($startDate, null, false, 'Ymd');
    $endDate = CRM_Utils_Date::processDate($endDate, null, false, 'Ymd');

    $tableName = self::getTableName();
    $query = "
      SELECT COUNT(*) as public_holidays
      FROM {$tableName}
      WHERE date >= %1 AND date <= %2 AND is_active = 1
    ";

    if($excludeWeekends) {
      $query .= ' AND DAYOFWEEK(date) BETWEEN 2 AND 6';
    }

    $queryParams = [
      1 => [$startDate, 'Date'],
      2 => [$endDate, 'Date'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $dao->fetch(true);

    return (int)$dao->public_holidays;
  }
}