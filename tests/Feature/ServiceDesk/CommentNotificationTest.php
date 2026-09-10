<?php

use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Services\CommentService;
use App\Services\TicketService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->ticketService = app(TicketService::class);
    $this->commentService = app(CommentService::class);
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
    $this->ticket = $this->ticketService->create([
        'department_id' => $this->department->id,
        'title' => 'Slow laptop',
        'description' => 'Laptop takes 10 minutes to boot.',
    ], $this->user);
});

it('notifies the ticket requester when an operator adds a public reply', function () {
    Notification::fake();

    $this->commentService->addReply($this->ticket, $this->operator, 'Have you tried a factory reset?');

    Notification::assertSentTo($this->user, NewCommentNotification::class);
});

it('does not notify the comment author about their own reply', function () {
    Notification::fake();

    $this->commentService->addReply($this->ticket, $this->user, 'Still an issue.');

    Notification::assertNotSentTo($this->user, NewCommentNotification::class);
});

it('does not notify anyone for an internal note', function () {
    Notification::fake();

    $this->commentService->addNote($this->ticket, $this->operator, 'Internal note for the team.');

    Notification::assertNothingSent();
});
