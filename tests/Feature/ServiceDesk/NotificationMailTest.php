<?php

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Services\CommentService;
use App\Services\TicketService;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
    $this->ticket = app(TicketService::class)->create([
        'department_id' => $this->department->id,
        'title' => 'Mail rendering check',
        'description' => 'Used to render this to verify Message-ID header.',
    ], $this->user);
});

it('renders TicketCreatedNotification to a real mail message without throwing', function () {
    $mail = (new TicketCreatedNotification($this->ticket))->toMail($this->user);

    expect((string) $mail->render())->toContain($this->ticket->reference_number);
});

it('renders TicketAssignedNotification to a real mail message without throwing', function () {
    $mail = (new TicketAssignedNotification($this->ticket))->toMail($this->operator);

    expect((string) $mail->render())->toContain($this->ticket->reference_number);
});

it('renders TicketStatusChangedNotification to a real mail message without throwing', function () {
    $mail = (new TicketStatusChangedNotification(
        $this->ticket,
        TicketStatus::Open,
        TicketStatus::Closed,
    ))->toMail($this->user);

    expect((string) $mail->render())->toContain($this->ticket->reference_number);
});

it('renders NewCommentNotification to a real mail message without throwing', function () {
    $comment = app(CommentService::class)->addReply($this->ticket, $this->operator, 'Test comment');

    $mail = (new NewCommentNotification($this->ticket, $comment))->toMail($this->user);

    expect((string) $mail->render())->toContain($this->ticket->reference_number);
});
