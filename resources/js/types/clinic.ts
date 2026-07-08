export interface DoctorAvailability {
    id: number;
    doctor_id: number;
    day_of_week: number; // 0 = Sunday ... 6 = Saturday
    start_time: string; // 'HH:MM'
    end_time: string; // 'HH:MM'
}

export interface Doctor {
    id: number;
    name: string;
    specialty: string;
    bio: string | null;
    availabilities: DoctorAvailability[];
}
