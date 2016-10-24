<?php
require_once __DIR__."/LeaveBalanceChangeHelpersTrait.php";
require_once __DIR__."/ContractHelpersTrait.php";

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use CRM_HRLeaveAndAbsences_BAO_AbsencePeriod as AbsencePeriod;
use CRM_HRLeaveAndAbsences_BAO_AbsenceType as AbsenceType;
use CRM_HRLeaveAndAbsences_BAO_LeavePeriodEntitlement as LeavePeriodEntitlement;
use CRM_HRLeaveAndAbsences_BAO_LeaveRequest as LeaveRequest;
use CRM_HRLeaveAndAbsences_EntitlementCalculation as EntitlementCalculation;
use CRM_HRLeaveAndAbsences_Test_Fabricator_AbsenceType as AbsenceTypeFabricator;
use CRM_HRLeaveAndAbsences_Test_Fabricator_PublicHoliday as PublicHolidayFabricator;
use CRM_HRLeaveAndAbsences_Test_Fabricator_AbsencePeriod as AbsencePeriodFabricator;

/**
 * Class CRM_HRLeaveAndAbsences_EntitlementCalculationTest
 *
 * @group headless
 */
class CRM_HRLeaveAndAbsences_EntitlementCalculationTest extends PHPUnit_Framework_TestCase implements
 HeadlessInterface, TransactionalInterface {

  use CRM_HRLeaveAndAbsences_LeaveBalanceChangeHelpersTrait;
  use CRM_HRLeaveAndAbsences_ContractHelpersTrait;

  private $contact;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->install('org.civicrm.hrjobcontract')
      ->apply();
    $jobContractUpgrader = CRM_Hrjobcontract_Upgrader::instance();
    $jobContractUpgrader->install();
  }

  public function setUp() {
    $this->createContract();
    $this->contact = ['id' => $this->contract['contact_id']];
  }

  public function testBroughtForwardShouldBeZeroIfAbsenceTypeDoesntAllowCarryForward()
  {
    $period = new AbsencePeriod();
    $type = new AbsenceType();

    $type->allow_carry_forward = false;
    $calculation = new EntitlementCalculation($period, $this->contact, $type);

    $this->assertEquals(0, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldBeZeroIfTheresNoPreviousPeriod()
  {
    $type = AbsenceTypeFabricator::fabricate();

    $period = AbsencePeriodFabricator::fabricate([
      'start_date' => CRM_Utils_Date::processDate('2016-01-01'),
      'end_date' => CRM_Utils_Date::processDate('2016-12-31'),
    ]);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertEquals(0, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldBeZeroIfThereIsNoEntitlementForPreviousPeriod()
  {
    $type = AbsenceTypeFabricator::fabricate();

    AbsencePeriodFabricator::fabricate([
      'start_date' => CRM_Utils_Date::processDate('2015-01-01'),
      'end_date' => CRM_Utils_Date::processDate('2015-12-31'),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => CRM_Utils_Date::processDate('2016-01-01'),
      'end_date' => CRM_Utils_Date::processDate('2016-12-31'),
    ]);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldBeZeroIfExpirationDurationHasExpired()
  {
    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 50,
      'carry_forward_expiration_unit'       => AbsenceType::EXPIRATION_UNIT_DAYS,
      'carry_forward_expiration_duration'   => 5
    ]);

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('-7 days')),
      'end_date' => date('YmdHis', strtotime('-6 days')),
    ]);

    // The absence type says brought forward should expire in 5 days,
    // so we set the period start date to 5 days ago. Since the expiration date
    // is based on the period start date, it will be considered to have expired
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('-5 days')),
      'end_date' => date('YmdHis', strtotime('now')),
    ], true);

    $this->createEntitlement($previousPeriod, $type);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldBeZeroIfThePreviousPeriodIsNotOverYet() {
    // never expires
    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 50,
    ]);

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('-7 days')),
      'end_date' => date('YmdHis', strtotime('+5 days')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('+6 days')),
      'end_date' => date('YmdHis', strtotime('+10 days')),
    ], true);

    $this->createEntitlement($previousPeriod, $type);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldNotBeMoreThanTheMaxNumberOfDaysAllowedToBeCarriedForward() {
    $this->setContractDates(date('YmdHis', strtotime('-2 days')), null);

    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 5
    ]);

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('-2 days')),
      'end_date' => date('YmdHis', strtotime('-1 day')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+1 day')),
    ], true);

    $this->createEntitlement($previousPeriod, $type, 10);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    // As the number of leaves taken is 0 at this point, all of days remaining
    // from the previous period entitlement (10 days) will be carried to the
    // current period, but that is more than the max number of days allowed by
    // the absence type, so the carried amount should be reduced to he maximum
    // allowed
    $this->assertEquals(5, $calculation->getBroughtForward());
  }

  public function testBroughtForwardShouldNotBeMoreThanTheNumberOfRemainingDaysInPreviousEntitlement() {
    $this->setContractDates(date('YmdHis', strtotime('-2 days')), null);

    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 5
    ]);

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('-2 days')),
      'end_date' => date('YmdHis', strtotime('-1 day')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+1 day')),
    ], true);

    $this->createEntitlement($previousPeriod, $type, 3);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    // The maximum allowed to be carried forward by the absence type is 5,
    // But the number of days remaining (it's balance) in the previous the period
    // is only 3 (the original entitlement without any leave taken), so that's
    // what will brought forward
    $this->assertEquals(3, $calculation->getBroughtForward());
  }

  public function testProRataShouldBeZeroIfTheContactHasNoContracts() {
    $type = new AbsenceType();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+1 day')),
    ], true);

    //A non-existing contact, which will have no contracts
    $contact = ['id' => 5321];

    $calculation = new EntitlementCalculation($currentPeriod, $contact, $type);
    $this->assertEquals(0, $calculation->getProRata());
  }

  public function testProRataShouldBeZeroIfTheContractDoesntHaveStartAndEndDates() {
    $type = new AbsenceType();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+1 day')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getProRata());
  }

  public function testProRataShouldBeRoundedToTheNearestHalfDay() {
    $type = AbsenceTypeFabricator::fabricate();

    // 261 working days
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    // 21 days to work
    $this->setContractDates(
      date('YmdHis', strtotime('2016-01-01')),
      date('YmdHis', strtotime('2016-01-31'))
    );
    $this->createJobLeaveEntitlement($type, 10);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    // (21/261) * 10 = 0.80 = 1 (rounded)
    $this->assertEquals(1, $calculation->getProRata());

    // 32 days to work
    $this->setContractDates(
      date('YmdHis', strtotime('2016-01-01')),
      date('YmdHis', strtotime('2016-02-15'))
    );

    //Instantiates the calculation again to get the updated contract dates
    $calculation = new EntitlementCalculation($currentPeriod,  $this->contact, $type);
    // (32/261) * 10 = 1.22 = 1.5 rounded
    $this->assertEquals(1.5, $calculation->getProRata());

    // 66 days to work
    $this->setContractDates(
      date('YmdHis', strtotime('2016-01-01')),
      date('YmdHis', strtotime('2016-04-01'))
    );

    //Instantiates the calculation again to get the updated contract dates
    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    // (66/261) * 10 = 2.52 = 3 rounded
    $this->assertEquals(3, $calculation->getProRata());
  }

  public function testProRataShouldNotIncludePublicHolidaysBetweenContractDatesEvenIfContractSaysPublicHolidaysShouldBeAdded() {
    $type = AbsenceTypeFabricator::fabricate();

    // 261 working days
    $period = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    // 141 working days to work (142 - 1 public holiday)
    $this->setContractDates(
      date('YmdHis', strtotime('2016-03-10')),
      date('YmdHis', strtotime('2016-09-23'))
    );
    $this->createJobLeaveEntitlement($type, 20, true);

    // This is between the contract dates, but will not be included
    PublicHolidayFabricator::fabricate([
      'date' => date('YmdHis', strtotime('2016-05-18'))
    ]);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);

    // 10 * (141/261) = 10.80 = 11 (rounded up to the nearest half day)
    $this->assertEquals(11, $calculation->getProRata());
  }

  public function testProRataShouldBeTheSumOfTheProRataForEachContractDuringTheAbsencePeriod() {
    $type = AbsenceTypeFabricator::fabricate();

    // Delete the contract created during setUp
    civicrm_api3('HRJobContract', 'deletecontract', ['id' => $this->contract['id']]);

    // 261 working days
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    // 62 working days
    // 3 days of contractual entitlement
    // Contract Pro Rata: 3 * (62 / 261) = 0,712
    $this->createContractWithDetailsAndLeaveEntitlement(
      $this->contact['id'],
      '2016-01-01',
      '2016-03-28',
      $type->id,
      3,
      true
    );

    // 104 working days
    // 5 days of contractual entitlement
    // Contract Pro Rata:  5 * (104 / 261) = 1,992
    $this->createContractWithDetailsAndLeaveEntitlement(
      $this->contact['id'],
      '2016-05-10',
      '2016-09-30',
      $type->id,
      5,
      false
    );

    // 64 working days
    // 20 days if contractual entitlement
    // Contract Pro Rata: 20 * (64 / 261): 4,904
    $this->createContractWithDetailsAndLeaveEntitlement(
      $this->contact['id'],
      '2016-10-01',
      null,
      $type->id,
      20,
      true
    );

    $entitlementCalculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    // Pro Rata: 0,712 + 1,992 + 4,904 = 7,608
    // Pro Rata rounded up to the nearest half day: 8
    $this->assertEquals(8, $entitlementCalculation->getProRata());
  }

  public function testTheProposedEntitlementForAContactWithoutAContractShouldBeZero() {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+1 days'))
    ], true);

    // a contact without any contract
    $contact = ['id' => 3453];

    $calculation = new EntitlementCalculation($currentPeriod, $contact, $type);
    $this->assertEquals(0, $calculation->getProposedEntitlement());
  }

  public function testTheProposedEntitlementForAContractWithoutStartAndEndDatesShouldBeZero() {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getProposedEntitlement());
  }

  public function testTheProposedEntitlementForAPeriodWithPreviouslyOverriddenEntitlementShouldNotBeTheTheOverriddenValue() {
    $type = AbsenceTypeFabricator::fabricate();
    $startDate = date('YmdHis', strtotime('2016-01-01'));
    $endDate = date('YmdHis', strtotime('2016-04-01'));
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => $startDate,
      'end_date' => $endDate
    ], true);

    // Set the contractual entitlement as 10 days
    $this->createJobLeaveEntitlement($type, 10);

    // The contract spans the whole period, so
    // The proposed entitlement will be the whole contractual entitlement
    $this->setContractDates(
      $startDate,
      $endDate
    );

    // Creates the previously overridden Leave Period Entitlement
    $this->createEntitlement($currentPeriod, $type, 10, 50);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(10, $calculation->getProposedEntitlement());
  }

  public function testTheProposedEntitlementShouldBeProRataPlusNumberOfDaysBroughtForward()
  {
    // To simplify the code, we use an Absence where the carried
    // forward never expires
    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 20,
    ]);

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    // Set the previous period entitlement as 10 days
    $this->createEntitlement($previousPeriod, $type, 10);

    // 261 working days
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    // Set the contractual entitlement as 10 days
    $this->createJobLeaveEntitlement($type, 10);

    // 66 days to work
    $this->setContractDates(
      date('YmdHis', strtotime('2016-01-01')),
      date('YmdHis', strtotime('2016-04-01'))
    );

    // (66/261) * 10 = 2.52 = 3 rounded
    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(3, $calculation->getProRata());

    // As the number of leaves taken is 0 at this point,
    // all of the proposed_entitlement from the previous period
    // entitlement will be carried to the current period
    $this->assertEquals(10, $calculation->getBroughtForward());

    // Proposed Entitlement in previous period: 20
    // Number of days brought from previous period: 10 (The whole entitlement from the previous period)
    // Number of Working days: 261
    // Number of Days to work: 66
    // Contractual Entitlement: 10
    // Pro Rata: (Number of days to work / Number working days) * Contractual Entitlement
    // Pro Rata: (66/261) * 10 = 2.52 = 3 rounded
    // Proposed entitlement: Pro Rata + Number of days brought from previous period
    // Proposed entitlement: 3 + 10
    $this->assertEquals(13, $calculation->getProposedEntitlement());
  }

  public function testProposedEntitlementShouldIncludePublicHolidaysBetweenContractsWhereTheyShouldBeAdded() {
    $type = AbsenceTypeFabricator::fabricate([
      'max_number_of_days_to_carry_forward' => 20,
    ]);

    // 261 working days
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date'   => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    // Set the contractual entitlement as 10 days
    $this->createJobLeaveEntitlement($type, 10, TRUE);

    // 64 days to work
    $this->setContractDates(
      date('YmdHis', strtotime('2016-01-01')),
      date('YmdHis', strtotime('2016-03-30'))
    );

    // This will not be included since it's before the contract start date
    PublicHolidayFabricator::fabricate([
      'date'  => date('YmdHis', strtotime('2015-01-01'))
    ]);

    // This will be included since it's between the contract dates
    PublicHolidayFabricator::fabricate([
      'date'  => date('YmdHis', strtotime('2016-01-01'))
    ]);

    // This will not be included since it's after the contract start date
    PublicHolidayFabricator::fabricate([
      'date'  => date('YmdHis', strtotime('2016-05-04'))
    ]);

    // Working days in Period = 261 - 3 (public holidays) = 259
    // Working days to work = 64 - 1 (the single public holiday between contract dates) = 63
    // (63/259) * 10 = 2.43 = 2.5 rounded
    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(2.5, $calculation->getProRata());

    // Number of days brought from previous period: 0 (No previous period)
    // Number of Working days: 259
    // Number of Days to work: 63
    // Contractual Entitlement: 10
    // Number of Public Holidays: 1
    // Pro Rata: (Number of days to work / Number working days) * Contractual Entitlement
    // Pro Rata: (63/259) * 10 = 2.43 = 2.5 rounded
    // Proposed entitlement: Pro Rata + Brought Forward + Public Holidays
    // Proposed entitlement: 2.5 + 0 + 1
    $this->assertEquals(3.5, $calculation->getProposedEntitlement());
  }

  public function testGetOverriddenEntitlementShouldBeZeroIfTheresNoPreviousPeriodEntitlement() {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getOverriddenEntitlement());
  }

  public function testGetOverriddenEntitlementShouldBeZeroIfThePreviousPeriodEntitlementIsNotOverridden() {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([], true);

    $this->createEntitlement($currentPeriod, $type, 10);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getOverriddenEntitlement());
  }

  public function testGetOverriddenEntitlementShouldShouldBeTheOverriddenValueIfThePreviousPeriodEntitlementIsOverridden() {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([], true);

    $this->createEntitlement($currentPeriod, $type, 10, 50);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(50, $calculation->getOverriddenEntitlement());
  }

  public function testPreviousPeriodProposedEntitlementShouldBeZeroIfThereIsNoPreviousPeriod()
  {
    $type = AbsenceTypeFabricator::fabricate();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getPreviousPeriodProposedEntitlement());
  }

  public function testPreviousPeriodProposedEntitlementShouldBeZeroIfThereIsNoEntitlementForThePreviousPeriod()
  {
    $type = AbsenceTypeFabricator::fabricate();

    AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getPreviousPeriodProposedEntitlement());
  }

  public function testPreviousPeriodProposedEntitlementShouldReturnTheProposedEntitlement()
  {
    $type = AbsenceTypeFabricator::fabricate();

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $this->createEntitlement($previousPeriod, $type, 10);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(10, $calculation->getPreviousPeriodProposedEntitlement());
  }

  public function testNumberOfDaysTakenOnThePreviousPeriodShouldBeZeroIfThereIsNoPreviousPeriod() {
    $type = new AbsenceType();
    $period = new AbsencePeriod();

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertEquals(0, $calculation->getNumberOfDaysTakenOnThePreviousPeriod());
  }

  public function testNumberOfDaysTakenOnThePreviousPeriodShouldBeZeroIfThereIsNoPeriodEntitlementForThePreviousPeriod() {
    $type = AbsenceTypeFabricator::fabricate();

    AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getNumberOfDaysTakenOnThePreviousPeriod());
  }

  public function testNumberOfDaysTakenOnThePreviousPeriodShouldBeZeroIfThereAreNoLeaveRequestsOnThePeriod() {
    $type = AbsenceTypeFabricator::fabricate();

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $this->createEntitlement($previousPeriod, $type, 10);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getNumberOfDaysTakenOnThePreviousPeriod());
  }

  public function testNumberOfDaysTakenOnThePreviousPeriodShouldBeTheTotalAmountOfDaysFromAllApprovedLeaveRequestsOnThePeriod() {
    $leaveRequestStatuses = array_flip(LeaveRequest::buildOptions('status_id'));

    $this->setContractDates(date('YmdHis', strtotime('2015-01-01')), null);

    $type = AbsenceTypeFabricator::fabricate();
    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $previousPeriodEntitlement = $this->createEntitlement($previousPeriod, $type, 20);

    $previousPeriodStartDateTimeStamp = strtotime($previousPeriod->start_date);
    // Add a 1 day Leave Request to the previous period
    $this->createLeaveRequestBalanceChange(
      $previousPeriodEntitlement->type_id,
      $previousPeriodEntitlement->contact_id,
      $leaveRequestStatuses['Approved'],
      date('Y-m-d', strtotime('+1 day', $previousPeriodStartDateTimeStamp))
    );

    // Add a 11 days Leave Request to the previous period
    $this->createLeaveRequestBalanceChange(
      $previousPeriodEntitlement->type_id,
      $previousPeriodEntitlement->contact_id,
      $leaveRequestStatuses['Approved'],
      date('Y-m-d', strtotime('+31 days', $previousPeriodStartDateTimeStamp)),
      date('Y-m-d', strtotime('+41 days', $previousPeriodStartDateTimeStamp))
    );

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(12, $calculation->getNumberOfDaysTakenOnThePreviousPeriod());
  }

  public function testNumberOfDaysRemainingInThePreviousPeriodShouldBeZeroIfThereIsNoPreviousPeriod()
  {
    $type = AbsenceTypeFabricator::fabricate();
    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    $this->assertEquals(0, $calculation->getNumberOfDaysRemainingInThePreviousPeriod());
  }

  public function testNumberOfDaysRemainingInThePreviousPeriodShouldBeEqualsToProposedEntitlementMinusNumberOfDaysTaken() {
    $leaveRequestStatuses = array_flip(LeaveRequest::buildOptions('status_id'));

    $this->setContractDates(date('YmdHis', strtotime('2015-01-01')), null);

    $type = AbsenceTypeFabricator::fabricate();

    $previousPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2015-01-01')),
      'end_date' => date('YmdHis', strtotime('2015-12-31')),
    ]);

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis', strtotime('2016-01-01')),
      'end_date' => date('YmdHis', strtotime('2016-12-31')),
    ], true);

    $this->createEntitlement($previousPeriod, $type, 10);

    // Add a 5 days taken as Leave Request
    $this->createLeaveRequestBalanceChange(
      $type->id,
      $this->contact['id'],
      $leaveRequestStatuses['Approved'],
      '2015-06-15',
      '2015-06-19'
    );

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);
    // 10 days from the entitlement - 5 days taken as leave
    $this->assertEquals(5, $calculation->getNumberOfDaysRemainingInThePreviousPeriod());
  }

  public function testGetAbsencePeriodShouldReturnTheAbsencePeriodUsedToCreateTheCalculation() {
    $type = new AbsenceType();
    $period = new AbsencePeriod();
    $period->title = 'Period 1';
    $period->start_date = date('Y-m-d');

    $calculation = new EntitlementCalculation($period, [], $type);
    $this->assertEquals($period, $calculation->getAbsencePeriod());
  }

  public function testGetAbsenceTypeShouldReturnTheAbsenceTypeUsedToCreateTheCalculation()
  {
    $type = new AbsenceType();
    $type->title = 'Absence Type 1';

    $period = new AbsencePeriod();

    $calculation = new EntitlementCalculation($period, [], $type);
    $this->assertEquals($type, $calculation->getAbsenceType());
  }

  public function testGetContactShouldReturnTheContactUsedToCreateTheCalculation()
  {
    $type = new AbsenceType();
    $period = new AbsencePeriod();

    $contact = $this->contact;

    $calculation = new EntitlementCalculation($period, $contact, $type);
    $this->assertEquals($contact, $calculation->getContact());
  }

  public function testCalculationCanUseTheAbsencePeriodToCalculateTheBroughtForwardExpirationDate() {
    $absenceType = new AbsenceType();

    // The getBroughtForwardExpirationDate just relays the work to
    // the AbsencePeriod::getExpirationDateForAbsenceType method.
    // So, since all the logic is on AbsencePeriod, all that's left
    // to be done on the EntitlementCalculation is to test if it
    // calls the AbsencePeriod method with the right argument and returns
    // it's value. For this, a mock or more than enough
    $mockExpirationDate = '2016-01-01';
    $absencePeriod = $this->getMockBuilder(AbsencePeriod::class)
                        ->setMethods([
                          'getExpirationDateForAbsenceType',
                        ])
                        ->getMock();

    $absencePeriod->expects($this->once())
                ->method('getExpirationDateForAbsenceType')
                ->with($this->identicalTo($absenceType))
                ->will($this->returnValue($mockExpirationDate));

    $calculation = new EntitlementCalculation($absencePeriod, [], $absenceType);

    $this->assertEquals($mockExpirationDate, $calculation->getBroughtForwardExpirationDate());
  }

  public function testGetPublicHolidaysShouldReturnAListOfPublicHolidaysAddedToTheEntitlement() {
    $type = AbsenceTypeFabricator::fabricate();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+50 days')),
    ], true);

    $this->setContractDates(
      date('YmdHis', strtotime('-5 days')),
      date('YmdHis', strtotime('+30 days'))
    );

    $leaveAmount = 10;
    $addPublicHolidays = true;
    $this->createJobLeaveEntitlement($type, $leaveAmount, $addPublicHolidays);

    $publicHoliday1 = PublicHolidayFabricator::fabricate([
      'title' => 'Holiday 1',
      'date' => date('YmdHis', strtotime('+1 day'))
    ]);
    $publicHoliday2 = PublicHolidayFabricator::fabricate([
      'title' => 'Holiday 2',
      'date' => date('YmdHis', strtotime('+3 days'))
    ]);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    $publicHolidays = $calculation->getPublicHolidaysInEntitlement();
    $this->assertCount(2, $publicHolidays);
    $this->assertEquals($publicHoliday1->title, $publicHolidays[0]->title);
    $this->assertEquals($publicHoliday2->title, $publicHolidays[1]->title);
  }

  public function testGetPublicHolidaysShouldOnlyReturnPublicHolidaysWithDatesBetweenTheContractDatesAndAbsencePeriod() {
    $type = AbsenceTypeFabricator::fabricate();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+50 days')),
    ], true);

    // The contract starts 5 days prior to the AbsencePeriod and
    // ends before the AbsencePeriod
    $this->setContractDates(
      date('YmdHis', strtotime('-5 days')),
      date('YmdHis', strtotime('+30 days'))
    );

    $leaveAmount = 10;
    $addPublicHolidays = true;
    $this->createJobLeaveEntitlement($type, $leaveAmount, $addPublicHolidays);

    // This is between both the AbsencePeriod and the Contract dates,
    // so it should be returned
    $publicHoliday1 = PublicHolidayFabricator::fabricate([
      'title' => 'Holiday 1',
      'date' => date('YmdHis', strtotime('+1 day'))
    ]);

    // This is between the contract dates but prior to the AbsencePeriod
    // start_date, so it shouldn't be returned
    PublicHolidayFabricator::fabricate([
      'date' => date('YmdHis', strtotime('-3 days'))
    ]);

    // This is between the AbsencePeriod dates but after the contract end date,
    // so it shouldn't be returned
    PublicHolidayFabricator::fabricate([
      'date' => date('YmdHis', strtotime('+31 days'))
    ]);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    $publicHolidays = $calculation->getPublicHolidaysInEntitlement();
    $this->assertCount(1, $publicHolidays);
    $this->assertEquals($publicHoliday1->title, $publicHolidays[0]->title);
  }

  public function testGetPublicHolidaysShouldReturnEmptyIfTheContractHasNoJobLeaveInformation() {
    $type = AbsenceTypeFabricator::fabricate();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+50 days')),
    ], true);

    $this->setContractDates(
      date('YmdHis'),
      date('YmdHis', strtotime('+30 days'))
    );

    PublicHolidayFabricator::fabricate([
      'date' => date('YmdHis', strtotime('+1 day'))
    ]);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    $publicHolidays = $calculation->getPublicHolidaysInEntitlement();
    $this->assertEmpty($publicHolidays);
  }

  public function testGetPublicHolidaysShouldReturnEmptyIfJobLeaveDoesNotAllowPublicHolidaysToBeAdded() {
    $type = AbsenceTypeFabricator::fabricate();

    $currentPeriod = AbsencePeriodFabricator::fabricate([
      'start_date' => date('YmdHis'),
      'end_date' => date('YmdHis', strtotime('+50 days')),
    ], true);

    $leaveAmount = 10;
    $addPublicHolidays = false;
    $this->createJobLeaveEntitlement($type, $leaveAmount, $addPublicHolidays);

    $this->setContractDates(
      date('YmdHis'),
      date('YmdHis', strtotime('+30 days'))
    );

    PublicHolidayFabricator::fabricate([
      'date' => date('YmdHis', strtotime('+1 day'))
    ]);

    $calculation = new EntitlementCalculation($currentPeriod, $this->contact, $type);

    $publicHolidays = $calculation->getPublicHolidaysInEntitlement();
    $this->assertEmpty($publicHolidays);
  }

  public function testIsCurrentPeriodEntitlementOverriddenShouldBeFalseIfThereIsNoPreviouslyCalculatedEntitlement() {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertFalse($calculation->isCurrentPeriodEntitlementOverridden());
  }

  public function testIsCurrentPeriodEntitlementOverriddenShouldBeFalseIfThePreviouslyCalculatedEntitlementIsNotOverridden()
  {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();
    $this->createEntitlement($period, $type, 10);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertFalse($calculation->isCurrentPeriodEntitlementOverridden());
  }

  public function testIsCurrentPeriodEntitlementOverriddenShouldBeTrueIfThePreviouslyCalculatedEntitlementIsOverridden() {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();
    $this->createEntitlement($period, $type, 10, 20);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertTrue($calculation->isCurrentPeriodEntitlementOverridden());
  }

  public function testGetCurrentPeriodEntitlementCommentReturnsAnEmptyStringIfThereIsNoPreviouslyCalculatedEntitlement() {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertEmpty($calculation->getCurrentPeriodEntitlementComment());
  }

  public function testGetCurrentPeriodEntitlementCommentReturnsAnEmptyStringIfThereThePreviouslyCalculatedEntitlementHasNoComment() {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();
    $this->createEntitlement($period, $type, 10);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertEmpty($calculation->getCurrentPeriodEntitlementComment());
  }

  public function testGetCurrentPeriodEntitlementCommentReturnsTheCommentIfThereThePreviouslyCalculatedEntitlementHasOne() {
    $type = AbsenceTypeFabricator::fabricate();
    $period = AbsencePeriodFabricator::fabricate();
    $comment = 'Lorem ipsum...';
    $this->createEntitlement($period, $type, 10, false, $comment);

    $calculation = new EntitlementCalculation($period, $this->contact, $type);
    $this->assertEquals($comment, $calculation->getCurrentPeriodEntitlementComment());
  }

  private function createEntitlement($period, $type, $numberOfDays = 20, $overridden = null, $comment = null) {
    $params = [
      'period_id'            => $period->id,
      'contact_id'           => $this->contact['id'],
      'type_id'              => $type->id,
      'overridden'           => $overridden ? '1' : '0',
    ];

    if($comment) {
      $params['comment'] = $comment;
      $params['comment_author_id'] = $this->contact['id'];
      $params['comment_date'] = date('YmdHis');
    }

    $periodEntitlement = LeavePeriodEntitlement::create($params);

    $this->createLeaveBalanceChange($periodEntitlement->id, $numberOfDays);

    if($overridden) {
      $this->createOverriddenBalanceChange($periodEntitlement->id, $overridden - $numberOfDays);
    }

    return $periodEntitlement;
  }

  private function createJobLeaveEntitlement($type, $leaveAmount, $addPublicHolidays = false) {
    CRM_Hrjobcontract_BAO_HRJobLeave::create([
      'jobcontract_id' => $this->contract['id'],
      'leave_type' => $type->id,
      'leave_amount' => $leaveAmount,
      'add_public_holidays' => $addPublicHolidays ? '1' : '0'
    ]);
  }

  private function createContractWithDetailsAndLeaveEntitlement(
    $contactID,
    $startDate,
    $endDate,
    $typeID,
    $leaveAmount,
    $addPublicHolidays
  ) {
    $result = civicrm_api3('HRJobContract', 'create', [
      'contact_id' => $contactID,
      'sequential' => 1
    ]);
    $contract = $result['values'][0];

    civicrm_api3('HRJobDetails', 'create', [
      'jobcontract_id' => $contract['id'],
      'period_start_date' => $startDate,
      'period_end_date' => $endDate
    ]);

    civicrm_api3('HRJobLeave', 'create', [
      'jobcontract_id' => $contract['id'],
      'leave_type' => $typeID,
      'leave_amount' => $leaveAmount,
      'add_public_holidays' => $addPublicHolidays
    ]);

    return $contract;
  }


}
