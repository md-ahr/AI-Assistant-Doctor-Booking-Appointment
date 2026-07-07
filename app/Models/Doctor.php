<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'specialty', 'bio'])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    /*
     * The hourly start times this doctor offers on the given date,
     * derived from their availability. Empty if they don't work that day.
     *
     * @return list<string>
     */
    public function slotsFor(Carbon $date): array
    {
        $availability = $this->availabilities()->firstWhere('day_of_week', $date->dayOfWeek);

        if (! $availability) {
            return [];
        }

        $slots = [];

        $start = Carbon::parse($availability->start_time);
        $end = Carbon::parse($availability->end_time);

        for ($time = $start->copy(); $time < $end; $time->addHour()) {
            $slots[] = $time->format('H:i');
        }

        return $slots;
    }
}
