<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;

class ErrorHandler extends \yii\web\ErrorHandler
{
    use ErrorMessageFormatterTrait;

    public function handleException($exception): void
    {
        if (defined('YII_ENV') && YII_ENV === 'dev') {
            parent::handleException($exception);
            return;
        }
        try {
            if ($this->shouldSendError($exception)) {
                $context = $this->getRequestContext();
                $message = $this->formatErrorMessage($exception, $context);
                Yii::$app->sendErrorMessage->send($message);
            }
            parent::handleException($exception);
        } catch (\Throwable $e) {
            parent::handleException($e);
        }
    }
}
