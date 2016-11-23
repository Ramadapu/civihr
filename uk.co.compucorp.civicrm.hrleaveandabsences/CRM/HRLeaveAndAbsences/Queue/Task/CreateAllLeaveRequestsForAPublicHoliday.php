<?php

use CRM_HRLeaveAndAbsences_BAO_PublicHoliday as PublicHoliday;
use CRM_HRLeaveAndAbsences_Factory_PublicHolidayLeaveRequestService as PublicHolidayLeaveRequestServiceFactory;

/**
 * This task will use the PublicHolidayLeaveRequest service to create Public
 * Leave Requests for all the contacts with contracts overlapping the given
 * Public Holiday date
 */
class CRM_HRLeaveAndAbsences_Queue_Task_CreateAllLeaveRequestsForAPublicHoliday {

  public function run(CRM_Queue_TaskContext $ctx, $date) {
    $date = new DateTime($date);
    $publicHoliday = new PublicHoliday();
    $publicHoliday->date = $date->format('Y-m-d');

    $service = PublicHolidayLeaveRequestServiceFactory::create();
    $service->createForAllContacts($publicHoliday);
  }

}
