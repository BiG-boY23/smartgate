@extends('layouts.app')

@section('title', __('messages.dashboard'))
@section('subtitle', __('messages.monitoring_tag'))

@section('content')

<!-- Real-Time System Clock -->
<div class="no-print" style="margin-bottom: 2rem; text-align: center; background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 1rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); display: flex; justify-content: center; align-items: center; gap: 1.5rem;">
    <i class="ph-bold ph-clock" style="font-size: 2rem; color: #f59e0b;"></i>
    <div>
        <div id="realTimeClock" style="font-size: 1.8rem; font-weight: 800; font-family: 'Inter', sans-serif; letter-spacing: 0.5px; line-height: 1.2;">-- --, ---- --:--:-- --</div>
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.8; font-weight: 700; margin-top: 2px;">University Reference Time (PST)</div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const dateOptions = { month: 'long', day: 'numeric', year: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        
        const dateStr = now.toLocaleDateString('en-US', dateOptions);
        const timeStr = now.toLocaleTimeString('en-US', timeOptions);
        
        const clockElement = document.getElementById('realTimeClock');
        if (clockElement) {
            clockElement.innerText = `${dateStr} at ${timeStr}`;
        }
    }
    setInterval(updateClock, 1000);
    updateClock(); // Initial call
</script>

@php 
    $ldState = \Illuminate\Support\Facades\Cache::get('system_lockdown', ['active' => false, 'reason' => 'N/A']);
    if (!is_array($ldState)) {
        $ldState = ['active' => (bool)$ldState, 'reason' => 'N/A'];
    }
@endphp

<div id="lockdownBanner" style="{{ $ldState['active'] ? 'display: flex;' : 'display: none;' }} background: #dc2626; color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; align-items: center; gap: 1.5rem; animation: lockdown-pulse 2s infinite; box-shadow: 0 10px 30px -5px rgba(220, 38, 38, 0.5);">
     <i class="ph-fill ph-warning-octagon" style="font-size: 3rem;"></i>
     <div>
         <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800;">EMERGENCY LOCKDOWN ACTIVE</h2>
         <p style="margin: 0; font-size: 1rem; font-weight: 700; background: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 4px; display: inline-block; margin: 0.5rem 0;">REASON: <span id="lockdownReasonLabel">{{ $ldState['reason'] }}</span></p>
         <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">All vehicle entry and exit is strictly prohibited. Please secure the gate manually.</p>
     </div>
</div>

<audio id="lockdownAlarm" src="https://www.soundjay.com/buttons/sounds/beep-01a.mp3" preload="auto"></audio>
<audio id="blacklistAlarm" preload="auto">
    <source src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" type="audio/ogg">
</audio>

<style>
    @keyframes lockdown-pulse {
        0% { transform: scale(1); box-shadow: 0 10px 30px -5px rgba(220, 38, 38, 0.5); }
        50% { transform: scale(1.01); box-shadow: 0 20px 50px -5px rgba(220, 38, 38, 0.8); }
        100% { transform: scale(1); box-shadow: 0 10px 30px -5px rgba(220, 38, 38, 0.5); }
    }
</style>

<!-- Overstaying Security Alert -->
@if($overstaying->count() > 0)
<div class="no-print" style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; animation: slideIn 0.3s ease;">
    <div style="background: #ef4444; color: white; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="ph-fill ph-shield-warning" style="font-size: 1.75rem;"></i>
    </div>
    <div style="flex: 1;">
        <h4 style="margin: 0; color: #991b1b; font-weight: 800; font-size: 1rem;">SECURITY ALERT: OVERSTAYING VEHICLES ({{ $overstaying->count() }})</h4>
        <p style="margin: 0.25rem 0 0 0; color: #b91c1c; font-size: 0.9rem; font-weight: 600;">
            Detected {{ $overstaying->count() }} vehicle(s) inside for over 12 hours. Check logs for potential abandoned or overnight vehicles.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        @foreach($overstaying->take(3) as $o)
            <span class="badge" style="background: #ef4444; color: white; border: none;">{{ $o->vehicleRegistration->plate_number ?? $o->rfid_tag_id }}</span>
        @endforeach
    </div>
