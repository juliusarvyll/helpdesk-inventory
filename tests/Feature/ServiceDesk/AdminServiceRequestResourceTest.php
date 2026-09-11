<?php

use App\Enums\ServiceRequestStatus;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function createServiceRequestForAdminTest(): ServiceRequest
{
    $category = ServiceCategory::create([
        'name' => 'IT Services',
        'slug' => 'it-services',
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $service = Service::create([
        'category_id' => $category->id,
        'department_id' => Department::factory()->create()->id,
        'name' => 'Password Reset',
        'slug' => 'password-reset',
        'requires_approval' => false,
        'default_priority' => 'medium',
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $requester = User::factory()->create();

    return ServiceRequest::create([
        'service_id' => $service->id,
        'requester_type' => $requester->getMorphClass(),
        'requester_id' => $requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);
}

it('lists all service requests for an admin', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $request = createServiceRequestForAdminTest();

    Livewire::actingAs($admin, 'admin')
        ->test(ListServiceRequests::class)
        ->assertCanSeeTableRecords([$request]);
});

it('lets an admin approve a pending service request', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $request = createServiceRequestForAdminTest();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewServiceRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('approve');

    expect($request->fresh()->status)->toBe(ServiceRequestStatus::Approved);
});

it('lets an admin update a service request status directly', function () {
    Filament::setCurrentPanel('admin');

    $admin = Admin::factory()->create();
    $request = createServiceRequestForAdminTest();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewServiceRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('updateStatus', data: ['status' => ServiceRequestStatus::Fulfilled->value]);

    expect($request->fresh()->status)->toBe(ServiceRequestStatus::Fulfilled);
});
