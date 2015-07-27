/*global angular:false */
(function(window, angular, undefined) {
	'use strict';

	var module = angular.module('netlogix.solrajax.simpleDatepicker', []);

	module.controller('SimpleDatepickerController', ['$scope', 'dateFilter', function($scope, dateFilter) {
		var self = this;

		$scope.date = {
			start: {
				month: null,
				year: null
			},
			end: {
				month: null,
				year: null
			}
		};

		$scope.months = [];
		$scope.years = [];

		$scope.submit = function() {
			var start = new Date($scope.date.start.year.date.getFullYear(), $scope.date.start.month.date.getMonth(), 1),
				end = new Date($scope.date.end.year.date.getFullYear(), $scope.date.end.month.date.getMonth(), 1);

			self.ngModelCtrl.$setViewValue({start: start, end: end});
			if (angular.isFunction($scope.submitCallback)) {
				$scope.submitCallback();
			}
		};

		$scope.reset = function() {
			if (angular.isFunction($scope.resetCallback)) {
				$scope.resetCallback();
			}
		};

		self.formatMonth = 'MMMM';
		self.formatYear = 'yyyy';
		self.startYear = new Date($scope.minDate).getFullYear();
		self.currentYear = new Date().getFullYear();
		self.ngModelCtrl = {};

		self.createDateObject = function(date, format) {
			return {
				date: date,
				label: dateFilter(date, format)
			};
		};

		self.init = function(ngModelCtrl) {
			self.ngModelCtrl = ngModelCtrl;


			for (var m = 0; m < 12; m++) {
				$scope.months.push(self.createDateObject(new Date(0, m, 1), self.formatMonth));
			}

			for (var y = 0; y < (self.currentYear - self.startYear + 1); y++) {
				var year = self.startYear + y;
				$scope.years.push(self.createDateObject(new Date(year, 0, 1), self.formatYear));
			}

			var selectedMonth, selectedYear;

			function findYear(year) {
				return $scope.years.filter(function(obj) {
					if (parseInt(obj.label) === year) {
						return obj;
					}
				})[0];
			}

			if ($scope.startDate) {
				selectedMonth = new Date($scope.startDate).getMonth();
				selectedYear = new Date($scope.startDate).getFullYear();

				if (angular.isObject($scope.months[selectedMonth])) {
					$scope.date.start.month = $scope.months[selectedMonth];
				}

				$scope.date.start.year = findYear(selectedYear);
			}

			if ($scope.endDate) {
				selectedMonth = new Date($scope.endDate).getMonth();
				selectedYear = new Date($scope.endDate).getFullYear();

				if (angular.isObject($scope.months[selectedMonth])) {
					$scope.date.end.month = $scope.months[selectedMonth];
				}

				$scope.date.end.year = findYear(selectedYear);
			}
		};

	}]);

	module.directive('simpleDatepicker', [function() {
		return {
			restrict: 'EA',
			replace: true,
			templateUrl: 'templates/simpledatepicker/simpledatepicker.html',
			scope: {
				selected: '=?',
				startDate: '=?',
				endDate: '=?',
				minDate: '=?',
				submitCallback: '&?submit',
				resetCallback: '&?reset'
			},
			require: ['simpleDatepicker', '?^ngModel'],
			controller: 'SimpleDatepickerController',
			link: function($scope, $element, $attrs, ctrls) {
				var datepickerCtrl = ctrls[0], ngModelCtrl = ctrls[1];
				datepickerCtrl.init(ngModelCtrl);
			}
		};
	}]);

}(window, angular));