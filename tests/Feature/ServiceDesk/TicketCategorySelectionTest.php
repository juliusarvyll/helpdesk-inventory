<?php

use App\Filament\App\Resources\Tickets\Pages\CreateTicket;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

it('offers every active parent category regardless of the chosen department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $parent = Category::factory()->create(['name' => 'Hardware']);
    $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Laptops']);
    $inactive = Category::factory()->inactive()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm(['department_id' => $department->id])
        ->assertFormFieldExists('category_id', function (Select $field) use ($parent, $child, $inactive) {
            $options = $field->getOptions();

            return array_key_exists($parent->id, $options)
                && ! array_key_exists($child->id, $options)
                && ! array_key_exists($inactive->id, $options);
        });
});
