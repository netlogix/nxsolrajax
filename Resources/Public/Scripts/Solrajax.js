/* global angular:ture */
(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name nx.solrajax
	 */
	var app = angular.module('nx.solrajax', [
		'ngSanitize',
		'ui.bootstrap.datepicker',
		'ui.bootstrap.typeahead',
		'nx.angular.variables'
	]);

	app.config(['$routeProvider', 'nxConfigurationServiceProvider', function ($routeProvider, configurationService) {

		$routeProvider.when('/ajaxsearch/:path*', {
			controller: 'SearchResultCtrl',
			templateUrl: configurationService.value('nx.solrajax.searchresult.templateUrl'),
			resolve: {
				searchResponse: ['$location', '$http', function ($location, $http) {
					return $http.get($location.path(), {
						params: $location.search()
					});
				}]
			}
		});

	}]);

	app.controller('SearchResultCtrl', ['searchResponse', '$http', '$location', '$scope', 'dateFilter', function (response, $http, $location, $scope, dateFilter) {

		$scope.showFacetFilters = false;
		$scope.loading = false;
		$scope.q = response.data.search.q || '';

		$scope.facets = response.data.facets;
		$scope.results = response.data.result;
		$scope.search = response.data.search;

		$scope.typeFacet = function() {
			if (angular.isUndefined($scope.facets.availableFacets)) {
				return false;
			}
			return $scope.facets.availableFacets.filter(function(facet) {return facet.label ? facet.label.toLowerCase() === 'type' : false;})[0];
		};

		$scope.autoSuggestion = function (search) {
			return $http.get($scope.search.suggestUrl, {
				params: {
					q: search.toLowerCase()
				}
			}).then(function (ressult) {
				return ressult.data.results;
			});
		};

		$scope.submitSearch = function () {
			var queryString = this.q.name || this.q;
			$location.path($scope.search.url + '?q=' + queryString);
		};

		$scope.removeSearch = function () {
			$location.path($scope.search.url);
		};

		$scope.select = function (target) {
			$location.path(target);
		};

		$scope.selectDate = function (option) {
			var start, end, target;

			start = option.start ? dateFilter(option.start, 'yyyyMMdd') : '*';
			end = option.end ? dateFilter(option.end, 'yyyyMMdd') : '*';

			if (!option.start && !option.end) {
				target = option.reseturl;
			} else {
				target = decodeURI(option.url).replace(encodeURI('{filterValue}'), encodeURI(start + option.delimiter + end));
			}

			$location.path(target);
		};

		$scope.removeStartDate = function (option) {
			option.start = '';
			$scope.selectDate(option);
		};

		$scope.removeEndDate = function (option) {
			option.end = '';
			$scope.selectDate(option);
		};

		$scope.isDateSelected = function() {
			var selected = false;
			angular.forEach($scope.facets.availableFacets, function(facet) {
				if ((facet.type === 'queryGroup' || facet.type === 'dateRange') && facet.active) {
					selected = true;
				}
			});

			return selected;
		};

		$scope.loadPrev = function () {
			$scope.loading = true;
			$http.get($scope.results.prevLink).then(function (response) {
				$scope.results.prevLink = response.data.result.prevLink || '';

				// Add new documents to scope
				angular.forEach(response.data.result.resultDocuments.reverse(), function (resultDocument) {
					$scope.results.resultDocuments.unshift(resultDocument);
				});
				$scope.loading = false;
			});
		};

		$scope.loadNext = function () {
			$scope.loading = true;
			$http.get($scope.results.nextLink, {cache: true}).then(function (response) {
				$location.replace($scope.results.nextLink);
				$scope.results.nextLink = response.data.result.nextLink || '';

				// Add new documents to scope
				angular.forEach(response.data.result.resultDocuments, function (resultDocument) {
					$scope.results.resultDocuments.push(resultDocument);
				});
				$scope.loading = false;
			});
		};

	}]);

	/**
	 * See http://docs.angularjs.org/api/ng/service/$sce#trustAsHtml
	 */
	app.directive('nxBindResult', ['$sce', '$parse', '$compile', function($sce, $parse, $compile) {
		return function(scope, element, attr) {
			element.addClass('ng-binding').data('$binding', attr.nxBindResult);

			var parsed = $parse(attr.nxBindResult);

			function getStringValue() {
				return (parsed(scope) || '').toString();
			}

			scope.$watch(getStringValue, function ngBindHtmlWatchAction() {
				element.html($compile($sce.getTrustedHtml($sce.trustAsHtml(parsed(scope))))(scope) || '');
			});
		};
	}]);

}(window, angular));