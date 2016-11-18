<?php

use CRM_HRLeaveAndAbsences_BAO_AbsenceType as AbsenceType;
use CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException as InvalidAbsenceTypeException;
use CRM_HRLeaveAndAbsences_Test_Fabricator_AbsenceType as AbsenceTypeFabricator;

/**
 * Class CRM_HRLeaveAndAbsences_BAO_AbsenceTypeTest
 *
 * @group headless
 */
class CRM_HRLeaveAndAbsences_BAO_AbsenceTypeTest extends BaseHeadlessTest {

  private $allColors = [
      '#5A6779', '#E5807F', '#ECA67F', '#8EC68A', '#C096AA', '#9579A8', '#42B0CB',
      '#3D4A5E', '#E56A6A', '#FA8F55', '#6DAD68', '#B37995', '#84619C', '#2997B3',
      '#263345', '#CC4A49', '#D97038', '#4F944A', '#995978', '#5F3D76', '#147E99',
      '#151D2C', '#B32E2E', '#BF561D', '#377A31', '#803D5E', '#47275C', '#056780'
  ];

  public function setUp() {
    // We delete everything two avoid problems with the default absence types
    // created during the extension installation
    $tableName = AbsenceType::getTableName();
    CRM_Core_DAO::executeQuery("DELETE FROM {$tableName}");
  }

  /**
   * @expectedException PEAR_Exception
   * @expectedExceptionMessage DB Error: already exists
   */
  public function testTypeTitlesShouldBeUnique() {
    AbsenceTypeFabricator::fabricate(['title' => 'Type 1']);
    AbsenceTypeFabricator::fabricate(['title' => 'Type 1']);
  }

  public function testThereShouldBeOnlyOneDefaultTypeOnCreate() {
    $basicEntity = AbsenceTypeFabricator::fabricate(['is_default' => true]);
    $entity1 = AbsenceType::findById($basicEntity->id);
    $this->assertEquals(1, $entity1->is_default);

    $basicEntity = AbsenceTypeFabricator::fabricate(['is_default' => true]);
    $entity2 = AbsenceType::findById($basicEntity->id);
    $entity1 = AbsenceType::findById($entity1->id);
    $this->assertEquals(0,  $entity1->is_default);
    $this->assertEquals(1, $entity2->is_default);
  }

