<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;

class LogError
{
    use ErrorMessageFormatterTrait;

    /**
     * @throws \Throwable
     */
    public static function error(
        string $message,
        \Throwable $e,
        bool $isException = true,
        string $category = 'application'
    ): void {
        if (defined('YII_ENV') && YII_ENV === 'dev' && $isException) {
            throw $e;
        }
        $instance = new self();
        if ($instance->shouldSendError($e)) {
            $context = $instance->getRequestContext();
            $errorMessage = $instance->formatErrorMessage($e, $context);
            Yii::$app->sendErrorMessage->send($errorMessage);
        }
        Yii::error($message, $category);
    }
}
