'use strict';

var page = require('../../../../page-objects/ssp-leave-absences-manager-leave-calendar');

module.exports = function (chromy) {
  page.init(chromy).toggleContactsWithLeaves();
};
