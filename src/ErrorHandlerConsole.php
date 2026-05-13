<?php

namespace ale10257\sendError;

class ErrorHandlerConsole extends \yii\console\ErrorHandler
{
    use ErrorMessageFormatterTrait;

    public function handleException($exception): void
    {
//        if (defined('YII_ENV') && YII_ENV === 'dev') {
//            parent::handleException($exception);
//            return;
//        }
        try {
            if ($this->shouldSendError($exception)) {
                $message = $this->formatErrorMessage($exception);
                \Yii::$app->sendErrorMessage->send($message);
            }
            parent::handleException($exception);
        } catch (\Throwable $e) {
            parent::handleException($e);
        }
    }
}