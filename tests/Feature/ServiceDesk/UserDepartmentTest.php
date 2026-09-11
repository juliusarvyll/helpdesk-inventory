<?php

use App\Models\Department;
use App\Models\User;

it('assigns a user to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    expect($user->department->is($department))->toBeTrue();
    expect($department->users->pluck('id'))->toContain($user->id);
});

it('allows a user to have no department', function () {
    $user = User::factory()->create();

    expect($user->department_id)->toBeNull();
    expect($user->department)->toBeNull();
});

it('nulls the user department when the department is force-deleted', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $department->forceDelete();

    expect($user->refresh()->department_id)->toBeNull();
});
