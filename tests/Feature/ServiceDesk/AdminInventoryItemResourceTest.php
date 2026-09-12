<?php

use App\Enums\InventoryItemType;
use App\Filament\Admin\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\Admin\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Filament\Admin\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Models\Admin;
use App\Models\Department;
use App\Models\InventoryItem;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('lists inventory items across every department for an admin', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();

    $itemA = InventoryItem::factory()->create(['department_id' => $deptA->id]);
    $itemB = InventoryItem::factory()->create(['department_id' => $deptB->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListInventoryItems::class)
        ->assertCanSeeTableRecords([$itemA, $itemB]);
});

it('lets an admin create an inventory item for any department', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateInventoryItem::class)
        ->fillForm([
            'department_id' => $department->id,
            'type' => InventoryItemType::Asset->value,
            'name' => 'Dell Laptop',
            'serial_number' => 'SN-12345678',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item = InventoryItem::query()->latest('id')->first();

    expect($item->department_id)->toBe($department->id);
    expect($item->created_by_id)->toBeNull();
});

it('lets an admin edit an inventory item belonging to a different department', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();
    $item = InventoryItem::factory()->create(['department_id' => $department->id, 'name' => 'Old Name']);

    Livewire::actingAs($admin, 'admin')
        ->test(EditInventoryItem::class, ['record' => $item->getRouteKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh()->name)->toBe('New Name');
});
