(function() {
    'use strict';

    describe('Search component', function() {

        var controller, searchResponse, $httpBackend, $controller, $location;

        beforeEach(module('netlogix.solrajax'));

        beforeEach(inject(function(_$controller_, _$httpBackend_, _$location_) {
            $controller = _$controller_;
            $httpBackend = _$httpBackend_;
            $location = _$location_;

            searchResponse = {
                search: {
                    q: 'foo',
                    suggestion: false,
                    links: {
                        next: '/en/search/?q=foo&page=2',
                        prev: '',
                        reset: '/en/search/?q=foo',
                        search: '/en/search/?q=QUERY_STRING'
                    }
                },
                facets: [{
                    label: 'Type',
                    name: 'type',
                    type: 'options',
                    used: true,
                    options: [{
                        selected: false,
                        count: 12,
                        label: 'Page',
                        links: {
                            self: '/en/search/?q=netlogix&tx_solr%5Bfilter%5D%5B0%5D=type%3Apage'
                        }
                    }, {
                        selected: false,
                        count: 1,
                        label: 'Press',
                        links: {
                            self: '/en/search/?q=netlogix&tx_solr%5Bfilter%5D%5B0%5D=type%3Apress'
                        }
                    }]
                }],
                result: {
                    totalResults: 10,
                    limit: 5,
                    offset: 0,
                    items: [
                        {
                            id: '56db08d7a89e66f93d9534171719d108fd9c8010/pages/1/0/0/0',
                            title: 'foo 1',
                            content: 'bar',
                            type: 'page',
                            url: 'http://netlogix.de/foo1'
                        },
                        {
                            id: '56db08d7a89e66f93d9534171719d108fd9c8010/pages/2/0/0/0',
                            title: 'foo 2',
                            content: 'bar',
                            type: 'page',
                            url: 'http://netlogix.de/foo2'
                        },
                        {
                            id: '56db08d7a89e66f93d9534171719d108fd9c8010/pages/3/0/0/0',
                            title: 'foo 3',
                            content: 'bar',
                            type: 'page',
                            url: 'http://netlogix.de/foo3'
                        },
                        {
                            id: '56db08d7a89e66f93d9534171719d108fd9c8010/pages/4/0/0/0',
                            title: 'foo 4',
                            content: 'bar',
                            type: 'page',
                            url: 'http://netlogix.de/foo4'
                        },
                        {
                            id: '56db08d7a89e66f93d9534171719d108fd9c8010/pages/5/0/0/0',
                            title: 'foo 5',
                            content: 'bar',
                            type: 'page',
                            url: 'http://netlogix.de/foo5'
                        }
                    ]
                }
            };

            $httpBackend.when('GET', '/ajaxsearch/en/search/?q=foo&page=2').respond(200, {
                search: {
                    q: 'foo',
                    suggestion: false,
                    links: {
                        next: ''
                    }
                },
                facets: searchResponse.facets,
                result: searchResponse.result
            });
            $httpBackend.when('GET', '/ajaxsearch/en/search/?error=1').respond(404, {});

            controller = $controller('SearchController', {searchResponse: searchResponse});
        }));

        afterEach(function() {
            $httpBackend.verifyNoOutstandingExpectation();
            $httpBackend.verifyNoOutstandingRequest();
        });

        it('should exist', function() {
            expect(controller).toBeDefined();
            $httpBackend.flush();
        });

        it('should set search', function() {
            expect(controller.search).toBe(searchResponse.search);
            $httpBackend.flush();
        });

        it('should set facets', function() {
            expect(controller.facets).toBe(searchResponse.facets);
            $httpBackend.flush();
        });

        it('should set result', function() {
            expect(controller.result).toBe(searchResponse.result);
            $httpBackend.flush();
        });

        it('should fetch next results', function() {
            expect(controller.result.items.length).toBe(5);
            controller.loadNext();
            $httpBackend.flush();
            expect(controller.result.items.length).toBe(10);
        });

        it('should reset set next link empty when an error occurred while feting more results', function() {
            controller.search.links.next = '/en/search/?error=1';
            controller.loadNext();
            $httpBackend.flush();
            expect(controller.search.links.next).toBe('');
        });

        it('should change the location', function() {
            $httpBackend.flush();
            controller.select('/en/search/?q=netlogix&tx_solr%5Bfilter%5D%5B0%5D=type:page');
            expect($location.url()).toBe('/en/search/?q=netlogix&tx_solr%5Bfilter%5D%5B0%5D=type:page');
        });

        it('should submit search', function() {
            $httpBackend.flush();
            $location.url('/en/search/');
            controller.q = 'netlogix';
            controller.submitSearch();
            expect($location.url()).toBe('/en/search/?q=netlogix');
        });

        it('should reset search', function() {
            $httpBackend.flush();
            $location.url('/en/search/?q=netlogix');
            controller.q = 'netlogix';
            controller.removeSearch();
            expect($location.url()).toBe('/en/search/');
        });
    });

})();
