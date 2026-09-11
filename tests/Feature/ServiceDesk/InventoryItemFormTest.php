<?php

use App\Enums\InventoryItemType;
use App\Filament\App\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\App\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('sets department_id and created_by_id from the authenticated user on create', function () {
    Filament::setCurrentPanel('app');

    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    Livewire::actingAs($user)
        ->test(CreateInventoryItem::class)
        ->fillForm([
            'type' => InventoryItemType::Asset->value,
            'name' => 'Dell Laptop',
            'serial_number' => 'SN-12345678',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item = InventoryItem::query()->latest('id')->first();

    expect($item->department_id)->toBe($department->id);
    expect($item->created_by_id)->toBe($user->id);
});

it('allows a different user in the same department to edit an inventory item', function () {
    Filament::setCurrentPanel('app');

    $department = Department::factory()->create();
    $creator = User::factory()->create(['department_id' => $department->id]);
    $editor = User::factory()->create(['department_id' => $department->id]);

    $item = InventoryItem::factory()->stock()->create([
        'department_id' => $department->id,
        'created_by_id' => $creator->id,
        'name' => 'Printer Paper',
        'quantity' => 10,
        'unit' => 'reams',
    ]);

    Livewire::actingAs($editor)
        ->test(EditInventoryItem::class, ['record' => $item->getRouteKey()])
        ->fillForm([
            'type' => InventoryItemType::Stock->value,
            'name' => 'Printer Paper (A4)',
            'quantity' => 25,
            'unit' => 'reams',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh()->name)->toBe('Printer Paper (A4)');
    expect($item->fresh()->quantity)->toBe(25);
});
