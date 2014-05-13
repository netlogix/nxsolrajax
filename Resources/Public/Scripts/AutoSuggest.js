/*global angular:false */
(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name nx.solrajax
	 */
	var module = angular.module('nx.solrajax.autosuggest', [
		'ui.bootstrap.typeahead',
		'nx.angular.variables'
	]);


	module.value('nx.solrajax.autosuggest.targetPage', '/');
	module.value('nx.solrajax.autosuggest.suggestUrl', '/');
	module.controller('AutoSuggestCtrl', ['$scope', '$http', 'nx.solrajax.autosuggest.targetPage', 'nx.solrajax.autosuggest.suggestUrl', function($scope, $http, targetPage, suggestUrl) {

		$scope.q = '';
		$scope.suggestUrl = suggestUrl;
		$scope.targetPageUrl = targetPage + '?q=QUERY_STRING';

		$scope.getSuggestions = function (search) {
			return $http.get($scope.suggestUrl, {
				params: {
					q: search.toLowerCase()
				}
			}).then(function (ressult) {
				return ressult.data.results;
			});
		};

		$scope.submit = function () {
			var queryString = this.q.name || this.q;
			window.location.href = $scope.targetPageUrl.replace('QUERY_STRING' ,queryString);
		};

	}]);

}(window, angular));