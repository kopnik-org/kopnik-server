Kopnik.org
==========

Docker
===============
В вк зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET и указать Доверенный redirect URI для OAuth. Ссылка https://vk.com/apps?act=manage

```
# https://vk.com/apps?act=manage раздел Настройки поле Доверенный redirect URI
# номер порта произвольный. по умолчанию 8081
http://localhost:8081/login/check-vk
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

systemctl reboot -i
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
