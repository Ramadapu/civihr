
// var reqHrjr = require.config({
//     context: 'hrjobroles',
//     baseUrl: CRM.vars.hrjobroles.baseURL + '/js',
//     urlArgs: 'bust=' + (new Date()).getTime(),
//     paths: {
//         angularEditable: 'vendor/angular/xeditable.min',
//         angularFilter: 'vendor/angular/angular-filter.min',
//         requireLib: CRM.vars.reqAngular.requireLib
//     }
// });

// reqHrjr([
//     'app',
//     'controllers/HRJobRolesController',
//     'services/HRJobRolesService',
//     'directives/example'
// ],function(app){
    
// });

(function () {
    var extPath = CRM.vars.hrjobroles.baseURL + '/js/src/job-roles';

    require.config({
        urlArgs: 'bust=' + (new Date()).getTime(),
        paths: {
            'job-roles': extPath,
            'job-roles/vendor/angular-editable': extPath + '/vendor/angular/xeditable.min',
            'job-roles/vendor/angular-filter': extPath + '/vendor/angular/angular-filter.min'
        }
    });

    require([
        'job-roles/app',
        'job-roles/controllers/hr-job-roles-controller',
        'job-roles/services/hr-job-roles-service',
        'job-roles/directives/example'
    ], function (app) {
        'use strict';

        app.constant('settings', {
            classNamePrefix: 'hrjobroles-',
            contactId: decodeURIComponent((new RegExp('[?|&]cid=([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null,
            debug: true,
            pathApp: '',
            pathRest: CRM.url('civicrm/ajax/rest'),
            pathBaseUrl: CRM.vars.hrjobroles.baseURL + '/',
            pathTpl: 'views/',
            pathIncludeTpl: 'views/include/'
        });

        app.config(['settings','$routeProvider','$resourceProvider','$httpProvider','$logProvider',
            function(settings, $routeProvider, $resourceProvider, $httpProvider, $logProvider){
                $logProvider.debugEnabled(settings.debug);

                $routeProvider.
                    when('/', {
                        templateUrl: settings.pathBaseUrl + settings.pathTpl + 'mainTemplate.html?v=1',
                        resolve: {}
                    }).
                    otherwise({redirectTo:'/'});

                $resourceProvider.defaults.stripTrailingSlashes = false;

                $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
            }
        ]);

        app.run(['settings','$rootScope','$q', '$log', 'editableOptions',
            function(settings, $rootScope, $q, $log, editableOptions){
                $log.debug('app.run');

                // Set bootstrap 3 as default theme
                editableOptions.theme = 'bs3';

                // Pass the values from our settings
                $rootScope.contactId = settings.contactId;
                $rootScope.pathBaseUrl = settings.pathBaseUrl;
                $rootScope.pathTpl = settings.pathTpl;
                $rootScope.pathIncludeTpl = settings.pathIncludeTpl;
                $rootScope.prefix = settings.classNamePrefix;

            }
        ]);

        document.addEventListener('hrjobrolesLoad', function(){
            angular.bootstrap(document.getElementById('hrjobroles'), ['hrjobroles']);
        });
    });
})(require);
