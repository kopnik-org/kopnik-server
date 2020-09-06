#Запуск в Docker

Работа с докером сделана, через Makefile и команду make. Так как в симфони есть понятие "окружение" (environment), то для каждого окружения можно поднять отдельные докер контейнеры. 

Значение окружения для симфони задаётся в конфиге .env.local, в репозитории он отсутсвует, его можно создать в ручную скопировав файл .env, либо вызвать команду `make generate-env-files`, которая по умолчанию создаст заготовки конфигов для prod окружения. Если нужно работать с другим окружением, что нужно для make указать в переменной имя окружения, например `make env=dev generate-env-files`. Притом если ранее конфиги уже были созданы, то они НЕ будут затёрты, а команда ренерации конфигов скажем, что конфиги уже существуют.

Итого, если нужно работать на локальной машине в дев окружении, то нужно, чтобы в .env.local было указано `APP_ENV=dev`

Чтобы узнать в каком окружении по умолчанию будет работать, команда make, то достаточно просто вызвать её и в квадратных скобках будет указано окружение, которое будет считываться из файла .env.local, если его нет, то из .env

##На продакшине

В вк зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET. Ссылка https://vk.com/apps?act=manage

В разделе Настройки указать Базовый домен и Доверенный redirect URI для OAuth

Получение кода:
```
git clone https://github.com/kopnik-org/kopnik-server
cd kopnik-server
```

Генерация конфигов:
```
make generate-env-files
```

В .env.local нужно задать следующие значения:
``` 
# Указать произвольную строку
# APP_SECRET=klu9rofij239rfsd0
APP_SECRET=~

# Убедиться, что заданы эти значения:
APP_ENV=prod
APP_DEBUG=0

# Группа > настройки > работа с АПИ > колбэк АПИ > поле group_id (жирным)
# VK_COMMUNITY_ID=144968351
VK_COMMUNITY_ID=~

# Группа > настройки > работа с АПИ > Ключи доступа
# VK_CALLBACK_API_ACCESS_TOKEN=jkh2349df8ujcrf9d8fujclrhjuwe9f8usdfjic9f8dufjac3qcuf
VK_CALLBACK_API_ACCESS_TOKEN=~

# https://vk.com/apps?act=manage, создать ВЕБ-приложение, пеерейти в рздел "Настройки"
# OAUTH_VK_CLIENT_ID=7210289
OAUTH_VK_CLIENT_ID=~

# OAUTH_VK_CLIENT_SECRET=UHIE908J098fjFE998
OAUTH_VK_CLIENT_SECRET=~
```

Развертывание и обновление:

```
make deploy
``` 


##Для разработки

В вк зарегать приложение и получить OAUTH_VK_CLIENT_ID и OAUTH_VK_CLIENT_SECRET. Ссылка https://vk.com/apps?act=manage

В разделе Настройки указать Базовый домен и Доверенный redirect URI для OAuth

Получение кода:
```
git clone https://github.com/kopnik-org/kopnik-server
cd kopnik-server
```

Генерация конфигов:
```
make env=dev generate-env-files
```

В .env.local нужно задать следующие значения:
``` 
# Указать произвольную строку
# APP_SECRET=klu9rofij239rfsd0
APP_SECRET=~

# Убедиться, что заданы эти значения:
APP_ENV=dev
APP_DEBUG=1

# Группа > настройки > работа с АПИ > колбэк АПИ > поле group_id (жирным)
# VK_COMMUNITY_ID=144968351
VK_COMMUNITY_ID=~

# Группа > настройки > работа с АПИ > Ключи доступа
# VK_CALLBACK_API_ACCESS_TOKEN=jkh2349df8ujcrf9d8fujclrhjuwe9f8usdfjic9f8dufjac3qcuf
VK_CALLBACK_API_ACCESS_TOKEN=~

# https://vk.com/apps?act=manage, создать ВЕБ-приложение, пеерейти в рздел "Настройки"
# OAUTH_VK_CLIENT_ID=7210289
OAUTH_VK_CLIENT_ID=~

# OAUTH_VK_CLIENT_SECRET=UHIE908J098fjFE998
OAUTH_VK_CLIENT_SECRET=~
```

В .env.docker.dev.local указать следующие значения:

```
WEB_PORT=8081
```

Развертывание и обновление:

```
make deploy
``` 

##Для тестирования

Получение кода:
```
git clone https://github.com/kopnik-org/kopnik-server
cd kopnik-server
```

Генерация конфигов:
```
make env=test generate-env-files
```

В .env.docker.test.local указать следующие значения:

```
WEB_PORT=8082
```

Запуск тестового докера:

```
make test-full-up 
```

##Работа с консолью симфони через докер

По умолчанию работа с консольными командами симфони, выполнчется через скрипт `bin/console` но он работает через локальный php. Для работы через докер, нужно вызывать скрипт `bin/docker_console`, которая сам будет подхватывать окружение из файла .env.local, например:

```
# Просмотр всех пользователей
bin/docker_console app:user:list   

# Назначить пользователя заверителем, указав vk_id юзера.
bin/docker_console app:witness:promote
```
