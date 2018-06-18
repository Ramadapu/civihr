/* global Drupal */
/* eslint-env amd */

(function (CRM, Drupal) {
  define([
    'common/angular',
    'common/lodash',
    'leave-absences/manager-leave/modules/settings'
  ], function (angular, _) {
    return angular.module('manager-leave.config', ['manager-leave.settings'])
      .config([
        '$stateProvider', '$resourceProvider', '$urlRouterProvider', '$httpProvider',
        '$logProvider', '$analyticsProvider', 'settings',
        function ($stateProvider, $resourceProvider, $urlRouterProvider, $httpProvider,
          $logProvider, $analyticsProvider, settings) {
          $logProvider.debugEnabled(settings.debug);

          $resourceProvider.defaults.stripTrailingSlashes = false;
          $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

          $urlRouterProvider.otherwise('/manager-leave/requests');
          $stateProvider
            .state('manager-leave', {
              abstract: true,
              url: '/manager-leave',
              template: '<manager-leave-container contact-id="$resolve.contactId"></manager-leave-container>',
              resolve: {
                contactId: function () {
                  return CRM.vars.leaveAndAbsences.contactId;
                },
                format: ['DateFormat', function (DateFormat) {
                  // stores the data format in HR_setting.DATE_FORMAT
                  return DateFormat.getDateFormat();
                }]
              }
            })
            .state('manager-leave.requests', {
              url: '/requests?leave-request-id',
              template: '<manage-leave-requests contact-id="managerLeave.contactId"></manage-leave-requests>',
              onEnter: [
                '$stateParams', 'LeavePopup', function ($stateParams, LeavePopup) {
                  if ($stateParams['leave-request-id']) {
                    LeavePopup.openModalByID($stateParams['leave-request-id']);
                  }
                }
              ]
            })
            .state('manager-leave.calendar', {
              url: '/calendar',
              template: '<leave-calendar contact-id="managerLeave.contactId"></leave-calendar>'
            })
            .state('manager-leave.leave-balances', {
              url: '/leave-balances',
              template: '<leave-balance-tab></leave-balance-tab>'
            });

          $analyticsProvider.withAutoBase(true);
          $analyticsProvider.settings.ga = {
            userId: _.get(Drupal, 'settings.currentCiviCRMUserId')
          };
        }
      ]);
  });
})(CRM, Drupal);
