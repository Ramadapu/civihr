<?php

use CRM_HRLeaveAndAbsences_Service_LeaveManager as LeaveManagerService;
use CRM_HRLeaveAndAbsences_Service_LeaveRequestStatusMatrix as LeaveRequestStatusMatrixService;
use CRM_HRLeaveAndAbsences_BAO_LeaveRequest as LeaveRequest;
use CRM_HRCore_Test_Fabricator_Contact as ContactFabricator;

/**
 * Class CRM_HRLeaveAndAbsences_Service_LeaveRequestStatusMatrixTest
 *
 * @group headless
 */
class CRM_HRLeaveAndAbsences_Service_LeaveRequestStatusMatrixTest extends BaseHeadlessTest {

  use CRM_HRLeaveAndAbsences_LeaveRequestHelpersTrait;
  use CRM_HRLeaveAndAbsences_SessionHelpersTrait;
  use CRM_HRLeaveAndAbsences_LeaveManagerHelpersTrait;

  /**
   * @var \CRM_HRLeaveAndAbsences_Service_LeaveRequestStatusMatrix
   */
  private $leaveRequestStatusMatrix;

  private $contactID;

  public function setUp() {
    $leaveManagerService = new  LeaveManagerService();
    $this->leaveRequestStatusMatrix = new LeaveRequestStatusMatrixService($leaveManagerService);

    $this->contactID = 1;
    $this->registerCurrentLoggedInContactInSession($this->contactID);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
  }

