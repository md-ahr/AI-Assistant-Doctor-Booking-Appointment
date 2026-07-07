<?php

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

test('send appointment reminders command emails patients about 24 hours before', function () {
    Mail::fake();

    $doctor = Doctor::factory()->create(['name' => 'Dr. Aisha Bello']);

    $appointment = Appointment::factory()->for($doctor)->create([
        'patient_email' => 'patient@example.com',
        'scheduled_at' => now()->addHours(24),
        'reminder_opt_in' => true,
    ]);

    Artisan::call('appointments:send-reminders');

    Mail::assertSent(AppointmentReminderMail::class, function (AppointmentReminderMail $mail) use ($appointment) {
        return $mail->hasTo('patient@example.com')
            && $mail->appointment->is($appointment);
    });

    expect($appointment->fresh()->reminder_sent_at)->not->toBeNull();
});

test('send appointment reminders command skips appointments without opt in', function () {
    Mail::fake();

    Appointment::factory()->create([
        'scheduled_at' => now()->addHours(24),
        'reminder_opt_in' => false,
    ]);

    Artisan::call('appointments:send-reminders');

    Mail::assertNothingSent();
});

test('send appointment reminders command skips appointments already reminded', function () {
    Mail::fake();

    Appointment::factory()->create([
        'scheduled_at' => now()->addHours(24),
        'reminder_opt_in' => true,
        'reminder_sent_at' => now()->subHour(),
    ]);

    Artisan::call('appointments:send-reminders');

    Mail::assertNothingSent();
});

test('send appointment reminders command can send a specific appointment manually', function () {
    Mail::fake();

    $appointment = Appointment::factory()->create([
        'patient_email' => 'patient@example.com',
        'scheduled_at' => now()->addHours(10),
        'reminder_opt_in' => true,
    ]);

    Artisan::call('appointments:send-reminders', [
        '--appointment' => $appointment->id,
    ]);

    Mail::assertSent(AppointmentReminderMail::class, fn (AppointmentReminderMail $mail) => $mail->appointment->is($appointment));

    expect($appointment->fresh()->reminder_sent_at)->not->toBeNull();
});

test('send appointment reminders command skips appointments outside the reminder window', function () {
    Mail::fake();

    Appointment::factory()->create([
        'scheduled_at' => now()->addDays(3),
        'reminder_opt_in' => true,
    ]);

    Artisan::call('appointments:send-reminders');

    Mail::assertNothingSent();
});
