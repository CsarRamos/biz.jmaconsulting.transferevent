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
    $this->_participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_contact_id = civicrm_api3('Participant', 'get', array('id' => $this->_participant_id));
    $params = array('id' => $this->_participant_id);
  }

  /**
   * Build form for input of transferree email, name
   *
   * return @void
   */
  public function buildQuickForm() {
    $this->addEntityRef('event_id', ts('Select Event'), array(
      'entity' => 'event',
      'placeholder' => ts('- Select Event -'),
      'select' => array('minimumInputLength' => 0),
    ));
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
    //return parent::formrule($fields, $files, $self);
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Check contact details
   *
   * return @void
   */
  public static function checkRegistration($fields, $self, &$errors) {
    // verify whether this contact already registered for this event
    $query = "select event_id from civicrm_participant where event_id = " . $contact_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $to_event_id[]  = $dao->event_id;
    }
    if (!empty($to_event_id)) {
      foreach ($to_event_id as $id) {
        if ($id == $self->_event_id) {
          $errors['email'] = $display_name . ts(" is already registered for this event");
        }
      }
    }
  }

  /**
   * Process transfer - first add the new participant to the event, then cancel
   * source participant - send confirmation email to transferee
   */
  public function postProcess() {
    //For transfer, process form to allow selection of transferree
    $params = $this->controller->exportValues($this->_name);
    //cancel 'from' participant row
    $query = "select contact_id from civicrm_email where email = '" . $params['email'] . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contact_id  = $dao->contact_id;
    }
    $from_participant = $params = array();
    $query = "select role_id, source, fee_level, is_test, is_pay_later, fee_amount, discount_id, fee_currency,campaign_id, discount_amount from civicrm_participant where id = " . $this->_from_participant_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    $value_to = array();
    while ($dao->fetch()) {
      $value_to['role_id'] = $dao->role_id;
      $value_to['source'] = $dao->source;
      $value_to['fee_level'] = $dao->fee_level;
      $value_to['is_test'] = $dao->is_test;
      $value_to['is_pay_later'] = $dao->is_pay_later;
      $value_to['fee_amount'] = $dao->fee_amount;
    }
    $value_to['contact_id'] = $contact_id;
    $value_to['event_id'] = $this->_event_id;
    $value_to['status_id'] = 1;
    $value_to['register_date'] = date("Y-m-d");
    //first create the new participant row -don't set registered_by yet or email won't be sent
    $participant = CRM_Event_BAO_Participant::create($value_to);
    //send a confirmation email to the new participant
    $this->participantTransfer($participant);
    //now update registered_by_id
    $query = "UPDATE civicrm_participant cp SET cp.registered_by_id = %1 WHERE  cp.id = ({$participant->id})";
    $params = array(1 => array($this->_from_participant_id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    //copy line items to new participant
    $line_items = CRM_Price_BAO_LineItem::getLineItems($this->_from_participant_id);
    foreach ($line_items as $item) {
      $item['entity_id'] = $participant->id;
      $item['id'] = NULL;
      $item['entity_table'] = "civicrm_participant";
      $new_item = CRM_Price_BAO_LineItem::create($item);
    }
    //now cancel the from participant record, leaving the original line-item(s)
    $value_from = array();
    $value_from['id'] = $this->_from_participant_id;
    $tansferId = array_search('Transferred', CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
    $value_from['status_id'] = $tansferId;
    $value_from['transferred_to_contact_id'] = $contact_id;
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($contact_id);
    $display_name = current($contact_details);
    $this->assign('to_participant', $display_name);
    CRM_Event_BAO_Participant::create($value_from);
    $this->sendCancellation();
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contact_id);
    $statusMsg = ts('Event registration information for %1 has been updated.', array(1 => $displayName));
    $statusMsg .= ' ' . ts('A confirmation email has been sent to %1.', array(1 => $email));
    CRM_Core_Session::setStatus($statusMsg, ts('Registration Transferred'), 'success');
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_event_id}");
    CRM_Utils_System::redirect($url);
  }

  /**
   * Based on input, create participant row for transferee and send email
   *
   * return @ void
   */
  public function participantTransfer($participant) {
    $contactDetails = array();
    $contactIds[] = $participant->contact_id;
    list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
      FALSE, FALSE, NULL, array(), 'CRM_Event_BAO_Participant');
    foreach ($currentContactDetails as $contactId => $contactValues) {
      $contactDetails[$contactId] = $contactValues;
    }
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = array();
    $query = "SELECT * FROM civicrm_participant WHERE id = " . $participant->id;
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $participantDetails[$dao->id] = array(
        'id' => $dao->id,
        'role' => $participantRoles[$dao->role_id],
        'is_test' => $dao->is_test,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'fee_amount' => $dao->fee_amount,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
      );
    }
    $domainValues = array();
    if (empty($domainValues)) {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $tokens = array(
        'domain' =>
        array(
          'name',
          'phone',
          'address',
          'email',
        ),
        'contact' => CRM_Core_SelectValues::contactTokens(),
      );
      foreach ($tokens['domain'] as $token) {
        $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
      }
    }
    $eventDetails = array();
    $eventParams = array('id' => $participant->event_id);
    CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails);
    //get default participant role.
    $eventDetails['participant_role'] = CRM_Utils_Array::value($eventDetails['default_role_id'], $participantRoles);
    //get the location info
    $locParams = array(
      'entity_id' => $participant->event_id,
      'entity_table' => 'civicrm_event',
    );
    $eventDetails['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    $toEmail = CRM_Utils_Array::value('email', $contactDetails[$participant->contact_id]);
    if ($toEmail) {
      //take a receipt from as event else domain.
      $receiptFrom = $domainValues['name'] . ' <' . $domainValues['email'] . '>';
      if (!empty($eventDetails['confirm_from_name']) && !empty($eventDetails['confirm_from_email'])) {
        $receiptFrom = $eventDetails['confirm_from_name'] . ' <' . $eventDetails['confirm_from_email'] . '>';
      }
      $participantName = $contactDetails[$participant->contact_id]['display_name'];
      $tplParams = array(
        'event' => $eventDetails,
        'participant' => $participantDetails[$participant->id],
        'participantID' => $participant->id,
        'participant_status' => 'Registered',
      );

      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_event',
        'valueName' => 'event_online_receipt',
        'contactId' => $participantDetails[$participant->id]['contact_id'],
        'tplParams' => $tplParams,
        'from' => $receiptFrom,
        'toName' => $participantName,
        'toEmail' => $toEmail,
        'cc' => CRM_Utils_Array::value('cc_confirm', $eventDetails),
        'bcc' => CRM_Utils_Array::value('bcc_confirm', $eventDetails),
      );
      CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    }
  }

  /**
   * Send confirmation of cancellation to source participant
   *
   * return @ void
   */
  public function sendCancellation() {
    $domainValues = array();
    $domain = CRM_Core_BAO_Domain::getDomain();
    $tokens = array(
      'domain' =>
      array(
        'name',
        'phone',
        'address',
        'email',
      ),
      'contact' => CRM_Core_SelectValues::contactTokens(),
    );
    foreach ($tokens['domain'] as $token) {
      $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
    }
    $participantRoles = array();
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = array();
    $query = "SELECT * FROM civicrm_participant WHERE id = {$this->_from_participant_id}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $participantDetails[$dao->id] = array(
        'id' => $dao->id,
        'role' => $participantRoles[$dao->role_id],
        'is_test' => $dao->is_test,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'fee_amount' => $dao->fee_amount,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
      );
    }
    $eventDetails = array();
    $eventParams = array('id' => $this->_event_id);
    CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails[$this->_event_id]);
    //get default participant role.
    $eventDetails[$this->_event_id]['participant_role'] = CRM_Utils_Array::value($eventDetails[$this->_event_id]['default_role_id'], $participantRoles);
    //get the location info
    $locParams = array('entity_id' => $this->_event_id, 'entity_table' => 'civicrm_event');
    $eventDetails[$this->_event_id]['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    //get contact details
    $contactIds[$this->_from_contact_id] = $this->_from_contact_id;
    list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
      FALSE, FALSE, NULL, array(),
      'CRM_Event_BAO_Participant'
    );
    foreach ($currentContactDetails as $contactId => $contactValues) {
      $contactDetails[$this->_from_contact_id] = $contactValues;
    }
    //send a 'cancelled' email to user, and cc the event's cc_confirm email
    $mail = CRM_Event_BAO_Participant::sendTransitionParticipantMail($this->_from_participant_id,
      $participantDetails[$this->_from_participant_id],
      $eventDetails[$this->_event_id],
      $contactDetails[$this->_from_contact_id],
      $domainValues,
      "Transferred",
      ""
    );
    $statusMsg = ts('Event registration information for %1 has been updated.', array(1 => $this->_contact_name));
    $statusMsg .= ' ' . ts('A cancellation email has been sent to %1.', array(1 => $this->_contact_email));
    CRM_Core_Session::setStatus($statusMsg, ts('Thanks'), 'success');
  }

}
