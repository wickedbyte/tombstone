SHELL := bash

app = docker compose run --rm app

build:
	@docker compose build --pull
	@$(app) composer install
	@cp phpstan.dist.neon phpstan.neon
	@cp phpunit.dist.xml phpunit.xml
	@$(app) mkdir -p build

.PHONY: clean
clean:
	@rm -rf ./build ./vendor

.PHONY: update
update: build
	@$(app) composer update --with-all-dependencies
	@$(app) composer bump

.PHONY: upgrade
upgrade: build
	@$(app) composer require --dev --update-with-all-dependencies \
		phpstan/phpstan \
		phpunit/phpunit \
		psy/psysh \
		squizlabs/php_codesniffer \
		slevomat/coding-standard \
		rector/rector
	@$(app) composer require --update-with-all-dependencies \
		symfony/console
	@$(app) app composer bump

.PHONY: bash
bash: build
	@$(app) bash

.PHONY: lint phpcbf phpcs phpstan phpunit rector rector-dry-run
lint phpcbf phpcs phpstan phpunit rector rector-dry-run:
	docker compose run --rm --user=$$(id -u):$$(id -g) app composer run-script "$@"

.NOTPARALLEL: ci
.PHONY: ci
ci: lint phpcs phpstan rector-dry-run phpunit
