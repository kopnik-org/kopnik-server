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

TODO
----

1. ~~Заменить MySQL на PostrgeSQL~~
2. ~~Выставить права на исполнения для файлов bin/*~~
