<?php

use App\Filament\App\Resources\Tickets\Pages\CreateTicket;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

it('only offers parent categories when creating a ticket as a user', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $parent = Category::factory()->create([
        'department_id' => $department->id,
        'name' => 'Hardware',
    ]);
    $child = Category::factory()->create([
        'department_id' => $department->id,
        'parent_id' => $parent->id,
        'name' => 'Laptops',
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm(['department_id' => $department->id])
        ->assertFormFieldExists('category_id', function (Select $field) use ($parent, $child) {
            $options = $field->getOptions();

            return array_key_exists($parent->id, $options)
                && ! array_key_exists($child->id, $options);
        });
});
