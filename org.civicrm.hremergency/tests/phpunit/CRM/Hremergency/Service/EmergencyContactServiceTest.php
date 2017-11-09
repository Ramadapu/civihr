<?php

use Tests\CiviHR\HREmergency\Fabricator\EmergencyContactFabricator;
use CRM_HRCore_Test_Fabricator_Contact as ContactFabricator;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

class EmergencyContactServiceTest extends \PHPUnit_Framework_TestCase
  implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install('uk.co.compucorp.civicrm.hrcore')
      ->installMe(__DIR__)
      ->apply();
  }

  public function testFind() {
    $contact = ContactFabricator::fabricate();
    $name = 'Kevin Bacon';
    $created = EmergencyContactFabricator::fabricate($contact['id'], $name);

    $service = Civi::container()->get('emergency_contact.service');
    $found = $service->find($created['id']);

    $this->assertEquals($name, $found['Name']);
  }

  public function testDelete() {
    $contact = ContactFabricator::fabricate();
    $name = 'Kevin Bacon';
    $created = EmergencyContactFabricator::fabricate($contact['id'], $name);

    $service = Civi::container()->get('emergency_contact.service');
    $service->delete($created['id']);

    $this->assertNull($service->find($created['id']));
  }
}
