@extends('layouts.app')

@section('title', 'Financial Ledger')
@section('subtitle', 'Complete history of all RFID issuance payments.')

@section('content')

<div id="report-print-container">
    <!-- 1. Official University Letterhead (Header) -->
    @include('partials.report-header', ['deptName' => 'Vehicle RFID Registration Financial Statement'])

    <!-- 2. Report Title & Filter Section -->
    <div id="print-title-section" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
        <div style="text-align: left;">
            <h2 class="document-title" style="margin-bottom: 0.5rem;">RFID ISSUANCE FINANCIAL LEDGER</h2>
            <p class="document-meta">
                Generated on: <strong>{{ \Carbon\Carbon::now()->format('F d, Y g:i A') }}</strong> |
                Staff in Charge: <strong>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</strong>
            </p>
        </div>
        
        <!-- Interactive Filter (No Print) -->
        <div class="no-print" style="background: #f8fafc; padding: 1rem; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; gap: 1rem; align-items: center;">
            <div style="text-align: right;">
                <label style="display: block; font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Filter Period</label>
                <form action="{{ route('payments.ledger') }}" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="date" name="start" value="{{ $start }}" class="date-mini-light">
                    <span style="color: #94a3b8; font-weight: 800; font-size: 0.7rem;">TO</span>
                    <input type="date" name="end" value="{{ $end }}" class="date-mini-light">
                    <button type="submit" class="btn-filter-apply"><i class="ph ph-funnel"></i> Apply</button>
                    <a href="{{ route('payments.ledger') }}" class="btn-filter-reset" title="Reset"><i class="ph ph-arrow-counter-clockwise"></i></a>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Trend & Statistical Charts (Visible in Print) -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card-premium" style="display: block; padding: 2rem; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-chart-line" style="color: #741b1b;"></i> Trend of Collections
                </h3>
                <button onclick="window.print()" class="no-print" style="background: none; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 0.8rem; font-weight: 700;">
                    <i class="ph ph-printer" style="font-size: 1.1rem;"></i> Print Analysis
                </button>
            </div>
            <div style="height: 250px; width: 100%;">
                <canvas id="collectionsTrendChart"></canvas>
            </div>
        </div>
    </div>

    @php
        $financialStats = [
            ['label' => 'Total Revenue', 'value' => '₱' . number_format($summary['totalRevenue'], 2), 'icon' => 'ph ph-briefcase-metal'],
            ['label' => 'Transaction Count', 'value' => $summary['transactionCount'], 'icon' => 'ph ph-receipt'],
            ['label' => 'Average Fee', 'value' => '₱' . number_format($summary['avgFee'], 2), 'icon' => 'ph ph-calculator'],
            ['label' => 'Projected Revenue', 'value' => '₱' . number_format($summary['projectedRevenue'], 2), 'icon' => 'ph ph-chart-line-up'],
        ];
    @endphp
    @include('partials.stats-overview', ['stats' => $financialStats])

    <div class="dashboard-grid-extended" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">

    <!-- 4. Primary Table -->
    <div class="table-container shadow-sm">
        <div class="section-header no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0;">Issuance Records</h3>
            <button onclick="window.print()" class="btn-print-official">
                <i class="ph ph-printer"></i> Generate Official Report
            </button>
        </div>

        <div class="table-wrapper">
            <table class="document-table">
                <thead>
                    <tr>
                        <th>Date Paid</th>
                        <th>Owner Name</th>
                        <th>RFID Tag ID</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issuanceRecords as $record)
                    <tr>
                        <td>{{ $record['date'] }}</td>
                        <td class="bold">{{ $record['name'] }}</td>
                        <td class="mono">{{ $record['tag'] }}</td>
                        <td style="text-align: right;" class="bold">
                            ₱{{ number_format($record['amount'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 4rem; color: #94a3b8;">
                            <i class="ph ph-coins" style="font-size: 3rem; opacity: 0.2;"></i>
                            <p style="margin-top: 1rem;">No payments found in the system for this period.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($issuanceRecords) > 0)
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #000;">
                        <td colspan="3" style="text-align: right; padding: 15px;">TOTAL COLLECTION:</td>
                        <td style="text-align: right; padding: 15px; color: #741b1b; font-size: 1.1rem;">₱{{ number_format($totalCollections, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div> <!-- End Table Wrapper -->
    </div> <!-- End Table Container -->

    <div class="no-print" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- REVENUE BY ROLE CHART -->
        <div class="stat-card-premium" style="display: block; padding: 2rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-chart-pie-slice" style="color: #741b1b;"></i> Revenue Breakdown
            </h3>
            <div style="height: 180px; width: 100%; position: relative; margin-bottom: 2rem;">
                <canvas id="revenueByRoleChart"></canvas>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                @foreach($summary['roleBreakdown'] as $role => $data)
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f8fafc; padding-top: 0.5rem;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #64748b;">{{ $data['label'] }}</span>
                    <span style="font-size: 0.85rem; font-weight: 800; color: #1e293b;">₱{{ number_format($data['total'], 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- THE TOP PAYORS LIST -->
        <div class="stat-card-premium" style="display: block; padding: 2rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-crown" style="color: #f59e0b;"></i> Top 10 Payors
            </h3>
            <p style="font-size: 0.75rem; color: #64748b; margin-top: -0.5rem; margin-bottom: 1.5rem;">Highest cumulative payments recorded.</p>
            <div class="hotlist-wrapper">
                <table style="width: 100%; border-collapse: collapse;">
                    @forelse($topPayors as $index => $payor)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; width: 30px; font-weight: 900; color: #94a3b8;">#{{ $index+1 }}</td>
                        <td style="padding: 12px 0;">
                            <div style="font-weight: 800; font-size: 0.85rem; color: #1e293b;">{{ $payor->full_name }}</div>
                            <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 700;">{{ strtoupper($payor->role) }}</div>
                        </td>
                        <td style="padding: 12px 0; text-align: right;">
                            <span class="badge-count" style="background: #f0fdf4; color: #166534; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 900;">
                                ₱{{ number_format($payor->calculated_total, 0) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td class="text-center py-4 text-muted">No payors yet.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>

    </div> <!-- End Dashboard Grid Extended -->

    <!-- 5. Signatory Section (Footer) -->
    @include('partials.report-signatories')
</div>

<style>
    /* --- Table Styles (Shared UI + Print) --- */
    .document-table { width: 100%; border-collapse: collapse; }
    .document-table th { background: #f8fafc; text-align: left; padding: 14px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .document-table td { padding: 14px; font-size: 0.95rem; border-bottom: 1px solid #f1f5f9; }
    .mono { font-family: monospace; }
    .bold { font-weight: 800; }

    .btn-print-official { background: white; border: 2px solid #741b1b; color: #741b1b; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s; }
    .btn-print-official:hover { background: #741b1b; color: white; }

    /* --- Universal Layout for Print (Center Matched) --- */
    #print-header, #print-signatories, #print-title-section { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 2cm !important; }
        .sidebar, .header, .no-print, .app-footer { display: none !important; }
        .main-content { margin:0 !important; padding:0 !important; background:white !important; }
        
        #report-print-container { display: block !important; visibility: visible !important; font-family: 'Serif', 'Times New Roman' !important; color: #000 !important; }
        
        #print-header { display: flex !important; justify-content: space-between !important; align-items: center !important; border-bottom: 2.5px solid #000 !important; padding-bottom: 15px !important; margin-bottom: 25px !important; }
        .header-logo img { height: 80px !important; }
        .header-text { text-align: center !important; flex: 1 !important; }
        .univ-name { font-size: 16pt !important; font-weight: bold !important; }
        .gov-text { font-size: 10pt !important; }
        
        #print-title-section { display: block !important; text-align: center !important; margin-bottom: 25px !important; }
        .document-title { font-size: 18pt !important; font-weight: 900 !important; text-decoration: underline !important; }
        
        .table-container { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .document-table { border: 1.5px solid #000 !important; }
        .document-table th { border: 1.5px solid #000 !important; background: #eee !important; color: #000 !important; }
        .document-table td { border: 1.5px solid #000 !important; color: #000 !important; }
        
        #print-signatories { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; }
        .signatory-block { width: 250px !important; text-align: center !important; }
        .signature-line { border-bottom: 1.5px solid #000 !important; margin-bottom: 5px !important; font-weight: bold !important; }
    }
    .btn-filter-apply { background: #741b1b; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 5px; }
    .btn-filter-reset { background: #f1f5f9; color: #64748b; border: none; padding: 6px; width: 32px; height: 32px; border-radius: 8px; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .date-mini-light { border: 1px solid #e2e8f0; background: #fff; font-size: 0.75rem; font-weight: 800; color: #1e293b; padding: 6px 10px; border-radius: 8px; outline: none; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueByRoleChart');
    if (ctx) {
        const labels = {!! json_encode(array_values(collect($summary['roleBreakdown'])->pluck('label')->toArray())) !!};
        const totals = {!! json_encode(array_values(collect($summary['roleBreakdown'])->pluck('total')->toArray())) !!};

        const colors = [
            '#ef4444', // Red
            '#3b82f6', // Blue
            '#10b981', // Green
            '#f59e0b', // Amber
            '#8b5cf6', // Violet
            '#64748b'  // Slate
        ];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: totals,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return `${label}: ₱${value.toLocaleString()}`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    const trendCtx = document.getElementById('collectionsTrendChart');
    if (trendCtx) {
        const trendLabels = {!! json_encode(array_keys($summary['dailyStats'])) !!};
        const trendData = {!! json_encode(array_values($summary['dailyStats'])) !!};

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Daily Collection',
                    data: trendData,
                    borderColor: '#741b1b',
                    backgroundColor: 'rgba(116, 27, 27, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#741b1b',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `₱${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: '#e2e8f0' },
                        ticks: {
                            callback: function(value) { return '₱' + value; }
                        }
                    }
                }
            }
        });
    }
});
</script>

@endsection
