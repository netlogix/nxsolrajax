(function(angular) {
    'use strict';

    var SimpleDateRangePicker, module = angular.module('netlogix.solrajax.simpledaterangepicker', []);

    SimpleDateRangePicker = {
        bindings: {
            start: '<',
            end: '<',
            min: '<',
            submitUrl: '<',
            resetUrl: '<'
        },
        templateUrl: ['$element', '$attrs', function($element, $attrs) {
            return $attrs.templateUrl || 'templates/search/components/simpledaterangepicker.html';
        }],
        controller: ['$location', 'dateFilter', function($location, dateFilter) {
            var self = this;

            this.startDate = new Date(this.start * 1000);
            this.endDate = new Date(this.end * 1000);

            this.minDate = new Date(this.min * 1000);
            this.maxDate = new Date();

            this.model = {
                start: {
                    month: null,
                    year: null
                },
                end: {
                    month: null,
                    year: null
                }
            };

            this.months = [];
            this.years = [];

            this.submit = function() {
                var start = new Date(this.model.start.year.date.getFullYear(), (this.model.start.month.date.getMonth() + 1), 1, 0, 0, -1),
                    end = new Date(this.model.end.year.date.getFullYear(), (this.model.end.month.date.getMonth() + 1), 1, 0, 0, -1);

                var dateRange = dateFilter(start, 'yyyyMMddHHmm') + '-' + dateFilter(end, 'yyyyMMddHHmm');
                $location.url(this.submitUrl.replace('{dateRange}', dateRange).replace(encodeURI('{dateRange}'), dateRange));
            };

            this.reset = function() {
                $location.url(this.resetUrl);
            };

            function createDateObject(date, format) {
                return {
                    date: date,
                    label: dateFilter(date, format)
                };
            }

            (function init() {
                for (var m = 0; m < 12; m++) {
                    self.months.push(createDateObject(new Date(0, m, 1), 'MMMM'));
                }

                var startYear = self.minDate.getFullYear(), currentYear = self.maxDate.getFullYear();
                for (var y = 0; y < (currentYear - startYear + 1); y++) {
                    var year = startYear + y;
                    self.years.push(createDateObject(new Date(year, 0, 1), 'yyyy'));
                }

                function findYear(year) {
                    return self.years.filter(function(obj) {
                        if (parseInt(obj.label) === year) {
                            return obj;
                        }
                    })[0];
                }

                if (angular.isDate(self.startDate)) {
                    self.model.start.month = self.months[self.startDate.getMonth()];
                    self.model.start.year = findYear(self.startDate.getFullYear());
                }

                if (angular.isDate(self.endDate)) {
                    self.model.end.month = self.months[self.endDate.getMonth()];
                    self.model.end.year = findYear(self.endDate.getFullYear());
                }

            })();

        }]
    };

    module.component('simpleDateRangePicker', SimpleDateRangePicker);

}(angular));