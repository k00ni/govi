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
	scripts/bin/read-dbpedia-archivo
	scripts/bin/read-linked-open-vocabularies
	scripts/bin/read-ontology-lookup-service
	scripts/bin/read-bioportal
	scripts/bin/merge-in-manually-maintained-metadata
	scripts/bin/write-index-csv

prepare:
	cd scripts && vendor/bin/php-cs-fixer fix
	cd scripts && vendor/bin/phpstan
