<?php

use App\Filament\App\Resources\Tickets\Pages\CreateTicket;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

it('links selected inventory items to the created ticket with quantity', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->stock()->create(['department_id' => $department->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Need more paper',
            'description' => 'Running low on printer paper.',
            'ticketInventoryItems' => [
                ['inventory_item_id' => $item->id, 'quantity' => 3],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->latest('id')->first();

    expect($ticket->ticketInventoryItems)->toHaveCount(1);
    expect($ticket->ticketInventoryItems->first()->inventory_item_id)->toBe($item->id);
    expect($ticket->ticketInventoryItems->first()->quantity)->toBe(3);
});

it('creates a ticket with no inventory items linked', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Cannot log in',
            'description' => 'Password reset email never arrives.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->latest('id')->first();

    expect($ticket->ticketInventoryItems)->toHaveCount(0);
});
