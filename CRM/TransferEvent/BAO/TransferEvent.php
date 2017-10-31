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
    civicrm_api3('Participant', 'create', $params);

    // Update the status of the original participant record.
    civicrm_api3('Participant', 'create', array(
      'id' => $participantId,
      'event_id' => $eventId,
    ));
  }
}