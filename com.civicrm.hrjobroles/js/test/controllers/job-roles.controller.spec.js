/* eslint-env amd, jasmine */

define([
  'common/angular',
  'common/lodash',
  'common/moment',
  'mocks/data/job-role.data',
  'common/angularMocks',
  'common/services/pub-sub',
  'job-roles/job-roles.module'
], function (angular, _, moment, JobRoleDataMock) {
  'use strict';

  describe('JobRolesController', function () {
    var $controller, $filter, $q, $rootScope, crmAngService, dateValidation, jobRoleService, ctrl, pubSub, scope, settingsData;
    var contactId = '123';

    beforeEach(module('hrjobroles', 'hrjobroles.templates'));
    beforeEach(inject(function (_$controller_, _$filter_, $httpBackend, _$q_, _$rootScope_, _crmAngService_, _pubSub_, _dateValidation_, _jobRoleService_) {
      $controller = _$controller_;
      $filter = _$filter_;
      $q = _$q_;
      $rootScope = _$rootScope_;

      crmAngService = _crmAngService_;
      pubSub = _pubSub_;
      dateValidation = _dateValidation_;
      jobRoleService = _jobRoleService_;

      // Mock data from CiviCRM settings
      dateValidation.dateFormats.push('DD/MM/YYYY');
    }));

    describe('when user opens option editor', function () {
      var url = '/civicrm/admin/options/hrjc_level_type?reset=1';
      var optionType = 'hrjc_level_type';
      beforeEach(function () {
        spyOn(crmAngService, 'loadForm').and.callFake(function () {
          return {
            on: function () {}
          };
        });
        initController();
        ctrl.openOptionsEditor(optionType);
      });

      it('calls the crmAngService with the requested url', function () {
        expect(crmAngService.loadForm).toHaveBeenCalledWith(url);
      });
    });

    describe('getOptionValues', function () {
      var expectedDepartmentValue, expectedLocationValue, expectedLevelValue, expectedRegionValue, expectedFunderValue;

      beforeEach(function () {
        expectedLevelValue = JobRoleDataMock.option_values.values[0].value;
        expectedDepartmentValue = JobRoleDataMock.option_values.values[4].value;
        expectedLocationValue = JobRoleDataMock.option_values.values[5].value;
        expectedRegionValue = JobRoleDataMock.option_values.values[6].value;
        expectedFunderValue = JobRoleDataMock.option_values.values[7].value;

        spyOn(jobRoleService, 'getOptionValues').and.returnValue($q.resolve(JobRoleDataMock.option_values));
        initController();
        $rootScope.$digest();
      });

      it('builds the "CostCentreList" array containing the "weight" property', function () {
        expect(ctrl.CostCentreList).toEqual(jasmine.any(Array));
        expect(ctrl.CostCentreList.length).toBe(3);
        expect(ctrl.CostCentreList[0].weight).not.toBeNull();
        expect(ctrl.CostCentreList[1].weight).not.toBeNull();
        expect(ctrl.CostCentreList[2].weight).not.toBeNull();
      });

      it('builds the "hrjc_level_type" collection containing the "value" property', function () {
        expect(Object.keys(ctrl.levelsData).length).toBe(1);
        expect(ctrl.levelsData[Object.keys(ctrl.levelsData)[0]]['value']).toBe(expectedLevelValue);
      });

      it('builds the "hrjc_department" collection containing the "value" property', function () {
        expect(Object.keys(ctrl.departmentsData).length).toBe(1);
        expect(ctrl.departmentsData[Object.keys(ctrl.departmentsData)[0]]['value']).toBe(expectedDepartmentValue);
      });

      it('builds the "hrjc_location" collection containing the "value" property', function () {
        expect(Object.keys(ctrl.locationsData).length).toBe(1);
        expect(ctrl.locationsData[Object.keys(ctrl.locationsData)[0]]['value']).toBe(expectedLocationValue);
      });

      it('builds the "hrjc_region" collection containing the "value" property', function () {
        expect(Object.keys(ctrl.regionsData).length).toBe(1);
        expect(ctrl.regionsData[Object.keys(ctrl.regionsData)[0]]['value']).toBe(expectedRegionValue);
      });

      it('builds the "hrjc_funder" collection containing the "value" property', function () {
        expect(Object.keys(ctrl.fundersData).length).toBe(1);
        expect(ctrl.fundersData[Object.keys(ctrl.fundersData)[0]]['value']).toBe(expectedFunderValue);
      });
    });

    describe('Basic tests', function () {
      beforeEach(function () {
        spyOn(jobRoleService, 'getContracts').and.callThrough();

        initController();
      });

      describe('on init', function () {
        it('fetches the Job Contract of the contact', function () {
          expect(jobRoleService.getContracts).toHaveBeenCalledWith(contactId);
        });
      });

      describe('Validate role', function () {
        var formData;

        beforeEach(function () {
          ctrl.contractsData = angular.copy(JobRoleDataMock.contracts_data);
          formData = angular.copy(JobRoleDataMock.form_data);
          formData.contract.$viewValue = '1';
          formData.title.$viewValue = 'test';
        });

        it('should not pass validation', function () {
          expect(ctrl.validateRole(formData)).not.toBe(true);
        });

        it('should pass validation dd/mm/yyyy', function () {
          formData.start_date.$viewValue = '31/12/2016';

          expect(ctrl.validateRole(formData)).toBe(true);
        });

        it('should pass validation for new Date()', function () {
          var contract = ctrl.contractsData[formData.contract.$viewValue];
          formData.start_date.$viewValue = new Date(contract.end_date);

          expect(ctrl.validateRole(formData)).toBe(true);
        });

        it('should pass validation yyyy-mm-dd', function () {
          formData.start_date.$viewValue = '2016-05-05';

          expect(ctrl.validateRole(formData)).toBe(true);
        });

        describe('when job role start date is lower than contract start date', function () {
          beforeEach(function () {
            formData.start_date.$viewValue = '2016-05-04';
            formData.end_date.$viewValue = '2017-05-05';
          });

          it('throws a validation error', function () {
            expect(ctrl.validateRole(formData)).not.toBe(true);
          });
        });

        describe('when job role start date is lower than contract start date and doesn\'t inform end_date', function () {
          beforeEach(function () {
            formData.start_date.$viewValue = '2016-01-01';
            formData.contract.$viewValue = '2';
          });

          it('throws a validation error', function () {
            expect(ctrl.validateRole(formData)).not.toBe(true);
          });
        });

        describe('when job role end date is higher than contract end date', function () {
          beforeEach(function () {
            formData.start_date.$viewValue = '2016-06-04';
            formData.end_date.$viewValue = '2017-05-06';
          });

          it('throws a validation error', function () {
            expect(ctrl.validateRole(formData)).not.toBe(true);
          });
        });

        describe('when job role start date and job role end date is higher than contract end date', function () {
          beforeEach(function () {
            formData.start_date.$viewValue = '2016-05-06';
            formData.end_date.$viewValue = '2017-05-06';
          });

          it('throws a validation error', function () {
            expect(ctrl.validateRole(formData)).not.toBe(true);
          });
        });
      });

      describe('Fetching Dates from contract', function () {
        beforeEach(function () {
          ctrl.contractsData = angular.copy(JobRoleDataMock.contracts_data);
        });

        describe('Checking if dates entered in job role are th same as those in contracts', function () {
          it('should check if entered dates are custom', function () {
            expect(ctrl.checkIfDatesAreCustom('2005-01-01', null)).toBe(true);
          });

          it('should omit a time information', function () {
            expect(ctrl.checkIfDatesAreCustom(JobRoleDataMock.contracts_data[0].start_date + ' 00:00:00', JobRoleDataMock.contracts_data[0].end_date)).toBe(false);
          });

          it('should successfully compare dates to contract without end date', function () {
            expect(ctrl.checkIfDatesAreCustom(JobRoleDataMock.contracts_data[2].start_date, null)).toBe(false);
          });

          it('should successfully compare date object', function () {
            expect(ctrl.checkIfDatesAreCustom(new Date(2016, 0, 1), new Date(2016, 0, 31))).toBe(false);
          });

          it('should return false only if both dates match the same contract', function () {
            expect(ctrl.checkIfDatesAreCustom(JobRoleDataMock.contracts_data[0].start_date, JobRoleDataMock.contracts_data[0].end_date)).toBe(false);
            expect(ctrl.checkIfDatesAreCustom(JobRoleDataMock.contracts_data[1].start_date, JobRoleDataMock.contracts_data[0].end_date)).toBe(true);
          });
        });

        describe('New Job Role', function () {
          beforeEach(function () {
            ctrl.editData['new_role_id'] = angular.copy(JobRoleDataMock.new_role);
          });

          it('should set dates', function () {
            ctrl.editData['new_role_id'].job_contract_id = 0;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));
          });

          it('should not modify if dates were edited manually', function () {
            ctrl.editData['new_role_id'].newStartDate = '2005-01-01';
            ctrl.editData['new_role_id'].job_contract_id = 1;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject('2005-01-01'));
            expect(ctrl.editData['new_role_id'].newEndDate).toBe(null);
          });

          it('should set only start date if contract has no end date', function () {
            ctrl.editData['new_role_id'].job_contract_id = 2;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[2].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toBe(null);
          });

          it('should change dates whenever contract change', function () {
            ctrl.editData['new_role_id'].job_contract_id = 0;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));

            // change contract
            ctrl.editData['new_role_id'].job_contract_id = 1;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[1].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[1].end_date));

            // change contract
            ctrl.editData['new_role_id'].job_contract_id = 2;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[2].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toBe(null);

            // change contract
            ctrl.editData['new_role_id'].job_contract_id = 0;
            ctrl.onContractSelected();
            expect(ctrl.editData['new_role_id'].newStartDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData['new_role_id'].newEndDate).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));
          });

          it('form should be validated', function () {
            ctrl.editData['new_role_id'].job_contract_id = 2;
            ctrl.onContractSelected();
          });
        });

        describe('Existing Job Role', function () {
          beforeEach(function () {
            ctrl.editData = angular.copy(JobRoleDataMock.roles_data);
            ctrl.contractsData = angular.copy(JobRoleDataMock.contracts_data);
          });

          it('should set dates', function () {
            ctrl.editData[0].start_date = null;
            ctrl.editData[0].end_date = null;
            ctrl.editData[0].job_contract_id = 0;
            ctrl.onContractEdited(0, 0);

            expect(ctrl.editData[0].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData[0].end_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));
          });

          it('should not modify if dates were edited manually', function () {
            ctrl.editData[2].start_date = '2005-01-01';
            ctrl.editData[2].job_contract_id = 1;
            ctrl.onContractEdited(1, 2);

            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject('2005-01-01'));
            expect(ctrl.editData[2].end_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[3].end_date));
          });

          it('should set only start date if contract has no end date', function () {
            ctrl.editData[2].start_date = JobRoleDataMock.contracts_data[1].start_date;
            ctrl.editData[2].end_date = JobRoleDataMock.contracts_data[1].end_date;

            ctrl.editData[2].job_contract_id = 2;
            ctrl.onContractEdited(2, 2);
            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[2].start_date));
            expect(ctrl.editData[2].end_date).toBe(null);
          });

          it('should change dates whenever contract change', function () {
            ctrl.editData[2].start_date = JobRoleDataMock.contracts_data[1].start_date;
            ctrl.editData[2].end_date = JobRoleDataMock.contracts_data[1].end_date;

            ctrl.editData[2].job_contract_id = 0;
            ctrl.onContractEdited(0, 2);
            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData[2].end_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));

            // change contract
            ctrl.editData[2].job_contract_id = 1;
            ctrl.onContractEdited(1, 2);
            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[1].start_date));
            expect(ctrl.editData[2].end_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[1].end_date));

            // change contract
            ctrl.editData[2].job_contract_id = 2;
            ctrl.onContractEdited(2, 2);
            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[2].start_date));
            expect(ctrl.editData[2].end_date).toBe(null);

            // change contract
            ctrl.editData[2].job_contract_id = 0;
            ctrl.onContractEdited(0, 2);
            expect(ctrl.editData[2].start_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].start_date));
            expect(ctrl.editData[2].end_date).toEqual(convertToDateObject(JobRoleDataMock.contracts_data[0].end_date));
          });
        });

        describe('When call onAfterSave', function () {
          beforeEach(function () {
            ctrl.editData = angular.copy(JobRoleDataMock.roles_data);
          });

          it('should remove the funders entries which are without funder_id', function () {
            ctrl.onAfterSave(3, 'funders');
            expect(ctrl.editData[3]['funders'].length).toBe(3);
          });

          it('should remove the cost_centers entries which are without cost_centre_id', function () {
            ctrl.onAfterSave(3, 'cost_centers');
            expect(ctrl.editData[3]['cost_centers'].length).toBe(2);
          });
        });

        describe('When call onCancel passing', function () {
          beforeEach(function () {
            ctrl.editData = angular.copy(JobRoleDataMock.roles_data);
          });

          describe('funders', function () {
            beforeEach(function () {
              ctrl.onCancel(3, 'funders');
            });

            it('should remove the funders entries which are without funder_id', function () {
              expect(ctrl.editData[3]['funders'].length).toBe(3);
            });
          });

          describe('cost_centers', function () {
            beforeEach(function () {
              ctrl.onCancel(3, 'cost_centers');
            });

            it('should remove the cost_centers entries which are without cost_centre_id', function () {
              expect(ctrl.editData[3]['cost_centers'].length).toBe(2);
            });
          });

          describe('both', function () {
            beforeEach(function () {
              ctrl.onCancel(3, 'both');
            });

            it('should remove the funders and cost_centers entries which are without id', function () {
              expect(ctrl.editData[3]['cost_centers'].length).toBe(2);
              expect(ctrl.editData[3]['funders'].length).toBe(3);
            });
          });
        });

        describe('Updating old revision dates', function () {

        });
      });
    });

    // Tests that needs to have control over the state prior
    // to the controller initialization
    describe('Initial state dependent tests', function () {
      var contracts, jobRoles;

      beforeEach(function () {
        contracts = _.toArray(angular.copy(JobRoleDataMock.contracts_data));
        jobRoles = _.toArray(angular.copy(JobRoleDataMock.roles_data));
      });

      beforeEach(function () {
        spyOn(jobRoleService, 'getContracts').and.callFake(function () {
          return fakeContractResponse(contracts);
        });
        spyOn(jobRoleService, 'getAllJobRoles').and.callFake(function () {
          return fakeJobRolesResponse(jobRoles);
        });
      });

      describe('Fetching Job Roles from contract', function () {
        describe('when the user does not have a contract', function () {
          beforeEach(function () {
            contracts = [];

            initController();
            $rootScope.$digest();
          });

          it('does not try to fetch any job role', function () {
            expect(jobRoleService.getAllJobRoles).not.toHaveBeenCalled();
          });
        });

        describe('when the user does have a contract', function () {
          beforeEach(function () {
            initController();
            $rootScope.$digest();
          });

          it('fetches the job roles', function () {
            expect(jobRoleService.getAllJobRoles).toHaveBeenCalledWith(contracts.map(function (contract) {
              return contract.id;
            }));
          });

          it('it separates past and present job roles', function () {
            expect(ctrl.presentJobRoles.length).toBe(2);
            expect(ctrl.pastJobRoles.length).toBe(2);
          });
        });
      });

      /**
       * Fakes the response that jobRoleService.getContracts() would get
       *
       * @param {Array} contracts
       * @return {Promise} resolves to the response
       */
      function fakeContractResponse (contracts) {
        var deferred = $q.defer();
        deferred.resolve({ count: contracts.length, values: contracts });

        return deferred.promise;
      }

      /**
       * Fakes the response that jobRoleService.getAllJobRoles() would get
       *
       * @param {Array} jobRoles
       * @return {Promise} resolves to the response
       */
      function fakeJobRolesResponse (jobRoles) {
        var deferred = $q.defer();
        deferred.resolve({ count: jobRoles.length, values: jobRoles });

        return deferred.promise;
      }
    });

    describe('When call updateRole passing a job contract with end date equals todays date', function () {
      beforeEach(function () {
        var todaysDate = moment().format('YYYY-MM-DD');

        spyOn(jobRoleService, 'getAllJobRoles').and.returnValue($q.resolve({
          values: [{
            title: 'Test',
            id: '19',
            job_contract_id: '22',
            end_date: todaysDate,
            start_date: '2016-04-01 00:00:00'
          }]
        }));

        spyOn(jobRoleService, 'getContracts').and.returnValue($q.resolve({
          count: 1,
          values: [{
            contact_id: '158',
            deleted: '0',
            id: '22',
            is_current: '1',
            period_end_date: '2016-08-31',
            period_start_date: '2916-04-01',
            revisions: [],
            title: 'Test'
          }]
        }));

        initController();
        ctrl.editData = angular.copy(JobRoleDataMock.roles_data);
        $rootScope.$digest();
        ctrl.updateRole(1);
      });

      it('the presentJobRoles.length should be 1', function () {
        expect(ctrl.presentJobRoles.length).toBe(1);
      });
    });

    describe('When contract or job role creating/deleting/updating events are published', function () {
      var contractId = '123';

      beforeEach(function () {
        spyOn(pubSub, 'publish').and.callThrough();
        spyOn(pubSub, 'subscribe').and.callThrough();
        spyOn(jobRoleService, 'getContracts').and.returnValue($q.resolve({}));

        initController();
      });

      describe('when contract is created', function () {
        beforeEach(function () {
          pubSub.publish('Contract::created', contractId);
          $rootScope.$apply();
        });

        it('calls job role service to get new contract', function () {
          expect(jobRoleService.getContracts).toHaveBeenCalledWith(contractId);
        });
      });

      describe('when contract is deleted', function () {
        beforeEach(function () {
          pubSub.publish('Contract::deleted', {'contractId': contractId});
        });

        it('calls job role service to get new contract data', function () {
          expect(jobRoleService.getContracts).toHaveBeenCalledWith(contractId);
        });
      });
    });

    /**
     * Converts date string to Date Obect
     */
    function convertToDateObject (dateString) {
      return $filter('formatDate')(dateString, Date);
    }

    /**
     * Initializes the controller
     *
     * Sets the contact id on the fake parent ctrl
     */
    function initController () {
      scope = $rootScope.$new();
      settingsData = {
        'contactId': contactId
      };

      ctrl = $controller('JobRolesController', { $scope: scope, format: 'DD/MM/YYYY', settings: settingsData });
    }
  });
});
