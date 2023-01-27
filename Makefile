.PHONY: help debug stop start clean pull build test-all shell ps logs cc composer-validate fixtures composer-install

DOCKER_COMP = docker-compose

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
	@$(DOCKER_COMP) up -d

start:
	@$(DOCKER_COMP) start

stop:
	@$(DOCKER_COMP) stop

clean: stop
	@$(DOCKER_COMP) rm -f

debug:
	@$(DOCKER_COMP) up

pull:
	@$(DOCKER_COMP) pull

build:
	@$(DOCKER_COMP) build

cc-envs:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php bin/console c:c
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php bin/console c:c --env=prod

ai:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php bin/console a:i --symlink --relative

cc: cc-envs ai

# Composer
composer-install: CMD=install
composer-update: CMD=update
composer-install composer-update:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer $(CMD)

composer-validate:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer validate

fixtures:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php bin/console pumukit:init:repo all --force

test-all: test test-lint-yaml test-lint-twig test-lint-generic test-php-cs-fixer test-php-stan test-rector

test:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer tests

test-lint-yaml:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer lint-yaml

test-lint-twig:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer lint-twig

test-lint-xliff:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer lint-xliff

test-lint-generic:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer lint-generic

test-php-cs-fixer:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer php-cs-fixer

test-php-stan:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer php-stan

test-rector:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php composer php-rector

shell:
	@$(DOCKER_COMP) -f docker-compose.yml run --service-ports php sh

ps:
	@$(DOCKER_COMP) ps

logs:
	@$(DOCKER_COMP) logs -f --tail=100
