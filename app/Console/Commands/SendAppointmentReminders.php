<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('appointments:send-reminders {--appointment= : Send the reminder for a specific appointment id}')]
#[Description('Send email reminders for appointments about 24 hours away')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $appointments = Appointment::query()
            ->with('doctor')
            ->where('reminder_opt_in', true)
            ->whereNull('reminder_sent_at')
            ->where('status', 'booked')
            ->when(
                $this->option('appointment'),
                fn ($query, $id) => $query->whereKey($id),
                fn ($query) => $query->whereBetween('scheduled_at', [now()->addHours(23), now()->addHours(25)])
            )
            ->get();

        if ($appointments->isEmpty()) {
            $this->warn('No reminders to send.');

            return self::SUCCESS;
        }

        foreach ($appointments as $appointment) {
            Mail::to($appointment->patient_email)->send(new AppointmentReminderMail($appointment));

            $appointment->update(['reminder_sent_at' => now()]);

            $this->line("Sent reminder for appointment #{$appointment->id} to {$appointment->patient_email}");
        }

        $this->info("Sent {$appointments->count()} reminder(s).");

        return self::SUCCESS;
    }
}
