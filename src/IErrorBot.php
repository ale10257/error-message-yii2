<?php

declare(strict_types=1);

namespace ale10257\sendError;

interface IErrorBot
{
    public function sendErrorMsg(string $message): void;
}
