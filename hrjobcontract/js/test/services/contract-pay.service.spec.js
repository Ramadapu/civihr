/* eslint-env amd, jasmine */

define([
  'mocks/data/contract',
  'job-contract/modules/job-contract.module'
], function (MockContract) {
  'use strict';

  describe('ContractPayService', function () {
    var $httpBackend, $rootScope, ContractPayService;

    beforeEach(module('job-contract'));
    beforeEach(inject(function (_ContractPayService_, _$httpBackend_, _$rootScope_) {
      ContractPayService = _ContractPayService_;
      $httpBackend = _$httpBackend_;
      $rootScope = _$rootScope_;

      $httpBackend.whenGET(/action=get&entity=HRJobContract/).respond(MockContract.contractPayment);
      $httpBackend.whenGET(/action=get&entity=HRJobPay/).respond({});
      $httpBackend.whenGET(/views.*/).respond({});
    }));

    afterEach(function () {
      $httpBackend.flush();
      $rootScope.$apply();
    });

    describe('getOne()', function () {
      it('calls getOne() and returns expected contract payment data', function () {
        ContractPayService.getOne({ jobcontract_revision_id: 68 }).then(function (result) {
          expect(result).toEqual(MockContract.contractPayment.values[0]);
        });
      });
    });
  });
});
