<?php

interface CRM_HRCore_Menu_Config {

  /**
   * Returns Navigation menu items in a format understood by the
   * CRM_HRCore_Helper_Menu_MenuBuilder class.
   *
   * @return array
   *   Sample return format:
   *   [
   *      'Home' => ['icon' => 'civicrm-logo'],
   *      'Search' => [
   *        'icon' => 'crm-i fa-search',
   *        'children' => [
   *          'Find Contacts' => 'civicrm/search/contacts',
   *          'Advanced Search' => 'civicrm/search/advanced'
   *      ]
   *   ]
   */
  public function getItems();
}
