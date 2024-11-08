<p align="center"><a href="https://www.localzet.com" target="_blank">
  <img src="https://static.zorin.space/media/logos/ZorinProjectsSP.svg">
</a></p>

# Noter

#### Утилита командной строки для создания и управления заметками

[![](https://img.shields.io/github/commit-activity/t/localzet/noter)](#)
[![](https://img.shields.io/github/v/release/localzet/noter)](#)
[![](https://img.shields.io/github/actions/workflow/status/localzet/noter/release.yml)](#)
[![](https://img.shields.io/github/downloads/localzet/noter/total)](#)
[![](https://img.shields.io/github/license/localzet/noter?longCache=true)](#)

# Noter

Noter - это утилита командной строки для создания и управления заметками. Программа использует PHP и может быть
установлена и использована на любой системе, поддерживающей PHP.

## Особенности

- Быстрое создание заметок через командную строку
- Отправка заметок на удаленный сервер-хранилище

## Установка и обновление

Вы можете установить Noter, выполнив следующую команду, где `https://your.custom.url` и `your_key_value` - URL и ключ
вашего сервера-хранилища.

```shell
bash <(curl -Ls https://raw.githubusercontent.com/localzet/noter/master/install.sh) -u "https://your.custom.url" -k "your_key_value"
```

## Использование

Чтобы создать заметку, просто выполните команду:

```bash
noter "Ваш текст заметки"
```

Или вы можете передать текст через стандартный входной поток:

```bash
echo "Ваш текст заметки" | noter
```

## Конфигурация

После установки Noter, файл конфигурации будет создан в `/usr/local/noter/noter.zconf`.
Этот файл будет содержать ваши настройки URL и ключа:

```ini
NOTER_URL = "https://your.custom.url"
NOTER_KEY = "your_key_value"
```

> Если при установке вы не укажете флаги `-u` и `-k` - параметры будут пусты и программа не сможет функционировать!

## Сервер-хранилище

Чтобы ваш сервер мог обрабатывать заметки, он должен принимать POST-запросы с содержимым заметок. 
Вот пример схемы работы бэкенда:

1. HTTP сервер: Ваш сервер должен принимать HTTP POST-запросы на указанный URL.
2. Параметры запроса: Запрос должен содержать JSON с полем content, который содержит текст заметки.
3. Аутентификация: Ваш сервер должен проверять наличие и правильность ключа API (X-API-KEY) в заголовках запроса.
4. Сохранение заметки: После проверки ключа, сервер должен сохранить содержимое заметки в базе данных или другом хранилище.

### Пример реализации на PHP

```php
<?php

// Конфигурация
$apiKey = "your_key_value";
$database = []; // Пример хранилища (может быть заменено на базу данных)

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    
    // Проверка API ключа
    if (isset($headers['X-API-KEY']) && $headers['X-API-KEY'] === $apiKey) {
        // Получение содержимого запроса
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['content'])) {
            // Сохранение заметки (в примере в массиве, может быть заменено на базу данных)
            $database[] = $data['content'];
            echo json_encode(['status' => 200, 'message' => 'Note saved']);
        } else {
            echo json_encode(['status' => 400, 'message' => 'Invalid content']);
        }
    } else {
        echo json_encode(['status' => 403, 'message' => 'Invalid API key']);
    }
} else {
    echo json_encode(['status' => 405, 'message' => 'Invalid request method']);
}
```

### Пример реализации в [Triangle Web](https://github.com/Triangle-org/Web)
```php
<?php // config/route.php

use Triangle\Database\Manager;
use Triangle\Exception\BusinessException;
use Triangle\Http\{Request, Response};
use Triangle\Router;

Router::post('/save', function (Request $request): Response {
    // Конфигурация
    $apiKey = "your_key_value";
    
    // Проверка API ключа
    if ($request->header('X-API-KEY') === $apiKey) {
        if ($content = $request->post('content')) {
            // Генерация UUID для идентификации записи
            $id = generateId();
        
            // Сохранение заметки в базе данных
            Manager::table('Noter')->insert(compact('id', 'content'));
            
            // Возврат в консоль идентификатора заметки
            return response($id);
        } else {
            throw new BusinessException('Invalid content', 400);
        }
    } else {
        throw new BusinessException('Invalid API key', 403);
    }
});
```