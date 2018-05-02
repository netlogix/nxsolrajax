(function() {
  'use strict';

  describe('Searchbox component', function() {

    var component, suggestResult, $window, $rootScope, $componentController, $httpBackend, $location;

    beforeEach(module('netlogix.solrajax.searchbox'));

    beforeEach(inject(function(_$rootScope_, _$componentController_, _$httpBackend_, _$location_) {
      $componentController = _$componentController_;
      $rootScope = _$rootScope_;
      $httpBackend = _$httpBackend_;
      $location = _$location_;

      suggestResult = [{name: 'netlogix Web Solutions'}];
      $httpBackend.when('GET', '/suggest/?q=netlogix').respond(200, suggestResult);

      $window = {location: {href: ''}};

      component = $componentController('searchbox', {$scope: $rootScope.$new(), $window: $window, $location: $location}, {
        resultUrl: '/search/',
        suggestUrl: '/suggest/'
      });
    }));

    afterEach(function() {
      $httpBackend.verifyNoOutstandingExpectation();
      $httpBackend.verifyNoOutstandingRequest();
    });

    it('should exist', function() {
      expect(component).toBeDefined();
    });

    it('should reset query string', function() {
      expect(component.q).toBe('');
      component.q = 'netlogix';
      expect(component.q).toBe('netlogix');
      component.reset();
      expect(component.q).toBe('');
    });

    it('should submit search', function() {
      component.q = 'netlogix';
      component.submitSearch();
      expect($window.location.href).toBe('/search/?q=netlogix');
    });

    it('should only change the querystring when the suggester is used on the result page', function() {
      $location.path('/search/');
      component.q = 'netlogix';
      component.submitSearch();
      expect($window.location.href).toBe('');
      expect($location.search()).toEqual({q: 'netlogix'});
    });

    it('should remove querystring from current url', function() {
      $location.url('/search/?q=netlogix');
      component.removeSearch();
      expect($location.url()).toBe('/search/');
    });

    it('should set loading while searching', function() {
      expect(component.loading).toBe(false);
      component.getSuggestions('netlogix');
      expect(component.loading).toBe(true);
      $httpBackend.flush();
      expect(component.loading).toBe(false);
    });

    it('should fetch suggestions', function() {
      var result = [];
      component.getSuggestions('netlogix').then(function(data) {
        result = data;
      });
      $httpBackend.flush();
      expect(result).toEqual(suggestResult);
    });

    describe('templateUrl', function() {
      var element;
      beforeEach(inject(function(_$compile_, _$templateCache_) {
        _$templateCache_.put('templates/search/components/searchbox.html', '<div><input type="search"></div>');
        element = _$compile_('<searchbox result-url="\'/search/\'" result-url="\'/suggest/\'"></searchbox>')($rootScope);
        $rootScope.$apply();
      }));

      afterEach(function() {
        element.remove();
      });

      it('should create component with default template', function() {
        expect(element[0].querySelectorAll('input').length).toEqual(1);
      });

    });

  });

})();
