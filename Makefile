up: docker-up composer-install
upb: docker-build docker-up
down: docker-down
build: docker-build
restart: docker-down docker-up
restart-build: docker-down docker-build docker-up
init: docker-down-clear  docker-pull docker-build docker-up composer-install db-schema-drop kopnik-init
kopnik-init: wait-db db-schema-drop migrations

# dev = prod + fixtures
dev-up: init fixtures

# test
test-up: test-docker-down test-docker-up test-composer-install-dev test-db-schema-drop test-migrations test-fixtures
test-down: test-docker-down
test-setup: test-db-schema-drop test-migrations

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
	docker-compose exec php composer install --no-dev

composer-install-dev:
	docker-compose exec php composer install

db-schema-drop:
	docker-compose exec php bin/console doctrine:schema:drop --force --full-database

wait-db:
	until docker-compose exec -T db pg_isready --timeout=0 --dbname=kopnik ; do sleep 1 ; done

migrations:
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker-compose exec php php bin/console hautelook:fixtures:load -q

ready:
	docker run --rm -v ${PWD}:/app --workdir=/app alpine touch .ready

test-docker-up:
	docker-compose -f docker-compose-test.yml up -d --build

test-docker-down:
	docker-compose -f docker-compose-test.yml down

test-composer-install-dev:
	docker-compose -f docker-compose-test.yml exec php-test composer install

test-db-schema-drop:
	docker-compose -f docker-compose-test.yml exec php-test bin/console doctrine:schema:drop --force --full-database

test-db-schema-update:
	docker-compose -f docker-compose-test.yml exec php-test php bin/console doctrine:schema:update --force

test-migrations:
	docker-compose -f docker-compose-test.yml exec php-test php bin/console doctrine:migrations:migrate --no-interaction

test-fixtures:
	docker-compose -f docker-compose-test.yml exec php-test php bin/console hautelook:fixtures:load -q
