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

dynamic_docker_php_name := $(shell echo $(notdir $(shell pwd) | tr A-Z a-z))_php_1

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
	docker-compose -f docker-compose.yml run --service-ports php bin/console c:c
	docker-compose -f docker-compose.yml run --service-ports php bin/console c:c --env=prod

ai:
	docker-compose -f docker-compose.yml run --service-ports php bin/console a:i --symlink --relative

cc: cc-envs ai

# Composer
composer-install: CMD=install
composer-update: CMD=update
composer-install composer-update:
	docker-compose -f docker-compose.yml run --service-ports php composer $(CMD)

composer-validate:
	docker-compose -f docker-compose.yml run --service-ports php composer validate

fixtures:
	docker-compose -f docker-compose.yml run --service-ports php bin/console pumukit:init:repo all --force

test-all: test test-lint-yaml test-lint-twig test-lint-generic test-php-cs-fixer test-php-stan test-rector

test:
	docker-compose -f docker-compose.yml run --service-ports php composer tests

test-lint-yaml:
	docker-compose -f docker-compose.yml run --service-ports php composer lint-yaml

test-lint-twig:
	docker-compose -f docker-compose.yml run --service-ports php composer lint-twig

test-lint-xliff:
	docker-compose -f docker-compose.yml run --service-ports php composer lint-xliff

test-lint-generic:
	docker-compose -f docker-compose.yml run --service-ports php composer lint-generic

test-php-cs-fixer:
	docker-compose -f docker-compose.yml run --service-ports php composer php-cs-fixer

test-php-stan:
	docker-compose -f docker-compose.yml run --service-ports php composer php-stan

test-rector:
	docker-compose -f docker-compose.yml run --service-ports php composer php-rector

shell:
	docker-compose -f docker-compose.yml run --service-ports php sh

ps:
	docker-compose ps

logs:
	docker-compose logs -f --tail=100
