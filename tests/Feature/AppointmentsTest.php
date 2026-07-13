<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot visit appointments', function () {
    $this->get(route('appointments.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit appointments and only see their own', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Own Patient',
        'scheduled_at' => now()->addDay()->setTime(10, 0),
    ]);

    Appointment::factory()->create([
        'user_id' => $other->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Other Patient',
        'scheduled_at' => now()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/index')
            ->has('appointments', 1)
            ->where('appointments.0.patient_name', 'Own Patient')
            ->has('appointments.0.doctor')
            ->etc()
        );
});
