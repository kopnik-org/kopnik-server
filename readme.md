Kopnik.org
==========

Установка
---------

Создать БД

В ве зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET

Скопировать и отредактировать конфиг
```bash
cp .env .env.local
```

Установка зависимостей

```bash
composer install
```

Обновление схемы БД
```bash
bin/console migrate
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

Запуск в Docker
===============

В режиме разработки (выход Ctrl+C)

```
docker-compose up --build
```

Также можно запустить в фоновом режиме и затем остановиить

```
docker-compose up --build -d
docker-compose down
```

или более короткий формат запуска через make

```
make up
make down
```

По умолчанию веб порт задан 8081, открывать проект по адресу:

```
http://localhost:8081/
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
