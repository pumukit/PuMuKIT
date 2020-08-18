.PHONY: help debug stop start clean pull build test-all shell ps logs cc composer-validate fixtures composer-install

help:
	@echo ''
	@echo 'PuMuKIT makefile'
	@echo ''
	@echo 'Usage:'
	@echo '    make up                    Deploy all the containers'
	@echo '    make debug                 Deploy all the containers with debug log'
	@echo '    make stop                  Stop all the containers'
	@echo '    make start                 Start stopped containers'
	@echo '    make clean                 Remove all the containers'
	@echo '    make pull                  Download container images from registry'
	@echo '    make build                 build project docker images'
	@echo '    make test-all              Run the PuMuKIT code tests'
	@echo '    make shell                 Attach to tte PuMuKIT tty'
	@echo '    make ps                    List service state'
	@echo '    make logs                  Show the log of all services'
	@echo '    make cc                    Clear cache and install assets'
	@echo '    make composer-validate     Validate composer'
	@echo '    make fixtures              Import basic fixtures'
	@echo '    make composer-install      Install composer dependencies'

current-dir := $(dir $(abspath $(lastword $(MAKEFILE_LIST))))

up:
	docker-compose up -d

start:
	docker-compose start

stop:
	docker-compose stop

clean: stop
	docker-compose rm -f

debug:
	docker-compose up

pull:
	docker-compose pull

build:
	docker-compose build

cc-envs:
	docker-compose exec php bin/console c:c
	docker-compose exec php bin/console c:c --env=prod

ai:
	docker-compose exec php bin/console a:i --symlink --relative

cc: cc-envs ai

# Composer
composer-install: CMD=install
composer-update: CMD=update
composer composer-install composer-update:
	@docker run --rm --interactive --volume $(current-dir):/app --user $(id -u):$(id -g) \
		clevyr/prestissimo $(CMD) \
			--ignore-platform-reqs \
			--no-ansi \
			--no-interaction

composer-validate:
	docker-compose exec -T php composer validate

fixtures:
	docker-compose exec -T php bin/console pumukit:init:repo all --force

test-all: test test-lint-yaml test-lint-twig test-lint-generic test-php-cs-fixer test-php-stan

test:
	docker-compose exec -T php composer tests

test-lint-yaml:
	docker-compose exec -T php composer lint-yaml

test-lint-twig:
	docker-compose exec -T php composer lint-twig

test-lint-generic:
	docker-compose exec -T php composer lint-generic

test-php-cs-fixer:
	docker-compose exec -T php composer php-cs-fixer

test-php-stan:
	docker-compose exec -T php composer php-stan

shell:
	docker-compose exec php sh

ps:
	docker-compose ps

logs:
	docker-compose logs -f --tail=100
