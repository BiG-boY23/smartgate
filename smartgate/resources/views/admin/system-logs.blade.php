@extends('layouts.app')

@section('title', 'System Logs')
@section('subtitle', 'SmartGate – Technical maintenance and hardware diagnostics.')

@section('content')
<div class="table-container">
    <div class="section-header">
        <h3 style="font-weight: 600; color: #1e293b;"><i class="ph ph-list-magnifying-glass"></i> Diagnostic Activity</h3>
        <p style="font-size: 0.8rem; color: #64748b;">Review hardware sync events and connection status history.</p>
    </div>

    <div class="table-wrapper">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Event Message</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('M d, Y h:i:s A') }}</td>
                    <td>
                        <span class="badge" style="
                            background: {{ $log->type === 'sync' ? '#dcfce7' : ($log->type === 'error' ? '#fef2f2' : '#f1f5f9') }};
                            color: {{ $log->type === 'sync' ? '#166534' : ($log->type === 'error' ? '#b91c1c' : '#475569') }};
                            padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;">
                            {{ $log->type }}
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 0.8rem;">{{ $log->source }}</td>
                    <td style="font-weight: 600; color: #1e293b;">{{ $log->message }}</td>
                    <td>
                        @if($log->details)
                            <pre style="font-size: 0.75rem; color: #64748b; margin: 0; background: #f8fafc; padding: 0.25rem; border-radius: 4px;">{{ json_encode($log->details) }}</pre>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No system logs available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .logs-table th {
        text-align: left;
        padding: 12px;
        background: #f8fafc;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    .logs-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .logs-table tr:hover {
        background: #fdfdfd;
    }
</style>
@endsection
