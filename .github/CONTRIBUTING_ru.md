# Участие в разработке

[English version](CONTRIBUTING.md)

Мы рады полезным и сфокусированным изменениям Noter. Следуйте [кодексу поведения](CODE_OF_CONDUCT_ru.md) и сохраняйте совместимость CLI и HTTP-запроса.

## Перед pull request

- Не добавляйте в тесты и примеры реальные заметки, API-ключи, учётные данные, закрытые ключи и production URL.
- Опишите поддерживаемые версии PHP и видимое пользователю поведение изменения.
- Соблюдайте форматирование PHP и YAML из `.editorconfig`.

## Локальные проверки

```sh
composer install --no-interaction --prefer-dist
composer validate --strict
composer run lint
bash -n install.sh
composer run build:phar
composer run test
```

Интеграционный тест запускает временный HTTP-приёмник на `127.0.0.1` и не обращается к настроенному удалённому сервису.

## Pull request

Используйте короткий заголовок Conventional Commit, объясните причину изменения и перечислите выполненные команды. Не добавляйте в diff несвязанные изменения форматирования и созданные артефакты сборки. Об уязвимостях сообщайте по [политике безопасности](SECURITY_ru.md), а не через открытый issue.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
