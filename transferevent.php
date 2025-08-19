<?php

require_once 'transferevent.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function transferevent_civicrm_config(&$config) {
  _transferevent_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function transferevent_civicrm_install() {
  _transferevent_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function transferevent_civicrm_enable() {
  _transferevent_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function transferevent_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'biz.jmaconsulting.transferevent',
    'name' => 'transferevent',
    'update' => 'never',
    'entity' => 'OptionValue',
    'params' => array(
      'label' => "Event Transferred",
      'name' => "event_transfer",
      'description' => "The event has been transferred for a single participant.",
      'option_group_id' => 'activity_type',
      'component_id' => CRM_Core_Component::getComponentID('CiviEvent'),
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => 3,
    ),
  );
}

function transferevent_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($objectName == "Participant" && $op == "participant.selector.row") {
    $transferLink = array(
      'name' => ts('Transfer Event'),
      'url' => 'civicrm/event/transfer',
      'qs' => "reset=1&pid=%%participantId%%",
    );
    $links[] = $transferLink;
    $values['participantId'] = $objectId;
  }
}

function transferevent_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'event') {
    $tasks[] = array(
      'title' => 'Transfer Event for participant(s)',
      'class' => 'CRM_TransferEvent_Form_Task_TransferEvent',
      'result' => 1,
    );
  }
}
