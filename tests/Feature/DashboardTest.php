<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('a bidder lands on their won items', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('won-items.index'));
});

test('an administrator lands on the auction manager', function () {
    $role = Role::findOrCreate(RolesEnum::ADMIN->value);
    $role->givePermissionTo(
        Permission::findOrCreate(PermissionsEnum::MANAGE_AUCTIONS->value),
    );

    $admin = User::factory()->create();
    $admin->assignRole($role);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertRedirect(route('admin.auctions.index'));
});
