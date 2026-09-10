<?php

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketClosedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Services\CommentService;
use App\Services\TicketService;
use Illuminate\Notifications\Channels\MailChannel;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
    $this->ticket = app(TicketService::class)->create([
        'department_id' => $this->department->id,
        'title' => 'Mail rendering check',
        'description' => 'Used to verify the real Symfony Message-ID header.',
    ], $this->user);
});

it('sends TicketCreatedNotification through the mail channel with a valid Message-ID header', function () {
    $notification = new TicketCreatedNotification($this->ticket);
    $mail = $notification->toMail($this->user);
    expect($mail->subject)->toContain($this->ticket->reference_number);

    // Ticket creation in beforeEach already fired a TicketCreated event, which
    // synchronously sent its own TicketCreatedNotification (QUEUE_CONNECTION=sync
    // in phpunit.xml) — flush that message so the explicit send below is isolated.
    app('mailer')->getSymfonyTransport()->flush();

    app(MailChannel::class)->send($this->user, $notification);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    // Reaching this without throwing catches the wrong-header-add-method bug;
    // the single-bracket-pair regex shape catches the double-bracketing bug.
    $headers = $messages[0]->getOriginalMessage()->getHeaders();
    expect($headers->get('Message-ID'))->not->toBeNull()
        ->and($headers->get('Message-ID')->getBodyAsString())
        ->toMatch('/^<[^<>]+@[^<>]+>$/');
});

it('sends TicketAssignedNotification through the mail channel with a valid Message-ID header', function () {
    $notification = new TicketAssignedNotification($this->ticket);
    $mail = $notification->toMail($this->operator);
    expect($mail->subject)->toContain($this->ticket->reference_number);

    // Flush the message the TicketCreated event already sent during beforeEach.
    app('mailer')->getSymfonyTransport()->flush();

    app(MailChannel::class)->send($this->operator, $notification);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    $headers = $messages[0]->getOriginalMessage()->getHeaders();
    expect($headers->get('Message-ID'))->not->toBeNull()
        ->and($headers->get('Message-ID')->getBodyAsString())
        ->toMatch('/^<[^<>]+@[^<>]+>$/');
});

it('sends TicketStatusChangedNotification through the mail channel with a valid Message-ID header', function () {
    $notification = new TicketStatusChangedNotification(
        $this->ticket,
        TicketStatus::Open,
        TicketStatus::Closed,
    );
    $mail = $notification->toMail($this->user);
    expect($mail->subject)->toContain($this->ticket->reference_number);

    // Flush the message the TicketCreated event already sent during beforeEach.
    app('mailer')->getSymfonyTransport()->flush();

    app(MailChannel::class)->send($this->user, $notification);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    $headers = $messages[0]->getOriginalMessage()->getHeaders();
    expect($headers->get('Message-ID'))->not->toBeNull()
        ->and($headers->get('Message-ID')->getBodyAsString())
        ->toMatch('/^<[^<>]+@[^<>]+>$/');
});

it('sends NewCommentNotification through the mail channel with a valid Message-ID header', function () {
    $comment = app(CommentService::class)->addReply($this->ticket, $this->operator, 'Test comment');

    $notification = new NewCommentNotification($this->ticket, $comment);
    $mail = $notification->toMail($this->user);
    expect($mail->subject)->toContain($this->ticket->reference_number);

    // Flush messages already sent by the TicketCreated and CommentAdded events.
    app('mailer')->getSymfonyTransport()->flush();

    app(MailChannel::class)->send($this->user, $notification);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    $headers = $messages[0]->getOriginalMessage()->getHeaders();
    expect($headers->get('Message-ID'))->not->toBeNull()
        ->and($headers->get('Message-ID')->getBodyAsString())
        ->toMatch('/^<[^<>]+@[^<>]+>$/');
});

it('sends TicketClosedNotification through the mail channel with a valid Message-ID header', function () {
    $notification = new TicketClosedNotification($this->ticket);
    $mail = $notification->toMail($this->user);
    expect($mail->subject)->toContain($this->ticket->reference_number);

    // Flush the message the TicketCreated event already sent during beforeEach.
    app('mailer')->getSymfonyTransport()->flush();

    app(MailChannel::class)->send($this->user, $notification);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    $headers = $messages[0]->getOriginalMessage()->getHeaders();
    expect($headers->get('Message-ID'))->not->toBeNull()
        ->and($headers->get('Message-ID')->getBodyAsString())
        ->toMatch('/^<[^<>]+@[^<>]+>$/');
});
