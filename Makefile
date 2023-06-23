.PHONY: clean deps test

clean:
	rm -rf .Build/

deps:
	mkdir -p .Build/logs/coverage/
	composer install

update:
	composer update -W

test:
	XDEBUG_MODE=coverage .Build/bin/phpunit -c phpunit.xml
	# merge into php coverage
	.Build/bin/phpcov merge --php .Build/logs/coverage.php .Build/logs/coverage/
