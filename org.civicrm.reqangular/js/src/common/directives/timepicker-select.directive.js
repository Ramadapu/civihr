/* eslint-env amd */

define([
  'common/lodash',
  'common/moment',
  'common/modules/directives'
], function (_, moment, directives) {
  'use strict';

  directives.directive('timepickerSelect', ['$templateCache', function ($templateCache) {
    return {
      scope: {
        timepickerSelectPlaceholder: '@',
        timepickerSelectTimeFrom: '<',
        timepickerSelectTimeTo: '<',
        timepickerSelectInterval: '<',
        timepickerSelectInitialValue: '<'
      },
      restrict: 'A',
      controllerAs: 'selector',
      controller: ['$scope', timepickerSelectController],
      template: $templateCache.get('timepicker-select.html')
    };
  }]);

  timepickerSelectController.$inject = ['$scope'];

  function timepickerSelectController ($scope) {
    var vm = this;

    vm.initialCustomValue = null;
    vm.placeholder = $scope.timepickerSelectPlaceholder;
    vm.options = [];

    /**
     * Builds options for the selector
     */
    function buildOptions () {
      var interval = +$scope.timepickerSelectInterval || 1;
      var timeFrom = moment.duration($scope.timepickerSelectTimeFrom || '00:00');
      var timeTo = moment.duration($scope.timepickerSelectTimeTo || '23:59');

      vm.options = [];

      while (timeFrom.asMinutes() <= timeTo.asMinutes()) {
        var time = moment.utc(timeFrom.asMilliseconds());

        vm.options.push(time.format('HH:mm'));
        timeFrom.add(interval, 'minutes');
      }

      if ($scope.timepickerSelectInitialValue &&
        !_.includes(vm.options, $scope.timepickerSelectInitialValue)) {
        vm.initialCustomValue = $scope.timepickerSelectInitialValue;
      }
    }

    $scope.$watchGroup([
      'timepickerSelectTimeFrom',
      'timepickerSelectTimeTo',
      'timepickerSelectInterval',
      'timepickerSelectInitialValue'
    ], function () {
      buildOptions();
    });
  }
});
