<?php

use App\Enums\InventoryItemType;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;

it('creates an asset-type inventory item scoped to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'created_by_id' => $user->id,
        'name' => 'Dell Laptop',
        'serial_number' => 'SN-12345',
    ]);

    expect($item->type)->toBe(InventoryItemType::Asset);
    expect($item->department->is($department))->toBeTrue();
    expect($item->createdBy->is($user))->toBeTrue();
    expect($department->inventoryItems->pluck('id'))->toContain($item->id);
});

it('creates a stock-type inventory item with quantity and unit', function () {
    $item = InventoryItem::factory()->stock()->create([
        'name' => 'Printer Paper',
        'quantity' => 50,
        'unit' => 'reams',
    ]);

    expect($item->type)->toBe(InventoryItemType::Stock);
    expect($item->quantity)->toBe(50);
    expect($item->unit)->toBe('reams');
});

it('soft deletes an inventory item', function () {
    $item = InventoryItem::factory()->create();

    $item->delete();

    expect(InventoryItem::find($item->id))->toBeNull();
    expect(InventoryItem::withTrashed()->find($item->id))->not->toBeNull();
});

it('cascades inventory items when their department is force-deleted', function () {
    $department = Department::factory()->create();
    $item = InventoryItem::factory()->create(['department_id' => $department->id]);

    $department->forceDelete();

    expect(InventoryItem::withTrashed()->find($item->id))->toBeNull();
});
