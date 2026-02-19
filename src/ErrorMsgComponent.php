<?php

declare(strict_types=1);

namespace ale10257\sendError;

use yii\base\Component;
class ErrorMsgComponent extends Component
{
    public IErrorBot $client;

    public function send(string $msg): void
    {
        $this->client->sendErrorMsg($msg);
    }
}
