# Если существует .env.local, то он будет прочитан, иначе .env
ifneq (",$(wildcard ./.env.local)")
    include .env.local
    DEFAULT_ENV_FILE = '.env.local'
else
    include .env
    DEFAULT_ENV_FILE = '.env'
endif

env = ${APP_ENV}

help:
	@echo "[${env}]: ENV get from ${DEFAULT_ENV_FILE}"

restart: down up

generate-env-files:
	@if [ ! -f .env.local ]; then \
  		echo "[${env}]: generate => .env.local"; \
		cp .env .env.local; \
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
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		build
	@echo "[${env}]: containers builded!"

up:
	@echo "[${env}]: start containers..."
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		up -d --build
	@echo "[${env}]: containers started!"

down:
	@echo "[${env}]: stopping containers..."
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		down --remove-orphans
	@echo "[${env}]: containers stopped!"

bin-console:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		run --rm php-cli \
		bin/console ${command} -e ${env}

cache-clear:
	@if [ -d var/cache/${env} ]; then \
		echo "[${env}]: Clearing var/cache/${env}..."; \
		rm -rf var/cache/${env}; \
	fi

cache-warmup:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		run --rm php-cli \
		bin/console cache:warmup -e ${env}
	# @todo пересмотреть работу с правами
	@if [ `whoami` != 'root' ]; then \
		echo "You must be root to fix cache folder permissions"; \
	else \
		chmod -R 777 var/cache/${env}; \
	fi
	@chmod -R 777 var/cache/${env}

composer-install:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		run --rm php-cli \
		composer install

composer-update:
	@docker-compose --file=./docker-compose.yml --file=./docker-compose.${env}.yml --env-file=./.env.docker.${env}.local -p "${PWD}_${env}" \
		run --rm php-cli \
		composer update
