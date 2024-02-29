default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make index       - Crawl portals and services and create index.csv"
	@echo "  - make prepare     - Prepare for commit"
	@echo ""

index:
	rm -f var/temporary-index.db
	bin/read-dbpedia-archivo
	bin/read-linked-open-vocabularies
	bin/write-index-csv

prepare:
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
