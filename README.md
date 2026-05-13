# ale10257/error-message-yii2

Yii2-компонент для отправки сообщений об ошибках в фоне через `queue`:
- в бот (любой клиент с интерфейсом `IErrorBot`);
- на email через стандартный `Yii::$app->mailer`.

## Требования

- PHP 7.4+
- В приложении при старте должна загружаться переменные окружения из `.env` (например через `vlucas/phpdotenv`). Компонент ожидает, что конфиг уже использует `$_ENV` для токенов и chat id.

## Установка

```bash
composer require ale10257/error-message-yii2
```

## Подключение в конфиге

### 1. Компонент `sendErrorMessage`

В конфиг приложения (например `config/web.php` или `config/common.php`) добавьте компонент:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\file\Queue::class,
        'path' => '@runtime/queue',
    ],
    'sendErrorMessage' => [
        'class' => \ale10257\sendError\ErrorMsgComponent::class,
        'queueComponentId' => 'queue',
        'enableBotError' => (int)($_ENV['ENABLE_BOT_ERROR']) === 1,
        'enableEmailError' => (int)($_ENV['ENABLE_EMAIL_ERROR']) === 1,
        'emailRecipients' => [
            (string)$_ENV['ADMIN_EMAIL'],
            (string)$_ENV['PROCTOLEHA_EMAIL'],
        ],
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

- **client** — объект, реализующий `\ale10257\sendError\IErrorBot` (метод `sendErrorMsg(string $message): void`).
- **useQueue** — если `true`, отправка сообщений выполняется через `Yii::$app->queue` и не блокирует веб-запрос.
- **queueComponentId** — id компонента очереди (по умолчанию `queue`).
- **enableBotError** — включает/выключает отправку в бот.
- **enableEmailError** — включает/выключает отправку email.
- **emailRecipients** — список email-получателей сообщений об ошибках.
- **errorCodeMap** — соответствие «класс исключения → HTTP-код». Исключения с кодами из этого маппинга (404, 403 и т.д.) в мессенджер не отправляются; отправляются только «неизвестные» (например 500).

### Запуск воркера очереди

После включения `useQueue` нужен запущенный воркер:

```bash
php yii queue/listen --verbose=1
```

### Переменные окружения

```dotenv
ENABLE_BOT_ERROR=1
ENABLE_EMAIL_ERROR=1
ADMIN_EMAIL=admin@example.com
PROCTOLEHA_EMAIL=proctoleha@example.com
SMTP_USERNAME=robot@example.com
```

`SMTP_USERNAME` используется как `from` при отправке email.  
Тема письма по умолчанию: `Application Error Alert`.

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
```php
// config/console.php
'components' => [
'errorHandler' => [
'class' => \ale10257\sendError\ErrorHandlerConsole::class,
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

## Интерфейс бота

Любая реализация должна реализовать `\ale10257\sendError\IErrorBot`:

```php
namespace YourNamespace;

use ale10257\sendError\IErrorBot;

class YourMessenger implements IErrorBot
{
    private string $apiKey;
    private string $chatId;

    public function __construct(string $apiKey, string $chatId)
    {
        $this->apiKey = $apiKey;
        $this->chatId = $chatId;
    }

    public function sendErrorMsg(string $message): void
    {
        // POST в API your-messenger (или другой сервис)
    }
}
```

В конфиге такой класс подставляется в `sendErrorMessage.client`.