</div>
@endif

<!-- Occupancy Tracker & Hourly Trend (Grid) -->
<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Left: Occupancy Tracker -->
    <div class="table-container" style="padding: 1.5rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; height: 100%; display: flex; flex-direction: column; justify-content: center;">
        @php
            $warningThreshold = (int)\App\Models\SystemSetting::get('occupancy_warning_threshold', 90);
            $percent = ($stats['occupancy'] / $stats['total_capacity']) * 100;
            $barColor = $percent >= $warningThreshold ? '#dc2626' : ($percent >= 70 ? '#f59e0b' : '#10b981');
        @endphp
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 750; color: #1e293b;"><i class="ph-bold ph-buildings"></i> Current Campus Occupancy</h3>
            <span id="occupancyText" style="font-weight: 800; color: {{ $barColor }}; font-size: 1.1rem;">
                <span id="occCount" style="font-size: 1.3rem;">{{ $stats['occupancy'] }}</span> / {{ $stats['total_capacity'] }}
            </span>
        </div>
        <div style="width: 100%; height: 20px; background: #f1f5f9; border-radius: 99px; overflow: hidden; border: 1px solid #e2e8f0; margin: 0.5rem 0;">
            <div id="occupancyBar" style="width: {{ $percent }}%; height: 100%; background: {{ $barColor }}; transition: width 0.5s ease; box-shadow: 0 0 10px {{ $barColor }}44;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.85rem; font-weight: 650;">
            <span style="{{ $percent >= $warningThreshold ? 'color: #dc2626;' : 'color: #64748b;' }}"><i class="ph ph-activity"></i> Daily Entry/Exit Status</span>
            <span id="occPercent">{{ round($percent) }}% Capacity Used</span>
        </div>
    </div>

    <!-- Right: Hourly Traffic Trend Chart -->
    <div class="table-container" style="padding: 1.25rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; height: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 750; color: #1e293b;"><i class="ph-bold ph-chart-line"></i> Traffic Trend (Last 12 Hours)</h3>
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Real-time Analytics Flow</span>
        </div>
        <div style="height: 140px; width: 100%;">
            <canvas id="trafficTrendChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($hourlyTrends['labels']),
                datasets: [
                    {
                        label: 'Entries',
                        data: @json($hourlyTrends['entries']),
                        borderColor: '#10b981',
                        backgroundColor: '#10b98122',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Exits',
                        data: @json($hourlyTrends['exits']),
                        borderColor: '#ef4444',
                        backgroundColor: '#ef444422',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { display: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } }
                }
            }
        });
    });
</script>

<!-- Guard Dashboard Ticker -->
<div class="ticker-wrapper" style="position: fixed; bottom: 0; left: 0; right: 0; background: #741b1b; color: white; padding: 0.6rem 0; overflow: hidden; z-index: 1000; border-top: 2px solid #f59e0b; box-shadow: 0 -5px 15px rgba(0,0,0,0.2);">
    <marquee id="guardTickerMarquee" behavior="scroll" direction="left" scrollamount="6" style="font-weight: 800; font-size: 0.95rem; text-transform: uppercase;">
        <i class="ph-fill ph-megaphone" style="margin: 0 1rem; color: #f59e0b;"></i>
        <span id="tickerContent">{{ $stats['guard_ticker'] }}</span>
        <i class="ph-fill ph-megaphone" style="margin: 0 1rem; color: #f59e0b;"></i>
    </marquee>
</div>

