<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $upcomingAppointments = Appointment::query()
            ->with('doctor:id,name,specialty')
            ->where('user_id', $user->id)
            ->where('scheduled_at', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $stats = [
            'upcoming' => Appointment::query()
                ->where('user_id', $user->id)
                ->where('scheduled_at', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->count(),
            'total' => Appointment::query()
                ->where('user_id', $user->id)
                ->count(),
            'doctors' => Appointment::query()
                ->where('user_id', $user->id)
                ->distinct()
                ->count('doctor_id'),
        ];

        $appointmentsByMonth = collect(range(5, 0))->map(function (int $monthsAgo) use ($user): array {
            $month = now()->subMonths($monthsAgo)->startOfMonth();

            return [
                'month' => $month->format('M'),
                'count' => Appointment::query()
                    ->where('user_id', $user->id)
                    ->whereBetween('scheduled_at', [$month->copy(), $month->copy()->endOfMonth()])
                    ->count(),
            ];
        })->values()->all();

        $appointmentsByStatus = Appointment::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $recentConversations = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'upcomingAppointments' => $upcomingAppointments,
            'recentConversations' => $recentConversations,
            'appointmentsByMonth' => $appointmentsByMonth,
            'appointmentsByStatus' => $appointmentsByStatus,
        ]);
    }
}
