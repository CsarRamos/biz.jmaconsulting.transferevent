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
    $eventFieldParams = array(
      'entity' => 'event',
      'select' => array('minimumInputLength' => 0),
    );

    $this->addEntityRef('event_id', ts('Select Event'), $eventFieldParams, TRUE);
    $this->addDefaultButtons(ts('Transfer Event'), 'done');
    $this->addFormRule(array('CRM_TransferEvent_Form_Task_TransferEvent', 'formRule'), $this);
  }

  /**
   * Validate email and name input
   *
   * return array $errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    //To check if the user is already registered for the event.
    if (!empty($fields['event_id'])) {
      self::checkRegistration($fields, $self, $errors);
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Check contact details
   *
   * return @void
   */
  public static function checkRegistration($fields, $self, &$errors) {
    // verify whether this contact already registered for this event
    $statuses = array_flip(CRM_Event_PseudoConstant::participantStatus());
    foreach ($self->_participantIds as $participantId) {
      $contactId = civicrm_api3('Participant', 'getvalue', array(
        'sequential' => 1,
        'return' => "contact_id",
        'id' => $participantId,
      ));
      $query = "SELECT event_id FROM civicrm_participant WHERE contact_id = " . $contactId . "
        AND status_id = " . CRM_Utils_Array::value('Registered', $statuses);
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        if ($dao->event_id == $fields['event_id']) {
          $errorContacts[] = CRM_Contact_BAO_Contact::displayName($contactId);
        }
      }
      if (!empty($errorContacts)) {
        $errors['event_id'] = ts('The following contact(s): ') . implode(", ", $errorContacts) . ts(' are already registered for this event.');
      }
    }
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
