(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name nx.solrajax
	 */
	var app = angular.module('nx.solrajax', [
		'ngSanitize',
		'ui.bootstrap.datepicker',
		'ui.bootstrap.typeahead'
	]);

	app.constant('nx.solrajax.searchresult.templateUrl', 'typo3conf/ext/nxsolrajax/Resources/Public/Templates/SearchResult.html?1395144505');
	app.config(['$routeProvider', 'nx.solrajax.searchresult.templateUrl', function ($routeProvider, templateUrl) {

		$routeProvider.when('/search/:path*', {
			controller: 'SearchResultCtrl',
			templateUrl: templateUrl,
			resolve: {
				searchResponse: ['$location', '$http', function ($location, $http) {
					return $http.get($location.path(), {
						params: $location.search()
					});
				}]
			}
		});

	}]);

	app.value('nx.solrajax.searchresult.initUrl', '');
	app.run(['$location', 'nx.solrajax.searchresult.initUrl', function ($location, initUrl) {
		if ($location.path() === '' && initUrl) {
			$location.path(initUrl);
		}
	}]);

	app.controller('SearchResultCtrl', ['searchResponse', '$http', '$location', '$scope', 'dateFilter', function (response, $http, $location, $scope, dateFilter) {

		$scope.loading = false;
		$scope.q = '';

		$scope.facets = response.data.facets;
		$scope.results = response.data.result;
		$scope.search = response.data.search;

		$scope.autoSuggestion = function (search) {
			return $http.get($scope.search.suggestUrl, {
				params: {
					q: search
				}
			}).then(function (ressult) {
				return ressult.data.results;
			});
		};

		$scope.submitSearch = function () {
			var queryString = $scope.q.name || $scope.q;
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

}(window, angular));