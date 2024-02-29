default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make index       - Crawl portals and services and create index.csv"
	@echo "  - make prepare     - Prepare for commit"
	@echo "  - make composer    - Run 'composer update' in /run"
	@echo ""

composer:
	cd run && composer update

index:
	rm -f run/var/temporary-index.db
	run/bin/read-dbpedia-archivo
	run/bin/read-linked-open-vocabularies
	run/bin/write-index-csv

prepare:
	cd run && vendor/bin/php-cs-fixer fix
	cd run && vendor/bin/phpstan
