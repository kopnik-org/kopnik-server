# Если существует .env.local, то он будет прочитан, иначе .env
ifneq (",$(wildcard ./.env.local)")
    include .env.local
    DEFAULT_ENV_FILE = '.env.local'
else
    include .env
    DEFAULT_ENV_FILE = '.env'
endif

env = ${APP_ENV}
pwd = $(shell eval pwd -P)

help:
	@echo "[${env}]: ENV get from ${DEFAULT_ENV_FILE}"

restart: down up
restart-build: down build up

deploy:
	@git pull
	@make -s restart-build
	@make -s composer-install
	@make -s cache-warmup
	@make -s bin-console c="doctrine:migrations:migrate --no-interaction"

generate-env-files:
	@if [ ! -f .env.local ]; then \
  		echo "[${env}]: generate => .env.local"; \
		cp .env .env.local; \
		sed -i "s/APP_ENV=prod/APP_ENV=${env}/g" .env.local; \
	else \
	  	echo "[${env}]: already exist => .env.local"; \
	fi
	@if [ ! -f .env.docker.${env}.local ]; then \
  		echo "[${env}]: generate => .env.docker.${env}.local"; \
		cp .env.docker .env.docker.${env}.local; \
		sed -i "s/APP_ENV=~/APP_ENV=${env}/g" .env.docker.${env}.local; \
	else \
	  	echo "[${env}]: already exist => .env.docker.${env}.local "; \
	fi

build:
	@echo "[${env}]: build containers..."
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
		build
	@echo "[${env}]: containers builded!"

up:
	@echo "[${env}]: start containers..."
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
		up -d
	@echo "[${env}]: containers started!"

down:
	@echo "[${env}]: stopping containers..."
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
		down --remove-orphans
	@echo "[${env}]: containers stopped!"

bin-console:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
		run --rm php-cli \
		bin/console -e ${env} ${c}

cache-clear:
	@if [ -d var/cache/${env} ]; then \
		echo "[${env}]: Clearing var/cache/${env}..."; \
		rm -rf var/cache/${env}; \
	fi

cache-warmup:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
		run --rm php-cli \
		bin/console cache:warmup -e ${env}
	#@chmod -R 777 var/cache/${env}

composer-install:
	@if [ ${env} = 'prod' ]; then \
		docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
			run --rm php-cli \
			composer install --no-dev; \
	else \
		docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
			run --rm php-cli \
			composer install; \
	fi

composer-update:
	@if [ ${env} = 'prod' ]; then \
		docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
			run --rm php-cli \
			composer update --no-dev; \
	else \
		docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${pwd}_${env}" \
			run --rm php-cli \
			composer update; \
	fi

test-full-up:
	@make env=test -s restart-build
	@make env=test -s composer-install
	@make env=test -s cache-warmup
	@make env=test -s bin-console c="doctrine:schema:drop --force --full-database"
	@make env=test -s bin-console c="doctrine:migrations:migrate --no-interaction"
