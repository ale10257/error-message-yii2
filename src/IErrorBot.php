<?php

declare(strict_types=1);

namespace ale10257\sendError;

interface IErrorBo
{
    public function sendErrorMsg(string $message): void;
}
