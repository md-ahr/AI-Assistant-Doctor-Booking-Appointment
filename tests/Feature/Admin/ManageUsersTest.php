<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot visit manage users', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('non-admins cannot visit manage users', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admins can visit manage users and see users list ordered by name', function () {
    $admin = User::factory()->admin()->create(['name' => 'Zoe Admin']);
    $member = User::factory()->create(['name' => 'Pat Member', 'is_admin' => false]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users', 2)
            ->where('users.0.name', 'Pat Member')
            ->where('users.0.email', $member->email)
            ->where('users.0.is_admin', false)
            ->where('users.1.name', 'Zoe Admin')
            ->where('users.1.is_admin', true)
            ->missing('users.0.password')
            ->etc()
        );
});

test('admins can create a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Member',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => false,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'new@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('New Member')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertTrue(auth()->attempt([
        'email' => 'new@example.com',
        'password' => 'password',
    ]));
});

test('creating a user requires name email password and role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'is_admin']);
});

test('creating a user requires a unique email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => false,
        ])
        ->assertSessionHasErrors(['email']);
});

test('updating a user requires a unique email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@example.com']);
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $member), [
            'name' => $member->name,
            'email' => 'taken@example.com',
            'is_admin' => false,
        ])
        ->assertSessionHasErrors(['email']);
});

test('guests cannot mutate users', function () {
    $target = User::factory()->create();

    $this->post(route('admin.users.store'), [
        'name' => 'Hack',
        'email' => 'hack@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => true,
    ])->assertRedirect(route('login'));

    $this->put(route('admin.users.update', $target), [
        'name' => 'Hack',
        'email' => $target->email,
        'is_admin' => true,
    ])->assertRedirect(route('login'));

    $this->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('login'));
});

test('admins can create an admin user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => true,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'second-admin@example.com')->first()->is_admin)->toBeTrue();
});

test('admins can update a user without changing password', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'is_admin' => false,
    ]);
    $originalPassword = $member->password;

    $this->actingAs($admin)
        ->put(route('admin.users.update', $member), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ])
        ->assertRedirect(route('admin.users.index'));

    $member->refresh();

    expect($member->name)->toBe('Updated Name')
        ->and($member->email)->toBe('updated@example.com')
        ->and($member->is_admin)->toBeTrue()
        ->and($member->password)->toBe($originalPassword);
});

test('admins can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $member))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($member);
});

test('admins cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    $this->assertModelExists($admin);
});

test('admins cannot demote themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_admin' => false,
        ])
        ->assertForbidden();

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('admins can update their own name and email while remaining admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
            'is_admin' => true,
        ])
        ->assertRedirect(route('admin.users.index'));

    $admin->refresh();

    expect($admin->name)->toBe('Updated Admin')
        ->and($admin->email)->toBe('updated-admin@example.com')
        ->and($admin->is_admin)->toBeTrue();
});

test('non-admins cannot mutate users', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.users.store'), [
            'name' => 'Hack',
            'email' => 'hack@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => true,
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('admin.users.update', $target), [
            'name' => 'Hack',
            'email' => $target->email,
            'is_admin' => true,
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.users.destroy', $target))
        ->assertForbidden();
});
