<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class ErrorMsgComponent extends Component
{
    use ErrorMessageFormatterTrait;

    /** @var IErrorBot Конфиг может быть массивом с ключом `class`; после {@see init()} всегда экземпляр. */
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

    public function init(): void
    {
        parent::init();
        $this->client = Yii::createObject($this->client);
    }

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
