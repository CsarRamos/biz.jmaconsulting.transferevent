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

class CRM_TransferEvent_BAO_TransferEvent extends CRM_Event_BAO_Event {

  /*
   * This function transfers the event from a participant record. To keep things simple, we change the event 
   * from the original participant record and create a new participant record with old event details and status = 'Transferred'.
   * This will avoid unnecessary update of custom data related to participants.
   *
   * @param int $participantId
   *   The participant ID
   *
   * @param int $eventId
   *   The event ID
   */
  public static function updateEvent($participantId, $eventId) {
    if (empty($participantId) || empty($eventId)) {
      return;
    }

    $participant = civicrm_api3('Participant', 'get', array(
      'id' => $participantId,
    ));
    $params = $participant['values'][$participant['id']];
    unset($params['participant_id']);
    unset($params['id']);
    $params['status_id'] = "Transferred";
    $params['transferred_to_contact_id'] = $params['contact_id'];
    civicrm_api3('Participant', 'create', $params);

    // Update the status of the original participant record.
    civicrm_api3('Participant', 'create', array(
      'id' => $participantId,
      'event_id' => $eventId,
    ));

    // Create the activity.
    $userID = CRM_Core_Session::singleton()->get('userID');
    $oldEventTitle = civicrm_api3('Event', 'getvalue', array(
      'sequential' => 1,
      'return' => "title",
      'id' => $params['event_id'],
    ));
    $newEventTitle = civicrm_api3('Event', 'getvalue', array(
      'sequential' => 1,
      'return' => "title",
      'id' => $eventId,
    ));
    $details = "The participant's event registration has been transferred from
      <a href='" . CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$params['event_id']}", TRUE) . "'><b>{$oldEventTitle}</b></a> to
      <a href='" . CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$eventId}", TRUE) . "'><b>{$newEventTitle}</b></a>.";
    $activityParams = array(
      'source_contact_id' => $userID,
      'target_contact_id' => $params['contact_id'],
      'assignee_contact_id' => $params['contact_id'],
      'source_record_id' => $participantId,
      'activity_type_id' => 'event_transfer',
      'subject' => "Event Transfer - $oldEventTitle to $newEventTitle",
      'details' => $details,
    );
    civicrm_api3('Activity', 'create', $activityParams);

    // Set status message
    $statusMsg = ts('Event registration information for %1 has been updated.', array(1 => $params['display_name']));
    CRM_Core_Session::setStatus($statusMsg, ts('Registration Transferred'), 'success');
  }

  public static function getValidEvents() {
    $events = civicrm_api3('Event', 'get', array('return' => 'event_type_id'));
    foreach ($events['values'] as $key => $values) {
      $event[$values['id']] = $values['event_type_id'];
    }
    return json_encode($event);
  }
}