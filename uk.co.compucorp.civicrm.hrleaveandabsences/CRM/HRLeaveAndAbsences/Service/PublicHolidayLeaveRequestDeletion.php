<?php

use CRM_HRLeaveAndAbsences_BAO_PublicHoliday as PublicHoliday;
use CRM_HRLeaveAndAbsences_BAO_LeaveBalanceChange as LeaveBalanceChange;
use CRM_HRLeaveAndAbsences_BAO_LeaveRequest as LeaveRequest;
use CRM_HRLeaveAndAbsences_Service_JobContract as JobContractService;

class CRM_HRLeaveAndAbsences_Service_PublicHolidayLeaveRequestDeletion {

  /**
   * @var \CRM_HRLeaveAndAbsences_Service_JobContract
   */
  private $jobContractService;

  public function __construct(JobContractService $jobContractService) {
    $this->jobContractService = $jobContractService;
  }

  /**
   * Deletes all the existing LeaveRequests for the given Public Holiday for
   * all contacts
   *
   * @param \CRM_HRLeaveAndAbsences_BAO_PublicHoliday $publicHoliday
   */
  public function deleteForAllContacts(PublicHoliday $publicHoliday) {
    $contracts = $this->jobContractService->getContractsForPeriod(
      new DateTime($publicHoliday->date),
      new DateTime($publicHoliday->date)
    );

    foreach($contracts as $contract) {
      $this->deleteForContact($contract['contact_id'], $publicHoliday);
    }
  }

  /**
   * Deletes the Public Holiday Leave Request for the contact and Public Holiday.
   *
   * If there are LeaveRequestDates overlapping the public holiday, their
   * balance change amount will be updated to no be 0 anymore.
   *
   * @param int $contactID
   * @param \CRM_HRLeaveAndAbsences_BAO_PublicHoliday $publicHoliday
   */
  public function deleteForContact($contactID, PublicHoliday $publicHoliday) {
    $leaveRequest = LeaveRequest::findPublicHolidayLeaveRequest($contactID, $publicHoliday);

    if(!$leaveRequest) {
      return;
    }

    foreach($leaveRequest->getDates() as $date) {
      LeaveBalanceChange::deleteForLeaveRequestDate($date);
      $this->recalculateDeductionForOverlappingLeaveRequestDate($leaveRequest, new DateTime($date->date));
      $date->delete();
    }

    $leaveRequest->delete();
  }

  /**
   * Deletes all the Public Holiday Leave Requests between the start and end
   * dates of the given contract.
   *
   * @param int $contractID
   */
  public function deleteAllForContract($contractID) {
    $contract = $this->jobContractService->getContractByID($contractID);

    if(!$contract) {
      return;
    }

    $publicHolidays = PublicHoliday::getAllForPeriod(
      $contract['period_start_date'],
      $contract['period_end_date']
    );

    foreach($publicHolidays as $publicHoliday) {
      if(strtotime($publicHoliday->date) >= strtotime('today')) {
        $this->deleteForContact($contract['contact_id'], $publicHoliday);
      }
    }
  }

  /**
   * Deletes all the Public Holiday Leave Requests for Public Holidays in the
   * future
   */
  public function deleteAllInTheFuture() {
    $futurePublicHolidays = PublicHoliday::getAllInFuture();
    $lastPublicHoliday = end($futurePublicHolidays);

    $contracts = $this->jobContractService->getContractsForPeriod(
      new DateTime(),
      new DateTime($lastPublicHoliday->date)
    );

    foreach($contracts as $contract) {
      foreach($futurePublicHolidays as $publicHoliday) {
        $this->deleteForContact($contract['contact_id'], $publicHoliday);
      }
    }
  }

  /**
   * First, searches for an existing balance change for the same contact and absence
   * type of the given $leaveRequest and linked to a LeaveRequestDate with the
   * same date as $date. Next, if such balance change exists, update
   * it's amount to using the Work Pattern assigned to the contact or the default
   * one, if the contact has no work patterns.
   *
   * @param \CRM_HRLeaveAndAbsences_BAO_LeaveRequest $leaveRequest
   * @param \DateTime $date
   */
  private function recalculateDeductionForOverlappingLeaveRequestDate(LeaveRequest $leaveRequest, DateTime $date) {
    $leaveBalanceChange = LeaveBalanceChange::getExistingBalanceChangeForALeaveRequestDate($leaveRequest, $date);

    if($leaveBalanceChange) {
      $deduction = LeaveBalanceChange::calculateAmountForDate(
        $leaveRequest,
        $date
      );

      LeaveBalanceChange::create([
        'id' => $leaveBalanceChange->id,
        'amount' => $deduction
      ]);
    }
  }
}
