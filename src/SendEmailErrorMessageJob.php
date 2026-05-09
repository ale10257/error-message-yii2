<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendEmailErrorMessageJob extends BaseObject implements JobInterface
{
    public string $message = '';
    private const EMAIL_SUBJECT = 'Application Market Error Alert';

    public function execute($queue): void
    {
        try {
            $from = $_ENV['SMTP_USERNAME'];
            $to = Yii::$app->sendErrorMessage->emailRecipients;

            $result = Yii::$app->mailer
                ->compose()
                ->setFrom($from)
                ->setTo($to)
                ->setSubject(self::EMAIL_SUBJECT)
                ->setTextBody($this->message)
                ->send();
        } catch (\Throwable $e) {
            Yii::error(
                'SendEmailErrorMessageJob exception: ' . $e->getMessage(),
                'sendErrorMessage'
            );
        }
    }
}
