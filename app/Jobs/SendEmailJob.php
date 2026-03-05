<?php
// FILE: app/Jobs/SendEmailJob.php

namespace App\Jobs;

use App\Services\Mailer;

class SendEmailJob
{
    public function handle(array $data): void
    {
        $mailer = new Mailer();
        $mailer->send($data['to'], $data['subject'], $data['body']);
    }
}
