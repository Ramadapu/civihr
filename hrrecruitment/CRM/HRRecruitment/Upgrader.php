<?php

/**
 * Collection of upgrade steps
 */
class CRM_HRRecruitment_Upgrader extends CRM_HRRecruitment_Upgrader_Base {

  /**
   * Sets the weight on "Application" CaseType
   *
   * @return bool
   */
  public function upgrade_1400() {
    $this->ctx->log->info('Applying update 1400');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_case_type SET weight = 7 WHERE name = 'Application'");
    return TRUE;
  }

  /**
   * Renames the main menu item "Vacancies" to "Recruitment"
   *
   * @return bool
   */
  public function upgrade_1401() {
    $default = [];
    $params = ['name' => 'Vacancies', 'url' => null];

    $menuItem = CRM_Core_BAO_Navigation::retrieve($params, $default);
    $menuItem->label = 'Recruitment';
    $menuItem->save();

    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }

  /**
   * Sets icon for top-level 'Recruitment' menu item
   *
   * @return bool
   */
  public function upgrade_1402() {
    $params = [
      'name' => 'Vacancies',
      'api.Navigation.create' => ['id' => '$value.id', 'icon' => 'crm-i fa-user-plus'],
      'parent_id' => ['IS NULL' => true],
    ];
    civicrm_api3('Navigation', 'get', $params);

    return TRUE;
  }

  /**
   * Upgrade CustomGroup, setting Application, application_case and Evaluation_fields names to is_reserved Yes
   *
   * @return bool
   */
  public function upgrade_1403() {
    civicrm_api3('CustomGroup', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'name' => ['IN' => ['Application', 'application_case', 'Evaluation_fields']],
      'api.CustomGroup.create' => ['id' => '\$value.id', 'is_reserved' => 1],
    ]);

    return TRUE;
  }

}
