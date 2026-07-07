<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'user_id' => User::factory(),
            'patient_name' => fake()->name(),
            'patient_email' => fake()->safeEmail(),
            'scheduled_at' => now()->addDay(),
            'reason' => fake()->optional()->sentence(),
            'status' => 'booked',
            'reminder_opt_in' => false,
            'reminder_sent_at' => null,
        ];
    }

    public function withReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_opt_in' => true,
        ]);
    }
}
