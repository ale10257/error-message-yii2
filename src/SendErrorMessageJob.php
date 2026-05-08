<?php

declare(strict_types=1);

namespace ale10257\sendError;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendErrorMessageJob extends BaseObject implements JobInterface
{
    public string $message = '';
    private const EMAIL_SUBJECT = 'Application Error Alert';

    public function execute($queue): void
    {
        try {
            $component = Yii::$app->sendErrorMessage ?? null;
            if ($component === null) {
                return;
            }

            if (!$component->enableBotError && !$component->enableEmailError) {
                return;
            }

            if ($component->enableBotError) {
                $client = $component->client ?? null;
                if (!$client instanceof IErrorBot) {
                    Yii::error('SendErrorMessageJob: client must implement IErrorBot.', 'sendErrorMessage');
                } else {
                    $client->sendErrorMsg($this->message);
                }
            }

            if ($component->enableEmailError) {
                $this->sendEmail($component);
            }
        } catch (\Throwable $e) {
            Yii::error('SendErrorMessageJob failed: ' . $e->getMessage(), 'sendErrorMessage');
        }
    }

    private function sendEmail($component): void
    {
        if (!Yii::$app->has('mailer')) {
            Yii::error('SendErrorMessageJob: mailer component not found.', 'sendErrorMessage');
            return;
        }

        $recipients = [];
        if (isset($component->emailRecipients) && is_array($component->emailRecipients)) {
            $recipients = $component->emailRecipients;
        } elseif (isset(Yii::$app->params['sendErrorEmails']) && is_array(Yii::$app->params['sendErrorEmails'])) {
            $recipients = Yii::$app->params['sendErrorEmails'];
        }

        $recipients = array_values(array_filter($recipients, static function ($email) {
            return is_string($email) && $email !== '';
        }));
        if ($recipients === []) {
            Yii::error('SendErrorMessageJob: recipients list is empty.', 'sendErrorMessage');
            return;
        }

        $from = (string)($_ENV['SMTP_USERNAME'] ?? '');
        if ($from === '') {
            Yii::error('SendErrorMessageJob: SMTP_USERNAME is empty.', 'sendErrorMessage');
            return;
        }

        Yii::$app->mailer
            ->compose()
            ->setFrom($from)
            ->setTo($recipients)
            ->setSubject(self::EMAIL_SUBJECT)
            ->setTextBody($this->message)
            ->send();
    }
}
