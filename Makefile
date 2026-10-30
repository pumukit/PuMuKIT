.PHONY: help up start stop clean down debug pull build \
        cc cc-envs ai \
        composer composer-install composer-update composer-validate fixtures \
        test-all test test-lint-yaml test-lint-twig test-lint-xliff test-lint-generic \
        test-php-cs-fixer test-php-stan test-rector fix-test-perms test-e2e test-e2e-down \
        update update-composer update-importmap update-paella paella-build vendor-outdated \
        integration integration-tests integration-clean \
        shell php-shell ps logs

.DEFAULT_GOAL := help

DOCKER_COMP = docker compose

DC_BASE = $(DOCKER_COMP) -f docker-compose.yml
DC_TEST = $(DOCKER_COMP) -f docker-compose.test.yml
DC_E2E = $(DOCKER_COMP) -f docker-compose.yml -f docker-compose.e2e.yml

PAELLA_DIR = src/Pumukit/PaellaPlayerBundle/Resources/public/paella
IMPORTMAP_DIR = src/Pumukit/CoreBundle/Resources/importmap

# Run a command in the php service: reuse the running container if the stack is up,
# otherwise spin up an ephemeral one that is removed afterwards (--rm).
define run_php
	@if [ -n "$$($(DOCKER_COMP) ps -q php 2>/dev/null)" ]; then \
		$(DOCKER_COMP) exec -u www-data php $(1); \
	else \
		$(DC_BASE) run --rm --user www-data php $(1); \
	fi
endef

define run_php_root
	@if [ -n "$$($(DOCKER_COMP) ps -q php 2>/dev/null)" ]; then \
		$(DOCKER_COMP) exec -u root php $(1); \
	else \
		$(DC_BASE) run --rm --user root php $(1); \
	fi
endef

define run_paella
	@docker run --rm --user $$(id -u):$$(id -g) -e npm_config_cache=/tmp/.npm -v $(CURDIR)/$(PAELLA_DIR):/src node:24-alpine \
		sh -c 'set -e; cp -a /src/. /tmp/build/; cd /tmp/build; $(1)'
endef

# Run a command in the php service of the test stack (always an ephemeral container).
define run_php_test
	@$(DC_TEST) run --rm --user www-data php $(1)
endef

help:
	@echo ''
	@echo 'PuMuKIT makefile'
	@echo ''
	@echo 'Usage:'
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "    \033[36m%-22s\033[0m %s\n", $$1, $$2}'

up:
	@$(DOCKER_COMP) up -d

start:
	@$(DOCKER_COMP) start

stop:
	@$(DOCKER_COMP) stop

clean: stop
	@$(DOCKER_COMP) rm -f

down:
	@$(DOCKER_COMP) down

debug:
	@$(DOCKER_COMP) up

pull:
	@$(DOCKER_COMP) pull

build:
	@$(DOCKER_COMP) build

cc-envs:
	$(call run_php,bin/console c:c)
	$(call run_php,bin/console c:c --env=prod)

ai:
	$(call run_php,bin/console a:i --symlink --relative)

cc: cc-envs ai

composer-install: CMD=install
composer-update:  CMD=update
composer-install composer-update:
	$(call run_php,composer $(CMD))

composer:
	$(call run_php,composer $(CMD))

composer-validate:
	$(call run_php,composer validate)

fixtures:
	$(call run_php,bin/console pumukit:init:repo all --force)

test-all: test test-smoke test-lint-yaml test-lint-twig test-lint-xliff test-lint-generic test-php-cs-fixer test-php-stan test-rector ## Run all the PuMuKIT code tests

test:
	$(call run_php_test,composer tests)

test-smoke: ## Request every GET route with fixtures: fails on server errors, GET requests that modify data and unresolved route parameters
	$(call run_php_test,composer tests-smoke)

test-lint-yaml:
	$(call run_php_test,composer lint-yaml)

test-lint-twig:
	$(call run_php_test,composer lint-twig)

test-lint-xliff:
	$(call run_php_test,composer lint-xliff)

