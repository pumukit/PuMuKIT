.PHONY: help  up start stop clean debug pull build test shell ps logs

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
	docker-compose run --service-ports php bin/console c:c
	docker-compose run --service-ports php bin/console c:c --env=prod

ai:
	docker-compose run --service-ports php bin/console a:i --symlink --relative

cc: cc-envs ai

composer-validate:
	docker-compose run --service-ports php composer validate

fixtures:
	docker-compose run --service-ports php bin/console pumukit:init:repo all --force

test-all: test test-lint-yaml test-lint-twig test-lint-generic test-php-cs-fixer test-php-stan

test:
	docker-compose run --service-ports php composer tests

test-lint-yaml:
	docker-compose run --service-ports php composer lint-yaml

test-lint-twig:
	docker-compose run --service-ports php composer lint-twig

test-lint-generic:
	docker-compose run --service-ports php composer lint-generic

test-php-cs-fixer:
	docker-compose run --service-ports php composer php-cs-fixer

test-php-stan:
	docker-compose run --service-ports php composer php-stan

shell:
	docker-compose exec php sh

ps:
	docker-compose ps

logs:
	docker-compose logs -f --tail=100
