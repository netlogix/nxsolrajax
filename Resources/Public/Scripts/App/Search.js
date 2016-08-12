(function(angular) {
    'use strict';

    var searchPrefix = '/ajaxsearch';
    var module = angular.module('netlogix.solrajax', []);

    SearchController.$inject = ['searchResponse', '$http', '$location'];
    function SearchController(response, $http, $location) {
        var self = this;
        self.loading = false;
        self.facets = response.facets;
        self.result = response.result;
        self.search = response.search;

        self.select = function(url) {
            $location.url(url);
        };

        self.loadNext = function() {
            self.loading = true;
            $http.get(searchPrefix + self.search.links.next, {cache: true})
                .success(function(data) {
                    self.search = data.search;
                    angular.forEach(data.result.items, function(item) {
                        self.result.items.push(item);
                    });
                    self.loading = false;
                    preloadResults();
                })
                .error(function() {
                    self.search.links.next = '';
                    self.loading = false;
                });
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