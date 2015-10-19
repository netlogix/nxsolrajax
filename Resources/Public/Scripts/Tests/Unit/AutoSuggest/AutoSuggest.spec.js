(function() {
	'use strict';

	describe('AutoSuggest', function() {
		beforeEach(module('netlogix.solrajax.autosuggest'));

		describe('AutoSuggestCtrl', function() {
			var scope, ctrl;

			beforeEach(inject(function($rootScope, $controller) {
				scope = $rootScope.$new();
				ctrl = $controller('AutoSuggestCtrl', {$scope: scope});
			}));

			it('should exist', function() {
				expect(ctrl).toBeDefined();
			});

			it('should set active to true', function() {
				expect(ctrl.active).toBe(false);
				ctrl.show();
				expect(ctrl.active).toBe(true);
			});

			it('should set active to false', function() {
				ctrl.show();
				expect(ctrl.active).toBe(true);
				ctrl.hide();
				expect(ctrl.active).toBe(false);
			});

			it('should reset the query string', function() {
				ctrl.q = 'foo';
				expect(ctrl.q).toBe('foo');
				ctrl.reset();
				expect(ctrl.q).toBe('');
			});

		});
	});

})();