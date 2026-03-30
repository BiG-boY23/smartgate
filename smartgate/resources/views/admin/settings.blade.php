@extends('layouts.app')

@section('title', 'System Command Center')
@section('subtitle', 'Configure system-wide parameters, hardware logic, and security alerts.')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- REGISTRATION & VALIDITY -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-calendar-check" style="color: #741b1b;"></i> Registration & Validity
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Standard Validity Period (Years)
                    </label>
                    <input type="number" name="validity_period" value="{{ $settings['validity_period'] }}" class="form-control" style="width: 100%;" min="1" max="10">
                    <small style="color: #94a3b8;">Default duration for new RFID tag registrations before expiry.</small>
                </div>
            </div>
        </div>

        <!-- BRANDING & LOGOS -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-paint-brush-broad" style="color: #db2777;"></i> Branding & Logos
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        EVSU Official Logo
                    </label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden;">
                            @if($settings['evsu_logo'])
                                <img src="{{ asset('storage/' . $settings['evsu_logo']) }}" style="max-width: 100%; max-height: 100%;">
                            @else
                                <i class="ph ph-image" style="color: #94a3b8;"></i>
                            @endif
                        </div>
                        <input type="file" name="evsu_logo" class="form-control" style="font-size: 0.75rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Chocobol Team Logo
                    </label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden;">
                            @if($settings['chocobol_logo'])
                                <img src="{{ asset('storage/' . $settings['chocobol_logo']) }}" style="max-width: 100%; max-height: 100%;">
                            @else
                                <i class="ph ph-image" style="color: #94a3b8;"></i>
                            @endif
                        </div>
                        <input type="file" name="chocobol_logo" class="form-control" style="font-size: 0.75rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- SCANNER & LOGIC CONTROL -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-cpu" style="color: #6366f1;"></i> Scanner & Logic Control
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Cooldown Interval (Seconds)
                    </label>
                    <input type="number" name="cooldown_interval" value="{{ $settings['cooldown_interval'] }}" class="form-control" style="width: 100%;" min="1" max="60">
                    <small style="color: #94a3b8;">Minimum time required between two consecutive scans of the same tag.</small>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Tag Processing Logic
                    </label>
                    <select name="tag_logic" class="form-control" style="width: 100%;">
                        <option value="strict" {{ $settings['tag_logic'] == 'strict' ? 'selected' : '' }}>Strict Sequence (In-Out Mandatory)</option>
                        <option value="flexible" {{ $settings['tag_logic'] == 'flexible' ? 'selected' : '' }}>Flexible Loop (Allow Multiple Entry)</option>
                    </select>
                    <small style="color: #94a3b8;">'Strict' ignores Entry scans if the vehicle is already inside.</small>
                </div>
            </div>
        </div>

        <!-- CAPACITY MANAGEMENT -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-car-profile" style="color: #f59e0b;"></i> Capacity Management
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Total Parking Slots
                    </label>
                    <input type="number" name="total_parking_slots" value="{{ $settings['total_parking_slots'] }}" class="form-control" style="width: 100%;" min="10">
                    <small style="color: #94a3b8;">Sets the hard limit for the live occupancy tracker.</small>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Occupancy Warning Threshold (%)
                    </label>
                    <input type="number" name="occupancy_warning_threshold" value="{{ $settings['occupancy_warning_threshold'] }}" class="form-control" style="width: 100%;" min="50" max="100">
                    <small style="color: #94a3b8;">Gauge turns yellow/red when this percentage is reached.</small>
                </div>
            </div>
        </div>

        <!-- SECURITY & NOTIFICATIONS -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-shield-warning" style="color: #ef4444;"></i> Security & Notifications
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b;">Blacklist Audio Alarm</div>
                        <small style="color: #64748b;">Alert sirens on the guard dashboard for rejected tags.</small>
                    </div>
                    <select name="blacklist_alarm" class="form-control" style="width: auto;">
                        <option value="on" {{ $settings['blacklist_alarm'] == 'on' ? 'selected' : '' }}>ENABLED</option>
                        <option value="off" {{ $settings['blacklist_alarm'] == 'off' ? 'selected' : '' }}>DISABLED</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Expiry Alert Lead-Time (Days)
                    </label>
                    <input type="number" name="expiry_alert_lead_time" value="{{ $settings['expiry_alert_lead_time'] }}" class="form-control" style="width: 100%;">
                    <small style="color: #94a3b8;">Show red validity highlights when this many days remain.</small>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Guard Ticker Message
                    </label>
                    <textarea name="guard_ticker" class="form-control" style="width: 100%; min-height: 80px; font-size: 0.85rem;">{{ $settings['guard_ticker'] }}</textarea>
                    <small style="color: #94a3b8;">Scrolling text message visible on the bottom of the Guard Dashboard.</small>
                </div>
            </div>
        </div>

        <!-- HARDWARE HEALTH -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-heartbeat" style="color: #10b981;"></i> Hardware Health
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label style="font-weight: 700; font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; display: block;">
                        Bridge Heartbeat Frequency (Sec)
                    </label>
                    <input type="number" name="bridge_heartbeat_freq" value="{{ $settings['bridge_heartbeat_freq'] }}" class="form-control" style="width: 100%;">
                    <small style="color: #94a3b8;">Interval for diagnostic pings from the python bridge.</small>
                </div>

                <div style="padding: 1rem; background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.85rem; color: #166534; font-weight: 600;">Force Offline Log Sync</div>
                    <button type="button" id="btnForceSync" class="btn btn-primary" style="background: #166534; padding: 0.4rem 1rem; font-size: 0.75rem;">
                        <i class="ph ph-arrows-merge"></i> Sync Now
                    </button>
                </div>
            </div>
        </div>

        <!-- FINANCIAL SETTINGS (NEW) -->
        <div class="table-container" style="padding: 1.5rem;">
            <div class="section-header">
                <h3 style="display: flex; align-items: center; gap: 0.75rem; color: #1e293b;">
                    <i class="ph ph-currency-circle-dollar" style="color: #16a34a;"></i> Financial Settings
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
                <div style="background: #f0fdf4; border-radius: 12px; padding: 1.25rem; border: 1px solid #dcfce7;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 800; color: #166534; font-size: 1.1rem;">RFID Issuance Fee</div>
                            <div style="font-size: 1.8rem; font-weight: 900; color: #14532d; font-family: monospace;">₱{{ number_format($settings['rfid_fee'], 2) }}</div>
                        </div>
                        <button type="button" id="btnSetRfidFee" class="btn btn-primary" style="background: #166534; padding: 0.75rem 1.25rem;">
                            <i class="ph ph-pencil-simple"></i> Set RFID Fee
                        </button>
                    </div>
                    <small style="margin-top: 0.75rem; display: block; color: #15803d; font-weight: 600;">This fee is automatically applied to all new issuance payments across the system.</small>
                </div>
                <input type="hidden" name="rfid_fee" id="hidden_rfid_fee" value="{{ $settings['rfid_fee'] }}">
            </div>
        </div>

    </div>

    <!-- SAVE BUTTON -->
    <div style="margin-top: 2rem; display: flex; justify-content: flex-end; padding-bottom: 3rem;">
        <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(116, 27, 27, 0.4);">
            <i class="ph ph-floppy-disk"></i> SAVE COMMAND CENTER CONFIGURATION
        </button>
    </div>
