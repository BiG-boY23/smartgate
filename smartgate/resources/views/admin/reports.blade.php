@extends('layouts.app')

@section('title', 'Reports & System Logs')
@section('subtitle', 'Track gate traffic, monitor staff actions, and generate audit trails.')

@section('content')

<div id="report-print-container">
    <!-- 1. Official University Letterhead (Header) -->
    @include('partials.report-header', ['deptName' => 'SmartGate Traffic Movement Trend & Volume Analysis'])

    <!-- 2. Report Title & Meta Info -->
    <div id="print-title-section">
        <h2 class="document-title">VEHICLE TRAFFIC LOG REPORT</h2>
        <p class="document-meta">
            @if(request('from') || request('to'))
                Ref: <strong>{{ request('from', date('Y-m-d')) }} - {{ request('to', date('Y-m-d')) }}</strong> |
            @endif
            Prepared by: <strong>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</strong> |
            Generated on: <strong>{{ \Carbon\Carbon::now()->format('F d, Y g:i A') }}</strong>
        </p>
    </div>

    <!-- 3. Primary Data Table (Dashboard / Print Shared) -->
    <div class="reports-container">
        <!-- Enhanced Filter Bar (Top of Content) -->
        <!-- Traffic Movement Trend (visible in print) -->
        <div style="margin-bottom: 2rem;">
            <div class="stat-card" style="padding: 1.5rem; margin-bottom: 2rem;">
                <div class="section-header" style="margin-bottom: 1.25rem;">
                    <div>
                        <h3><i class="ph ph-trend-up" style="color:#741b1b;"></i> Traffic Movement Trend</h3>
                        <p class="sub-text">Volume Analysis ({{ request('from') ? $chartData['labels'][0] . ' - ' . end($chartData['labels']) : 'Last 7 Days' }})</p>
                    </div>
                    <button onclick="window.print()" class="no-print" style="background: none; border: 1.5px solid #741b1b; color: #741b1b; padding: 0.5rem 1rem; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s;"
                            onmouseover="this.style.background='#741b1b';this.style.color='white'"
                            onmouseout="this.style.background='none';this.style.color='#741b1b'">
                        <i class="ph ph-printer"></i> Print Analysis
                    </button>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="trafficTrendChart"></canvas>
                </div>
            </div>
        </div>{{-- end trend wrapper --}}

        <div class="no-print">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('traffic')">
                    <i class="ph ph-intersect"></i> Gate Traffic Logs
                </button>
                <button class="tab-btn" onclick="switchTab('audit')">
                    <i class="ph ph-shield-check"></i> System Audit Trail
                </button>
            </div>

            <!-- Enhanced Filter Bar -->
            <div class="filter-bar">
                <form action="{{ route('admin.reports') }}" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="from" value="{{ request('from', date('Y-m-d')) }}" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="to" value="{{ request('to', date('Y-m-d')) }}" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="filter-input">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>ALL ENTRIES</option>
                            <option value="entry" {{ request('status') == 'entry' ? 'selected' : '' }}>ENTRY ONLY</option>
                            <option value="exit" {{ request('status') == 'exit' ? 'selected' : '' }}>EXIT ONLY</option>
                        </select>
                    </div>
                    <div class="filter-group flex-grow">
                        <label>Owner / Plate Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or plate..." class="filter-input">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" style="height: 42px;">
                            <i class="ph ph-funnel"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline" style="height: 42px;">
                            <i class="ph ph-arrows-counter-clockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 1: Gate Traffic Logs -->
        <div id="traffic-tab" class="tab-content active printable-content">
            <div class="table-container">
                <div class="section-header no-print">
                    <h3>Filtered Results ({{ count($logs) }})</h3>
                    <div class="action-buttons">
                        <button class="btn btn-outline" id="btnPrint" onclick="startPrint()">
                            <i class="ph ph-printer"></i> <span class="btn-text">Print Official Report</span>
                        </button>
                        <a href="{{ route('admin.reports.export', request()->all()) }}" class="btn btn-success" id="btnExport" onclick="startExport()">
                            <i class="ph ph-file-xls"></i> <span class="btn-text">Export Excel (.xlsx)</span>
                        </a>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="document-table">
                        <thead>
                            <tr>
                                <th>DateTime</th>
                                <th>Tag ID</th>
                                <th>Identity / Owner</th>
                                <th>Plate</th>
                                <th>Log Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->timestamp->format('M d, h:i A') }}</td>
                                <td class="mono">{{ $log->rfid_tag_id }}</td>
                                <td class="bold">{{ $log->vehicleRegistration->full_name ?? 'VISITOR' }}</td>
                                <td class="mono bold">{{ $log->vehicle?->plate_number ?? $log->vehicleRegistration->plate_number ?? 'N/A' }}</td>
                                <td class="status-cell">
                                    @if($log->type === 'entry')
                                        <span class="status-entry-txt"><i class="ph-bold ph-arrow-circle-down-left"></i> ENTRY</span>
                                    @else
                                        <span class="status-exit-txt"><i class="ph-bold ph-arrow-circle-up-right"></i> EXIT</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="empty-state">No movement logs found matching your criteria.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: System Audit Trail (Dashboard Only) -->
        <div id="audit-tab" class="tab-content no-print">
            <div class="table-container">
                <div class="section-header">
                    <h3>Admin & Staff Actions</h3>
                    <p class="sub-text">Historical record of management actions.</p>
                </div>
                <div class="table-wrapper">
                    <table class="document-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Staff Member</th>
                                <th>Action Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                <td class="bold">{{ $log->user->first_name ?? 'System' }} {{ $log->user->last_name ?? '' }}</td>
                                <td><span class="badge">{{ $log->action }}</span></td>
                                <td>{{ $log->details }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="empty-state">No audit logs recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Signatory Section (Footer) -->
    @include('partials.report-signatories')
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const trendData = @json($chartData);
        const ctx = document.getElementById('trafficTrendChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Entries',
                        data: trendData.entries,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Exits',
                        data: trendData.exits,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, font: { weight: '700' } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b' }, grid: { borderDash: [5, 5] } },
                    x: { ticks: { color: '#64748b' }, grid: { display: false } }
                }
            }
        });
    });

    function startPrint() {
        const btn = document.getElementById('btnPrint');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Processing...';
        btn.style.opacity = '0.7';
        setTimeout(() => {
            window.print();
            btn.innerHTML = original;
            btn.style.opacity = '1';
        }, 500);
    }

    function startExport() {
        const btn = document.getElementById('btnExport');
        const text = btn.querySelector('.btn-text');
        const icon = btn.querySelector('i');
        const originalText = text.innerText;
        text.innerText = 'Downloading...';
        icon.className = 'ph ph-spinner animate-spin';
        setTimeout(() => {
            text.innerText = originalText;
            icon.className = 'ph ph-file-xls';
        }, 5000);
    }

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        const currentBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.innerText.toLowerCase().includes(tabId));
        if (currentBtn) currentBtn.classList.add('active');
        document.getElementById(tabId + '-tab').classList.add('active');
    }
