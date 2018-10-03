/* eslint-disable no-unused-expressions */

({
  baseUrl: 'test',
  out: 'dist/reqangular.mocks.min.js',
  uglify: {
    no_mangle: true,
    max_line_length: 1000
  },
  paths: {
    'common': 'empty:',
    'common/mocks': 'mocks/'
  },
  include: [
    'common/mocks/models/instances/session-mock',
    'common/mocks/services/hr-settings-mock',
    'common/mocks/services/api/contact-mock',
    'common/mocks/services/api/contract-mock',
    'common/mocks/services/api/contact-job-role-api.api.mock',
    'common/mocks/services/api/group-mock',
    'common/mocks/services/api/group-contact-mock',
    'common/mocks/services/api/option-group-mock',
    'common/mocks/services/api/relationship-type-mock',
    'common/mocks/services/file-uploader-mock'
  ]
});
