define([
    'job-roles/services/services'
], function (services) {
    'use strict';

    services.factory('HRJobRolesService',['settings', '$log' , '$q', function (settings, $log, $q) {

        return {

            getContracts: function(contact_id) {

                var deferred = $q.defer();

                // Return only the non deleted contracts
                CRM.api3('HRJobContract', 'get', {
                    "sequential": 1,
                    "contact_id": contact_id,
                    "deleted": 0,
                    "return": "title,period_end_date,period_start_date"
                }).done(function(result) {

                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(result);

                }).error(function(result) {

                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while fetching items");

                });

                // Returning the promise object
                return deferred.promise;

            },

            getContractDetails: function getContractDetails(id){
                return CRM.api3('HRJobContractRevision', 'getcurrentrevision', {
                    "sequential": 1,
                    "jobcontract_id": id
                }).then(function(result){
                    return CRM.api3('HRJobDetails', 'get', {
                        "sequential": 1,
                        "jobcontract_id": id,
                        "jobcontract_revision_id": result.values.details_revision_id
                    });
                });
            },

            getAllJobRoles: function(job_contract_ids) {

                var deferred = $q.defer();

                CRM.api3('HrJobRoles', 'get', {
                    "sequential": 1,
                    "return": "id,job_contract_id,title,description,status,funder,funder_val_type,percent_pay_funder,amount_pay_funder,cost_center,cost_center_val_type,percent_pay_cost_center,amount_pay_cost_center,level_type,location,region,department,end_date,start_date",
                    "job_contract_id": {"IN": job_contract_ids}
                }).done(function(result) {

                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(result);

                }).error(function(result) {

                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while fetching items");

                });

                // Returning the promise object
                return deferred.promise;

            },

            deleteJobRole: function(job_role_id) {

                var deferred = $q.defer();

                CRM.api3('HrJobRoles', 'delete', {
                    "sequential": 1,
                    "id": job_role_id
                }).done(function(result) {

                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(result);

                }).error(function(result) {

                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while deleting items");

                });

                // Returning the promise object
                return deferred.promise;

            },

            createJobRole: function(job_roles_data) {

                // Define funder IDs string
                var funders = "|";

                // Set the funder types
                var funder_types = "|";

                // Set the percent value for the funder
                var percent_funders = "|";

                // Set the amount value for the funder
                var amount_funders = "|";

                // Define cost_center IDs string
                var cost_centers = "|";

                // Set the cost_center types
                var cost_center_types = "|";

                // Set the percent value for the cost_center
                var percent_cost_centers = "|";

                // Set the amount value for the cost_center
                var amount_cost_centers = "|";

                // If we have any funders added, loop and save them
                if (typeof job_roles_data.funders !== "undefined") {

                    // Loop funders and set up the data to store the funders
                    for (var i = 0, l = job_roles_data.funders.length; i < l; i++) {

                        if (job_roles_data.funders[i]) {
                            funders += job_roles_data.funders[i]['funder_id']['id'] + "|";
                            funder_types += job_roles_data.funders[i]['type'] + "|";
                            percent_funders += job_roles_data.funders[i]['percentage'] + "|";
                            amount_funders += job_roles_data.funders[i]['amount'] + "|";
                        }
                    }
                }

                // If we have any cost_centers added, loop and save them
                if (typeof job_roles_data.cost_centers !== "undefined") {

                    // Loop cost_centers and set up the data to store the cost_centers
                    for (i = 0, l = job_roles_data.cost_centers.length; i < l; i++) {

                        if (job_roles_data.cost_centers[i]) {
                            cost_centers += job_roles_data.cost_centers[i]['cost_centre_id'] + "|";
                            cost_center_types += job_roles_data.cost_centers[i]['type'] + "|";
                            percent_cost_centers += job_roles_data.cost_centers[i]['percentage'] + "|";
                            amount_cost_centers += job_roles_data.cost_centers[i]['amount'] + "|";
                        }
                    }
                }

                var deferred = $q.defer();
                //FIXME 'solution' to the bug failing saving correct dates to DB a first save
                this.getNewJobRole(job_roles_data.job_contract_id).done(function(result){
                        return CRM.api3('HrJobRoles', 'create', {
                            "id": result.id,
                            "sequential": 1,
                            "job_contract_id": job_roles_data.job_contract_id,
                            "title": job_roles_data.title,
                            "description": job_roles_data.description,
                            "funder": funders,
                            "funder_val_type": funder_types,
                            "percent_pay_funder": percent_funders,
                            "amount_pay_funder": amount_funders,
                            "cost_center": cost_centers,
                            "cost_center_val_type": cost_center_types,
                            "percent_pay_cost_center": percent_cost_centers,
                            "amount_pay_cost_center": amount_cost_centers,
                            "level_type": job_roles_data.level,
                            "location": job_roles_data.location,
                            "region": job_roles_data.region,
                            "department": job_roles_data.department,
                            "start_date": job_roles_data.newStartDate,
                            "end_date": job_roles_data.newEndDate || 0
                        });
                }).done(function(response) {
                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(response);
                }).error(function(result) {
                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while adding items");
                });

                // Returning the promise object
                return deferred.promise;

            },

            updateJobRole: function(role_id, job_roles_data) {

                // Define funder IDs string
                var funders = "|";

                // Set the funder types
                var funder_types = "|";

                // Set the percent value for the funder
                var percent_funders = "|";

                // Set the amount value for the funder
                var amount_funders = "|";

                // Define cost_center IDs string
                var cost_centers = "|";

                // Set the cost_center types
                var cost_center_types = "|";

                // Set the percent value for the cost_center
                var percent_cost_centers = "|";

                // Set the amount value for the cost_center
                var amount_cost_centers = "|";

                // If we have any funders added, loop and save them
                if (typeof job_roles_data.funders !== "undefined") {
                    // Loop funders and set up the data to store the funders
                    for (var i = 0, l = job_roles_data.funders.length; i < l; i++) {

                        if (job_roles_data.funders[i]) {
                            funders += job_roles_data.funders[i]['funder_id']['id'] + "|";
                            funder_types += job_roles_data.funders[i]['type'] + "|";
                            percent_funders += job_roles_data.funders[i]['percentage'] + "|";
                            amount_funders += job_roles_data.funders[i]['amount'] + "|";
                        }
                    }
                }

                // If we have any cost_centers added, loop and save them
                if (typeof job_roles_data.cost_centers !== "undefined") {

                    // Loop cost_centers and set up the data to store the cost_centers
                    for (var i = 0, l = job_roles_data.cost_centers.length; i < l; i++) {

                        if (job_roles_data.cost_centers[i]) {
                            cost_centers += job_roles_data.cost_centers[i]['cost_centre_id'] + "|";
                            cost_center_types += job_roles_data.cost_centers[i]['type'] + "|";
                            percent_cost_centers += job_roles_data.cost_centers[i]['percentage'] + "|";
                            amount_cost_centers += job_roles_data.cost_centers[i]['amount'] + "|";
                        }
                    }
                }

                var deferred = $q.defer();

                CRM.api3('HrJobRoles', 'create', {
                    "sequential": 1,
                    "id": role_id,
                    "job_contract_id": job_roles_data.job_contract_id,
                    "title": job_roles_data.title,
                    "description": job_roles_data.description,
                    "status": job_roles_data.status,
                    "funder": funders,
                    "funder_val_type": funder_types,
                    "percent_pay_funder": percent_funders,
                    "amount_pay_funder": amount_funders,
                    "cost_center": cost_centers,
                    "cost_center_val_type": cost_center_types,
                    "percent_pay_cost_center": percent_cost_centers,
                    "amount_pay_cost_center": amount_cost_centers,
                    "level_type": job_roles_data.level,
                    "location": job_roles_data.location,
                    "region": job_roles_data.region,
                    "start_date": job_roles_data.start_date,
                    "end_date": job_roles_data.end_date || 0,
                    "department": job_roles_data.department

                }).done(function(result) {

                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(result);

                }).error(function(result) {

                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while updating items");

                });

                // Returning the promise object
                return deferred.promise;

            },

            getContactList: function(search_value) {

                var deferred = $q.defer();

                CRM.api3('Contact', 'get', {
                    "sequential": 1,
                    "return": "id,sort_name"
                }).done(function(result) {

                    // Passing data to deferred's resolve function on successful completion
                    deferred.resolve(result);

                }).error(function(result) {

                    // Sending a friendly error message in case of failure
                    deferred.reject("An error occured while fetching items");

                });

                // Returning the promise object
                return deferred.promise;

            },

            getOptionValues: function(option_group_name) {

                var deferred = $q.defer();

                // Define option group names and IDs
                var optionGroupData = {};

                CRM.api3('OptionGroup', 'get', {
                    "sequential": 1,
                    "name": { "IN": option_group_name },
                    "options": {"limit":1000}
                }).done(function(option_group_data) {

                    if (option_group_data.is_error !== 1) {

                        var option_group_ids = [];

                        angular.forEach(option_group_data['values'], function (option_group, key) {

                            // Store the option group names and IDs
                            optionGroupData[option_group['name']] = option_group['id'];

                            // Prepare option group IDs for the API call
                            option_group_ids.push(option_group['id']);

                        });

                        CRM.api3('OptionValue', 'get', {
                            "sequential": 1,
                            "option_group_id": { "IN": option_group_ids },
                            "options": {"limit":1000}
                        }).done(function(result) {

                            // Pass the additional info about optionGroupData
                            result['optionGroupData'] = optionGroupData;

                            // Passing data to deferred's resolve function on successful completion
                            deferred.resolve(result);

                        }).error(function(result) {

                            // Sending a friendly error message in case of failure
                            deferred.reject("An error occured while fetching items");

                        });

                    }

                });

                // Returning the promise object
                return deferred.promise;

            },

            getNewJobRole: function getNewJobRole(contract_id){
                //Creates new JobRole depending on contract id and returns promise
                return CRM.api3('HrJobRoles', 'create', {
                    "sequential": 1,
                    "job_contract_id": contract_id,
                    "title": ''
                });
            }

        }
    }]);

});
