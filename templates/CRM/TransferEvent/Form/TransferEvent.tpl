{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright JMAConsulting (c) 2004-2017                                |
 +--------------------------------------------------------------------+
*}
<div class="crm-transferevent-form">
  <div class="crm-public-form-item crm-section transferevent-section">
   <div class="crm-public-form-item crm-section transferevent-event-section">
     <div class="label">{$form.event_id.label}</div>
     <div class="content">{$form.event_id.html}</div>
     <div class="clear"></div>
   </div>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var allEvents = {/literal}'{$allEvents}'{literal};
    var currentType = {/literal}'{$currentType}'{literal};
    var allEvents = $.parseJSON(allEvents);
    $('input[name="_qf_TransferEvent_submit"]').click(function(e) {
      var eventid = $('#event_id').val();
      if (allEvents[eventid] != currentType) {
      	var confirmation = CRM.confirm({
          width: 400,
          message: {/literal}"{ts escape='js'}The selected event has an event type which is different from the original event. Transferring this may cause loss of view of some custom data in the new participant record. However, the custom data will be preserved in the original participant record. Would you still like to transfer this registration?{/ts}"{literal}
        }).on('crmConfirm:yes', function() {
          $('#TransferEvent').submit();
        })
      }
    });
  });
</script>
{/literal}