#Запуск в Docker

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



##Для разработки

##Для тестирования