<!-- Hardware Connection Header -->
<div class="tag-scanner-box mb-8" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div id="statusIcon" style="font-size: 1.5rem;"><i class="ph ph-broadcast text-gray-400"></i></div>
        <div>
            <div id="statusText" class="text-sm font-semibold text-gray-700">{{ __('messages.scanner_status') }}</div>
            <div id="scannerSubtext" class="text-xs text-gray-500">{{ __('messages.scanner_hint') }}</div>
        </div>

    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <!-- TTS Toggle Switch -->
        <div style="display: flex; align-items: center; gap: 0.75rem; background: #f8fafc; padding: 0.5rem 1rem; border-radius: 99px; border: 1px solid #e2e8f0; margin-right: 1.5rem;">
            <i class="ph ph-speaker-high" style="font-size: 1.25rem; color: #64748b;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Voice Alert</span>
                <label class="switch" style="position: relative; display: inline-block; width: 44px; height: 22px;">
                    <input type="checkbox" id="toggleVoice" checked style="opacity: 0; width: 0; height: 0;">
                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px;"></span>
                </label>
            </div>
        </div>

        <button type="button" id="btnConnectHardware" class="btn btn-outline" style="gap: 0.5rem;">
            <i class="ph ph-plugs"></i> <span>Connect Reader</span>
        </button>
        <button type="button" id="btnSimulateScan" class="btn btn-outline" style="gap: 0.5rem; border-style: dashed;">
            <i class="ph ph-monitor"></i> <span>Simulate</span>
        </button>
    </div>
</div>

