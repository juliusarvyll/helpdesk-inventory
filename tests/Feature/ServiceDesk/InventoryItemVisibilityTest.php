<?php

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;

it('only includes inventory items from the current user\'s department', function () {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $userA = User::factory()->create(['department_id' => $departmentA->id]);

    $ownItem = InventoryItem::factory()->create(['department_id' => $departmentA->id]);
    $otherItem = InventoryItem::factory()->create(['department_id' => $departmentB->id]);

    $this->actingAs($userA);

    $ids = InventoryItemResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($ownItem->id);
    expect($ids)->not->toContain($otherItem->id);
});

it('includes an inventory item created by a different user in the same department', function () {
    $department = Department::factory()->create();
    $creator = User::factory()->create(['department_id' => $department->id]);
    $viewer = User::factory()->create(['department_id' => $department->id]);

    $item = InventoryItem::factory()->create([
        'department_id' => $department->id,
        'created_by_id' => $creator->id,
    ]);

    $this->actingAs($viewer);

    expect(InventoryItemResource::getEloquentQuery()->pluck('id'))->toContain($item->id);
});

it('excludes all inventory items for a user with no department', function () {
    $department = Department::factory()->create();
    InventoryItem::factory()->create(['department_id' => $department->id]);
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(InventoryItemResource::getEloquentQuery()->count())->toBe(0);
});

it('does not allow creating inventory when the user has no department', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(InventoryItemResource::canCreate())->toBeFalse();
});

it('allows creating inventory when the user has a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $this->actingAs($user);

    expect(InventoryItemResource::canCreate())->toBeTrue();
});
