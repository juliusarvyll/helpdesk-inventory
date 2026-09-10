<?php

use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Events\TicketStatusChanged;
use App\Models\Department;
use App\Models\Operator;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(TicketService::class);
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create();
    $this->operator = Operator::factory()->create();
});

it('creates a ticket and dispatches TicketCreated', function () {
    Event::fake([TicketCreated::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Cannot log in',
        'description' => 'Password reset email never arrives.',
    ], $this->user);

    expect($ticket->status)->toBe(TicketStatus::Open);
    expect($ticket->user_id)->toBe($this->user->id);
    Event::assertDispatched(TicketCreated::class);
});

it('assigns (claims) a ticket to an operator and dispatches TicketAssigned', function () {
    Event::fake([TicketAssigned::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Printer jam',
        'description' => 'Printer on 3rd floor is jammed.',
    ], $this->user);

    $this->service->assign($ticket, $this->operator, $this->operator);

    $ticket->refresh();
    expect($ticket->assigned_to_id)->toBe($this->operator->id);
    Event::assertDispatched(TicketAssigned::class, fn ($event) => $event->ticket->is($ticket) && $event->assignedTo->is($this->operator));
});

it('dispatches TicketStatusChanged and TicketClosed when closing a ticket', function () {
    Event::fake([TicketStatusChanged::class, TicketClosed::class]);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Broken chair',
        'description' => 'Office chair leg broke.',
    ], $this->user);

    $this->service->close($ticket, $this->operator);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed);
    Event::assertDispatched(TicketStatusChanged::class);
    Event::assertDispatched(TicketClosed::class);
});

it('dispatches TicketReopened when reopening a closed ticket', function () {
    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Broken chair',
        'description' => 'Office chair leg broke.',
    ], $this->user);
    $this->service->close($ticket, $this->operator);

    Event::fake([TicketReopened::class]);
    $this->service->reopen($ticket, $this->operator);

    $ticket->refresh();
    expect($ticket->status)->not->toBe(TicketStatus::Closed);
    Event::assertDispatched(TicketReopened::class);
});
