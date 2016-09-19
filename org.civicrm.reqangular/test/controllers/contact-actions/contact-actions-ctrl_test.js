/* eslint angular/di: 0, jasmine/no-spec-dupes: 0, jasmine/no-suite-dupes: 0 */

define([
  'common/angularMocks',
  'common/controllers/contact-actions/contact-actions-ctrl'
], function (ctrl) {
  'use strict';

  describe('ContactActionsCtrl', function () {
    var $scope, ctrl, modalSpy, contactActionsSpy;

    beforeEach(module('common.apis', 'common.controllers'));

    /**
     * Spies setup
     */
    beforeEach(inject(function ($q) {
      modalSpy = jasmine.createSpyObj('modalSpy', ['open']);
      contactActionsSpy = jasmine.createSpyObj('contactActionsSpy', [
        'getContactTypeOptions', 'getGroupOptions', 'getTagOptions', 'getStateProvinceOptions',
        'getCountryOptions', 'getGenderOptions', 'getDeceasedOptions'
      ]);
      contactActionsSpy.getContactTypeOptions.and.returnValue($q.resolve('contact type options'));
      contactActionsSpy.getGroupOptions.and.returnValue($q.resolve('group options'));
      contactActionsSpy.getTagOptions.and.returnValue($q.resolve('tag options'));
      contactActionsSpy.getStateProvinceOptions.and.returnValue($q.resolve('state / province options'));
      contactActionsSpy.getCountryOptions.and.returnValue($q.resolve('country options'));
      contactActionsSpy.getGenderOptions.and.returnValue($q.resolve('gender options'));
      contactActionsSpy.getDeceasedOptions.and.returnValue($q.resolve('deceased options'));
    }));

    beforeEach(inject(function (_$controller_, _$rootScope_) {
      $scope = _$rootScope_.$new();
      ctrl = _$controller_('ContactActionsCtrl', {
        '$scope': $scope,
        '$uibModal': modalSpy,
        'api.contactActions': contactActionsSpy
      });
    }));

    describe('refineSearch', function () {
      describe('on change selected', function() {
        beforeEach(function () {
          $scope.$apply();
          spyOn($scope, '$emit');

          ctrl.refineSearch.selected.field = {
            label: 'selected field'
          };
          ctrl.refineSearch.selected.option = {
            value: 'selected option'
          };
          $scope.$apply();
        });

        it('emits the "contactRefineSearch" event', function () {
          expect($scope.$emit).toHaveBeenCalledWith('contactRefineSearch', {
            field: 'selected field',
            option: 'selected option'
          });
        });
      });

      describe('availableFields', function() {
        it('has "Contact Type"', function() {
          expect(ctrl.refineSearch.availableFields[0].label).toEqual('Contact Type');
          expect(typeof ctrl.refineSearch.availableFields[0].getOptions === 'function').toBeTruthy();
        });

        it('has "Group"', function() {
          expect(ctrl.refineSearch.availableFields[1].label).toEqual('Group');
          expect(typeof ctrl.refineSearch.availableFields[1].getOptions === 'function').toBeTruthy();
        });

        it('has "Tag"', function() {
          expect(ctrl.refineSearch.availableFields[2].label).toEqual('Tag');
          expect(typeof ctrl.refineSearch.availableFields[2].getOptions === 'function').toBeTruthy();
        });

        it('has "State / Province"', function() {
          expect(ctrl.refineSearch.availableFields[3].label).toEqual('State / Province');
          expect(typeof ctrl.refineSearch.availableFields[3].getOptions === 'function').toBeTruthy();
        });

        it('has "Country"', function() {
          expect(ctrl.refineSearch.availableFields[4].label).toEqual('Country');
          expect(typeof ctrl.refineSearch.availableFields[4].getOptions === 'function').toBeTruthy();
        });

        it('has "Gender"', function() {
          expect(ctrl.refineSearch.availableFields[5].label).toEqual('Gender');
          expect(typeof ctrl.refineSearch.availableFields[5].getOptions === 'function').toBeTruthy();
        });

        it('has "Deceased"', function() {
          expect(ctrl.refineSearch.availableFields[6].label).toEqual('Deceased');
          expect(typeof ctrl.refineSearch.availableFields[6].getOptions === 'function').toBeTruthy();
        });
      });

      describe('Contact Type', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[0];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getContactTypeOptions', function () {
            expect(contactActionsSpy.getContactTypeOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('contact type options');
          });
        });
      });

      describe('Group', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[1];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getGroupOptions', function () {
            expect(contactActionsSpy.getGroupOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('group options');
          });
        });
      });

      describe('Tag', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[2];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getTagOptions', function () {
            expect(contactActionsSpy.getTagOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('tag options');
          });
        });
      });

      describe('State / Province', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[3];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getStateProvinceOptions', function () {
            expect(contactActionsSpy.getStateProvinceOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('state / province options');
          });
        });
      });

      describe('Country', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[4];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getCountryOptions', function () {
            expect(contactActionsSpy.getCountryOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('country options');
          });
        });
      });

      describe('Gender', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[5];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getGenderOptions', function () {
            expect(contactActionsSpy.getGenderOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('gender options');
          });
        });
      });

      describe('Deceased', function () {
        beforeEach(function () {
          // Set the selected field
          ctrl.refineSearch.selected.field = ctrl.refineSearch.availableFields[6];
        });

        describe('when refineSearch.availableOptions.refresh is called', function () {
          beforeEach(function () {
            ctrl.refineSearch.availableOptions.refresh();
            $scope.$apply();
          });

          it('calls contactActions.getDeceasedOptions', function () {
            expect(contactActionsSpy.getDeceasedOptions).toHaveBeenCalled();
          });

          it('sets refineSearch.availableOptions.options', function () {
            expect(ctrl.refineSearch.availableOptions.options).toEqual('deceased options');
          });
        });
      });
    });

    describe('showNewIndividualModal', function () {
      beforeEach(function () {
        ctrl.showNewIndividualModal();
      });

      it('opens the modal', function () {
        expect(modalSpy.open).toHaveBeenCalled();
      });
    });

    describe('showNewHouseholdModal', function () {
      beforeEach(function () {
        ctrl.showNewHouseholdModal();
      });

      it('opens the modal', function () {
        expect(modalSpy.open).toHaveBeenCalled();
      });
    });

    describe('showNewOrganizationModal', function () {
      beforeEach(function () {
        ctrl.showNewOrganizationModal();
      });

      it('opens the modal', function () {
        expect(modalSpy.open).toHaveBeenCalled();
      });
    });
  });
});
