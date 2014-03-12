(function (window, angular, undefined) {
	'use strict';

	/**
	 * @name nx.solrajax
	 */
	var app = angular.module('nx.solrajax', [
		'ngSanitize',
		'ui.bootstrap.datepicker'
	]);

	app.run(['$location', 'nx.solrajax.searchresult.initUrl', function ($location, initUrl) {
		if ($location.path() == '' && initUrl) {
			$location.path(initUrl);
		}
	}]);

	app.constant('nx.solrajax.searchresult.templateUrl', 'typo3conf/ext/nxsolrajax/Resources/Public/Templates/SearchResult.html');

	app.config(['$routeProvider', 'nx.solrajax.searchresult.templateUrl', function ($routeProvider, templateUrl) {

		$routeProvider.when('/search/:path*', {
			controller: 'SearchResultCtrl',
			templateUrl: templateUrl,
			resolve: {
				searchResponse: app.loadSearchResults
			}
		});

	}]);

	app.controller('SearchResultCtrl', ['searchResponse', '$http', '$location', '$scope', function(response, $http, $location, $scope) {

		$scope.loading = false;

		$scope.facets = response.data.facets;

		$scope.results = response.data.result;

		$scope.search = function() {

		};

		$scope.select = function(target) {
			$location.path(target);
		};

		$scope.loadPrev = function() {
			$scope.loading = true;
			$http.get($scope.results.prevLink).then(function(response) {
				$scope.results.prevLink = response.data.result.prevLink || '';

				// Add new documents to scope
				angular.forEach(response.data.result.resultDocuments.reverse(), function(resultDocument) {
					$scope.results.resultDocuments.unshift(resultDocument);
				});
				$scope.loading = false;
			});
		};

		$scope.loadNext = function() {
			$scope.loading = true;
			$http.get($scope.results.nextLink, {cache: true}).then(function(response) {
				$location.replace($scope.results.nextLink);
				$scope.results.nextLink = response.data.result.nextLink || '';

				// Add new documents to scope
				angular.forEach(response.data.result.resultDocuments, function(resultDocument) {
					$scope.results.resultDocuments.push(resultDocument);
				});
				$scope.loading = false;
			});
		};

	}]);

	app.loadSearchResults = ['$location', '$http', function ($location, $http) {
		return $http.get($location.path());
	}];

}(window, angular));