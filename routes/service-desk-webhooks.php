<?php

use App\Http\Controllers\ServiceDesk\MailgunWebhookController;
use App\Http\Controllers\ServiceDesk\PostmarkWebhookController;
use App\Http\Controllers\ServiceDesk\ResendWebhookController;
use App\Http\Controllers\ServiceDesk\SendGridWebhookController;
use App\Http\Middleware\VerifyMailgunSignature;
use App\Http\Middleware\VerifyPostmarkSignature;
use App\Http\Middleware\VerifyResendSignature;
use App\Http\Middleware\VerifySendGridSignature;
use Illuminate\Support\Facades\Route;

Route::prefix(config('service-desk.webhooks.prefix', 'service-desk/webhooks'))
    ->middleware(config('service-desk.webhooks.middleware', []))
    ->group(function () {
        Route::post('mailgun', MailgunWebhookController::class)
            ->middleware(VerifyMailgunSignature::class)
            ->name('service-desk.webhooks.mailgun');

        Route::post('sendgrid', SendGridWebhookController::class)
            ->middleware(VerifySendGridSignature::class)
            ->name('service-desk.webhooks.sendgrid');

        Route::post('resend', ResendWebhookController::class)
            ->middleware(VerifyResendSignature::class)
            ->name('service-desk.webhooks.resend');

        Route::post('postmark', PostmarkWebhookController::class)
            ->middleware(VerifyPostmarkSignature::class)
            ->name('service-desk.webhooks.postmark');
    });
