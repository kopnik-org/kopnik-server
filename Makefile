up: docker-up composer-install
upb: docker-build docker-up
down: docker-down
build: docker-build
restart: docker-down docker-up
restart-build: docker-down docker-build docker-up
init: docker-down-clear  docker-pull docker-build docker-up composer-install db-schema-drop kopnik-init
kopnik-init: wait-db migrations
	# clear  fixtures ready
test-up: test-docker-down test-docker-up test-db-schema-drop test-migrations
test-down: test-docker-down

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
	docker-compose run php bin/console ${ARGS}

composer-install:
	docker-compose run php composer install

db-schema-drop:
	docker-compose run php bin/console doctrine:schema:drop --force --full-database

wait-db:
	until docker-compose exec -T db pg_isready --timeout=0 --dbname=kopnik ; do sleep 1 ; done

migrations:
	docker-compose run --rm php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker-compose run --rm php php bin/console doctrine:fixtures:load --no-interaction

ready:
	docker run --rm -v ${PWD}:/app --workdir=/app alpine touch .ready

test-docker-up:
    docker-compose -f docker-compose-test.yml up -d --build

test-docker-down:
	docker-compose -f docker-compose-test.yml down --remove-orphans

test-db-schema-drop:
	docker-compose -f docker-compose-test.yml run php bin/console doctrine:schema:drop --force --full-database

test-migrations
    docker-compose -f docker-compose-test.yml run --rm php php bin/console doctrine:migrations:migrate --no-interaction
