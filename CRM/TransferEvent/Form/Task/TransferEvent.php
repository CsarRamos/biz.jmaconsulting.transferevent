<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright JMAConsulting (c) 2004-2017                              |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright JMAConsulting (c) 2004-2017
 * $Id$
 *
 */

/**
 * This class provides the functionality for cancel registration for event participations
 */
class CRM_TransferEvent_Form_Task_TransferEvent extends CRM_Event_Form_Task {

  /**
   * Variable to store redirect path.
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Transfer Event for Participant(s)'));
    $session = CRM_Core_Session::singleton();
    $this->addEntityRef('event_id', ts('Select Event'), array(
      'entity' => 'event',
      'placeholder' => ts('- Select Event -'),
      'select' => array('minimumInputLength' => 0),
    ));
    $this->addDefaultButtons(ts('Transfer Event'), 'done');
  }

  /**
   * Process the form after the input has been submitted.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();
    $value = array();

    foreach ($this->_participantIds as $participantId) {
      CRM_TransferEvent_BAO_TransferEvent::updateEvent($participantId, $params['event_id']);
    }
  }

}
