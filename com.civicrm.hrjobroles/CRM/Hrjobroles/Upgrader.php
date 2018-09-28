<?php

/**
 * Collection of upgrade steps
 */
class CRM_Hrjobroles_Upgrader extends CRM_Hrjobroles_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed
   */
  public function install() {
//    $this->executeSqlFile('sql/myinstall.sql');
    $this->installCostCentreTypes();
  }

  /**
   * Example: Add start and and date for job roles
   *
   * @return TRUE on success
   * @throws Exception
   *
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update for job role start and end dates');
    CRM_Core_DAO::executeQuery("ALTER TABLE  `civicrm_hrjobroles` ADD COLUMN  `start_date` timestamp DEFAULT 0 COMMENT 'Start Date of the job role'");
    CRM_Core_DAO::executeQuery("ALTER TABLE  `civicrm_hrjobroles` ADD COLUMN  `end_date` timestamp DEFAULT 0 COMMENT 'End Date of the job role'");
    return TRUE;
  }

  public function upgrade_1002(){
    $this->installCostCentreTypes();

    return TRUE;
  }

  public function upgrade_1004() {
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_hrjobroles` MODIFY COLUMN `start_date` DATETIME DEFAULT NULL');
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_hrjobroles` MODIFY COLUMN `end_date` DATETIME DEFAULT NULL');
    //Update any end_date using "0000-00-00 00:00:00" as an empty value to NULL
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_hrjobroles` SET `end_date` = NULL WHERE end_date = '0000-00-00 00:00:00'");

    return TRUE;
  }

  /**
   * Deletes cost centre "other" option value if not in use
   *
   * @return bool
   */
  public function upgrade_1006() {
    $jobRoles = civicrm_api3('HrJobRoles', 'get');
    if ($jobRoles['count'] == 0) {
      $this->deleteCostCentreOther();
      return TRUE;
    }

    $otherId = $this->retrieveCostCentreOtherId();
    if ($otherId == null) {
      return TRUE;
    }
    $inUse = FALSE;
    $roles = $jobRoles['values'];
    $pattern = '/\|' . $otherId . '\|/';
    foreach ($roles as $role) {
      if (preg_match($pattern, $role['cost_center'])) {
        $inUse = TRUE;
        break;
      }
    }

    if (!$inUse) {
      $this->deleteCostCentreOther();
    }

    return TRUE;
  }

  /**
   * Deletes cost centre option value with name "other"
   */
  private function deleteCostCentreOther() {
    civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'cost_centres',
      'name' => 'Other',
      'api.OptionValue.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * Fetches the id of cost center "other" option value
   *
   * @return int
   */
  private function retrieveCostCentreOtherId() {
    $result = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'cost_centres',
      'name' => 'Other',
    ]);

    return isset($result['id']) ? $result['id'] : null;
  }

  /**
   * Creates new Option Group for Cost Centres
   */
  public function installCostCentreTypes() {
    try{
      $result = civicrm_api3('OptionGroup', 'create', [
          'sequential' => 1,
          'name' => "cost_centres",
          'title' => "Cost Centres",
          'is_active' => 1
      ]);

      $id = $result['id'];

      $val = 'Other';
      civicrm_api3('OptionValue', 'create', [
        'sequential' => 1,
        'option_group_id' => $id,
        'label' => $val,
        'value' => $val,
        'name' => $val,
      ]);

    } catch(Exception $e){
      // OptionGroup already exists
      // Skip this
    }
  }
}
