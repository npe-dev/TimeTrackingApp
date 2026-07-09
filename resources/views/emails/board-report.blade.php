@php
    $sections = $report['sections'] ?? [];
    $priorityColors = [
        'high' => '#ef4444',
        'medium' => '#f59e0b',
        'low' => '#3b82f6',
        'none' => '#9ca3af',
    ];
    $taskRow = function ($t) use ($priorityColors) {
        $pc = $priorityColors[$t['priority']] ?? '#9ca3af';
        return [$t, $pc];
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['board']['name'] }} report</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:28px 32px;">
                            <div style="font-size:13px; color:#e0e7ff; text-transform:uppercase; letter-spacing:1px;">Daily Report</div>
                            <div style="font-size:24px; font-weight:700; color:#ffffff; margin-top:4px;">{{ $report['board']['name'] }}</div>
                            <div style="font-size:13px; color:#e0e7ff; margin-top:6px;">{{ $report['generated_at'] }} &nbsp;·&nbsp; Week {{ $report['week_label'] }}</div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:8px 32px 32px 32px;">

                            {{-- ── Overdue ─────────────────────────────────────────── --}}
                            @if (in_array('critical', $sections) && count($report['overdue']))
                                <h2 style="font-size:16px; color:#b91c1c; margin:24px 0 12px 0;">🔴 Overdue ({{ count($report['overdue']) }})</h2>
                                @foreach ($report['overdue'] as $t)
                                    @php [$task, $pc] = $taskRow($t); @endphp
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px; background-color:#fef2f2; border-left:3px solid {{ $pc }}; border-radius:6px;">
                                        <tr>
                                            <td style="padding:10px 12px;">
                                                <div style="font-size:14px; font-weight:600; color:#111827;">{{ $task['title'] }}</div>
                                                <div style="font-size:12px; color:#6b7280; margin-top:3px;">
                                                    Due {{ $task['due_date'] }} · {{ ucfirst($task['priority']) }} priority · {{ $task['column'] }}@if ($task['project']) · {{ $task['project'] }}@endif
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            @endif

                            {{-- ── Critical: due this week ─────────────────────────── --}}
                            @if (in_array('critical', $sections))
                                <h2 style="font-size:16px; color:#111827; margin:24px 0 12px 0;">🎯 Due this week ({{ count($report['critical']) }})</h2>
                                @if (count($report['critical']))
                                    @foreach ($report['critical'] as $t)
                                        @php [$task, $pc] = $taskRow($t); @endphp
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px; background-color:#f9fafb; border-left:3px solid {{ $pc }}; border-radius:6px;">
                                            <tr>
                                                <td style="padding:10px 12px;">
                                                    <div style="font-size:14px; font-weight:600; color:#111827;">{{ $task['title'] }}</div>
                                                    <div style="font-size:12px; color:#6b7280; margin-top:3px;">
                                                        Due {{ $task['due_date'] }} · {{ ucfirst($task['priority']) }} priority · {{ $task['column'] }}@if ($task['project']) · {{ $task['project'] }}@endif
                                                    </div>
                                                    @if (count($task['labels']))
                                                        <div style="margin-top:6px;">
                                                            @foreach ($task['labels'] as $label)
                                                                <span style="display:inline-block; font-size:11px; color:#ffffff; background-color:{{ $label['color'] }}; padding:2px 8px; border-radius:10px; margin-right:4px;">{{ $label['label'] }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    @endforeach
                                @else
                                    <p style="font-size:13px; color:#6b7280; margin:0;">✅ Nothing due this week — all clear.</p>
                                @endif
                            @endif

                            {{-- ── Summary: count per column ───────────────────────── --}}
                            @if (in_array('columns', $sections) && count($report['columns']))
                                <h2 style="font-size:16px; color:#111827; margin:28px 0 12px 0;">📊 Tasks per column</h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                    @foreach ($report['columns'] as $col)
                                        <tr style="background-color:{{ $loop->even ? '#f9fafb' : '#ffffff' }};">
                                            <td style="padding:8px 14px; font-size:13px; color:#374151;">{{ $col['name'] }}</td>
                                            <td style="padding:8px 14px; font-size:13px; font-weight:700; color:#111827; text-align:right;">{{ $col['count'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            {{-- ── Time tracked this week ──────────────────────────── --}}
                            @if (in_array('time', $sections))
                                <h2 style="font-size:16px; color:#111827; margin:28px 0 12px 0;">⏱ Time tracked this week</h2>
                                <div style="font-size:22px; font-weight:700; color:#6366f1; margin-bottom:12px;">{{ $report['time']['total_label'] ?? '0m' }}</div>
                                @if (count($report['time']['projects']))
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                        @foreach ($report['time']['projects'] as $proj)
                                            <tr style="background-color:{{ $loop->even ? '#f9fafb' : '#ffffff' }};">
                                                <td style="padding:8px 14px; font-size:13px; color:#374151;">
                                                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:{{ $proj['color'] }}; margin-right:8px;"></span>{{ $proj['name'] }}
                                                </td>
                                                <td style="padding:8px 14px; font-size:13px; font-weight:600; color:#111827; text-align:right;">{{ $proj['label'] }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @else
                                    <p style="font-size:13px; color:#6b7280; margin:0;">No time logged this week.</p>
                                @endif
                            @endif

                            {{-- ── Completed this week ─────────────────────────────── --}}
                            @if (in_array('completed', $sections))
                                <h2 style="font-size:16px; color:#111827; margin:28px 0 12px 0;">✅ Completed this week ({{ count($report['completed']) }})</h2>
                                @if (count($report['completed']))
                                    @foreach ($report['completed'] as $t)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:6px;">
                                            <tr>
                                                <td style="padding:6px 12px; font-size:13px; color:#374151; background-color:#f0fdf4; border-radius:6px;">
                                                    <span style="color:#16a34a; font-weight:700;">✓</span> {{ $t['title'] }}@if ($t['project']) <span style="color:#9ca3af;">· {{ $t['project'] }}</span>@endif
                                                </td>
                                            </tr>
                                        </table>
                                    @endforeach
                                @else
                                    <p style="font-size:13px; color:#6b7280; margin:0;">Nothing completed yet this week.</p>
                                @endif
                            @endif

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 32px; background-color:#f9fafb; border-top:1px solid #e5e7eb;">
                            <div style="font-size:12px; color:#9ca3af;">
                                Sent by {{ $report['app_name'] }}.
                                @if (!empty($report['app_url']))
                                    <a href="{{ $report['app_url'] }}" style="color:#6366f1; text-decoration:none;">Open the app →</a>
                                @endif
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
