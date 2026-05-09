<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class ErrorMsgComponent extends Component
{
    use ErrorMessageFormatterTrait;

    /** @var IErrorBot|array */
    public $client;
    public ?string $pathTo504 = null;
    public string $queueComponentId = 'queue';
    public bool $enableBotError = true;
    public bool $enableEmailError = true;
    /** @var string[] */
    public array $emailRecipients = [];

    /**
     * Соответствие класс исключения → HTTP-код. Задаётся в конфиге приложения.
     * @var array<string, int>
     */
    public array $errorCodeMap = [
        NotFoundHttpException::class => 404,
    ];

    public function send(string $msg): void
    {
        /** @var mixed $queue */
        $queue = Yii::$app->get($this->queueComponentId);

        if ($this->enableBotError) {
            $queue->push(new SendBotErrorMessageJob([
                'message' => $msg,
            ]));
        }

        if ($this->enableEmailError) {
            $queue->push(new SendEmailErrorMessageJob([
                'message' => $msg,
            ]));
        }
    }
}
