<?php

use App\Models\Doctor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot visit manage doctors', function () {
    $this->get(route('admin.doctors.index'))
        ->assertRedirect(route('login'));
});

test('non-admins cannot visit manage doctors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.doctors.index'))
        ->assertForbidden();
});

test('admins can visit manage doctors and see doctors list', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create(['name' => 'Dr. Pat']);

    $this->actingAs($admin)
        ->get(route('admin.doctors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/doctors/index')
            ->has('doctors', 1)
            ->where('doctors.0.name', 'Dr. Pat')
            ->etc()
        );

    expect($doctor)->not->toBeNull();
});

test('admins can create a doctor with availabilities', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.doctors.store'), [
            'name' => 'Dr. New',
            'specialty' => 'Dermatologist',
            'bio' => 'Skin specialist',
            'availabilities' => [
                [
                    'day_of_week' => 1,
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.doctors.index'));

    $doctor = Doctor::query()->where('name', 'Dr. New')->first();

    expect($doctor)->not->toBeNull()
        ->and($doctor->specialty)->toBe('Dermatologist')
        ->and($doctor->availabilities)->toHaveCount(1)
        ->and($doctor->availabilities->first()->day_of_week)->toBe(1);
});

test('admins can update a doctor and sync availabilities', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create([
        'name' => 'Dr. Old',
        'specialty' => 'GP',
    ]);
    $doctor->availabilities()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.doctors.update', $doctor), [
            'name' => 'Dr. Updated',
            'specialty' => 'Cardiologist',
            'bio' => 'Heart specialist',
            'availabilities' => [
                [
                    'day_of_week' => 3,
                    'start_time' => '10:00',
                    'end_time' => '14:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.doctors.index'));

    $doctor->refresh();

    expect($doctor->name)->toBe('Dr. Updated')
        ->and($doctor->specialty)->toBe('Cardiologist')
        ->and($doctor->availabilities)->toHaveCount(1)
        ->and($doctor->availabilities->first()->day_of_week)->toBe(3);
});

test('admins can delete a doctor', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.doctors.destroy', $doctor))
        ->assertRedirect(route('admin.doctors.index'));

    $this->assertModelMissing($doctor);
});

test('creating a doctor requires name and specialty', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.doctors.store'), [
            'bio' => 'Missing required fields',
        ])
        ->assertSessionHasErrors(['name', 'specialty']);
});

test('non-admins cannot mutate doctors', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.doctors.store'), [
            'name' => 'Hack',
            'specialty' => 'Hack',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('admin.doctors.update', $doctor), [
            'name' => 'Hack',
            'specialty' => 'Hack',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.doctors.destroy', $doctor))
        ->assertForbidden();
});