</form>

@section('scripts')
<script>
document.getElementById('btnForceSync').addEventListener('click', function() {
    Swal.fire({
        title: 'Force Bridge Sync?',
        text: 'This will signal the python service to immediately push all buffered offline logs.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#166534'
    }).then((result) => {
        if (result.isConfirmed) {
            const ws = new WebSocket('ws://127.0.0.1:8080');
            ws.onopen = function() {
                ws.send(JSON.stringify({ cmd: 'sync' }));
                Swal.fire('Signal Sent', 'Manual sync command transmitted to hardware bridge.', 'success');
                ws.close();
            };
            ws.onerror = function() {
                Swal.fire('Hardware Offline', 'Bridge service is currently unreachable.', 'error');
            }
        }
    });
});

document.getElementById('btnSetRfidFee').addEventListener('click', function() {
        Swal.fire({
            title: 'Set RFID Issuance Fee',
            text: 'Configure the price for new tag issuances (₱)',
            input: 'number',
            inputAttributes: { min: 1, step: 1 },
            inputValue: document.getElementById('hidden_rfid_fee').value,
            showCancelButton: true,
            confirmButtonText: 'Confirm Price Change',
            confirmButtonColor: '#166534',
            inputValidator: (value) => {
                if (!value || value <= 0) return 'Please enter a valid amount greater than zero.'
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Syncing Price...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const response = await fetch("{{ route('admin.settings.update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ rfid_fee: result.value })
                    });

                    if (response.ok) {
                        document.getElementById('hidden_rfid_fee').value = result.value;
                        const display = document.querySelector('div[style*="font-size: 1.8rem;"]');
                        if (display) display.innerHTML = '₱' + parseFloat(result.value).toLocaleString(undefined, { minimumFractionDigits: 2 });

                        Swal.fire({
                            icon: 'success',
                            title: 'Settings Persisted!',
                            text: 'RFID Issuance fee has been updated to ₱' + result.value + ' across all dashboards.',
                            timer: 3000
                        });
                    } else { throw new Error('Update failed'); }
                } catch (e) {
                    Swal.fire('Update Failed', 'Server rejected the configuration sync.', 'error');
                }
            }
        });
    });
</script>
@endsection
@endsection
