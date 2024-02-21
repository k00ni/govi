default:
	@echo ""
	@echo "Available commands:"
	@echo "  - make prepare     - Prepare for commit"
	@echo ""

prepare:
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan
