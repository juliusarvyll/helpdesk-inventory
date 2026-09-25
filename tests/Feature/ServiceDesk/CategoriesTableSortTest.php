<?php

use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Department;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('sorts categories with each parent followed by its own children', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();

    $software = Category::factory()->create([
        'department_id' => $department->id,
        'name' => 'Software',
        'sort_order' => 2,
    ]);
    $softwareChild = Category::factory()->create([
        'department_id' => $department->id,
        'parent_id' => $software->id,
        'name' => 'Operating Systems',
        'sort_order' => 1,
    ]);

    $hardware = Category::factory()->create([
        'department_id' => $department->id,
        'name' => 'Hardware',
        'sort_order' => 1,
    ]);
    $hardwareChild = Category::factory()->create([
        'department_id' => $department->id,
        'parent_id' => $hardware->id,
        'name' => 'Laptops',
        'sort_order' => 1,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListCategories::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([
            $hardware,
            $hardwareChild,
            $software,
            $softwareChild,
        ], inOrder: true);
});
