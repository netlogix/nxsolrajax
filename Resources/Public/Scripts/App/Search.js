(function(angular) {
    'use strict';

    var searchPrefix = '/ajaxsearch';
    var module = angular.module('netlogix.solrajax', []);

    SearchController.$inject = ['$rootScope', '$http', '$location', 'searchResponse'];
    function SearchController($rootScope, $http, $location, response) {
        var self = this;
        self.q = $location.search().q || '';
        self.loading = false;
        self.facets = response.facets;
        self.result = response.result;
        self.search = response.search;

        self.select = function(url) {
            $location.url(url);
        };

        self.loadNext = function() {
            $rootScope.$broadcast('$solrajaxLoadMoreStart');
            self.loading = true;
            $http.get(searchPrefix + self.search.links.next, {cache: true})
                .success(function(data) {
                    self.search = data.search;
                    angular.forEach(data.result.items, function(item) {
                        self.result.items.push(item);
                    });
                    $rootScope.$broadcast('$solrajaxLoadMoreSuccess');
                    self.loading = false;
                    preloadResults();
                })
                .error(function() {
                    self.search.links.next = '';
                    $rootScope.$broadcast('$solrajaxLoadMoreError');
                    self.loading = false;
                });
        };

        self.submitSearch = function() {
            $location.search({q: self.q});
        };

        self.removeSearch = function() {
            self.q = '';
            $location.search({});
        };

        function preloadResults() {
            if (self.search.links.next !== '') {
                $http.get(searchPrefix + self.search.links.next, {cache: true});
            }
        }

        preloadResults();
    }

    module.controller('SearchController', SearchController);

}(angular));