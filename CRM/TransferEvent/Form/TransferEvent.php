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
 * This class generates form components to transfer an Event to another participant
 *
 */
class CRM_TransferEvent_Form_TransferEvent extends CRM_Core_Form {
  /**
   * participant id
   *
   * @var string
   *
   */
  protected $_participant_id;
  /**
   * from event id
   *
   * @var string
   *
   */
  protected $_from_event_id;
  /**
   * to event id
   *
   * @var string
   */
  protected $_to_event_id;
  /**
   * event title
   *
   * @var string
   */
  protected $_event_title;
  /**
   * event title
   *
   * @var string
   */
  protected $_event_start_date;
  /**
   * action
   *
   * @var string
   */
  public $_action;
  /**
   * event object
   *
   * @var string
   */
  protected $_event = array();
  /**
   * participant object
   *
   * @var string
   */
  protected $_participant = array();
  /**
   * participant values
   *
   * @array string
   */
  protected $_part_values;
  /**
   * details
   *
   * @array string
   */
  protected $_details = array();
  /**
   * contact_id
   *
   * @array string
   */
  protected $_contact_id;
  /**
   * contact_id
   *
   * @array string
   */
  protected $contact_id;

  /**
   * Get source values for transfer based on participant id in URL. Line items will
   * be transferred to this participant - at this point no transaction changes processed
   *
   * return @void
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
    $this->_participantId = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $participant = civicrm_api3('Participant', 'get', array(
      'id' => $this->_participantId,
    ));
    $this->_params = $participant['values'][$participant['id']];
  }

  /**
   * Build form for input of transferree email, name
   *
   * return @void
   */
  public function buildQuickForm() {
    $eventFieldParams = array(
      'entity' => 'event',
      'select' => array('minimumInputLength' => 0),
    );

    $this->addEntityRef('event_id', ts('Select Event'), $eventFieldParams, TRUE);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Transfer Event'),),
      )
    );
    $this->addFormRule(array('CRM_TransferEvent_Form_TransferEvent', 'formRule'), $this);
    parent::buildQuickForm();
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
    $participantId = $self->_participantId;
    $contactId = $self->_params['contact_id'];
    // verify whether this contact already registered for this event
    $statuses = array_flip(CRM_Event_PseudoConstant::participantStatus());
    $query = "SELECT event_id FROM civicrm_participant WHERE contact_id = " . $contactId . "
      AND status_id = " . CRM_Utils_Array::value('Registered', $statuses);
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if ($dao->event_id == $fields['event_id']) {
        $errors['event_id'] = $self->_params['display_name']. ts(" is already registered for this event");
        break;
      }
    }
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $participantId = $this->_participantId;
    $eventId = $params['event_id'];
    CRM_TransferEvent_BAO_TransferEvent::updateEvent($participantId, $params['event_id']);
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$eventId}");
    CRM_Utils_System::redirect($url);
  }

}
