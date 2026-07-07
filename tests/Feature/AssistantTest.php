<?php

use App\Ai\Agents\AppointmentAssistant;
use App\Ai\Tools\BookAppointment;
use App\Ai\Tools\CheckDoctorAvailability;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DoctorSeeder;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

test('guests cannot send assistant messages', function () {
    $this->postJson(route('assistant.message'), [
        'message' => 'Hello',
    ])->assertUnauthorized();
});

test('authenticated users can visit the assistant page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('assistant'))
        ->assertOk();
});

test('authenticated users receive assistant replies', function () {
    AppointmentAssistant::fake(['I can help you book an appointment.']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.message'), [
            'message' => 'I need to see a doctor',
        ])
        ->assertOk()
        ->assertJson([
            'reply' => 'I can help you book an appointment.',
        ]);
});

test('assistant message endpoint validates the message field', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.message'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});

test('assistant message endpoint returns a clear message when rate limited', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'You exceeded your current quota, please check your plan and billing details.',
            ],
        ], 429),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.message'), [
            'message' => 'Book an appointment',
        ])
        ->assertStatus(429)
        ->assertJson([
            'message' => 'The assistant is temporarily unavailable because the AI provider rate limit was reached. Please wait a minute and try again.',
        ]);
});

test('book appointment creates a record for an available slot', function () {
    $this->seed(DoctorSeeder::class);

    $user = User::factory()->create();
    $doctor = Doctor::query()->where('name', 'Dr. Aisha Bello')->firstOrFail();
    $date = now()->next('Friday')->toDateString();
    $time = $doctor->slotsFor(Carbon::parse($date))[0];

    $this->actingAs($user);

    $response = (new BookAppointment)->handle(new Request([
        'doctor_id' => $doctor->id,
        'date' => $date,
        'time' => $time,
        'patient_name' => 'Abdul Halim',
        'patient_email' => 'ahr@gmail.com',
        'reason' => 'Skin concerns',
    ]));

    expect($response)->toContain('Appointment #');
    expect(Appointment::query()->where('patient_email', 'ahr@gmail.com')->exists())->toBeTrue();
});

test('check doctor availability resolves the doctor model', function () {
    $this->seed(DoctorSeeder::class);

    $doctor = Doctor::query()->firstOrFail();

    $response = (new CheckDoctorAvailability)->handle(new Request([
        'doctor_id' => $doctor->id,
        'date' => now()->toDateString(),
    ]));

    expect($response)->toMatch('/\d{2}:\d{2}/');
});
