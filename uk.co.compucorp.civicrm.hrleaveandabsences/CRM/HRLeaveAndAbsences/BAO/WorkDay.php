<?php

class CRM_HRLeaveAndAbsences_BAO_WorkDay extends CRM_HRLeaveAndAbsences_DAO_WorkDay {

  const WORK_DAY_OPTION_NO = '1';
  const WORK_DAY_OPTION_YES = '2';
  const WORK_DAY_OPTION_WEEKEND = '3';

  /**
   * Create a new WorkDay based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_HRLeaveAndAbsences_DAO_WorkDay|NULL
   *
   */
  public static function create($params) {
    $className = 'CRM_HRLeaveAndAbsences_DAO_WorkDay';
    $entityName = 'WorkDay';
    $hook = empty($params['id']) ? 'create' : 'edit';
    self::validateParams($params);

    if(!empty($params['id'])) {
      unset($params['week_id']);
    }

    $params['number_of_hours'] = null;
    if($params['type'] == self::WORK_DAY_OPTION_YES) {
      $params['number_of_hours'] = self::calculateNumberOfHours(
          $params['time_from'],
          $params['time_to'],
          $params['break']
      );
    }

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Return a list of possible options for the WorkDay::type field.
   *
   * The list is the format $value => $label, so it can be used
   * to populate select controls.
   *
   * @return array the list of possible options
   */
  public static function getWorkTypeOptions()
  {
    return [
      self::WORK_DAY_OPTION_NO => ts('No'),
      self::WORK_DAY_OPTION_YES => ts('Yes'),
      self::WORK_DAY_OPTION_WEEKEND => ts('Weekend'),
    ];
  }

  private static function validateParams($params) {
    self::validateWorkDayType($params);
    self::validateDayOfTheWeek($params);
    self::validateWorkHours($params);
  }

  private static function validateDayOfTheWeek($params)
  {
    $dayOfTheWeek = empty($params['day_of_the_week']) ? null : $params['day_of_the_week'];

    if(!is_int($dayOfTheWeek) || ($dayOfTheWeek < 1 || $dayOfTheWeek > 7)) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Day of the Week should be a number between 1 and 7, according to ISO-8601'
      );
    }
  }

  private static function validateWorkHours($params)
  {
    $typeOfDay = empty($params['type']) ? null : $params['type'];
    $timeFrom = empty($params['time_from']) ? null : $params['time_from'];
    $timeTo = empty($params['time_to']) ? null : $params['time_to'];
    $break = empty($params['break']) ? null : $params['break'];

    $hasTimesOrBreak = $timeFrom || $timeTo || $break;
    $isWorkingDay = $typeOfDay == self::WORK_DAY_OPTION_YES;
    if(!$isWorkingDay && $hasTimesOrBreak) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Time From, Time To and Break should be empty for Non Working Days and Weekends'
      );
    }

    if($timeFrom && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $timeFrom)) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Time From format should be hh:mm'
      );
    }

    if($timeTo && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $timeTo)) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Time To format should be hh:mm'
      );
    }

    $hasTimesAndBreak = $timeFrom && $timeTo && $break;
    if($isWorkingDay && !$hasTimesAndBreak) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Time From, Time To and Break are required for Working Days'
      );
    }

    $hasTimes = !is_null($timeFrom) && !is_null($timeTo);
    if($hasTimes && (strtotime($timeFrom) >= strtotime($timeTo))) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
          'Time From should be less than Time To'
      );
    }

    $secondsInWorkingHours = strtotime($timeTo) - strtotime($timeFrom);
    $secondsInBreak = $break * 3600;
    if($hasTimes && ($secondsInBreak >= $secondsInWorkingHours)) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
          'Break should be less than the number of hours between Time From and Time To'
      );
    }
  }

  private static function validateWorkDayType($params) {
    $type = empty($params['type']) ? null : $params['type'];
    if(!in_array($type, array_keys(self::getWorkTypeOptions()))) {
      throw new CRM_HRLeaveAndAbsences_Exception_InvalidWorkDayException(
        'Invalid Work Day Type'
      );
    }
  }

  private static function calculateNumberOfHours($timeFrom, $timeTo, $break)
  {
    $timeFromInHours = strtotime($timeFrom) / 3600;
    $timeToInHours = strtotime($timeTo) / 3600;
    $numberOfHours = $timeToInHours - $timeFromInHours - $break;

    return $numberOfHours;
  }
}
