Kopnik.org
==========

Установка
---------

Создать БД

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

1. Заменить MySQL на PostrgeSQL
2. ~~Выставить права на исполнения для файлов bin/*~~
