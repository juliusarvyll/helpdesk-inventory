<?php

use App\Filament\App\Resources\Tickets\Pages\ViewTicket;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

it('shows linked inventory items on the ticket view page', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'name' => 'Dell Laptop',
    ]);
    $ticket = Ticket::factory()->create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Dell Laptop');
});

it('still shows a soft-deleted linked inventory item on the ticket view page', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'name' => 'Retired Printer',
    ]);
    $ticket = Ticket::factory()->create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);
    $item->delete();

    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Retired Printer');
});
