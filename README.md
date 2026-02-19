# ale10257/error-message-yii2

Yii2-компонент для отправки сообщений об ошибках через подключаемый мессенджер (your-messenger, email, Slack и т.д.). Реализация мессенджера задаётся в конфиге, логика приложения от неё не зависит.

## Требования

- PHP 8.1+
- В приложении при старте должна загружаться переменные окружения из `.env` (например через `vlucas/phpdotenv`). Компонент ожидает, что конфиг уже использует `$_ENV` для токенов и chat id.

## Установка

```bash
composer require ale10257/error-message-yii2
```

## Подключение в конфиге

### 1. Компонент `sendErrorMessage`

В конфиг приложения (например `config/web.php` или `config/common.php`) добавьте компонент и реализацию интерфейса `IErrorBot`:

```php
'components' => [
    'sendErrorMessage' => [
        'class' => \ale10257\sendError\ErrorMsgComponent::class,
        'client' => [
            'class' => \YourNamespace\YourMessenger::class,  // реализация IErrorBot
            'apiKey'   => $_ENV['YOUR_MESSENGER_API_KEY'],
            'chatId'   => $_ENV['YOUR_MESSENGER_CHAT_ID'],
        ],
        'errorCodeMap' => [
            \yii\web\NotFoundHttpException::class => 404,
            \yii\web\ForbiddenHttpException::class => 403,
        ],
    ],
],
```

- **client** — объект, реализующий `\ale10257\sendError\IErrorBot` (метод `sendErrorMsg(string $message): void`). В конфиге задаётся класс и параметры (токен, chat id и т.д.), значения можно брать из `$_ENV`.
- **errorCodeMap** — соответствие «класс исключения → HTTP-код». Исключения с кодами из этого маппинга (404, 403 и т.д.) в мессенджер не отправляются; отправляются только «неизвестные» (например 500).

### 2. Обработчик исключений приложения

Чтобы необработанные исключения уходили в мессенджер, подключите `ErrorHandler` пакета:

```php
// config/web.php
'components' => [
    'errorHandler' => [
        'class' => \ale10257\sendError\ErrorHandler::class,
    ],
    // ...
],
```

## Примеры использования

### Отправка произвольного текста

```php
\Yii::$app->sendErrorMessage->send('Критическая ошибка: сервис недоступен');
```

### Логирование с отправкой в мессенджер

Класс `LogError` пишет в лог и при необходимости отправляет сообщение через компонент:

```php
use ale10257\sendError\LogError;

try {
    // ...
} catch (\Throwable $e) {
    LogError::error('Ошибка при обработке заказа', $e, false);
}
```

В dev-окружении (`YII_ENV === 'dev'`) исключение можно пробрасывать дальше: третий параметр `true` — тогда в dev будет выброшен exception, а не только лог и мессенджер.

### Какую точку входа использовать

- **Необработанное исключение**: ничего вручную не вызывайте — если в конфиге подключён `\\ale10257\\sendError\\ErrorHandler`, он сам перехватит исключение и отправит сообщение.
- **“Мягкая” ошибка (исключение поймали и обработали)**: используйте `LogError::error(...)`, потому что глобальный `ErrorHandler` в этом случае не сработает.

## Интерфейс мессенджера

Любая реализация должна реализовать `\ale10257\sendError\IErrorBot`:

```php
namespace YourNamespace;

use ale10257\sendError\IErrorBot;

class YourMessenger implements IErrorBot
{
    public function __construct(
        private string $apiKey,
        private string $chatId,
    ) {}

    public function sendErrorMsg(string $message): void
    {
        // POST в API your-messenger (или другой сервис)
    }
}
```

В конфиге такой класс подставляется в `sendErrorMessage.client`; подойдёт your-messenger, email, Slack и т.д.