test-lint-generic:
	$(call run_php_test,composer lint-generic)

test-php-cs-fixer:
	$(call run_php_test,composer php-cs-fixer)

test-php-stan:
	$(call run_php_test,composer php-stan)

test-rector:
	$(call run_php_test,composer php-rector)

test-e2e: ## Run the Playwright browser tests against an isolated stack (pumukit-e2e); extra Playwright options in ARGS
	@$(DC_E2E) up -d --wait
	@$(DC_E2E) exec -T php chown -R www-data:www-data var
	@$(DC_E2E) exec -T --user www-data php bin/console pumukit:create:user admin mail@example.com admin >/dev/null 2>&1 || true
	@$(DC_E2E) run --rm --user $$(id -u):$$(id -g) playwright sh -c 'npm ci --no-audit --no-fund --loglevel=error && npx playwright test $(ARGS)'

test-e2e-down: ## Remove the end-to-end stack and its data
	@$(DC_E2E) --profile e2e down -v --remove-orphans

update: update-composer update-importmap update-paella vendor-outdated ## Update composer, the importmap and Paella within their major versions; review the diff and commit by hand

update-composer: ## composer update within composer.json, regenerate config/reference.php, validate, audit and list the new major versions (except Symfony 7.4 components)
	$(call run_php_root,sh -c 'export COMPOSER_ALLOW_SUPERUSER=1; composer update && APP_ENV=prod APP_DEBUG=1 bin/console cache:clear --no-warmup >/dev/null && APP_ENV=prod APP_DEBUG=1 bin/console cache:warmup >/dev/null; status=$$?; chown -R www-data:www-data vendor var; chown --reference=composer.json composer.lock symfony.lock config/reference.php; exit $$status')
	$(call run_php,composer validate --no-check-publish)
	$(call run_php,composer audit)
	$(call run_php,composer outdated --direct --major-only) | awk '!/^symfony\/[^ ]+ +7\.4\./'

update-importmap: ## Update the importmap packages used by PuMuKIT within their major versions, remove the unused ones and audit
	$(call run_php_root,sh -c 'bin/console pumukit:importmap:update; status=$$?; chown -R --reference=composer.json $(IMPORTMAP_DIR); chown -R www-data:www-data var; exit $$status')
	$(call run_php,bin/console importmap:audit)

update-paella: ## Update the Paella dependencies within package.json, fix the npm advisories and build the player
	$(call run_paella,npm update --save --no-fund --loglevel=error; npm audit fix --no-fund --loglevel=error || true; cp package.json package-lock.json /src/; npm outdated || true; npm audit)
	@$(MAKE) --no-print-directory paella-build

paella-build: ## Build the Paella player into $(PAELLA_DIR)/dist with Node 24
	$(call run_paella,npm ci --no-audit --no-fund --loglevel=error; npm run build -- --output-path /src/dist)

vendor-outdated: ## List the updates of jQuery, Bootstrap and Font Awesome in bundles/pumukitcore/vendor (updated by hand)
	$(call run_php,bin/console pumukit:vendor:outdated)


integration: ## Check core + dev-bundles together: composer, container, routes, lints, deprecations, PHPStan
	$(call run_php,php bin/integration-check $(ARGS))

integration-tests: ## Same as integration plus PHPUnit (core suite and bundle tests)
	$(call run_php_test,php bin/integration-check --tests $(ARGS))

integration-clean: ## Remove the integration workspace (var/integration)
	$(call run_php,rm -rf var/integration)

fix-test-perms: ## Reset ownership of test fixture dirs (run after a stale root-owned state breaks make test)
	@$(DC_TEST) run --rm --user root php sh -c 'chown -R www-data:www-data tests/tmp src/Pumukit/EncoderBundle/Tests/Resources .phpunit.cache'

shell:
	@$(DC_BASE) run --rm --user www-data php sh

php-shell:
	@$(DOCKER_COMP) exec -u www-data php bash

ps:
	@$(DOCKER_COMP) ps

logs:
	@$(DOCKER_COMP) logs -f --tail=100
