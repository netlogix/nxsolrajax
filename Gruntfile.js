/*jslint node: true */
/*global require, module, __dirname */
module.exports = function (grunt) {

	'use strict';

	var path = require('path');

	require(path.join('../../../../', 'Gruntfile.Base'))(grunt, 'Netlogix.Nxsolrajax');
};
