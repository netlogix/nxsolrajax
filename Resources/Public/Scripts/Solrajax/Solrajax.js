/* global angular:ture */
(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name netlogix.solrajax.solrajax
	 */
	var app = angular.module('netlogix.solrajax.solrajax', [
		'ngRoute',
		'ngSanitize',
		'ui.bootstrap.typeahead'
	]);

	app.config(['$routeProvider', function ($routeProvider) {

		$routeProvider.when('/ajaxsearch/:path*', {
			controller: 'SearchResultCtrl',
			templateUrl: 'templates/solrajax/searchresult.html',
			resolve: {
				searchResponse: ['$location', '$http', function ($location, $http) {
					return $http.get($location.path(), {
						params: $location.search(),
						cache: true
					});
				}]
			}
		});

	}]);

	app.controller('SearchResultCtrl', ['searchResponse', '$http', '$location', '$scope', '$rootScope', 'dateFilter', function (response, $http, $location, $scope, $rootScope, dateFilter) {

		$scope.showFacetFilters = false;
		$scope.loading = false;
		$scope.suggestLoading = false;
		$scope.q = response.data.search.q || '';

		$scope.facets = response.data.facets;
		$scope.results = response.data.result;
		$scope.search = response.data.search;

		$scope.typeFacet = function() {
			if (angular.isUndefined($scope.facets.availableFacets)) {
				return false;
			}
			return $scope.facets.availableFacets.filter(function(facet) {return facet.label ? facet.name.toLowerCase() === 'type' : false;})[0];
		};

		$scope.autoSuggestion = function (search) {
			return $http.get($scope.search.suggestUrl, {
				params: {
					q: search.toLowerCase()
				},
				cache: true
			}).then(function (ressult) {
				return ressult.data.results;
			});
		};

		$scope.submitSearch = function ($element) {
			var queryString = ($scope.suggestLoading && angular.isDefined($element)) ? $element.val() : this.q.name || this.q;
			$location.path($scope.search.url.replace('QUERY_STRING', queryString));
		};

		$scope.removeSearch = function () {
			$location.path($scope.search.url.replace('QUERY_STRING', ''));
		};

		$scope.select = function (target) {
			$location.path(target);
		};

		$scope.selectDate = function (option) {
			var start, end, target;

			if (angular.isObject(option.range)) {
				option.start = option.range.start;
				option.end = option.range.end;
			}

			start = option.start ? dateFilter(option.start, 'yyyyMMdd') : '*';
			end = option.end ? dateFilter(option.end, 'yyyyMMdd') : '*';

			if (!option.start && !option.end) {
				target = option.reseturl;
			} else {
				target = decodeURI(option.url).replace(encodeURI('{filterValue}'), encodeURI(start + option.delimiter + end));
			}

			$location.path(target);
		};

		$scope.removeDate = function (option) {
			option.start = '';
			option.end = '';
			option.range = '';
			$scope.selectDate(option);
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
			$rootScope.$broadcast('$solrajaxLoadMoreStart', $scope.results.prevLink);
			$scope.loading = true;
			$http.get($scope.results.prevLink, {cache: true})
				.success(function (data) {
					$rootScope.$broadcast('$solrajaxLoadMoreSuccess', $scope.results.prevLink);
					$scope.results.prevLink = data.result.prevLink || '';

					// Add new documents to scope
					angular.forEach(data.result.resultDocuments.reverse(), function (resultDocument) {
						$scope.results.resultDocuments.unshift(resultDocument);
					});
					$scope.loading = false;
					preloadResults();
				})
				.error(function() {
					$rootScope.$broadcast('$solrajaxLoadMoreError', $scope.results.prevLink);
					$scope.results.prevLink = '';
					$scope.loading = false;
				});
		};

		$scope.loadNext = function () {
			$rootScope.$broadcast('$solrajaxLoadMoreStart', $scope.results.nextLink);
			$scope.loading = true;
			$http.get($scope.results.nextLink, {cache: true})
				.success(function (data) {
					$rootScope.$broadcast('$solrajaxLoadMoreSuccess', $scope.results.nextLink);
					$scope.results.nextLink = data.result.nextLink || '';

					// Add new documents to scope
					angular.forEach(data.result.resultDocuments, function (resultDocument) {
						$scope.results.resultDocuments.push(resultDocument);
					});
					$scope.loading = false;
					preloadResults();
				})
				.error(function() {
					$rootScope.$broadcast('$solrajaxLoadMoreError', $scope.results.nextLink);
					$scope.results.nextLink = '';
					$scope.loading = false;
				});
		};

		$scope.isSiteActive = function(name) {
			return $scope.search.site.selected === name;
		};

		$scope.selectSite = function(name) {
			if (!angular.isUndefined($scope.search.site[name])) {
				$scope.select($scope.search.site[name]);
			}
		};

		function preloadResults() {
			if ($scope.results.prevLink) {
				$http.get($scope.results.prevLink, {cache: true});
			}
			if ($scope.results.nextLink) {
				$http.get($scope.results.nextLink, {cache: true});
			}
		}

		preloadResults();

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