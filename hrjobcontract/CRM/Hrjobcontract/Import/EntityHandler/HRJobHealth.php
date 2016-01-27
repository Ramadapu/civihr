<?php

class CRM_Hrjobcontract_Import_EntityHandler_HRJobHealth extends CRM_Hrjobcontract_Import_EntityHandler {
  public function __construct() {
    parent::__construct('HRJobHealth');
  }

  public function handle(array $params, CRM_Hrjobcontract_DAO_HRJobContractRevision $contractRevision, array &$previousRevision) {
    $entityParams = $this->extractFields($params);

    if(count($entityParams) === 0) {
      return array();
    }

    $entityParams['import'] = 1;
    $entityParams['jobcontract_id'] = $contractRevision->jobcontract_id;
    $entityParams['jobcontract_revision_id'] = $contractRevision->id;

    $entityParams = $this->normaliseContactReference($entityParams, 'provider');
    $entityParams = $this->normaliseContactReference($entityParams, 'provider_life_insurance');

    return array(CRM_Hrjobcontract_BAO_HRJobHealth::create($entityParams));
  }

  /**
   * @param $entityParams
   * @return null|string
   */
  private function getContactIdByDisplayName($entityParams) {
    return CRM_Contact_BAO_Contact::getFieldValue('CRM_Contact_BAO_Contact', $entityParams, 'id', 'display_name');
  }

  /**
   * @param $entityParams
   * @return array
   */
  private function normaliseContactReference($entityParams, $fieldName) {
    if (!is_numeric($entityParams[$fieldName]) && !is_null($entityParams[$fieldName])) {
      $entityParams['provider'] = (int) $this->getContactIdByDisplayName($entityParams[$fieldName]);
    }

    return $entityParams;
  }
}
