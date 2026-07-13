import { Head, Link, usePage } from '@inertiajs/react';
import { BellRing, CalendarCheck, CalendarDays, MessageSquare, Stethoscope, UserCog } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Label, Pie, PieChart, XAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';
import type {
    Appointment,
    AppointmentsByMonth,
    AppointmentsByStatus,
    ConversationSummary,
    DashboardStats,
} from '@/types/clinic';

interface Props {
    stats: DashboardStats;
    upcomingAppointments: Appointment[];
    recentConversations: ConversationSummary[];
    appointmentsByMonth: AppointmentsByMonth[];
    appointmentsByStatus: AppointmentsByStatus[];
}

const appointmentsByMonthConfig = {
    count: {
        label: 'Appointments',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

const statusChartColors = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

function formatWhen(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function Dashboard({
    stats,
    upcomingAppointments,
    recentConversations,
    appointmentsByMonth,
    appointmentsByStatus,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user.name.split(' ')[0];

    const statusChartData = appointmentsByStatus.map((item, index) => ({
        ...item,
        fill: statusChartColors[index % statusChartColors.length],
    }));

    const statusChartConfig = {
        count: { label: 'Appointments' },
        ...Object.fromEntries(
            appointmentsByStatus.map((item, index) => [
                item.status,
                {
                    label: item.status.charAt(0).toUpperCase() + item.status.slice(1),
                    color: statusChartColors[index % statusChartColors.length],
                },
            ]),
        ),
    } satisfies ChartConfig;

    const hasMonthlyData = appointmentsByMonth.some((item) => item.count > 0);
    const hasStatusData = appointmentsByStatus.length > 0;
    const totalStatusCount = appointmentsByStatus.reduce((sum, item) => sum + item.count, 0);

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-8xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">Welcome back, {firstName}</h1>
                    <p className="text-sm text-muted-foreground">
                        Here&apos;s a quick look at your appointments and assistant activity.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <CalendarDays className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Upcoming</p>
                                <p className="text-2xl font-semibold">{stats.upcoming}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <CalendarCheck className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Total booked</p>
                                <p className="text-2xl font-semibold">{stats.total}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Stethoscope className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Doctors seen</p>
                                <p className="text-2xl font-semibold">{stats.doctors}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Link
                        href="/assistant"
                        className="flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition hover:bg-muted/50 dark:border-sidebar-border"
                    >
                        <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Stethoscope className="size-5" />
                        </div>
                        <div>
                            <p className="font-medium">Book with assistant</p>
                            <p className="text-sm text-muted-foreground">Describe symptoms and get matched</p>
                        </div>
                    </Link>
                    <Link
                        href="/appointments"
                        className="flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition hover:bg-muted/50 dark:border-sidebar-border"
                    >
                        <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <CalendarCheck className="size-5" />
                        </div>
                        <div>
                            <p className="font-medium">My appointments</p>
                            <p className="text-sm text-muted-foreground">View and manage your bookings</p>
                        </div>
                    </Link>
                    {auth.user.is_admin ? (
                        <Link
                            href="/admin/doctors"
                            className="flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition hover:bg-muted/50 dark:border-sidebar-border"
                        >
                            <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <UserCog className="size-5" />
                            </div>
                            <div>
                                <p className="font-medium">Manage doctors</p>
                                <p className="text-sm text-muted-foreground">Update roster and availability</p>
                            </div>
                        </Link>
                    ) : (
                        <div className="flex items-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                                <MessageSquare className="size-5" />
                            </div>
                            <div>
                                <p className="font-medium">Need help?</p>
                                <p className="text-sm text-muted-foreground">Chat with the assistant anytime</p>
                            </div>
                        </div>
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="mb-4">
                            <h2 className="text-lg font-semibold">Appointments by month</h2>
                            <p className="text-sm text-muted-foreground">Last 6 months of scheduled visits</p>
                        </div>
                        {hasMonthlyData ? (
                            <ChartContainer config={appointmentsByMonthConfig} className="aspect-auto h-[240px] w-full">
                                <BarChart accessibilityLayer data={appointmentsByMonth}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis dataKey="month" tickLine={false} tickMargin={8} axisLine={false} />
                                    <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                                    <Bar dataKey="count" fill="var(--color-count)" radius={6} />
                                </BarChart>
                            </ChartContainer>
                        ) : (
                            <div className="flex h-[240px] items-center justify-center text-sm text-muted-foreground">
                                Book appointments to see monthly trends.
                            </div>
                        )}
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <div className="mb-4">
                            <h2 className="text-lg font-semibold">Appointments by status</h2>
                            <p className="text-sm text-muted-foreground">Breakdown of all your bookings</p>
                        </div>
                        {hasStatusData ? (
                            <ChartContainer
                                config={statusChartConfig}
                                className="mx-auto aspect-square max-h-[240px]"
                            >
                                <PieChart>
                                    <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                                    <Pie
                                        data={statusChartData}
                                        dataKey="count"
                                        nameKey="status"
                                        innerRadius={55}
                                        strokeWidth={4}
                                    >
                                        <Label
                                            content={({ viewBox }) => {
                                                if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                                    return (
                                                        <text
                                                            x={viewBox.cx}
                                                            y={viewBox.cy}
                                                            textAnchor="middle"
                                                            dominantBaseline="middle"
                                                        >
                                                            <tspan
                                                                x={viewBox.cx}
                                                                y={viewBox.cy}
                                                                className="fill-foreground text-2xl font-bold"
                                                            >
                                                                {totalStatusCount}
                                                            </tspan>
                                                            <tspan
                                                                x={viewBox.cx}
                                                                y={(viewBox.cy ?? 0) + 20}
                                                                className="fill-muted-foreground text-xs"
                                                            >
                                                                Total
                                                            </tspan>
                                                        </text>
                                                    );
                                                }

                                                return null;
                                            }}
                                        />
                                    </Pie>
                                </PieChart>
                            </ChartContainer>
                        ) : (
                            <div className="flex h-[240px] items-center justify-center text-sm text-muted-foreground">
                                No appointment status data yet.
                            </div>
                        )}
                    </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Upcoming appointments</h2>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/appointments">View all</Link>
                            </Button>
                        </div>
                        {upcomingAppointments.length === 0 ? (
                            <div className="space-y-3 rounded-xl border border-sidebar-border/70 py-10 text-center dark:border-sidebar-border">
                                <CalendarDays className="mx-auto size-8 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">No upcoming appointments.</p>
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/assistant">Book one now</Link>
                                </Button>
                            </div>
                        ) : (
                            <ul className="space-y-3">
                                {upcomingAppointments.map((appointment) => (
                                    <li
                                        key={appointment.id}
                                        className="flex items-center justify-between rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                                    >
                                        <div className="space-y-1">
                                            <p className="font-medium">{appointment.doctor.name}</p>
                                            <p className="text-sm text-muted-foreground">{appointment.doctor.specialty}</p>
                                            <p className="text-sm">{formatWhen(appointment.scheduled_at)}</p>
                                        </div>
                                        <div className="flex flex-col items-end gap-2">
                                            <Badge variant="secondary" className="capitalize">
                                                {appointment.status}
                                            </Badge>
                                            {appointment.reminder_opt_in && (
                                                <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <BellRing className="size-3" /> Reminder on
                                                </span>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Recent chats</h2>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/assistant">Open assistant</Link>
                            </Button>
                        </div>
                        {recentConversations.length === 0 ? (
                            <div className="space-y-3 rounded-xl border border-sidebar-border/70 py-10 text-center dark:border-sidebar-border">
                                <MessageSquare className="mx-auto size-8 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">No assistant chats yet.</p>
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/assistant">Start a conversation</Link>
                                </Button>
                            </div>
                        ) : (
                            <ul className="space-y-2">
                                {recentConversations.map((conversation) => (
                                    <li key={conversation.id}>
                                        <Link
                                            href="/assistant"
                                            className="flex items-center justify-between rounded-xl border border-sidebar-border/70 px-4 py-3 transition hover:bg-muted/50 dark:border-sidebar-border"
                                        >
                                            <span className="truncate text-sm font-medium">
                                                {conversation.title || 'Untitled chat'}
                                            </span>
                                            <span className="ml-3 shrink-0 text-xs text-muted-foreground">
                                                {new Date(conversation.updated_at).toLocaleDateString()}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
