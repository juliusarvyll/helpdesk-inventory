<?php

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Models\TicketInventoryItem;

it('links an inventory item to a ticket with a per-line quantity', function () {
    $department = Department::factory()->create();
    $item = InventoryItem::factory()->stock()->create(['department_id' => $department->id]);
    $ticket = Ticket::factory()->create(['department_id' => $department->id]);

    $ticket->ticketInventoryItems()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 5,
    ]);

    $linked = $ticket->inventoryItems()->first();

    expect($ticket->inventoryItems)->toHaveCount(1);
    expect($linked->id)->toBe($item->id);
    expect($linked->pivot->quantity)->toBe(5);
    expect($item->tickets()->first()->id)->toBe($ticket->id);
});

it('still shows a soft-deleted inventory item on a ticket that already linked it', function () {
    $item = InventoryItem::factory()->create();
    $ticket = Ticket::factory()->create(['department_id' => $item->department_id]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    $item->delete();

    expect($ticket->inventoryItems()->count())->toBe(0);
    expect($ticket->inventoryItemsIncludingTrashed()->count())->toBe(1);
    expect($ticket->inventoryItemsIncludingTrashed()->first()->id)->toBe($item->id);
});

it('cascades pivot rows when the ticket is force-deleted', function () {
    $item = InventoryItem::factory()->create();
    $ticket = Ticket::factory()->create(['department_id' => $item->department_id]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    $ticket->forceDelete();

    expect(TicketInventoryItem::where('inventory_item_id', $item->id)->count())->toBe(0);
    expect($item->fresh())->not->toBeNull();
});
