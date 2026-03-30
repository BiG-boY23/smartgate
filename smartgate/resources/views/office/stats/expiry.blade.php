@extends('layouts.app')

@section('title', 'Tag Expiry Tracking')
@section('subtitle', 'Monitoring registration validity and proactive renewal tracking.')

@section('content')
<div class="stats-container">
    <div class="stats-header">
        <div class="header-info">
            <h2>Tag Lifecycle Oversight</h2>
            <p>Tracking the validity periods of all active vehicle tags and registrations across the campus.</p>
        </div>
        <div class="header-actions">
            <button id="btnSendAlerts" class="btn-export">
                <i class="ph ph-envelope-simple"></i> <span id="btnText">Send Renewal Alerts</span>
            </button>
        </div>
    </div>

    <div class="stats-grid">
        <!-- Expired Card -->
        <div class="expiry-card expired">
            <div class="card-icon"><i class="ph-fill ph-warning-circle"></i></div>
            <div class="card-data">
                <span class="label">Expired Tags</span>
                <h3>{{ $expired }}</h3>
                <span class="trend text-danger"><i class="ph ph-lock-key"></i> Access Blocked</span>
            </div>
            <div class="card-foot" style="background: rgba(239, 68, 68, 0.05);">{{ $expiredPerc }}% of Fleet</div>
        </div>

        <!-- Critical Card -->
        <div class="expiry-card critical">
            <div class="card-icon"><i class="ph-fill ph-clock-countdown"></i></div>
            <div class="card-data">
                <span class="label">Expiring Soon (< 15d)</span>
                <h3>{{ $critical }}</h3>
                <span class="trend text-warning"><i class="ph ph-bell-ringing"></i> Renewal Required</span>
            </div>
            <div class="card-foot" style="background: rgba(245, 158, 11, 0.05);">{{ $criticalPerc }}% of Fleet</div>
        </div>

        <!-- Healthy Card -->
        <div class="expiry-card active">
            <div class="card-icon"><i class="ph-fill ph-check-circle"></i></div>
            <div class="card-data">
                <span class="label">Long-term Active</span>
                <h3>{{ $healthy }}</h3>
                <span class="trend text-success"><i class="ph ph-shield-check"></i> Compliant Flow</span>
            </div>
            <div class="card-foot" style="background: rgba(16, 185, 129, 0.05);">{{ $healthyPerc }}% of Fleet</div>
        </div>
    </div>

    <!-- Expiry Directory Table -->
    <div class="table-container shadow-sm" style="background: white; border-radius: 24px; border: 1px solid #e2e8f0; margin-top: 1rem;">
        <div style="padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
             <i class="ph-bold ph-calendar-check" style="color: #741b1b; font-size: 1.25rem;"></i>
             <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b;">Registration Validity Directory</h3>
        </div>
        <div class="table-wrapper">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 1rem 2rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 800;">Owner Name</th>
                        <th style="padding: 1rem 2rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 800;">RFID Tag ID</th>
                        <th style="padding: 1rem 2rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 800;">Plate</th>
                        <th style="padding: 1rem 2rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 800; text-align: right;">Validity Until</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeRegistrations as $reg)
                        @php
                            $expiryDate = \Carbon\Carbon::parse($reg->validity_to);
                            $isExpiringSoon = $expiryDate->isAfter(now()) && $expiryDate->diffInDays(now()) <= 15;
                            $isExpired = $expiryDate->isBefore(now());
                            $rowColor = '';
                            if($isExpired) $rowStyle = 'color: #ef4444; font-weight: bold;';
                            elseif($isExpiringSoon) $rowStyle = 'color: #f59e0b; font-weight: bold;';
                            else $rowStyle = 'color: #1e293b;';
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1.25rem 2rem;"><strong>{{ $reg->full_name }}</strong></td>
                            <td style="padding: 1.25rem 2rem; font-family: monospace; font-weight: 700;">{{ $reg->rfid_tag_id }}</td>
                            <td style="padding: 1.25rem 2rem;">{{ $reg->plate_number }}</td>
                            <td style="padding: 1.25rem 2rem; text-align: right; {{ $rowStyle }}">
                                {{ $expiryDate->format('M d, Y') }}
                                @if($isExpired)
                                    <span class="badge" style="background: #fee2e2; color: #ef4444; font-size: 0.65rem; margin-top: 4px;">EXPIRED</span>
                                @elseif($isExpiringSoon)
                                    <span class="badge" style="background: #fef3c7; color: #f59e0b; font-size: 0.65rem; margin-top: 4px;">SOON</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <i class="ph ph-shield-slash" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p style="margin-top: 1rem;">No active tags found with validity dates.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .stats-container { display: flex; flex-direction: column; gap: 2rem; }
    .stats-header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 2rem; border-radius: 20px; border: 1px solid #e2e8f0; }
    .header-info h2 { margin: 0; font-size: 1.5rem; font-weight: 800; color: #1e293b; }
    .header-info p { margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem; }
    
    .btn-export { background: #741b1b; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
    .expiry-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; position: relative; }
    .expiry-card .card-icon { position: absolute; top: 1.5rem; right: 1.5rem; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    
    .expired .card-icon { background: #fee2e2; color: #ef4444; }
    .critical .card-icon { background: #fef3c7; color: #f59e0b; }
    .active .card-icon { background: #dcfce7; color: #10b981; }

    .card-data { padding: 1.5rem; }
    .card-data .label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }
    .card-data h3 { margin: 4px 0; font-size: 1.75rem; font-weight: 800; color: #1e293b; }
    .card-data .trend { font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }

    .card-foot { padding: 0.75rem 1.5rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: center; }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('btnSendAlerts');
        const btnText = document.getElementById('btnText');

        btn.addEventListener('click', function() {
            Swal.fire({
                title: 'Send Renewal Alerts?',
                text: "This will send an email reminder to all owners whose tags are expiring within the next 15 days.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#741b1b',
                confirmButtonText: 'Yes, send them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btnText.innerText = 'Sending...';

                    fetch("{{ route('office.stats.expiry.alerts') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btnText.innerText = 'Send Renewal Alerts';
                        if(data.success) {
                            Swal.fire('Dispatched!', data.message, 'success');
                        } else {
                            Swal.fire('Error', 'Failed to send alerts.', 'error');
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btnText.innerText = 'Send Renewal Alerts';
                        Swal.fire('Error', 'A system error occurred.', 'error');
                    });
                }
            });
        });
    });
</script>
@endsection
