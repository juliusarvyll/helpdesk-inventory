<?php

use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Department;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('sorts parent categories by sort order by default', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $department = Department::factory()->create();

    $software = Category::factory()->create([
        'department_id' => $department->id,
        'name' => 'Software',
        'sort_order' => 2,
    ]);
    $hardware = Category::factory()->create([
        'department_id' => $department->id,
        'name' => 'Hardware',
        'sort_order' => 1,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListCategories::class)
        ->assertCanSeeTableRecords([$hardware, $software], inOrder: true);
});
