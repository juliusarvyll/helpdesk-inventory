<?php

use App\Filament\Admin\Resources\Tickets\Pages\CreateTicket;
use App\Models\Admin;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('lets an admin create a ticket attributed to themselves', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Server down',
            'description' => 'The main server is unreachable.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->latest('id')->first();

    expect($ticket->user_id)->toBe($admin->id);
    expect($ticket->user_type)->toBe($admin->getMorphClass());
    expect($ticket->department_id)->toBe($department->id);
});

it('lets an admin attach inventory items from the ticket\'s department, across any department', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();

    $item = InventoryItem::factory()->stock()->create(['department_id' => $department->id]);
    InventoryItem::factory()->stock()->create(['department_id' => $otherDepartment->id]);

    Livewire::actingAs($admin, 'admin')
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
