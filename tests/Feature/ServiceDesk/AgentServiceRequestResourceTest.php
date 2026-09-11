<?php

use App\Enums\ServiceRequestStatus;
use App\Filament\Agent\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Agent\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Models\Department;
use App\Models\Operator;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function createServiceRequestInDepartment(Department $department): ServiceRequest
{
    $category = ServiceCategory::create([
        'name' => 'IT Services '.$department->id,
        'slug' => 'it-services-'.$department->id,
        'visibility' => 'public',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $service = Service::create([
        'category_id' => $category->id,
        'department_id' => $department->id,
        'name' => 'Password Reset',
        'slug' => 'password-reset-'.$department->id,
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

it('only lists service requests for departments the agent belongs to', function () {
    Filament::setCurrentPanel('agent');

    $myDepartment = Department::factory()->create();
    $otherDepartment = Department::factory()->create();

    $agent = Operator::factory()->create();
    $agent->serviceDeskDepartments()->attach($myDepartment->id, ['operator_type' => Operator::class]);

    $myRequest = createServiceRequestInDepartment($myDepartment);
    $otherRequest = createServiceRequestInDepartment($otherDepartment);

    Livewire::actingAs($agent, 'operator')
        ->test(ListServiceRequests::class)
        ->assertCanSeeTableRecords([$myRequest])
        ->assertCanNotSeeTableRecords([$otherRequest]);
});

it('lets an agent update the status of a service request in their department', function () {
    Filament::setCurrentPanel('agent');

    $department = Department::factory()->create();
    $agent = Operator::factory()->create();
    $agent->serviceDeskDepartments()->attach($department->id, ['operator_type' => Operator::class]);

    $request = createServiceRequestInDepartment($department);

    Livewire::actingAs($agent, 'operator')
        ->test(ViewServiceRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('updateStatus', data: ['status' => ServiceRequestStatus::InProgress->value]);

    expect($request->fresh()->status)->toBe(ServiceRequestStatus::InProgress);
});
