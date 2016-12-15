define([
  'leave-absences/shared/modules/models-instances',
  'common/services/api/option-group',
  'common/models/instances/instance'
], function (instances) {
  'use strict';

  instances.factory('LeaveRequestInstance', [
    'ModelInstance',
    'LeaveRequestAPI',
    'api.optionGroup',
    function (ModelInstance, LeaveRequestAPI, OptionGroup) {

      /**
       * This method is used to get ID of an option value
       *
       * @param {string} name - name of the option value
       * @return {Promise} Resolved with {Object} Specific leave request
       */
      function getOptionIDByName(name) {
        return OptionGroup.valuesOf('hrleaveandabsences_leave_request_status')
          .then(function (data) {
            return data.find(function (statusObj) {
              return statusObj.name === name;
            })
          })
      }

      return ModelInstance.extend({

        /**
         * This method is used to cancel a leave request
         */
        cancel: function () {
          return getOptionIDByName('cancelled')
            .then(function (cancelledStatusId) {
              return this.update({
                'status_id': cancelledStatusId.value
              });
            }.bind(this))
            .then(function (data) {
              if (data.is_error === 1) {
                return data;
              }
              this.status_id = data.values[0].status_id;
            }.bind(this));
        },

        /**
         * This method is used to update a leave request
         *
         * @param {object} attributes - Values which needs to be updated
         * @return {Promise} Resolved with {Object} Updated Leave request
         */
        update: function (attributes) {
          return LeaveRequestAPI.sendPOST('LeaveRequest', 'create', _.assign({}, this.attributes(), attributes))
        }
      });
    }]);
});
