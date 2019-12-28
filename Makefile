up: docker-up
upb: docker-build docker-up
down: docker-down
build: docker-build
restart: docker-down docker-up
restart-build: docker-down docker-build docker-up
init: docker-down-clear  docker-pull docker-build docker-up db-schema-drop kopnik-init
kopnik-init: composer-install wait-db migrations
	# clear  fixtures ready

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

clear:
	docker run --rm -v ${PWD}:/app --workdir=/app alpine rm -f .ready

cli:
	docker-compose run php-cli bin/console ${ARGS}

composer-install:
	docker-compose run php-cli composer install

db-schema-drop:
	docker-compose run php-cli bin/console doctrine:schema:drop --force --full-database

wait-db:
	until docker-compose exec -T db pg_isready --timeout=0 --dbname=kopnik ; do sleep 1 ; done

migrations:
	docker-compose run --rm php-cli php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker-compose run --rm php-cli php bin/console doctrine:fixtures:load --no-interaction

ready:
	docker run --rm -v ${PWD}:/app --workdir=/app alpine touch .ready
