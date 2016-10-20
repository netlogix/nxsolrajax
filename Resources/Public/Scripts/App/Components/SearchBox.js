(function(window, angular) {
  'use strict';

  var searchbox, module = angular.module('netlogix.solrajax.searchbox', [
    'ui.bootstrap.typeahead',
    'uib/template/typeahead/typeahead-match.html',
    'uib/template/typeahead/typeahead-popup.html'
  ]);

  searchbox = {
    bindings: {
      resultUrl: '<',
      suggestUrl: '<',
      placeholder: '@'
    },
    templateUrl: ['$element', '$attrs', function($element, $attrs) {
      return $attrs.templateUrl || 'templates/search/components/searchbox.html';
    }],
    controller: ['$window', '$http', '$location', function($window, $http, $location) {
      var self = this;

      self.q = $location.search().q || '';
      self.loading = false;
      self.active = !!$location.search().q;

      self.getSuggestions = function(search) {
        self.loading = true;
        return $http.get(self.suggestUrl, {
          params: {
            q: search.toLowerCase()
          },
          cache: true
        }).then(function(result) {
          self.loading = false;
          return result.data;
        });
      };

      self.submitSearch = function($element) {
        var queryString = (self.loading && angular.isDefined($element)) ? $element.val() : self.q.name || self.q;
        if (self.resultUrl === $location.path()) {
          $location.search({q: queryString});
        } else if (self.resultUrl.indexOf('?') !== -1) {
          $window.location.href = self.resultUrl + '&q=' + queryString;
        } else {
          $window.location.href = self.resultUrl + '?q=' + queryString;
        }
      };

      self.removeSearch = function() {
        $location.search({});
      };

      self.reset = function() {
        self.q = '';
      };
    }]
  };

  module.component('searchbox', searchbox);

}(window, angular));
