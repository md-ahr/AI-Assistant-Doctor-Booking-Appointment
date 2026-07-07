<p>Hi {{ $appointment->patient_name }},</p>

<p>This is a reminder that you have an appointment tomorrow with {{ $appointment->doctor->name }}.</p>

<p>
    <strong>When:</strong> {{ $appointment->scheduled_at->format('l, F j, Y \a\t g:i A') }}<br>
    <strong>Reference:</strong> #{{ $appointment->id }}
</p>

<p>See you at Lumen Health clinic.</p>
