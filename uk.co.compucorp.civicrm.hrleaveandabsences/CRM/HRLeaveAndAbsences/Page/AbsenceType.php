<?php

use CRM_HRLeaveAndAbsences_Service_AbsenceType as AbsenceTypeService;
use CRM_HRLeaveAndAbsences_BAO_AbsenceType as AbsenceType;

class CRM_HRLeaveAndAbsences_Page_AbsenceType extends CRM_Core_Page {

  private $links = [];
  private $categories = [];

  /**
   * @var \CRM_HRLeaveAndAbsences_Service_AbsenceType
   */
  private $absenceTypeService;

  public function run() {
    CRM_Utils_System::setTitle(ts('Leave/Absence Types'));
    $this->absenceTypeService = new AbsenceTypeService();
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $action);

    if ($action & CRM_Core_Action::BROWSE) {
      $this->browse();
    } else {
      $this->assign('leaveTypeId', CRM_Utils_Array::value('id', $_GET, ''));
      $this->loadResources(TRUE);
    }

    parent::run();
  }

  public function browse() {
    $object = new CRM_HRLeaveAndAbsences_BAO_AbsenceType();
    $object->orderBy('weight');
    $object->find();
    $rows = [];
    while($object->fetch()) {
      $rows[$object->id] = array();

      CRM_Core_DAO::storeValues($object, $rows[$object->id]);

      $rows[$object->id]['action'] = CRM_Core_Action::formLink(
          $this->links(),
          $this->calculateLinksMask($object),
          ['id' => $object->id]
      );
      $rows[$object->id]['category'] = $this->getCategoryLabel($rows[$object->id]['category']);
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/leaveandabsences/types', 'reset=1');
    CRM_Utils_Weight::addOrder($rows, 'CRM_HRLeaveAndAbsences_DAO_AbsenceType', 'id', $returnURL);

    $this->loadResources(FALSE);
    $this->assign('rows', $rows);
  }

  public function edit($action, $id = NULL, $imageUpload = FALSE, $pushUserContext = TRUE) {
    if($action & CRM_Core_Action::DELETE) {
      $this->delete($id);
    } else {
      parent::edit($action, $id, $imageUpload, $pushUserContext);
    }
  }

  public function delete($id) {
    try {
      CRM_HRLeaveAndAbsences_BAO_AbsenceType::del($id);
      CRM_Core_Session::setStatus(ts('The Leave/Absence type was deleted'), 'Success', 'success');
    } catch(Exception $ex) {
      $message = ts('Error deleting the Leave/Absence type.') . ' '. $ex->getMessage();
      CRM_Core_Session::setStatus($message, 'Error', 'error');
    }

    $url = CRM_Utils_System::url('civicrm/admin/leaveandabsences/types', 'reset=1&action=browse');
    CRM_Utils_System::redirect($url);
  }

  /**
   * name of the BAO to perform various DB manipulations
   *
   * @return string
   * @access public
   */
  public function getBAOName() {
    return 'CRM_HRLeaveAndAbsences_BAO_AbsenceType';
  }

  /**
   * an array of action links
   *
   * @return array (reference)
   * @access public
   */
  public function &links() {
    if(empty($this->links)) {
      $this->links = [
        CRM_Core_Action::UPDATE  => [
          'name'  => ts('Edit'),
          'url'   => 'civicrm/admin/leaveandabsences/types',
          'qs'    => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Leave/Absence Type'),
        ],
        CRM_Core_Action::DISABLE => [
          'name'  => ts('Disable'),
          'class' => 'crm-enable-disable',
          'title' => ts('Disable Leave/Absence Type'),
        ],
        CRM_Core_Action::ENABLE  => [
          'name'  => ts('Enable'),
          'class' => 'crm-enable-disable',
          'title' => ts('Enable Leave/Absence Type'),
        ],
        CRM_Core_Action::DELETE  => [
          'name'  => ts('Delete'),
          'class' => 'civihr-delete',
          'title' => ts('Delete Leave/Absence Type'),
        ],
      ];
    }

    return $this->links;
  }

  /**
   * name of the form
   *
   * @return string
   * @access public
   */
  public function editName() {
    return 'Leave/Absence Types';
  }

  /**
   * userContext to pop back to
   *
   * @param int $mode mode that we are in
   *
   * @return string
   * @access public
   */
  public function userContext($mode = null) {
    return 'civicrm/admin/leaveandabsences/types';
  }

  private function calculateLinksMask($absenceType) {
    $mask = array_sum(array_keys($this->links()));

    if($this->canNotDelete($absenceType)) {
      $mask -= CRM_Core_Action::DELETE;
    }

    if($absenceType->is_active) {
      $mask -= CRM_Core_Action::ENABLE;
    } else {
      $mask -= CRM_Core_Action::DISABLE;
    }

    return $mask;
  }

  /**
   * Checks whether an AbsenceType object cannot be deleted.
   *
   * @param CRM_HRLeaveAndAbsences_BAO_AbsenceType $absenceType
   *
   * @return bool
   */
  private function canNotDelete($absenceType) {
    return $this->absenceTypeService->absenceTypeHasEverBeenUsed($absenceType->id)
           || $absenceType->is_reserved;
  }

  /**
   * Retrieves the category label from option value
   *
   * @param int $categoryId
   *
   * @return mixed
   */
  private function getCategoryLabel($categoryId) {
    if (empty($this->categories)) {
      $this->categories = AbsenceType::getCategories();
    }

    if (array_key_exists($categoryId, $this->categories)) {
      return $this->categories[$categoryId];
    }
  }

  /**
   * Loads required page resources
   *
   * @param bool $addResourcesForAddingOrEditing
   */
  private function loadResources($addResourcesForAddingOrEditing) {
    CRM_Core_Resources::singleton()
      ->addStyleFile('uk.co.compucorp.civicrm.hrleaveandabsences', 'css/leaveandabsence.css')
      ->addScriptFile('uk.co.compucorp.civicrm.hrleaveandabsences', 'js/dist/crm-app-list.min.js', 1001)
      ->addScriptFile('civicrm', 'js/jquery/jquery.crmEditable.js', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');

    if ($addResourcesForAddingOrEditing) {
      CRM_Core_Resources::singleton()
        ->addScriptFile('uk.co.compucorp.civicrm.hrleaveandabsences', 'js/dist/leave-type-wizard-form.min.js', 1001)
        ->addScriptFile('uk.co.compucorp.civicrm.hrleaveandabsences', 'js/src/leave-absences/crm/vendor/spectrum/spectrum.min.js', 1)
        ->addVars('leaveAndAbsences', [
          'baseURL' => CRM_Core_Resources::singleton()->getUrl('uk.co.compucorp.civicrm.hrleaveandabsences')
        ]);
    }
  }
}
