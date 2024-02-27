default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make index       - Crawl portals and services and create index.csv"
	@echo "  - make prepare     - Prepare for commit"
	@echo ""

index:
	bin/read-dbpedia-archivo

prepare:
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
