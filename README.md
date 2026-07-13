# AI Appointment

A Laravel application that lets patients book clinic appointments through a conversational AI assistant. The assistant helps users find the right doctor, check availability, confirm details, and optionally receive email reminders 24 hours before their visit.

## Features

- **AI receptionist** — Chat-based booking powered by [Laravel AI](https://laravel.com/docs/ai) and Google Gemini
- **Doctor directory** — Seeded specialists with weekly availability schedules
- **Real-time availability** — Hourly slots checked against each doctor's schedule and existing bookings
- **Email reminders** — Optional reminders sent ~24 hours before an appointment
- **User authentication** — Registration, login, and profile management via Laravel Fortify
- **Conversation memory** — Multi-turn chats persist per user via the AI SDK

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia v3, React 19, Tailwind CSS v4 |
| AI | Laravel AI SDK, Gemini 2.5 Flash |
| Auth | Laravel Fortify, passkeys |
| Database | SQLite (default) |
| Testing | Pest 4 |

## Requirements

- PHP 8.4+
- Composer
- Node.js 22+
- A [Google Gemini API key](https://aistudio.google.com/apikey)

## Getting Started

### Quick setup

```bash
composer setup
```

This installs dependencies, copies `.env`, generates an app key, runs migrations, and builds frontend assets.

### Manual setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
```

### Configuration

Add your Gemini API key to `.env`:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your-api-key-here
```

For email reminders, configure mail settings. The defaults target a local SMTP catcher on port `8025` (e.g. [Mailpit](https://github.com/axllent/mailpit)):

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@example.com"
```

### Development

Start the dev server (PHP, queue worker, logs, and Vite):

```bash
composer dev
```

Or run Vite separately:

```bash
npm run dev
```

With [Laravel Herd](https://herd.laravel.com), the app is available at `https://ai-appointment.test`.

### Default login

After seeding, sign in with:

| Email | Password |
| --- | --- |
| `test@example.com` | `password` |

## Usage

1. Register or log in.
2. Open **Assistant** from the sidebar.
3. Describe your symptoms or the type of doctor you need.
4. The assistant will recommend a doctor, check availability, and collect your name and email.
5. Confirm the booking when prompted.
6. Choose whether to receive an email reminder.

### Seeded doctors

| Doctor | Specialty | Availability |
| --- | --- | --- |
| Dr. Sarah Chen | General Practitioner | Mon–Thu, 9:00–17:00 |
| Dr. James Okoro | Cardiologist | Tue–Thu, 10:00–16:00 |
| Dr. Aisha Bello | Dermatologist | Mon, Wed, Fri, 9:00–13:00 |

## AI Tools

The `AppointmentAssistant` agent exposes four tools:

| Tool | Purpose |
| --- | --- |
| `list_doctors` | Returns doctors and specialties |
| `check_doctor_availability` | Returns open hourly slots for a date range |
| `book_appointment` | Creates a confirmed appointment |
| `set_appointment_reminder` | Records the patient's reminder preference |

## Scheduled Tasks

Appointment reminders run hourly via the scheduler:

```bash
php artisan appointments:send-reminders
```

To process the scheduler in development, run:

```bash
php artisan schedule:work
```

Send a reminder for a specific appointment (useful for testing):

```bash
php artisan appointments:send-reminders --appointment=1
```

## Testing

```bash
php artisan test
```

Run the full CI suite locally:

```bash
composer ci:check
```

## Project Structure

```
app/
├── Ai/
│   ├── Agents/AppointmentAssistant.php   # AI agent instructions & tools
│   └── Tools/                            # list, availability, book, reminder
├── Console/Commands/SendAppointmentReminders.php
├── Http/Controllers/AssistantController.php
├── Mail/AppointmentReminderMail.php
└── Models/                               # Doctor, Appointment, User

resources/js/pages/assistant/index.tsx   # Chat UI
database/seeders/DoctorSeeder.php        # Sample doctors & schedules
tests/Feature/AssistantTest.php
tests/Feature/SendAppointmentRemindersTest.php
```

## License

MIT