<style>
    .switch input:checked + .slider { background-color: #741b1b; }
    .switch input:focus + .slider { box-shadow: 0 0 1px #741b1b; }
    .switch input:checked + .slider:before { transform: translateX(20px); }
    .slider:before {
        position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
        background-color: white; transition: .4s; border-radius: 50%;
    }
</style>

<!-- Summary Cards -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-label">{{ __('messages.total_entries') }}</div>
        <div class="stat-value" id="countEntries">{{ $stats['entries_today'] }}</div>
        <i class="ph ph-arrow-circle-down-left stat-icon"></i>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">{{ __('messages.total_exits') }}</div>
        <div class="stat-value" id="countExits">{{ $stats['exits_today'] }}</div>
        <i class="ph ph-arrow-circle-up-right stat-icon"></i>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">{{ __('messages.active_users') }}</div>
        <div class="stat-value">{{ $stats['visitors_inside'] }}</div>
        <i class="ph ph-users-three stat-icon"></i>
    </div>

    
    <div id="systemStatusCard" class="stat-card" style="transition: all 0.5s ease;">
        <div class="stat-label">System Status</div>
        <div id="systemStatusValue" class="stat-value text-success" style="font-size: 1.5rem;">ACTIVE</div>
        <i id="systemStatusIcon" class="ph ph-check-circle stat-icon text-success" style="opacity: 0.2"></i>
    </div>
</div>

<!-- Real-time Activity Logs -->
<div class="table-container">
    <div class="section-header">
        <h3>{{ __('messages.recent_activity') }}</h3>
        <div style="display: flex; gap: 1rem;">
            <a href="{{ route('guard.entry') }}" class="btn btn-primary">
                <i class="ph ph-plus"></i> {{ __('messages.manual_entry') }}
            </a>
        </div>
    </div>


    <div class="table-wrapper">
        <table id="logsTable">
            <thead>
                <tr>
                    <th>{{ __('messages.field_timestamp') }}</th>
                    <th>{{ __('messages.field_vehicle') }}</th>
                    <th>{{ __('messages.field_owner') }}</th>
                    <th>{{ __('messages.field_plate') }}</th>
                    <th>{{ __('messages.field_type') }}</th>
                    <th>{{ __('messages.field_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentLogs as $log)
                <tr>
                    <td>{{ $log->timestamp->format('h:i:s A') }}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="ph ph-car" style="font-size: 1.25rem;"></i>
                            <div>
                                <div style="font-weight: 600;">{{ ($log->vehicleRegistration->make_brand ?? 'Unknown') . ' ' . ($log->vehicleRegistration->model_name ?? '') }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">{{ $log->vehicleRegistration->vehicle_type ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $log->vehicleRegistration->full_name ?? 'N/A' }}</td>
                    <td><span class="badge" style="background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0;">{{ $log->vehicleRegistration->plate_number ?? 'N/A' }}</span></td>
                    <td>
                        @if($log->type == 'entry')
                            <span class="badge badge-toggle" data-id="{{ $log->id }}" style="background: #ecfdf5; color: #059669; cursor: pointer;">ENTRY</span>
                        @else
                            <span class="badge badge-toggle" data-id="{{ $log->id }}" style="background: #fef2f2; color: #dc2626; cursor: pointer;">EXIT</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View Details</button>
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">No recent activity detected.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Manual Visitors Inside -->
<div class="table-container mt-8">
    <div class="section-header">
        <h3>Manual Visitors Inside</h3>
        <a href="{{ route('guard.exit') }}" class="btn btn-outline">
            View All Entries
        </a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Visitor Name</th>
                    <th>Plate Number</th>
                    <th>Time In</th>
                    <th>Purpose</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($visitors_inside as $visitor)
                <tr>
                    <td style="font-weight: 600;">{{ $visitor->name }}</td>
                    <td><span class="badge" style="background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0;">{{ $visitor->plate ?? 'N/A' }}</span></td>
                    <td>{{ $visitor->time_in->format('h:i A') }}</td>
                    <td>{{ $visitor->purpose }}</td>
                    <td style="text-align: right;">
                        <form action="{{ route('guard.visitor.exit.process', $visitor->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="background: #ef4444; border: none; padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                Log Exit
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">No manual visitors currently inside.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>



@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnConnect = document.getElementById('btnConnectHardware');
        const btnSimulate = document.getElementById('btnSimulateScan');
        const toggleVoice = document.getElementById('toggleVoice');
        const statusText = document.getElementById('statusText');
        const statusIcon = document.getElementById('statusIcon');
        const scannerSubtext = document.getElementById('scannerSubtext');
        const logsTable = document.getElementById('logsTable').getElementsByTagName('tbody')[0];
        
        // --- TTS Engine ---
        let voiceEnabled = localStorage.getItem('guard_voice_alerts') !== 'false';
        toggleVoice.checked = voiceEnabled;
        toggleVoice.onchange = (e) => {
            voiceEnabled = e.target.checked;
            localStorage.setItem('guard_voice_alerts', voiceEnabled);
            if (voiceEnabled) announce("Voice announcements enabled.");
        };

        function announce(text, isUrgent = false) {
            if (!voiceEnabled) return;
            const synth = window.speechSynthesis;
            const utterance = new SpeechSynthesisUtterance(text);
            
            // Try to find a professional natural voice
            const voices = synth.getVoices();
            utterance.voice = voices.find(v => v.name.includes('Google') || v.name.includes('Natural')) || voices[0];
            utterance.rate = 0.9;  // Slightly slower for clarity
            utterance.pitch = 1.0;

            if (isUrgent) {
                synth.cancel(); // Interrupt current speech for emergency
                // Play alarm sound before blacklisted warning
                const alarm = new Audio('https://www.soundjay.com/buttons/sounds/beep-01a.mp3');
                alarm.play().catch(e => {});
            }
            
            synth.speak(utterance);
        }
        
        let bridgeSocket = null;
        let isConnected = false;

        function updateUIStatus(status) {
            if (status === 'connected') {
                isConnected = true;
                statusText.innerText = 'Scanner Online';
                statusIcon.innerHTML = '<span class="live-badge"><span class="pulse-dot"></span>LIVE</span>';
                scannerSubtext.innerText = 'Live hardware monitoring active — broadcasting to all portals.';
                btnConnect.innerHTML = '<i class="ph ph-plugs-connected"></i> Disconnect Reader';
                btnConnect.classList.remove('btn-outline');
                btnConnect.classList.add('btn-primary');
            } else if (status === 'connecting') {
                statusText.innerText = 'Connecting...';
                statusIcon.innerHTML = '<i class="ph ph-spinner-gap animate-spin" style="color:#f59e0b;font-size:1.5rem;"></i>';
                btnConnect.disabled = true;
            } else {
                isConnected = false;
                statusText.innerText = 'Scanner Offline';
                statusIcon.innerHTML = '<i class="ph ph-broadcast" style="color:#94a3b8;font-size:1.5rem;"></i>';
                scannerSubtext.innerText = 'Connect to hardware bridge to begin monitoring.';
                btnConnect.innerHTML = '<i class="ph ph-plugs"></i> Connect Reader';
                btnConnect.classList.remove('btn-primary');
                btnConnect.classList.add('btn-outline');
                btnConnect.disabled = false;
            }
        }

        btnConnect.addEventListener('click', async function() {

            if (isConnected) {
                bridgeSocket.close();
                return;
            }

            updateUIStatus('connecting');

            // Step 1 — auto-launch bridge_service.py via Laravel if not already running
            try {
                const res  = await fetch('{{ route("bridge.start") }}');
                const data = await res.json();
                if (data.status === 'error') {
                    updateUIStatus('offline');
                    Swal.fire({
                        icon: 'error',
                        title: 'Bridge Script Missing',
                        text: data.message,
                        confirmButtonColor: '#1e293b'
                    });
                    return;
                }
            } catch (e) {
                console.warn('Bridge start check failed, attempting direct connect:', e);
            }

            // Step 2 — open WebSocket (bridge v3.0 broadcasts to all clients universally)
            bridgeSocket = new WebSocket('ws://127.0.0.1:8080');

            bridgeSocket.onopen = function() {
                updateUIStatus('connected');
            };

            bridgeSocket.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    if (data.tagId && data.status === 'scanned') {
                        processTag(data.tagId);
                    }
                } catch (e) { console.error('Bridge error', e); }
            };

            bridgeSocket.onclose = function() {
                updateUIStatus('offline');
            };

            bridgeSocket.onerror = function() {
                updateUIStatus('offline');
                Swal.fire({
                    icon: 'error',
                    title: 'Bridge Not Responding',
                    text: 'Could not reach bridge_service.py. It may still be starting — try clicking Connect Reader again in a moment.',
                    confirmButtonColor: '#1e293b'
                });
            };
        });

        // Simulation for testing
        btnSimulate.addEventListener('click', function() {
            Swal.fire({
                title: 'Simulate Plate Scan',
                input: 'text',
                inputLabel: 'Enter RFID Tag ID or Plate Number',
                inputPlaceholder: 'e.g. 1234567890',
                showCancelButton: true,
                confirmButtonText: 'Scan',
                confirmButtonColor: '#1e293b'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    processTag(result.value);
                }
            });
        });

        let lastProcessedTags = {}; // Store last seen time for each tag

        async function processTag(tagId) {
            // 1. Protection against modal spam: Don't trigger if a popup is already visible (only for unregistered tags)
            if (Swal.isVisible() && !Swal.isTimerRunning()) {
                 console.log("Ignoring scan - SweetAlert is currently visible");
                 return;
            }

            // 2. Cooldown: Don't process the same tag ID too quickly (within 5 seconds)
            const now = Date.now();
            if (lastProcessedTags[tagId] && (now - lastProcessedTags[tagId]) < 5000) {
                console.log("Ignoring repeat scan - Cooldown active for " + tagId);
                return;
            }

            lastProcessedTags[tagId] = now;
            scannerSubtext.innerHTML = `Last detected: <b style="color:var(--bg-sidebar)">${tagId}</b>`;

            // First lookup the tag
            try {
                const response = await fetch(`{{ url('guard/lookup-tag') }}?tagId=${tagId}`);
                const result = await response.json();

                if (result.success) {
                    // AUTO-PILOT: Registered tag found, log immediately without modal
                    const vehicle = result.data;
                    const action = result.suggested_action;
                    
                    logVehicle(tagId, action, vehicle);
                } else {
                    // Trigger Audio for Unregistered Tag
                    announce("Unregistered Tag detected. Please check documents.", true);

                    // EXCEPTIONS: Only show popup for unregistered tags (Visitors)
                    Swal.fire({
                        icon: 'warning',
                        title: 'Unregistered Tag',
                        text: `The tag ID ${tagId} is not registered in the system.`,
                        footer: '<span style="color: #64748b; font-size: 0.8rem;">Register this vehicle or log as visitor.</span>',
                        confirmButtonText: 'Manual Entry',
                        showCancelButton: true,
                        confirmButtonColor: '#1e293b'
                    }).then((r) => {
                        if (r.isConfirmed) {
                             window.location.href = "{{ route('guard.entry') }}?tagId=" + tagId;
                        }
                    });
                }
            } catch (e) {
                console.error('Processing error', e);
            }
        }

        async function logVehicle(tagId, type, vehicleInfo = null) {
            try {
                const response = await fetch("{{ route('guard.log.vehicle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ tagId, type })
                });
                
                const result = await response.json();
                if (result.success) {
                    addLogRow(result.log);
                    updateCounters(type, result.occupancy);
                    
                    // Instant UI Feedback via Toast (Top-Right)
                    const title = type === 'entry' ? 'Entry Logged' : 'Exit Logged';
                    const name = vehicleInfo ? vehicleInfo.full_name : (result.log.vehicle_registration?.full_name || 'User');
                    const plate = vehicleInfo ? vehicleInfo.plate_number : (result.log.vehicle_registration?.plate_number || '');
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `${title}: ${name} (${plate})`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });

                    // Trigger Voice
                    announce(`${name}, ${type === 'entry' ? 'Entry' : 'Exit'}.`);
                } else if (result.lockdown) {
                    // CRITICAL LOCKDOWN ALERT
                    Swal.fire({
                        icon: 'error',
                        title: 'SYSTEM LOCKDOWN',
                        text: result.message,
                        background: '#741b1b',
                        color: '#ffffff',
                        confirmButtonColor: '#dc2626'
                    });
                } else if (result.blacklisted) {
                    // ████ BLACKLIST ALERT — Hard Denial ████
                    const statusCard = document.getElementById('systemStatusCard');
                    const statusVal = document.getElementById('systemStatusValue');
                    const statusIco = document.getElementById('systemStatusIcon');

                    if (statusCard) {
                        statusCard.style.background = '#7f1d1d';
                        statusCard.style.borderColor = '#991b1b';
                        statusCard.style.color = '#fff';
                        statusVal.innerHTML = '⚠ BLACKLISTED';
                        statusVal.className = 'stat-value';
                        statusVal.style.color = '#fca5a5';
                        statusIco.className = 'ph ph-prohibit stat-icon';
                        statusIco.style.color = '#fca5a5';

                        setTimeout(() => {
                            statusCard.style.background = '';
                            statusCard.style.borderColor = '';
                            statusCard.style.color = '';
                            statusVal.innerText = 'ACTIVE';
                            statusVal.className = 'stat-value text-success';
                            statusVal.style.color = '';
                            statusIco.className = 'ph ph-check-circle stat-icon text-success';
                            statusIco.style.color = '';
                        }, 8000);
                    }

                    // Play blacklist alarm (repeat 3×)
                    const blAlarm = document.getElementById('blacklistAlarm');
                    if (blAlarm) {
                        let plays = 0;
                        const playAlarm = () => { if (plays++ < 3) { blAlarm.currentTime = 0; blAlarm.play().catch(() => {}); } };
                        playAlarm();
                        blAlarm.onended = playAlarm;
                    }

                    // Urgent voice announcement
                    announce(`Warning! ${result.owner} is blacklisted. Do not allow entry. Detain immediately.`, true);

                    Swal.fire({
                        icon: 'error',
                        title: '🚫 BLACKLISTED VEHICLE',
                        html: `
                            <div style="background:#7f1d1d;color:#fff;padding:1rem;border-radius:12px;margin-bottom:1rem;">
                                <strong style="font-size:1.25rem;">${result.owner}</strong><br>
                                <span style="font-family:monospace;font-size:1.1rem;background:#991b1b;padding:2px 8px;border-radius:4px;">${result.plate}</span>
                            </div>
                            <p style="color:#dc2626;font-weight:700;">${result.message}</p>
                            <p style="font-size:0.85rem;color:#64748b;">This vehicle is flagged in the system. Do NOT allow access. Alert security if needed.</p>
                        `,
                        background: '#fff1f2',
                        confirmButtonColor: '#7f1d1d',
                        confirmButtonText: '✓ Acknowledged — Deny Entry',
                        showCancelButton: false,
                        allowOutsideClick: false,
                    }).then(() => {
                        if (blAlarm) { blAlarm.pause(); blAlarm.onended = null; }
                    });

                } else if (result.expired) {
                    // RED ALERT: Expiry Access Denied
                    const statusCard = document.getElementById('systemStatusCard');
                    const statusVal = document.getElementById('systemStatusValue');
                    const statusIcon = document.getElementById('systemStatusIcon');
                    
                    if (statusCard) {
                        statusCard.style.background = '#fef2f2';
                        statusCard.style.borderColor = '#ef4444';
                        statusVal.innerHTML = 'DENIED: EXPIRED TAG';
                        statusVal.className = 'stat-value text-danger';
                        statusIcon.className = 'ph ph-x-circle stat-icon text-danger';
                        
                        setTimeout(() => {
                            statusCard.style.background = '';
                            statusCard.style.borderColor = '';
                            statusVal.innerText = 'ACTIVE';
                            statusVal.className = 'stat-value text-success';
                            statusIcon.className = 'ph ph-check-circle stat-icon text-success';
                        }, 5000);
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'ACCESS DENIED',
                        text: result.message,
                        background: '#fee2e2',
                        confirmButtonColor: '#dc2626'
                    });

                    // Trigger Emergency Voice (Denial/Blacklist)
                    const deniedName = result.log?.vehicle_registration?.full_name || 'Expired Tag Detected';
                    announce(`Warning: ${deniedName} is Blacklisted.`, true);
                }
            } catch (e) { console.error('Logging error', e); }
        }

        function addLogRow(log) {
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            const row = logsTable.insertRow(0); // Add at top
            
            // Format time correctly from timestamp
            const logTime = new Date(log.timestamp);
            const time = logTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            
            const badgeType = log.type === 'entry' ? 
                `<span class="badge badge-toggle" data-id="${log.id}" style="background: #ecfdf5; color: #059669; cursor: pointer;">ENTRY</span>` : 
                `<span class="badge badge-toggle" data-id="${log.id}" style="background: #fef2f2; color: #dc2626; cursor: pointer;">EXIT</span>`;

            row.innerHTML = `
                <td>${time}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="ph ph-car" style="font-size: 1.25rem;"></i>
                        <div>
                            <div style="font-weight: 600;">${(log.vehicle_registration?.make_brand || 'Unknown')} ${(log.vehicle_registration?.model_name || '')}</div>
                            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">${(log.vehicle_registration?.vehicle_type || 'N/A')}</div>
                        </div>
                    </div>
                </td>
                <td>${log.vehicle_registration?.full_name || 'N/A'}</td>
                <td><span class="badge" style="background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0;">${log.vehicle_registration?.plate_number || 'N/A'}</span></td>
                <td>${badgeType}</td>
                <td><button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View</button></td>
            `;
            
            // Highlight animation
            row.style.animation = 'highlight 1s ease-out';
        }

        function updateCounters(type, newOccupancy = null) {
            const counter = type === 'entry' ? document.getElementById('countEntries') : document.getElementById('countExits');
            if (counter) {
                counter.innerText = parseInt(counter.innerText) + 1;
            }

            if (newOccupancy !== null) {
                const total = {{ $stats['total_capacity'] }};
                const occCount = document.getElementById('occCount');
                const occPercent = document.getElementById('occPercent');
                const occBar = document.getElementById('occupancyBar');

                occCount.innerText = newOccupancy;
                const percent = Math.round((newOccupancy / total) * 100);
                occPercent.innerText = `${percent}% Capacity Used`;
                occBar.style.width = `${percent}%`;

                // Update Bar Color
                if (percent > 90) occBar.style.background = '#ef4444';
                else if (percent > 70) occBar.style.background = '#f59e0b';
                else occBar.style.background = '#10b981';
            }
        }

        // Manual Correction: Click badge to toggle Entry/Exit
        logsTable.addEventListener('click', function(e) {
            const badge = e.target.closest('.badge-toggle');
            if (badge) {
                const logId = badge.getAttribute('data-id');
                if (logId) {
                    toggleLogType(logId, badge);
                }
            }
        });

        async function toggleLogType(id, badgeElement) {
            try {
                const response = await fetch(`{{ url('guard/log-vehicle') }}/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    const newType = result.new_type;
                    
                    // Update the badge UI
                    if (newType === 'entry') {
                        badgeElement.style.background = '#ecfdf5';
                        badgeElement.style.color = '#059669';
                        badgeElement.innerText = 'ENTRY';
                        
                        // Increment Entry counter, decrement Exit counter
                        document.getElementById('countEntries').innerText = parseInt(document.getElementById('countEntries').innerText) + 1;
                        document.getElementById('countExits').innerText = Math.max(0, parseInt(document.getElementById('countExits').innerText) - 1);
                    } else {
                        badgeElement.style.background = '#fef2f2';
                        badgeElement.style.color = '#dc2626';
                        badgeElement.innerText = 'EXIT';
                        
                        // Increment Exit counter, decrement Entry counter
                        document.getElementById('countExits').innerText = parseInt(document.getElementById('countExits').innerText) + 1;
                        document.getElementById('countEntries').innerText = Math.max(0, parseInt(document.getElementById('countEntries').innerText) - 1);
                    }
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Status Updated',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            } catch (e) {
                console.error('Toggle error', e);
            }
        }
    });

    let isLockdownActive = {{ $ldState['active'] ? 'true' : 'false' }};
    function checkLockdownStatus() {
        fetch('{{ route('guard.lockdown.check') }}')
            .then(res => res.json())
            .then(data => {
                const banner = document.getElementById('lockdownBanner');
                const reasonLabel = document.getElementById('lockdownReasonLabel');
                const alarm = document.getElementById('lockdownAlarm');
                const ticker = document.getElementById('tickerContent');
                
                if (ticker && data.ticker) ticker.textContent = data.ticker;

                if (data.active) {
                    banner.style.display = 'flex';
                    if (reasonLabel) reasonLabel.textContent = data.reason;
                    if (!isLockdownActive) {
                        if (alarm) {
                            alarm.currentTime = 0;
                            alarm.play().catch(e => console.log('Audio playback blocked by browser policy until interaction', e));
                        }
                        isLockdownActive = true;
                    }
                } else {
                    banner.style.display = 'none';
                    isLockdownActive = false;
                }
            })
            .catch(e => console.error('Lockdown poll error', e));
    }
    setInterval(checkLockdownStatus, 10000);
</script>

<style>
    .animate-spin { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes highlight { from { background: #f0f9ff; } to { background: transparent; } }
    .badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
</style>
@endsection
@endsection
