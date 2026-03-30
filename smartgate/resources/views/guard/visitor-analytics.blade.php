@extends('layouts.app')

@section('title', 'Visitor Traffic Analytics')
@section('subtitle', 'Deep-dive into visitor patterns, peaks, and recurring entries.')

@section('content')
<div class="analytics-container">
    @include('partials.report-header')

    <!-- Filters header -->
    <div class="header-filters no-print">
        <div class="filter-group">
            <button class="filter-btn {{ $range === 'daily' ? 'active' : '' }}" onclick="updateRange('daily')">Daily (24h)</button>
            <button class="filter-btn {{ $range === '7d' ? 'active' : '' }}" onclick="updateRange('7d')">Weekly</button>
            <button class="filter-btn {{ $range === '30d' ? 'active' : '' }}" onclick="updateRange('30d')">Monthly</button>
            <button class="filter-btn {{ $range === 'custom' ? 'active' : '' }}" onclick="showCustomDates()">Custom Range</button>
        </div>
        
        <div id="custom-inputs" class="filter-group {{ $range === 'custom' ? '' : 'hidden' }}" style="background: white; border: 1px solid #e2e8f0; animation: slideIn 0.3s ease;">
            <input type="date" id="start-date" value="{{ request('start', date('Y-m-d')) }}" style="border: none; outline: none; font-size: 0.8rem; font-weight: 700; color: #1e293b;">
            <span style="color: #94a3b8; font-weight: 800; font-size: 0.6rem;">TO</span>
            <input type="date" id="end-date" value="{{ request('end', date('Y-m-d')) }}" style="border: none; outline: none; font-size: 0.8rem; font-weight: 700; color: #1e293b;">
            <button onclick="applyCustomRange()" style="background: #741b1b; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; cursor: pointer;">Apply</button>
        </div>

        <div class="header-actions">
            <button onclick="window.print()" class="btn-print">
                <i class="ph ph-printer"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-overview">
        <div class="stat-glass-card">
            <div class="stat-icon-bg" style="background: rgba(116, 27, 27, 0.1); color: #741b1b;">
                <i class="ph-fill ph-users-three"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Unique Visitors (Month)</span>
                <h3 class="stat-value">{{ number_format($totalUniqueMonth) }}</h3>
                <span class="stat-sub">New entries this month</span>
            </div>
        </div>

        <div class="stat-glass-card">
            <div class="stat-icon-bg" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="ph-fill ph-arrows-clockwise"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Return Rate</span>
                <h3 class="stat-value">{{ $returnRate }}%</h3>
                <span class="stat-sub">Recurring visitor frequency</span>
            </div>
        </div>

        <div class="stat-glass-card">
            <div class="stat-icon-bg" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="ph-fill ph-calendar-check"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Busiest Day</span>
                <h3 class="stat-value">{{ $busiestDay }}</h3>
                <span class="stat-sub">Peak volume day of week</span>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <!-- Visitor Trend Line Chart -->
        <div class="chart-card glass">
            <div class="card-header">
                <h3><i class="ph ph-trend-up"></i> Visitor Traffic Trend</h3>
                <p>Volume of visitor entries over time</p>
            </div>
            <div class="chart-wrapper">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Peak Arrival Hours Bar Chart -->
        <div class="chart-card glass">
            <div class="card-header">
                <h3><i class="ph ph-clock-countdown"></i> Peak Arrival Hours</h3>
                <p>Time distribution of visitor check-ins</p>
            </div>
            <div class="chart-wrapper">
                <canvas id="peakChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Recurring Visitors List -->
    <div class="table-container glass mt-8">
        <div class="section-header">
            <h3><i class="ph ph-crown" style="color: #f59e0b;"></i> Top 10 Recurring Visitors</h3>
            <p>Frequent visitors with the most campus entries</p>
        </div>
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Plate Number</th>
                        <th>Frequency</th>
                        <th>Main Purpose</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topVisitors as $v)
                    <tr>
                        <td class="font-bold text-maroon">{{ $v->name }}</td>
                        <td><span class="plate-badge">{{ $v->plate ?? 'N/A' }}</span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="freq-count">{{ $v->count }} entries</span>
                                @if($v->count > 5)
                                    <span class="badge badge-gold">VIP ACCESS</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $v->purpose }}</td>
                        <td>
                            @if($v->count > 5)
                                <span class="type-tag tag-frequent">Frequent</span>
                            @else
                                <span class="type-tag tag-standard">Standard</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-8">No visitor data recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.report-signatories')
</div>

