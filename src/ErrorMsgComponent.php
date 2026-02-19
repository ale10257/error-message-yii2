<?php

declare(strict_types=1);

namespace ale10257\sendError;

use yii\base\Component;
use yii\web\NotFoundHttpException;

class ErrorMsgComponent extends Component
{
    use ErrorMessageFormatterTrait;

    public IErrorBot $client;

    /**
     * Соответствие класс исключения → HTTP-код. Задаётся в конфиге приложения.
     * @var array<string, int>
     */
    public array $errorCodeMap = [
        NotFoundHttpException::class => 404,
    ];

    public function send(string $msg): void
    {
        $this->client->sendErrorMsg($msg);
    }

    /**
     * Отправляет сообщение об ошибке в мессенджер, если код не в errorCodeMap (значения маппинга не отправляются).
     */
    public function sendException(\Throwable $e): void
    {
        if (!$this->shouldSendError($e)) {
            return;
        }
        $context = $this->getRequestContext();
        $this->send($this->formatErrorMessage($e, $context));
    }
}