  public function testThereShouldBeOnlyOneDefaultTypeOnUpdate() {
    $basicEntity1 = AbsenceTypeFabricator::fabricate(['is_default' => false]);
    $basicEntity2 = AbsenceTypeFabricator::fabricate(['is_default' => false]);
    $entity1 = AbsenceType::findById($basicEntity1->id);
    $entity2 = AbsenceType::findById($basicEntity2->id);
    $this->assertEquals(0,  $entity1->is_default);
    $this->assertEquals(0,  $entity2->is_default);

    $this->updateBasicType($basicEntity1->id, ['is_default' => true]);
    $entity1 = AbsenceType::findById($basicEntity1->id);
    $entity2 = AbsenceType::findById($basicEntity2->id);
    $this->assertEquals(1, $entity1->is_default);
    $this->assertEquals(0,  $entity2->is_default);

    $this->updateBasicType($basicEntity2->id, ['is_default' => true]);
    $entity1 = AbsenceType::findById($basicEntity1->id);
    $entity2 = AbsenceType::findById($basicEntity2->id);
    $this->assertEquals(0,  $entity1->is_default);
    $this->assertEquals(1, $entity2->is_default);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage There is already one Absence Type where public holidays should be added to it
   */
  public function testThereShouldBeOnlyOneTypeWithAddPublicHolidayToEntitlementOnCreate() {
    $basicEntity = AbsenceTypeFabricator::fabricate(['add_public_holiday_to_entitlement' => true]);
    $entity1 = AbsenceType::findById($basicEntity->id);
    $this->assertEquals(1, $entity1->add_public_holiday_to_entitlement);

    AbsenceTypeFabricator::fabricate(['add_public_holiday_to_entitlement' => true]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage There is already one Absence Type where public holidays should be added to it
   */
  public function testThereShouldBeOnlyOneTypeWithAddPublicHolidayToEntitlementOnUpdate() {
    $basicEntity1 = AbsenceTypeFabricator::fabricate(['add_public_holiday_to_entitlement' => true]);
    $basicEntity2 = AbsenceTypeFabricator::fabricate();
    $entity1 = AbsenceType::findById($basicEntity1->id);
    $entity2 = AbsenceType::findById($basicEntity2->id);
    $this->assertEquals(1, $entity1->add_public_holiday_to_entitlement);
    $this->assertEquals(0, $entity2->add_public_holiday_to_entitlement);

    $this->updateBasicType($basicEntity2->id, ['add_public_holiday_to_entitlement' => true]);
  }

  public function testUpdatingATypeWithAddPublicHolidayToEntitlementShouldNotTriggerErrorAboutHavingAnotherTypeWithItSelected() {
    $basicEntity = AbsenceTypeFabricator::fabricate(['add_public_holiday_to_entitlement' => true]);
    $entity1 = AbsenceType::findById($basicEntity->id);
    $this->assertEquals(1, $entity1->add_public_holiday_to_entitlement);

    $this->updateBasicType($entity1->id, ['add_public_holiday_to_entitlement' => true]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage There is already one Absence Type where "Must staff take public holiday as leave" is selected
   */
  public function testThereShouldBeOnlyOneTypeWithMustTakePublicHolidayAsLeaveOnCreate() {
    $basicEntity = AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true]);
    $entity1 = AbsenceType::findById($basicEntity->id);
    $this->assertEquals(1, $entity1->must_take_public_holiday_as_leave);

    AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage There is already one Absence Type where "Must staff take public holiday as leave" is selected
   */
  public function testThereShouldBeOnlyOneTypeWithMustTakePublicHolidayAsLeaveOnUpdate() {
    $basicEntity1 = AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true]);
    $basicEntity2 = AbsenceTypeFabricator::fabricate();
    $entity1 = AbsenceType::findById($basicEntity1->id);
    $entity2 = AbsenceType::findById($basicEntity2->id);
    $this->assertEquals(1, $entity1->must_take_public_holiday_as_leave);
    $this->assertEquals(0, $entity2->must_take_public_holiday_as_leave);

    $this->updateBasicType($basicEntity2->id, ['must_take_public_holiday_as_leave' => true]);
  }

  public function testUpdatingATypeWithMustTakePublicHolidayAsLeaveShouldNotTriggerErrorAboutHavingAnotherTypeWithItSelected() {
    $basicEntity = AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true]);
    $entity1 = AbsenceType::findById($basicEntity->id);
    $this->assertEquals(1, $entity1->must_take_public_holiday_as_leave);

    $this->updateBasicType($entity1->id, ['must_take_public_holiday_as_leave' => true]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage To set maximum amount of leave that can be accrued you must allow staff to accrue additional days
   */
  public function testAllowAccrualsRequestShouldBeTrueIfMaxLeaveAccrualIsNotEmpty() {
    AbsenceTypeFabricator::fabricate([
        'allow_accruals_request' => false,
        'max_leave_accrual' => 1
    ]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage To allow accrue in the past you must allow staff to accrue additional days
   */
  public function testAllowAccrualsRequestShouldBeTrueIfAllowAccrueInThePast() {
    AbsenceTypeFabricator::fabricate([
        'allow_accruals_request'   => false,
        'allow_accrue_in_the_past' => 1
    ]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage To set the accrual expiry duration you must allow staff to accrue additional days
   */
  public function testAllowAccrualsRequestShouldBeTrueIfAllowAccrualDurationAndUnitAreNotEmpty() {
    AbsenceTypeFabricator::fabricate([
        'allow_accruals_request' => false,
        'accrual_expiration_duration' => 1,
        'accrual_expiration_unit' => AbsenceType::EXPIRATION_UNIT_DAYS
    ]);
  }

  /**
   * @dataProvider expirationUnitDataProvider
   */
  public function testShouldNotAllowInvalidAccrualExpirationUnit($expirationUnit, $throwsException) {
    if($throwsException) {
      $this->setExpectedException(
          InvalidAbsenceTypeException::class,
          'Invalid Accrual Expiration Unit'
      );
    }

    AbsenceTypeFabricator::fabricate([
        'allow_accruals_request' => true,
        'accrual_expiration_duration' => 1,
        'accrual_expiration_unit' => $expirationUnit
    ]);
  }

  /**
   * @dataProvider accrualExpirationUnitAndDurationDataProvider
   */
  public function testShouldNotAllowAccrualExpirationUnitWithoutDurationAndViceVersa($unit, $duration, $throwsException) {
    if($throwsException) {
      $this->setExpectedException(
          InvalidAbsenceTypeException::class,
          'Invalid Accrual Expiration. It should have both Unit and Duration'
      );
    }

    AbsenceTypeFabricator::fabricate([
        'allow_accruals_request' => true,
        'accrual_expiration_unit' => $unit,
        'accrual_expiration_duration' => $duration,
    ]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage To set the Max Number of Days to Carry Forward you must allow Carry Forward
   */
  public function testAllowCarryForwardShouldBeTrueIfMaxNumberOfDaysToCarryForwardIsNotEmpty() {
    AbsenceTypeFabricator::fabricate([
        'allow_carry_forward'   => false,
        'max_number_of_days_to_carry_forward' => 1
    ]);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_InvalidAbsenceTypeException
   * @expectedExceptionMessage To set the carry forward expiry duration you must allow Carry Forward
   */
  public function testAllowCarryForwardShouldBeTrueIfCarryForwardExpirationDurationAndUnitAreNotEmpty() {
    AbsenceTypeFabricator::fabricate([
        'allow_carry_forward' => false,
        'carry_forward_expiration_duration' => 1,
        'carry_forward_expiration_unit' => AbsenceType::EXPIRATION_UNIT_DAYS
    ]);
  }

  /**
   * @dataProvider accrualExpirationUnitAndDurationDataProvider
   */
  public function testShouldNotAllowCarryForwardExpirationUnitWithoutDurationAndViceVersa($unit, $duration, $throwsException) {
    if($throwsException) {
      $this->setExpectedException(
          InvalidAbsenceTypeException::class,
          'Invalid Carry Forward Expiration. It should have both Unit and Duration'
      );
    }

    AbsenceTypeFabricator::fabricate([
        'allow_carry_forward' => true,
        'carry_forward_expiration_unit' => $unit,
        'carry_forward_expiration_duration' => $duration,
    ]);
  }

  /**
   * @dataProvider expirationUnitDataProvider
   */
  public function testShouldNotAllowInvalidCarryForwardExpirationUnit($expirationUnit, $throwsException) {
    if($throwsException) {
      $this->setExpectedException(
          InvalidAbsenceTypeException::class,
          'Invalid Carry Forward Expiration Unit'
      );
    }

    AbsenceTypeFabricator::fabricate([
        'allow_carry_forward' => true,
        'carry_forward_expiration_duration' => 1,
        'carry_forward_expiration_unit' => $expirationUnit
    ]);
  }

  /**
   * @dataProvider allowRequestCancelationDataProvider
   */
  public function testShouldNotAllowInvalidRequestCancelationOptions($requestCancelationOption, $throwsException) {
    if($throwsException) {
      $this->setExpectedException(
          InvalidAbsenceTypeException::class,
          'Invalid Request Cancelation Option'
      );
    }

    AbsenceTypeFabricator::fabricate(['allow_request_cancelation' => $requestCancelationOption]);
  }

  public function testWeightShouldAlwaysBeMaxWeightPlus1OnCreate() {
    $firstEntity = AbsenceTypeFabricator::fabricate();
    $this->assertNotEmpty($firstEntity->weight);

    $secondEntity = AbsenceTypeFabricator::fabricate();
    $this->assertNotEmpty($secondEntity->weight);
    $this->assertEquals($firstEntity->weight + 1, $secondEntity->weight);
  }

  public function testShouldHaveAllTheColorsAvailableIfTheresNotTypeCreated() {
    $availableColors = AbsenceType::getAvailableColors();
    foreach($this->allColors as $color) {
      $this->assertContains($color, $availableColors);
    }
  }

  public function testShouldNotAllowColorToBeReusedUntilAllColorsHaveBeenUsed() {
    $usedColors = [];
    $numberOfColors = count($this->allColors);
    for($i = 0; $i < $numberOfColors; $i++) {
      $color = $this->allColors[$i];
      AbsenceTypeFabricator::fabricate(['color' => $color]);
      $usedColors[] = $color;
      $availableColors = AbsenceType::getAvailableColors();

      $isLastColor = ($i == $numberOfColors - 1);
      foreach($usedColors as $usedColor) {
        if($isLastColor) {
          $this->assertContains($usedColor, $availableColors);
        } else {
          $this->assertNotContains($usedColor, $availableColors);
        }
      }
    }
  }

  public function testIsReservedCannotBeSetOnCreate() {
    $entity = AbsenceTypeFabricator::fabricate(['is_reserved' => 1]);
    $this->assertEquals(0, $entity->is_reserved);
  }

  public function testIsReservedCannotBeSetOnUpdate() {
    $entity = AbsenceTypeFabricator::fabricate();
    $this->assertEquals(0, $entity->is_reserved);
    $entity = $this->updateBasicType($entity->id, ['is_reserved' => 1]);
    $this->assertEquals(0, $entity->is_reserved);
  }

  /**
   * @expectedException CRM_HRLeaveAndAbsences_Exception_OperationNotAllowedException
   * @expectedExceptionMessage Reserved types cannot be deleted!
   */
  public function testShouldNotBeAllowedToDeleteReservedTypes() {
    $id = $this->createReservedType();
    $this->assertNotNull($id);
    AbsenceType::del($id);
  }

  public function testShouldBeAllowedToDeleteNonReservedTypes() {
    $entity = AbsenceTypeFabricator::fabricate();
    $this->assertNotNull($entity->id);
    AbsenceType::del($entity->id);

    $this->setExpectedException(
      Exception::class,
      "Unable to find a CRM_HRLeaveAndAbsences_BAO_AbsenceType with id {$entity->id}"
    );
    AbsenceType::findById($entity->id);
  }

  public function testGetValuesArrayShouldReturnAbsenceTypeValues() {
    $params = [
      'title'                               => 'Title 1',
      'color'                               => '#000101',
      'default_entitlement'                 => 21,
      'allow_request_cancelation'           => 1,
      'is_active'                           => 1,
      'is_default'                          => 1,
      'allow_carry_forward'                 => 1,
      'max_number_of_days_to_carry_forward' => 10,
    ];
    $entity = AbsenceTypeFabricator::fabricate($params);
    $values = AbsenceType::getValuesArray($entity->id);
    foreach ($params as $field => $value) {
      $this->assertEquals($value, $values[$field]);
    }
  }

  public function testHasExpirationDuration() {
    $absenceType1 = AbsenceTypeFabricator::fabricate([
      'allow_carry_forward' => true
    ]);

    $absenceType2 = AbsenceTypeFabricator::fabricate([
      'allow_carry_forward' => true,
      'carry_forward_expiration_duration' => 3,
      'carry_forward_expiration_unit' => AbsenceType::EXPIRATION_UNIT_DAYS,
    ]);

    $this->assertFalse($absenceType1->hasExpirationDuration());
    $this->assertTrue($absenceType2->hasExpirationDuration());
  }

  public function testCarryForwardNeverExpiresShouldReturnTrueIfTypeHasNoExpirationDuration() {
    $absenceType = AbsenceTypeFabricator::fabricate(['allow_carry_forward' => true]);
    $this->assertTrue($absenceType->carryForwardNeverExpires());
  }

  public function testCarryForwardNeverExpiresShouldReturnFalseIfTypeHasExpirationDuration() {
    $absenceType = AbsenceTypeFabricator::fabricate([
      'allow_carry_forward' => true,
      'carry_forward_expiration_duration' => 4,
      'carry_forward_expiration_unit' => AbsenceType::EXPIRATION_UNIT_MONTHS
    ]);
    $this->assertFalse($absenceType->carryForwardNeverExpires());
  }

  public function testCarryForwardNeverExpiresShouldBeNullIfTypeDoesAllowCarryForward() {
    $absenceType = AbsenceTypeFabricator::fabricate(['allow_carry_forward' => false]);
    $this->assertNull($absenceType->carryForwardNeverExpires());
  }

  public function testGetEnabledAbsenceTypesShouldReturnAListOfEnabledAbsenceTypesOrderedByWeight() {
    $absenceType1 = AbsenceTypeFabricator::fabricate();
    $absenceType2 = AbsenceTypeFabricator::fabricate();
    $absenceType3 = AbsenceTypeFabricator::fabricate();

    // Let's change the types order
    $absenceType2 = $this->updateBasicType($absenceType2->id, ['weight' => 1]);
    $absenceType3 = $this->updateBasicType($absenceType3->id, ['weight' => 2]);
    $absenceType1 = $this->updateBasicType($absenceType1->id, ['weight' => 3]);

    $absenceTypes = AbsenceType::getEnabledAbsenceTypes();
    $this->assertCount(3, $absenceTypes);

    $this->assertEquals($absenceType2->id, $absenceTypes[0]->id);
    $this->assertEquals($absenceType2->title, $absenceTypes[0]->title);

    $this->assertEquals($absenceType3->id, $absenceTypes[1]->id);
    $this->assertEquals($absenceType3->title, $absenceTypes[1]->title);

    $this->assertEquals($absenceType1->id, $absenceTypes[2]->id);
    $this->assertEquals($absenceType1->title, $absenceTypes[2]->title);
  }

  public function testGetEnabledAbsenceTypesShouldNotIncludeDisabledTypes() {
    AbsenceTypeFabricator::fabricate(['is_active' => 1]);
    AbsenceTypeFabricator::fabricate(['is_active' => 0]);

    $absenceTypes = AbsenceType::getEnabledAbsenceTypes();
    $this->assertCount(1, $absenceTypes);
  }

  public function testGetOneWithMustTakePublicHolidayAsLeaveRequestShouldReturnTheAbsenceTypeIfItExists() {
    $expectedAbsenceType = AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true, 'is_active' => true]);

    $absenceType = AbsenceType::getOneWithMustTakePublicHolidayAsLeaveRequest();

    $this->assertEquals($expectedAbsenceType->id, $absenceType->id);
  }

  public function testGetOneWithMustTakePublicHolidayAsLeaveRequestShouldReturnADisabledAbsenceType() {
    AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => true, 'is_active' => false]);

    $absenceType = AbsenceType::getOneWithMustTakePublicHolidayAsLeaveRequest();

    $this->assertNull($absenceType);
  }

  public function testGetOneWithMustTakePublicHolidayAsLeaveRequestShouldReturnNullIfThereIsNoSuchAbsenceType() {
    AbsenceTypeFabricator::fabricate(['must_take_public_holiday_as_leave' => false]);

    $absenceType = AbsenceType::getOneWithMustTakePublicHolidayAsLeaveRequest();

    $this->assertNull($absenceType);
  }

  private function updateBasicType($id, $params) {
    $params['id'] = $id;
    return AbsenceTypeFabricator::fabricate($params);
  }

  public function expirationUnitDataProvider() {
    $data = [
        [rand(3, PHP_INT_MAX), true],
        [rand(3, PHP_INT_MAX), true],
    ];
    $validOptions = array_keys(AbsenceType::getExpirationUnitOptions());
    foreach($validOptions as $option) {
      $data[] = [$option, false];
    }
    return $data;
  }

  public function accrualExpirationUnitAndDurationDataProvider() {
    return [
      [AbsenceType::EXPIRATION_UNIT_DAYS, null, true],
      [null, 10, true],
      [AbsenceType::EXPIRATION_UNIT_MONTHS, 5, false],
    ];
  }

  public function allowRequestCancelationDataProvider() {
    $data = [
        [rand(3, PHP_INT_MAX), true],
        [rand(3, PHP_INT_MAX), true],
    ];
    $validOptions = array_keys(AbsenceType::getRequestCancelationOptions());
    foreach($validOptions as $option) {
      $data[] = [$option, false];
    }
    return $data;
  }

  public function carryForwardExpirationDateDataProvider() {
    return [
      [12, 12, false],
      [1, 2, false],
      [31, 1, false],
      [30, 2, true],
      [31, 4, true],
      [77, 9, true],
      [12, 31, true],
    ];
  }

  /**
   * Since we cannot create reserved types through the API,
   * we have this helper method to insert one directly in
   * the database
   */
  private function createReservedType() {
    $title = 'Title ' . microtime();
    $query = "
      INSERT INTO
        civicrm_hrleaveandabsences_absence_type(title, color, default_entitlement, allow_request_cancelation, is_reserved, weight)
        VALUES('{$title}', '#000000', 0, 1, 1, 1)
    ";
    CRM_Core_DAO::executeQuery($query);

    $query = "SELECT id FROM civicrm_hrleaveandabsences_absence_type WHERE title = '{$title}'";
    $dao = CRM_Core_DAO::executeQuery($query);
    if($dao->N == 1) {
      $dao->fetch();
      return $dao->id;
    }

    return null;
  }
}
