@extends('layouts.app')

@section('title', 'Office Dashboard')
@section('subtitle', 'SmartGate – EVSU Vehicle Owner Management Summary')

@section('content')
<div class="dashboard-grid">
    <!-- TOTAL USERS -->
    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem;">{{ $totalUsers ?? 0 }}</div>
        <i class="ph ph-users stat-icon"></i>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            All registered users in the system
        </p>
    </div>

    <!-- REGISTERED TODAY -->
    <div class="stat-card">
        <div class="stat-label">Registered Today</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem;">{{ $registeredToday ?? 0 }}</div>
        <i class="ph ph-user-plus stat-icon"></i>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            New owner registration applications today
        </p>
    </div>

    <!-- ACTIVE VEHICLES -->
    <div class="stat-card">
        <div class="stat-label">Active Vehicles</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem;">{{ $activeVehicles ?? 0 }}</div>
        <i class="ph ph-car stat-icon"></i>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            Vehicles currently registered and valid
        </p>
    </div>

    <!-- PENDING ONLINE REGISTRATIONS -->
    <div class="stat-card" style="{{ ($pendingRegistrations ?? 0) > 0 ? 'border: 1px solid #741b1b; background: #fffcfc;' : '' }}">
        <div class="stat-label">Pending Online</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: {{ ($pendingRegistrations ?? 0) > 0 ? '#741b1b' : 'inherit' }};">{{ $pendingRegistrations ?? 0 }}</div>
        <i class="ph ph-fingerprint stat-icon" style="{{ ($pendingRegistrations ?? 0) > 0 ? 'color: #741b1b;' : '' }}"></i>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            Applications waiting for verification
        </p>
        @if(($pendingRegistrations ?? 0) > 0)
        <a href="{{ route('office.users') }}" style="display: block; margin-top: 0.5rem; font-size: 0.75rem; color: #741b1b; font-weight: 700; text-decoration: none;">
            Review Now <i class="ph ph-arrow-right"></i>
        </a>
        @endif
    </div>

    <!-- CURRENT OCCUPANCY -->
    <div class="stat-card">
        <div class="stat-label">Current Occupancy</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: #1e40af;">{{ $currentOccupancy ?? 0 }} <span style="font-size: 1rem; color: #94a3b8; font-weight: 400;">/ {{ $totalCapacity ?? 200 }}</span></div>
        <i class="ph ph-buildings stat-icon" style="color: #1e40af;"></i>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
            Vehicles (including visitors) currently on premises
        </p>
        @php
            $occPercent = min(100, (($currentOccupancy ?? 0) / ($totalCapacity ?? 200)) * 100);
        @endphp
        <div style="width: 100%; height: 6px; background: #eff6ff; border-radius: 99px; margin-top: 0.5rem; overflow: hidden; border: 1px solid #dbeafe;">
            <div style="width: {{ $occPercent }}%; height: 100%; background: #2563eb; border-radius: 99px; transition: width 0.5s ease;"></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- QUICK SUMMARY -->
    <div class="table-container">
        <div class="section-header">
            <h3 style="font-weight: 600; color: var(--bg-sidebar); display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-chart-pie"></i> Quick Summary
            </h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @php
                $summaryItems = $summary ?? [];
                $renderItem = function($item) {
                    return [
                        'label' => $item['label'] ?? '',
                        'percent' => $item['percent'] ?? 0,
                        'count' => $item['count'] ?? 0,
                    ];
                };
            @endphp
            @foreach($summaryItems as $item)
            @php $it = $renderItem($item); @endphp
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <span>{{ $it['label'] }}</span>
                <span style="font-weight: 700; color: var(--bg-sidebar);">
                    {{ $it['percent'] }}%
                    <span style="color: #94a3b8; font-weight: 600;">({{ $it['count'] }})</span>
                </span>
            </div>
            @endforeach
            @if(empty($summaryItems))
            <div style="text-align:center; color:#94a3b8; padding:0.5rem;">No data yet.</div>
            @endif
        </div>
    </div>

    <!-- SYSTEM STATUS -->
    <div class="table-container">
        <div class="section-header">
            <h3 style="font-weight: 600; color: var(--bg-sidebar); display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-shield-check"></i> System Status
            </h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <span>Database</span>
                <span class="text-success" style="font-weight: 600;">Online</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <span>RFID Reader</span>
                <span class="text-success" style="font-weight: 600;">Connected</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem;">
                <span>Gate Controller</span>
                <span class="text-warning" style="font-weight: 600;">Standby</span>
            </div>
        </div>
    </div>
</div>
@endsection
