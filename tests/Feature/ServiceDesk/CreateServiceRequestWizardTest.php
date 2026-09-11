<?php

use App\Filament\App\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Models\Department;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function createServiceForWizardTest(): Service
{
    $category = ServiceCategory::create([
        'name' => 'IT Services',
        'slug' => 'it-services',
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return Service::create([
        'category_id' => $category->id,
        'department_id' => Department::factory()->create()->id,
        'name' => 'Password Reset',
        'slug' => 'password-reset',
        'description' => 'Reset your account password',
        'requires_approval' => false,
        'default_priority' => 'medium',
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

it('wires a submit action into the wizard so the final step has a create button', function () {
    Filament::setCurrentPanel('app');

    $user = User::factory()->create();

    $html = Livewire::actingAs($user)
        ->test(CreateServiceRequest::class)
        ->html();

    expect($html)->toContain('$wire.create()');
});

it('creates a service request after completing the wizard', function () {
    Filament::setCurrentPanel('app');

    $user = User::factory()->create();
    $service = createServiceForWizardTest();

    Livewire::actingAs($user)
        ->test(CreateServiceRequest::class)
        ->fillForm([
            'service_id' => $service->id,
            'notes' => 'Please reset my password.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $request = ServiceRequest::query()->latest('id')->first();

    expect($request)->not->toBeNull();
    expect($request->service_id)->toBe($service->id);
    expect($request->requester_id)->toBe($user->id);
    expect($request->notes)->toBe('Please reset my password.');
});