  /**
   * @dataProvider allPossibleStatusTransitionForStaffDataProvider
   */
  public function testCanTransitionToForStaffReturnsTrueForAllPossibleTransitionStatuses($fromStatus, $toStatus) {
    $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo($fromStatus, $toStatus, $this->contactID));
  }

  /**
   * @dataProvider allNonPossibleStatusTransitionForStaffDataProvider
   */
  public function testCanTransitionToForStaffReturnsFalseForAllNonPossibleTransitionStatuses($fromStatus, $toStatus) {
    $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo($fromStatus, $toStatus, $this->contactID));
  }

  public function testCanTransitionToForLeaveApproverReturnsTrueForAllPossibleTransitionStatuses() {
    $manager = ContactFabricator::fabricate();
    $leaveContact = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $leaveContact);

    $possibleTransitions = $this->allPossibleStatusTransitionForLeaveApprover();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $leaveContact['id']));
    }
  }

  public function testCanTransitionToForLeaveApproverReturnsFalseForAllNonPossibleTransitionStatuses() {
    $manager = ContactFabricator::fabricate();
    $leaveContact = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $leaveContact);

    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForLeaveApprover();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $leaveContact['id']));
    }
  }

  public function testCanTransitionToForAdminForReturnsTrueAllPossibleTransitionStatuses() {
    $adminID = 5;
    $leaveContact = 2;
    $this->registerCurrentLoggedInContactInSession($adminID);
    $this->setPermissions(['administer leave and absences']);

    $possibleTransitions = $this->allPossibleStatusTransitionForLeaveApprover();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $leaveContact));
    }
  }

  public function testCanTransitionToForAdminReturnsFalseForAllNonPossibleTransitionStatuses() {
    $adminID = 5;
    $leaveContact = 2;
    $this->registerCurrentLoggedInContactInSession($adminID);
    $this->setPermissions(['administer leave and absences']);

    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForLeaveApprover();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $leaveContact));
    }
  }

  public function testCanTransitionToReturnsTrueForAllPossibleStaffTransitionStatusesWhenLeaveApproverIsTheLeaveContact() {
    $manager = ContactFabricator::fabricate();
    $leaveContact = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $leaveContact);

    $possibleTransitions = $this->allPossibleStatusTransitionForStaffDataProvider();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $manager['id']));
    }
  }

  public function testCanTransitionToReturnsFalseForAllNonPossibleStaffTransitionStatusesWhenLeaveApproverIsTheLeaveContact() {
    $manager = ContactFabricator::fabricate();
    $leaveContact = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $leaveContact);

    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForStaffDataProvider();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $manager['id']));
    }
  }

  public function testCanTransitionToReturnsFalseForPossibleManagerExclusiveStatusTransitionsWhenLeaveApproverIsTheLeaveContact() {
    $manager = ContactFabricator::fabricate();
    $leaveContact = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $leaveContact);

    $managerExclusivePossibleStatusTransition = $this->getManagerExclusivePossibleStatusTransitionsDataProvider();
    foreach($managerExclusivePossibleStatusTransition as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo($transition[0], $transition[1], $manager['id']));
    }
  }

  public function testCanTransitionToReturnsFalseWhenAdminIsTheLeaveContactAndOwnLeaveApproverForAllNonPossibleManagerTransitionStatuses() {
    $admin = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($admin['id']);
    $this->setPermissions(['administer leave and absences']);
    $this->setContactAsLeaveApproverOf($admin, $admin);
    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForLeaveApprover();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $admin['id']
      ));
    }
  }

  public function testCanTransitionToReturnsTrueWhenAdminIsTheLeaveContactAndOwnLeaveApproverForAllPossibleManagerTransitionStatuses() {
    $admin = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($admin['id']);
    $this->setPermissions(['administer leave and absences']);
    $this->setContactAsLeaveApproverOf($admin, $admin);
    $possibleTransitions = $this->allPossibleStatusTransitionForLeaveApprover();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $admin['id']
      ));
    }
  }

  public function testCanTransitionToReturnsTrueForAllPossibleStaffTransitionStatusesWhenAdminIsTheLeaveContactAndNotOwnApprover() {
    $adminID = 5;
    $this->registerCurrentLoggedInContactInSession($adminID);
    $this->setPermissions(['administer leave and absences']);
    $possibleTransitions = $this->allPossibleStatusTransitionForStaffDataProvider();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $adminID
      ));
    }
  }

  public function testCanTransitionToReturnsFalseForAllNonPossibleStaffTransitionStatusesWhenAdminIsTheLeaveContactAndNotOwnApprover() {
    $adminID = 5;
    $this->registerCurrentLoggedInContactInSession($adminID);
    $this->setPermissions(['administer leave and absences']);
    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForStaffDataProvider();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $adminID
      ));
    }
  }
  public function testCanTransitionToReturnsFalseWhenUserIsTheLeaveContactAndOwnApproverForAllNonPossibleManagerTransitionStatuses() {
    $manager = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $manager);

    $nonPossibleTransitions = $this->allNonPossibleStatusTransitionForLeaveApprover();

    foreach($nonPossibleTransitions as $transition) {
      $this->assertFalse($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $manager['id']
      ));
    }
  }

  public function testCanTransitionToReturnsTrueWhenUserIsTheLeaveContactAndOwnApproverForAllPossibleManagerTransitionStatuses() {
    $manager = ContactFabricator::fabricate();
    $this->registerCurrentLoggedInContactInSession($manager['id']);
    $this->setContactAsLeaveApproverOf($manager, $manager);

    $possibleTransitions = $this->allPossibleStatusTransitionForLeaveApprover();

    foreach($possibleTransitions as $transition) {
      $this->assertTrue($this->leaveRequestStatusMatrix->canTransitionTo(
        $transition[0],
        $transition[1],
        $manager['id']
      ));
    }
  }

  public function allPossibleStatusTransitionForStaffDataProvider() {
    $leaveRequestStatuses = $this->getLeaveRequestStatuses();

    return [
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['cancelled']],
      ['', $leaveRequestStatuses['awaiting_approval']],
    ];
  }

  public function allNonPossibleStatusTransitionForStaffDataProvider() {
    $leaveRequestStatuses = $this->getLeaveRequestStatuses();

    return [
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['cancelled']],
      ['', $leaveRequestStatuses['more_information_required']],
      ['', $leaveRequestStatuses['rejected']],
      ['', $leaveRequestStatuses['approved']],
      ['', $leaveRequestStatuses['cancelled']],
    ];
  }

  public function allPossibleStatusTransitionForLeaveApprover() {
    $leaveRequestStatuses = $this->getLeaveRequestStatuses();

    return [
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['cancelled']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['more_information_required']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['rejected']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['approved']],
      [$leaveRequestStatuses['cancelled'], $leaveRequestStatuses['cancelled']],
      ['', $leaveRequestStatuses['more_information_required']],
      ['', $leaveRequestStatuses['approved']],
    ];
  }

  public function allNonPossibleStatusTransitionForLeaveApprover() {
    $leaveRequestStatuses = $this->getLeaveRequestStatuses();

    return [
      [$leaveRequestStatuses['awaiting_approval'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['more_information_required'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['rejected'], $leaveRequestStatuses['awaiting_approval']],
      [$leaveRequestStatuses['approved'], $leaveRequestStatuses['awaiting_approval']],
      ['', $leaveRequestStatuses['awaiting_approval']],
      ['', $leaveRequestStatuses['rejected']],
      ['', $leaveRequestStatuses['cancelled']],
    ];
  }

  /**
   * Return possible status transitions that is only exclusive to the Manager or Admin
   *
   * @return array
   */
  public function getManagerExclusivePossibleStatusTransitionsDataProvider() {
    $possibleStaffTransitions = $this->allPossibleStatusTransitionForStaffDataProvider();
    $possibleManagerTransitions = $this->allPossibleStatusTransitionForLeaveApprover();

    $results = array_diff(array_map('serialize', $possibleManagerTransitions), array_map('serialize', $possibleStaffTransitions));
    $managerExclusiveStatusTransition = array_map('unserialize', $results);

    return $managerExclusiveStatusTransition;
  }
}
