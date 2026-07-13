<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows appointment stats and upcoming appointments for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->setTime(10, 0),
        'status' => 'booked',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->subDay()->setTime(10, 0),
        'status' => 'booked',
    ]);

    Appointment::factory()->create([
        'user_id' => $otherUser->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->setTime(11, 0),
        'status' => 'booked',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.upcoming', 1)
            ->where('stats.total', 2)
            ->where('stats.doctors', 1)
            ->has('upcomingAppointments', 1)
            ->has('recentConversations')
            ->has('appointmentsByMonth', 6)
            ->where('appointmentsByMonth.5.count', 2)
            ->has('appointmentsByStatus', 1)
            ->where('appointmentsByStatus.0.status', 'booked')
            ->where('appointmentsByStatus.0.count', 2)
        );
});
