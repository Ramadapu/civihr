<?php

trait CRM_HRLeaveAndAbsences_Upgrader_Step_1007 {

  /**
   * Removes the url of the "Leave" menu item and adds a sub menu to it
   *
   * @return bool
   */
  public function upgrade_1007() {
    $default = [];
    $menuItemParams = ['name' => 'leave_and_absences_dashboard'];
    $menuItem = CRM_Core_BAO_Navigation::retrieve($menuItemParams, $default);

    $this->up1007_clearMenuItemUrl($menuItem);
    $this->up1007_addSubMenuToMenuItem($menuItem);

    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }

  /**
   * Removes the url of the given menu item
   *
   * @param CRM_Core_BAO_Navigation $menuItem
   */
  private function up1007_clearMenuItemUrl(&$menuItem) {
    $menuItem->url = 'NULL';
    $menuItem->save();
  }

  /**
   * Adds the leave sub menu items to the given menu item
   *
   * @param CRM_Core_BAO_Navigation $menuItem
   */
  private function up1007_addSubMenuToMenuItem($menuItem) {
    $subMenu = [
      [
        'label' => ts('Leave Requests'),
        'name' => 'leave_and_absences_leave_requests',
        'url' => 'civicrm/leaveandabsences/dashboard#/requests',
      ],
      [
        'label' => ts('Leave Calendar'),
        'name' => 'leave_and_absences_leave_calendar',
        'url' => 'civicrm/leaveandabsences/dashboard#/calendar',
      ],
      [
        'label' => ts('Leave Balances'),
        'name' => 'leave_and_absences_leave_balances',
        'url' => 'civicrm/leaveandabsences/dashboard#/balance-report',
      ]
    ];

    foreach ($subMenu as $key => $subMenuItem) {
      if ($this->up1007_doesMenuItemExist($subMenuItem['name'])) {
        continue;
      }

      $subMenuItem['parent_id'] = $menuItem->id;
      $subMenuItem['weight'] = $key;
      $subMenuItem['is_active'] = 1;

      CRM_Core_BAO_Navigation::add($subMenuItem);
    }
  }

  /**
   * Checks if the menu item with the given name already exists
   *
   * @param string $subMenuItem
   *
   * @return bool
   */
  private function up1007_doesMenuItemExist($subMenuItemName) {
    $default = [];
    $subMenuItemParams = ['name' => $subMenuItemName];

    return CRM_Core_BAO_Navigation::retrieve($subMenuItemParams, $default) != NULL;
  }
}
