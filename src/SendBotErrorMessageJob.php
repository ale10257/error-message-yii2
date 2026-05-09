<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendBotErrorMessageJob extends BaseObject implements JobInterface
{
    public string $message = '';

    public function execute($queue): void
    {
        $client = Yii::$app->sendErrorMessage->client;
        $client->sendErrorMsg($this->message);
    }
}
