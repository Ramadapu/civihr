<?php

trait CRM_HRUI_Upgrader_Steps_4707 {

  /**
   * Adds "NI/SSN application in progress" field and reminder activity type.
   */
  public function upgrade_4707() {
    $this->up4707_create_ssn_applying_field();
    $this->up4707_create_activity_type();

    return TRUE;
  }

  /**
   * @throws Exception
   */
  private function up4707_create_ssn_applying_field() {
    $groupName = 'Inline_Custom_Data';
    $customGroup = civicrm_api3('CustomGroup', 'get', ['name' => $groupName]);

    if ($customGroup['count'] != 1) {
      throw new \Exception("Cannot continue without Inline_Custom_Data group");
    }

    $customGroup = array_shift($customGroup['values']);

    $fieldName = 'has_applied_for_identification';
    $customField = civicrm_api3('CustomField', 'get', ['name' => $fieldName]);

    if ($customField['count'] > 0) {
      return;
    }

    // Add "NI/SSN application in progress" field
    $fieldData = [
      'custom_group_id' => $customGroup['id'],
      'name' => $fieldName,
      'label' => 'NI/SSN application in progress',
      'html_type' => 'Radio',
      'data_type' => 'Boolean',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1
    ];
    civicrm_api3('CustomField', 'create', $fieldData);
  }

  private function up4707_create_activity_type() {
    $activityName = 'Check on contact for NI/SSN';
    $activityType = civicrm_api3('OptionValue', 'get', ['name' => $activityName]);

    if ($activityType['count'] > 0) {
      return;
    }

    civicrm_api3('OptionValue', 'create', [
      'option_group_id' => "activity_type",
      'component_id' => "CiviTask",
      'name' => $activityName,
    ]);
  }

}
