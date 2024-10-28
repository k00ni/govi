default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make install     - Set up project the first time
	@echo "  - make index       - Crawl portals and services and create index.csv"
	@echo "  - make prepare     - Prepare for commit"
	@echo ""

index:
	rm -i -f ./scripts/var/index.db
	rm -i -f ./scripts/var/temp_files/*
	php ./scripts/bin/renew_index.php

install:
	cd scripts && composer update
	mkdir scripts/var/downloaded_rdf_files

prepare:
	cd scripts && vendor/bin/php-cs-fixer fix
	cd scripts && vendor/bin/phpstan
	cd scripts && vendor/bin/phpunit --display-warnings
