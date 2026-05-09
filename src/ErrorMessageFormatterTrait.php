<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\web\Application;

trait ErrorMessageFormatterTrait
{
    /**
     * Определяет код ошибки из исключения. Маппинг берётся из компонента sendErrorMessage (errorCodeMap).
     */
    protected function getErrorCode(\Throwable $e): int
    {
        $component = Yii::$app->sendErrorMessage;
        foreach ($component->errorCodeMap as $class => $code) {
            if ($e instanceof $class) {
                return $code;
            }
        }
        return $e->getCode() ?: 500;
    }

    /**
     * Проверяет, нужно ли отправлять ошибку в мессенджер.
     * Не отправляем, если код ошибки есть среди значений errorCodeMap компонента.
     */
    protected function shouldSendError(\Throwable $e): bool
    {
        if (defined('YII_ENV') && YII_ENV === 'dev') {
            return false;
        }

        $code = $this->getErrorCode($e);
        $excludedCodes = array_values(Yii::$app->sendErrorMessage->errorCodeMap);
        return !in_array($code, $excludedCodes, true);
    }

    /**
     * Формирует контекст запроса для ошибки
     */
    protected function getRequestContext(): array
    {
        $context = ['url' => null];

        if (Yii::$app instanceof Application && Yii::$app->has('request')) {
            $request = Yii::$app->request;
            $context['url'] = $request->absoluteUrl ?? $request->url ?? null;
        }

        return $context;
    }

    /**
     * Формирует сообщение об ошибке для мессенджера
     */
    protected function formatErrorMessage(\Throwable $e, array $context = []): string
    {
        $message = 'Ошибка: ' . $e->getMessage() . PHP_EOL;

        if (!empty($context['url'])) {
            $message .= 'URL: ' . $context['url'] . PHP_EOL;
        }

        $message .= 'Trace:' . PHP_EOL . $e->getTraceAsString();

        return $message;
    }
}
