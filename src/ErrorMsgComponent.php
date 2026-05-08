<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\Component;
use yii\di\NotInstantiableException;
use yii\web\NotFoundHttpException;

class ErrorMsgComponent extends Component
{
    use ErrorMessageFormatterTrait;

    /** @var IErrorBot|array */
    public $client;
    public ?string $pathTo504 = null;
    public bool $useQueue = true;
    public string $queueComponentId = 'queue';
    public bool $enableBotError = true;
    public bool $enableEmailError = true;
    /** @var string[] */
    public array $emailRecipients = [];

    public function init()
    {
        parent::init();

        // Превращаем массив конфигурации в объект
        if (is_array($this->client)) {
            $this->client = Yii::createObject($this->client);
        }
    }

    /**
     * Соответствие класс исключения → HTTP-код. Задаётся в конфиге приложения.
     * @var array<string, int>
     */
    public array $errorCodeMap = [
        NotFoundHttpException::class => 404,
    ];

    public function send(string $msg): void
    {
        if (!$this->enableBotError && !$this->enableEmailError) {
            return;
        }

        if ($this->useQueue && $this->canPushToQueue()) {
            try {
                /** @var mixed $queue */
                $queue = Yii::$app->get($this->queueComponentId);
                $queue->push(new SendErrorMessageJob([
                    'message' => $msg,
                ]));

                return;
            } catch (\Throwable $e) {
                Yii::error('Failed to enqueue error message: ' . $e->getMessage(), 'sendErrorMessage');
                return;
            }
        }

        try {
            $this->client->sendErrorMsg($msg);
        } catch (\Throwable $e) {
            Yii::error('Failed to send error message: ' . $e->getMessage(), 'sendErrorMessage');
        }
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

    private function canPushToQueue(): bool
    {
        if (!Yii::$app->has($this->queueComponentId)) {
            return false;
        }

        try {
            /** @var mixed $queue */
            $queue = Yii::$app->get($this->queueComponentId);
        } catch (NotInstantiableException $e) {
            Yii::error('Queue component is not instantiable: ' . $e->getMessage(), 'sendErrorMessage');
            return false;
        } catch (\Throwable $e) {
            Yii::error('Failed to resolve queue component: ' . $e->getMessage(), 'sendErrorMessage');
            return false;
        }

        return method_exists($queue, 'push');
    }
}