<style>
    .hidden { display: none !important; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

    .analytics-container { padding-bottom: 3rem; }
    
    .header-filters { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        background: white; 
        padding: 1.25rem 2rem; 
        border-radius: 16px; 
        border: 1px solid #e2e8f0; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .filter-group { 
        display: flex; 
        background: #f1f5f9; 
        padding: 0.35rem; 
        border-radius: 12px; 
        gap: 0.25rem;
    }

    .filter-btn { 
        padding: 0.6rem 1.5rem; 
        border-radius: 10px; 
        font-size: 0.85rem; 
        font-weight: 700; 
        border: none; 
        background: transparent; 
        color: #64748b; 
        cursor: pointer; 
        transition: 0.2s;
    }

    .filter-btn:hover { color: #1e293b; }
    .filter-btn.active { background: white; color: #741b1b; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

    .btn-print { 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        background: #1e293b; 
        color: white; 
        border: none; 
        padding: 0.75rem 1.5rem; 
        border-radius: 10px; 
        font-weight: 700; 
        cursor: pointer; 
    }

    .stats-overview { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
        gap: 1.5rem; 
        margin-bottom: 2rem;
    }

    .stat-glass-card { 
        background: rgba(255, 255, 255, 0.8); 
        backdrop-filter: blur(12px); 
        border: 1px solid rgba(255, 255, 255, 0.3); 
        padding: 1.5rem; 
        border-radius: 20px; 
        display: flex; 
        gap: 1.25rem; 
        align-items: center;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
    }

    .stat-icon-bg { 
        width: 64px; 
        height: 64px; 
        border-radius: 16px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 2rem; 
    }

    .stat-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-value { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0.25rem 0; }
    .stat-sub { font-size: 0.75rem; color: #64748b; font-weight: 500; }

    .charts-grid { 
        display: grid; 
        grid-template-columns: 2fr 1fr; 
        gap: 1.5rem; 
    }

    .chart-card { 
        background: white; 
        padding: 1.5rem; 
        border-radius: 24px; 
        border: 1px solid #e2e8f0; 
    }

    .card-header h3 { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 0.25rem; }
    .card-header p { font-size: 0.85rem; color: #94a3b8; margin-bottom: 2rem; }

    .chart-wrapper { height: 350px; position: relative; }

    .premium-table { width: 100%; border-collapse: collapse; }
    .premium-table th { text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
    .premium-table td { padding: 1.25rem 1rem; border-bottom: 1px solid #f8fafc; font-size: 0.95rem; }
    
    .plate-badge { background: #f1f5f9; color: #1e293b; font-weight: 800; padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace; }
    .freq-count { font-weight: 700; color: #1e293b; }
    .badge-gold { background: #fefce8; color: #f59e0b; border: 1px solid #fef3c7; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; }
    
    .type-tag { font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; }
    .tag-frequent { background: #fee2e2; color: #741b1b; }
    .tag-standard { background: #f1f5f9; color: #64748b; }

    @media print {
        .analytics-container { background: white; }
        .no-print { display: none !important; }
        .glass { box-shadow: none !important; border: 1px solid #ddd !important; }
        .charts-grid { grid-template-columns: 1fr !important; }
    }
</style>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let trendChart, peakChart;

    function initCharts() {
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendGrad = trendCtx.createLinearGradient(0, 0, 0, 400);
        trendGrad.addColorStop(0, 'rgba(116, 27, 27, 0.15)');
        trendGrad.addColorStop(1, 'rgba(116, 27, 27, 0)');

        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'Visitor Entries',
                    data: @json($trendData),
                    borderColor: '#741b1b',
                    backgroundColor: trendGrad,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'white'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        backgroundColor: '#1e293b', 
                        padding: 12, 
                        titleFont: { size: 14, weight: '800' },
                        displayColors: false
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { weight: '600' } } },
                    x: { grid: { display: false }, ticks: { font: { weight: '600' } } }
                }
            }
        });

        // Peak Chart
        const peakCtx = document.getElementById('peakChart').getContext('2d');
        peakChart = new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: @json($peakHoursLabels),
                datasets: [{
                    label: 'Visitor Arrivals',
                    data: @json($peakHoursData),
                    backgroundColor: 'rgba(245, 158, 11, 0.6)',
                    borderColor: '#f59e0b',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function showCustomDates() {
        document.getElementById('custom-inputs').classList.toggle('hidden');
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        const customBtn = Array.from(document.querySelectorAll('.filter-btn')).find(b => b.innerText.includes('Custom'));
        if (customBtn) customBtn.classList.add('active');
    }

    async function applyCustomRange() {
        const start = document.getElementById('start-date').value;
        const end = document.getElementById('end-date').value;
        if (!start || !end) return;

        try {
            const response = await fetch(`{{ route('guard.visitor.analytics') }}?range=custom&start=${start}&end=${end}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            
            // Update Trend
            trendChart.data.labels = data.trendLabels;
            trendChart.data.datasets[0].data = data.trendData;
            trendChart.update();
        } catch (error) {
            console.error('Failed to update custom range:', error);
        }
    }

    async function updateRange(newRange) {
        // Hide custom inputs if visible
        document.getElementById('custom-inputs').classList.add('hidden');

        // Update Buttons
        const btns = document.querySelectorAll('.filter-btn');
        btns.forEach(btn => {
            btn.classList.toggle('active', btn.innerText.toLowerCase().includes(newRange.replace('30d','monthly').replace('7d','weekly').replace('daily','daily')));
        });
        
        try {
            const response = await fetch(`{{ route('guard.visitor.analytics') }}?range=${newRange}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            
            // Update Trend
            trendChart.data.labels = data.trendLabels;
            trendChart.data.datasets[0].data = data.trendData;
            trendChart.update();
        } catch (error) {
            console.error('Failed to update analytics range:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', initCharts);
</script>
@endsection
