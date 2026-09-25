<?php

use App\Filament\Admin\Resources\Categories\Pages\EditCategory;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\Categories\RelationManagers\ChildrenRelationManager;
use App\Models\Admin;
use App\Models\Category;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->admin = Admin::factory()->create();
    $this->parent = Category::factory()->create(['name' => 'Hardware']);
    $this->child = Category::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Laptops']);
});

it('lists only parent categories', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ListCategories::class)
        ->assertCanSeeTableRecords([$this->parent])
        ->assertCanNotSeeTableRecords([$this->child]);
});

it('lists a parent category\'s children in the subcategories relation manager', function () {
    $otherParentsChild = Category::factory()->create(['parent_id' => Category::factory()->create()->id]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(ChildrenRelationManager::class, [
            'ownerRecord' => $this->parent,
            'pageClass' => EditCategory::class,
        ])
        ->assertCanSeeTableRecords([$this->child])
        ->assertCanNotSeeTableRecords([$otherParentsChild]);
});

it('creates a subcategory under the parent category', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ChildrenRelationManager::class, [
            'ownerRecord' => $this->parent,
            'pageClass' => EditCategory::class,
        ])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Printers'])
        ->assertHasNoFormErrors();

    expect(Category::where('name', 'Printers')->first()->parent_id)->toBe($this->parent->id);
});

it('does not show subcategories on a child category', function () {
    expect(ChildrenRelationManager::canViewForRecord($this->child, EditCategory::class))->toBeFalse()
        ->and(ChildrenRelationManager::canViewForRecord($this->parent, EditCategory::class))->toBeTrue();
});
