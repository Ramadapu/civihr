// https://civicrm.org/licensing
(function($) {
  function updateTimes() {
    $('.hr-recent-activity-time').each(function () {
      var recentActvityDate = moment($(this).data('time'), "YYYY-MM-DD hh:mm:ss");
      $(this).text(recentActvityDate.fromNow());
    });
  }
  window.setInterval(updateTimes, 60000);
  $('#crm-container').on('crmLoad', updateTimes);

  $('.hr-stage-link').each(function() {
    $(this).text('Apply Now - ' + $(this).text() + ' Applications');
  });
}(CRM.$));
