/*global angular:false */
(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name nx.solrajax
	 */
	var module = angular.module('netlogix.solrajax.autosuggest', [
		'ui.bootstrap.typeahead'
	]);

	module.value('netlogix.solrajax.autosuggest.targetPage', '/');
	module.value('netlogix.solrajax.autosuggest.suggestUrl', '/');
	module.controller('AutoSuggestCtrl', ['$http', 'netlogix.solrajax.autosuggest.targetPage', 'netlogix.solrajax.autosuggest.suggestUrl', function($http, targetPage, suggestUrl) {
		var self = this;

		self.q = '';
		self.suggestUrl = suggestUrl;
		self.targetPageUrl = targetPage + '?q=QUERY_STRING';
		self.loading = false;
		self.active = false;

		self.getSuggestions = function (search) {
			return $http.get(self.suggestUrl, {
				params: {
					q: search.toLowerCase()
				},
				cache: true
			}).then(function (ressult) {
				return ressult.data.results;
			});
		};

		self.submitSearch = function ($element) {
			var queryString = (self.suggestLoading && angular.isDefined($element)) ? $element.val() : self.q.name || self.q;
			if (queryString === '') {
				return;
			}
			window.location.href = self.targetPageUrl.replace('QUERY_STRING', queryString);
		};

		self.reset = function () {
			self.q = '';
		};

		self.show = function () {
			self.active = true;
		};

		self.hide = function () {
			self.active = false;
		};

	}]);

}(window, angular));