</script>

<style>
    /* --- Dashboard UI & Filters --- */
    .filter-bar { background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 2rem; }
    .filter-form { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .filter-group label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
    .filter-input { padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-size: 0.9rem; min-width: 150px; }
    .filter-actions { display: flex; gap: 10px; }
    .flex-grow { flex-grow: 1; }

    .tab-nav { display: flex; gap: 2rem; border-bottom: 2px solid #e2e8f0; margin-bottom: 2rem; padding-bottom: 0.5rem; }
    .tab-btn { background: none; border: none; padding: 0.75rem 0; font-weight: 600; color: #94a3b8; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; border-bottom: 3px solid transparent; margin-bottom: -0.5rem; transition: 0.2s; }
    .tab-btn:hover { color: #475569; }
    .tab-btn.active { color: #1e293b; border-bottom-color: #1e293b; }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .action-buttons { display: flex; gap: 10px; }
    .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
    .badge { background: #f1f5f9; color: #475569; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; font-family: monospace; }
    .mono { font-family: monospace; }
    .bold { font-weight: 600; }
    .sub-text { font-size: 0.8rem; color: #64748b; }
    
    .btn-primary { background: #741b1b; border: 1px solid #741b1b; color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .btn-success { background: #166534; border: 1px solid #166534; color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .btn-outline { background: white; border: 1px solid #e2e8f0; color: #1e293b; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-sizing: border-box; }

    /* --- Universal Table (UI + Print) --- */
    .document-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .document-table th { background: #f8fafc; text-align: left; padding: 12px; font-size: 0.85rem; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .document-table td { padding: 12px; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
    .status-entry-txt { color: #10b981; font-weight: 700; }
    .status-exit-txt { color: #ef4444; font-weight: 700; }

    /* --- PRINT SPECIFIC ENGINE (ALIGNED & CENTERED) --- */
    #print-header, #print-signatories, #print-title-section { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 2.54cm !important; }
        * { box-sizing: border-box !important; }
        
        .sidebar, .header, .app-footer, .no-print, nav { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; background: white !important; width: 100% !important; overflow: visible !important; }
        .page-body { padding: 0 !important; margin: 0 !important; overflow: visible !important; }
        
        #report-print-container { width: 100% !important; margin: 0 auto !important; display: block !important; visibility: visible !important; font-family: 'Times New Roman', Times, serif !important; }
        
        #print-header { display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; border-bottom: 2px solid #000 !important; padding-bottom: 20px !important; margin-bottom: 30px !important; }
        .header-logo { width: 100px !important; flex-shrink: 0 !important; }
        .header-logo img { height: 85px !important; width: auto !important; }
        .header-text { flex: 1 !important; text-align: center !important; }
        .gov-text { font-size: 11pt !important; text-transform: uppercase !important; }
        .univ-name { font-size: 16pt !important; font-weight: bold !important; }
        .dept-name { font-size: 12pt !important; font-weight: bold !important; }
        .address-text { font-size: 9pt !important; font-style: italic !important; }

        #print-title-section { display: block !important; text-align: center !important; margin-bottom: 30px !important; }
        .document-title { font-size: 18pt !important; font-weight: bold !important; text-transform: uppercase !important; border:none !important; }

        .tab-content { display: none !important; }
        .tab-content.active { display: block !important; visibility: visible !important; }
        
        .document-table { display: table !important; border: 1px solid #000 !important; width: 100% !important; }
        .document-table th { border: 1px solid #000 !important; background: #eee !important; padding: 8px !important; font-size: 10pt !important; text-align: left !important; color:#000 !important; }
        .document-table td { border: 1px solid #000 !important; padding: 6px 8px !important; font-size: 9pt !important; color:#000 !important; }
        .status-cell span { color: #000 !important; font-weight: bold !important; }

        #print-signatories { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; width: 100% !important; }
        .signatory-block { width: 280px !important; text-align: center !important; }
        .signature-line { border-bottom: 1.5px solid #000 !important; font-weight: bold !important; padding-bottom: 5px !important; margin-bottom: 5px !important; }
    }
</style>
@endsection
