/* eslint-env amd, jasmine */

define([
  'common/lodash',
  'common/mocks/data/contact.data',
  'common/mocks/data/contract.data',
  'common/angularMocks',
  'common/models/contact',
  'common/models/group',
  'common/models/contact-job-role.model',
  'common/mocks/models/instances/session-mock',
  'common/mocks/services/hr-settings-mock',
  'common/mocks/services/api/contact-mock',
  'common/mocks/services/api/contract-mock',
  'common/mocks/services/api/contact-job-role-api.api.mock',
  'common/mocks/models/instances/contact-instance-mock',
  'common/services/api/contract'
], function (_, contactData, jobContractData) {
  'use strict';

  describe('Contact', function () {
    var $provide, $rootScope, Contact, ContactInstanceMock, contactAPI,
      contactAPIMock, jobContractAPI, ContactJobRole, ContactJobRoleAPI, contactJobRoles,
      contacts, Group, groupContactAPIMock, groupContacts, SessionMock;

    beforeEach(function () {
      module('common.models', 'common.mocks', function (_$provide_) {
        $provide = _$provide_;
      });
      inject([
        'api.contact.mock', 'api.contract.mock', 'ContactJobRoleAPIMock', 'HR_settingsMock',
        function (_contactAPIMock_, jobContractAPIMock, ContactJobRoleAPIMock, HRSettingsMock) {
          contactAPIMock = _contactAPIMock_;
          $provide.value('api.contact', contactAPIMock);
          $provide.value('api.contract', jobContractAPIMock);
          $provide.value('ContactJobRoleAPI', ContactJobRoleAPIMock);
          $provide.value('HR_settings', HRSettingsMock);
        }
      ]);
    });

    beforeEach(inject([
      '$rootScope', 'api.contact', 'api.contract', 'api.group-contact.mock',
      'Contact', 'ContactInstanceMock', 'ContactJobRole', 'ContactJobRoleAPI',
      'Group', 'SessionMock',
      function (_$rootScope_, _contactAPI_, _jobContractAPI_, _groupContactAPIMock_, _Contact_,
        _ContactInstanceMock_, _ContactJobRole_, _ContactJobRoleAPI_, _Group_,
        _SessionMock_) {
        $rootScope = _$rootScope_;
        Contact = _Contact_;
        Group = _Group_;
        ContactJobRole = _ContactJobRole_;
        ContactInstanceMock = _ContactInstanceMock_;
        contactAPI = _contactAPI_;
        jobContractAPI = _jobContractAPI_;
        ContactJobRoleAPI = _ContactJobRoleAPI_;
        groupContactAPIMock = _groupContactAPIMock_;
        SessionMock = _SessionMock_;

        contactAPI.spyOnMethods();
        ContactJobRoleAPI.spyOnMethods();

        contacts = contactAPI.mockedContacts().list;
        contactJobRoles = ContactJobRoleAPI.mockedContactJobRoles.list;
        groupContacts = groupContactAPIMock.mockedGroupsContacts.list;
      }
    ]));

    it('has the expected api', function () {
      expect(Object.keys(Contact)).toEqual([
        'all',
        'getStaff',
        'find',
        'getLoggedIn',
        'leaveManagees'
      ]);
    });

    describe('all()', function () {
      describe('instances', function () {
        var resultsAreInstances;

        beforeEach(function () {
          Contact.all().then(function (response) {
            resultsAreInstances = response.list.every(function (contact) {
              return ContactInstanceMock.isInstance(contact);
            });
          });
          $rootScope.$digest();
        });

        it('returns a list of model instances', function () {
          expect(resultsAreInstances).toBe(true);
        });
      });

      describe('when called without arguments', function () {
        var response;

        beforeEach(function () {
          Contact.all().then(function (_response_) { response = _response_; });
          $rootScope.$digest();
        });

        it('returns all contacts', function () {
          expect(contactAPI.all).toHaveBeenCalled();
          expect(response.list.length).toEqual(contacts.length);
        });
      });

      describe('contact api called with right parameters', function () {
        var filter = {display_name: 'kri'};
        var pagination = 'page';
        var sort = 'display_name';
        var additionalParams = 'additionalParams';

        afterEach(function () {
          $rootScope.$digest();
        });

        it('passes the filters to the api', function () {
          Contact.all(filter, pagination, sort, additionalParams).then(function () {
            expect(contactAPI.all).toHaveBeenCalledWith(filter, pagination, sort, additionalParams);
          });
        });
      });

      describe('filters', function () {
        describe('when called with filters', function () {
          var partialName = 'kri';

          beforeEach(function () {
            Contact.all({ display_name: partialName });
            $rootScope.$digest();
          });

          it('passes the filters to the api', function () {
            expect(contactAPI.all).toHaveBeenCalledWith({ display_name: partialName }, undefined, undefined, undefined);
          });
        });

        describe('when called with job roles filters', function () {
          var jobRolesFilters = {
            department: '2',
            level_type: '1'
          };

          beforeEach(function () {
            spyOn(ContactJobRole, 'all').and.callThrough();
            Contact.all(_.assign({ display_name: 'foo' }, jobRolesFilters));
            $rootScope.$digest();
          });

          it('passes the filters to the JobRole model', function () {
            expect(ContactJobRole.all).toHaveBeenCalledWith(jasmine.objectContaining(jobRolesFilters));
          });

          it('does not pass the filters to its api', function () {
            expect(contactAPI.all).not.toHaveBeenCalledWith(jasmine.objectContaining(jobRolesFilters), undefined);
          });
        });

        describe('when called with a group id filter', function () {
          var groupIdFilter = { group_id: '3' };

          beforeEach(function () {
            spyOn(Group, 'contactIdsOf').and.callThrough();
            Contact.all(_.assign({ display_name: 'foo' }, groupIdFilter));
            $rootScope.$digest();
          });

          it('passes the filter to the Group model', function () {
            expect(Group.contactIdsOf).toHaveBeenCalledWith(groupIdFilter.group_id);
          });

          it('does not pass the filters to its api', function () {
            expect(contactAPI.all).not.toHaveBeenCalledWith(jasmine.objectContaining(groupIdFilter), undefined);
          });

          it('passes to its api the ids of the contacts belonging to the group', function () {
            expect(contactAPI.all).toHaveBeenCalledWith(jasmine.objectContaining({
              display_name: 'foo',
              id: {'IN': jasmine.any(Array)}
            }), undefined, undefined, undefined);
          });
        });

        describe('when filter includes a period for contracts', function () {
          var result;
          var sampleDates = {
            from: '2018-01-01 00:00:00',
            to: '2018-12-31 23:59:59'
          };

          beforeEach(function (done) {
            spyOn(jobContractAPI, 'getContactsWithContractsInPeriod').and.callThrough();
            Contact.all({
              with_contract_in_period: [
                sampleDates.from,
                sampleDates.to
              ]
            })
              .then(function (_result_) {
                result = _result_;
              })
              .finally(done);
            $rootScope.$digest();
          });

          it('calls the job contract API', function () {
            expect(jobContractAPI.getContactsWithContractsInPeriod)
              .toHaveBeenCalledWith(sampleDates.from, sampleDates.to);
          });

          it('returns only the contacts with job contracts', function () {
            var sampleContact = _.find(contactData.all.values, {
              id: _.first(jobContractData.contactsWithContractsInPeriod.values).id
            });

            expect(result.list).toEqual([sampleContact]);
          });
        });

        describe('when passing a mix of foreign model keys', function () {
          var mixedFilters = {};

          beforeEach(function () {
            mixedFilters.department = contactJobRoles[0].department;
            mixedFilters.group_id = groupContacts[0].group_id;

            Contact.all(_.assign({ display_name: 'foo' }, mixedFilters));
            $rootScope.$digest();
          });

          it('passes to its api the intersection of the contact ids returned by the models', function () {
            expect(contactAPI.all).toHaveBeenCalledWith(jasmine.objectContaining({
              display_name: 'foo',
              id: { 'IN': contactIdsIntersection(mixedFilters) }
            }), undefined, undefined, undefined);
          });

          /**
           * Returns the intersection of all the contact ids returned
           * by the models
           *
           * @param {object} mixedFilters
           * @return {Array}
           */
          function contactIdsIntersection (mixedFilters) {
            var groupContactIds = groupContacts.filter(function (groupContact) {
              return groupContact.group_id === mixedFilters.group_id;
            }).map(function (groupContact) {
              return groupContact.contact_id;
            });
            var contactJobRoleContactIds = contactJobRoles.filter(function (contactJobRole) {
              return contactJobRole.department === mixedFilters.department;
            }).map(function (contactJobRole) {
              return contactJobRole.contact_id;
            });

            return _.intersection(groupContactIds, contactJobRoleContactIds);
          }
        });
      });

      describe('when called with pagination', function () {
        var response;
        var pagination = { page: 3, size: 2 };

        beforeEach(function () {
          Contact.all(null, pagination).then(function (_response_) {
            response = _response_;
          });
          $rootScope.$digest();
        });

        it('can paginate the contacts list', function () {
          expect(contactAPI.all).toHaveBeenCalledWith(null, pagination, undefined, undefined);
          expect(response.list.length).toEqual(2);
        });
      });
    });

    describe('find()', function () {
      var contact;
      var targetId = '2';

      beforeEach(function () {
        Contact.find(targetId).then(function (_contact_) {
          contact = _contact_;
        });
        $rootScope.$digest();
      });

      it('finds a contact by id', function () {
        expect(contactAPI.find).toHaveBeenCalledWith(targetId);
        expect(contact.id).toBe(targetId);
      });

      it('returns an instance of the model', function () {
        expect(ContactInstanceMock.isInstance(contact)).toBe(true);
      });
    });

    describe('getLoggedIn()', function () {
      var result, loggedInContact;

      beforeEach(function () {
        var loggedInContactId;

        Contact.getLoggedIn()
          .then(function (_result_) {
            result = _result_;
          });
        SessionMock.get()
          .then(function (session) {
            loggedInContactId = session.contactId;
          });
        $rootScope.$digest();

        loggedInContact = _.find(contactData.all.values, { id: loggedInContactId });
      });

      it('resolves with a currently logged in contact', function () {
        expect(result).toEqual(loggedInContact);
      });
    });

    describe('leaveManagees()', function () {
      var contactID = '123';
      var params = { key: 'value' };

      afterEach(function () {
        $rootScope.$digest();
      });

      it('calls leaveManagees function of contact API with same parameters', function () {
        Contact.leaveManagees(contactID, params).then(function () {
          expect(contactAPI.leaveManagees).toHaveBeenCalledWith(contactID, params);
        });
      });

      it('returns contacts managed by the contact id', function () {
        Contact.leaveManagees(contactID, params).then(function (data) {
          expect(data).toEqual(contactAPIMock.mockedContacts().list);
        });
      });
    });
  });
});
