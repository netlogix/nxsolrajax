(function() {
    'use strict';

    describe('SimpleDateRangePicker component', function() {

        var component, $rootScope, $componentController, $location;

        beforeEach(module('netlogix.solrajax.simpledaterangepicker'));

        beforeEach(inject(function(_$rootScope_, _$componentController_, _$location_) {
            $componentController = _$componentController_;
            $rootScope = _$rootScope_;
            $location = _$location_;

            component = $componentController('simpleDateRangePicker', {$scope: $rootScope.$new()}, {
                start: '1136133944',
                end: '1483202744',
                min: '1158796800',
                submitUrl: '/search/?filter[0]={dateRange}',
                resetUrl: '/search/'
            });
        }));

        it('should exist', function() {
            expect(component).toBeDefined();
        });

        it('should submit the search', function() {
            component.submit();
            expect($location.url()).toEqual('/search/?filter%5B0%5D=200601312359-201612312359');
        });

        it('should reset the search', function() {
            component.reset();
            expect($location.url()).toEqual('/search/');
        });

        it('should create month array', function() {
            expect(component.months.length).toEqual(12);
        });

        it('should create year array', function() {
            expect(component.years.length).toEqual(11);
        });

        describe('templateUrl', function() {
            var element;
            beforeEach(inject(function(_$compile_, _$templateCache_) {
                _$templateCache_.put('templates/search/components/simpledaterangepicker.html', '<div><input type="text"></div>');
                element = _$compile_('<simple-date-range-picker min="1158796800"></simple-date-range-picker>')($rootScope);
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
