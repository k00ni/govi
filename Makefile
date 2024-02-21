default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make crawl       - Crawl portals and services"
	@echo "  - make prepare     - Prepare for commit"
	@echo ""

crawl:
	bin/read-dbpedia-archivo

prepare:
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
