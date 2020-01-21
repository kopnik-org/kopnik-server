Kopnik.org
==========

Docker
===============
В вк зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET. Ссылка https://vk.com/apps?act=manage

В разделе Настройки указать Базовый домен и Доверенный redirect URI для OAuth 
```
# базовый домен
localhost
# номер порта произвольный. по умолчанию 8081
http://localhost:8081/login/check-vk

# для запуска приложения через сотовый телефон по локальной сети указать локальный Базовый домен 
# (локальный IP выводится в консоли dev сервера Vue.js
192.168.43.9
# и локальный Доверенный redirect URI
http://192.168.43.9:8081/login/check-vk
```

Склонировать репозиторий:
```
git clone https://github.com/kopnik-org/kopnik-server
cd kopnik-server
```

Скопировать и отредактировать конфиг:
```bash
cp .env .env.local
```

В .env.local нужно задать следующие значения:
``` 
# произвольнаяя строка
# APP_SECRET=klu9rofij239rfsd0
APP_SECRET=~

# https://vk.com/apps?act=manage , создать ВЕБ-приложение, пеерейти в рздел "Настройки"
# OAUTH_VK_CLIENT_ID=7210289
OAUTH_VK_CLIENT_ID=~
# OAUTH_VK_CLIENT_SECRET=UHIE908J098fjFE998
OAUTH_VK_CLIENT_SECRET=~


# группа - настройки - работа с АПИ - колбэк АПИ -  поле group_id (жирным)
# VK_COMMUNITY_ID=144968351
VK_COMMUNITY_ID=~
# руппа - настройки - работа с АПИ - Ключи доступа
# VK_CALLBACK_API_ACCESS_TOKEN=jkh2349df8ujcrf9d8fujclrhjuwe9f8usdfjic9f8dufjac3qcuf
VK_CALLBACK_API_ACCESS_TOKEN=~
```

Для запуска на телефоне через внутреннюю сеть добавить доверенный хост
```
# CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1|192.168.43.9)(:[0-9]+)?$
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

Запуск докера и инициализация приолжения:

```
make init
```

По умолчанию веб порт задан 8081, открывать проект по адресу:

```
http://localhost:8081/
``` 

Если нужно изменить порт, тогда запускать проект так:
```
make down
WEB_PORT=80 make up
```
в этом случае, проект будет доступен на 80 порту:
```
http://localhost/
``` 


Запуск клиента и сервера
-----------------------------
Шаг 1. Скачать исходники клиента https://github.com/kopnik-org/kopnik-client

Шаг 2. Перейти в директорию с клиентом и собрать проект
``` 
# cd ../kopnik-client
# ./build.sh
``` 

Проверьте наличие собранных файлов в директории html

Шаг 3. Вернитесь в директорию с исходниками сервера и отредактируйте файл .env.local. Экспортируйте переменные среды.

```
# cd ../kopnik-server/
# cat .env.local

export CLIENT_STATIC=../kopnik-client/html
export COMPOSE_FILE=docker-compose-with-client.yml

export CLIENT_DC_EXTERNAL_PORT=80
export SERVER_DC_EXTERNAL_PORT=8080

# . ./env.local
```

Шаг 4. Используйте  make для запуска и остановки кластера.
```
# make up
# make down
```

Шаг 5. Перейдите по ссылке http://127.0.0.1

Дополнительные команды докера
----------------------------- 

Дальше можно работать с докером в обычном режиме.
В режиме разработки (выход Ctrl+C)

```
docker-compose up --build
```

Также можно запустить в фоновом режиме и затем остановиить

```
docker-compose up --build -d
docker-compose down
```

Или более короткий формат запуска через make

```
make up
make down
```

Посмотреть список всех пользователей:
```
docker-compose run php bin/console app:user:list
```

Посмотреть список всех заверителей:
```
docker-compose run php bin/console app:witness:list
```

Назначить пользователя заверителем:
```
docker-compose run php bin/console app:witness:promote
```


Тестирование 
============

Заполнить файл .env.test.local аналогично .env.local (см. выше)

Создать фикстуру локального юзера в файле fixtures/local_user.yaml пример содержимого:

```yaml
App\Entity\User:
    user_me:
        firstName: Test
        lastName: Test
        patronymic: Test
        passportCode: 1234
        birthYear: 1234
        latitude: 0
        longitude: 0
        isWitness: 1
        isAllowMessagesFromCommunity: 1
        createdAt: <date_create()>

App\Entity\UserOauth:
    user_oauth_{@user_me}:
        user: <current()>
        access_token: <sha256()>
        email: test@test.com
        provider: vkontakte
        identifier: 11111111
        createdAt: <date_create()>
``` 

Запуск/остановка тестового окружения:

```
make test-up
make test-down
```

Для обнуления тестовых данных достаточно выполнить:  

```
make test-up
```

Вывести список юзеров:

```
docker-compose -f docker-compose-test.yml run --rm php-test php bin/console app:user:list
```

По умолчанию веб порт для тестов задан 8082, открывать проект по адресу:

```
http://localhost:8082/
``` 

Аутентификация:

```
http://localhost:8082/api/test/login/{id}
```

Локальная установка 
===================

Создать БД postgres.

В вк зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET

Скопировать и отредактировать конфиг
```bash
cp .env .env.local
```

В .env.local нужно задать следующие значения:
```
APP_SECRET=~

OAUTH_VK_CLIENT_ID=~
OAUTH_VK_CLIENT_SECRET=~

VK_COMMUNITY_ID=~
VK_CALLBACK_API_ACCESS_TOKEN=~
```

Установка зависимостей

```bash
composer install
```

Обновление схемы БД
```bash
bin/console migrate
```

Для открытия файлов из профайлера в phpstrom, следует создать файл /config/_local.dev.yaml со следующим содержимым:

```yaml
framework:
    profiler: { only_exceptions: false }
    ide: 'phpstorm://open?url=file://%%f&line=%%l'
```

PostgreSQL
----------

https://medium.com/coding-blocks/creating-user-database-and-adding-access-on-postgresql-8bfcd2f4a91e

```
sudo -u postgres createuser kopnik
sudo -u postgres createdb kopnik
sudo -u postgres psql

alter user <username> with encrypted password '<password>';
grant all privileges on database kopnik to kopnik;
```

В продакшен режиме
------------------

```
@todo 
```

В режиме тестирования

```
@todo 
```

TODO
----
