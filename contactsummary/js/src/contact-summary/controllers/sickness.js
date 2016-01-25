define([
    'contact-summary/modules/controllers',
    'contact-summary/services/leave'
], function (controllers) {
    'use strict';

    /**
     * @ngdoc controller
     * @name SicknessCtrl
     * @param $log
     * @param {LeaveService} Leave
     * @constructor
     */
    function SicknessCtrl($log, Leave) {
        $log.debug('Controller: SicknessCtrl');

        var self = this;

        this.taken = 0;
        this.takenPreviously = 0;
        this.staffAverage = 0;
        this.ready = false;
        this.currentPeriod = '-';

        Leave.getStaffAverage('sick')
            .then(function (response) {
                self.staffAverage = response;

                return Leave.getCurrent();
            })
            .then(function (response) {
                angular.forEach(response, function (leave) {
                    if (leave.title === 'Sick') {
                        self.taken = leave.taken;
                    }
                });

                return Leave.getPrevious();
            })
            .then(function (response) {
                angular.forEach(response, function (leave) {
                    if (leave.title === 'Sick') {
                        self.takenPreviously = leave.taken;
                    }
                });

                return Leave.getCurrentPeriod();
            })
            .then(function (response) {
                self.currentPeriod = response.title;
            })
            .finally(function () {
                self.ready = true;
            });
    }

    controllers.controller('SicknessCtrl', ['$log', 'LeaveService', SicknessCtrl]);
});
