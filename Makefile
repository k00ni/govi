default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make index       - Crawl portals and services and create index.csv"
	@echo "  - make prepare     - Prepare for commit"
	@echo "  - make composer    - Run 'composer update' in /scripts"
	@echo ""

composer:
	cd scripts && composer update

index:
	rm -f scripts/var/temporary-index.db
	php scripts/bin/renew_index.php

prepare:
	cd scripts && vendor/bin/php-cs-fixer fix
	cd scripts && vendor/bin/phpstan
