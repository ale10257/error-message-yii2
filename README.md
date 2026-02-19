# ale10257/error-message-yii2

Yii2-компонент для отправки сообщений об ошибках через подключаемый мессенджер (Telegram, email и т.д.).

## Требования

- PHP 8.1+
- В приложении должен быть подключён и загружен `vlucas/phpdotenv` (загрузка `.env` при старте). Без этого компонент не запустится.

## Установка

```bash
composer require ale10257/error-message-yii2
```

## Настройка

В конфиг приложения (например `config/web.php`) добавьте компонент и реализацию интерфейса `IErrorBot`:

```php
'components' => [
    'sendErrorMessage' => [
        'class' => ale10257\sendError\ErrorMsgComponent::class,
        'client' => [
            'class' => \YourNamespace\YourMessenger::class,  // реализация IErrorBot переменные $_ENV должны быть для вашего мессенджера, они должны его инициализировать
            'botToken' => $_ENV['TELEGRAM_API_KEY'],
            'chatId'   => $_ENV['TELEGRAM_CHAT_ID'],
        ],
    ],
],
```

Переменные $_ENV должны быть заданы в `.env` (загружается через dotenv в `index.php` или бутстрапе).

## Использование

```php
\Yii::$app->sendErrorMessage->send('Текст сообщения об ошибке');
```

Любая реализация `IErrorBot` (Telegram, email, Slack и т.д.) подставляется через конфиг; логика приложения не меняется.
