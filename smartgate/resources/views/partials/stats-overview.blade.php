<div class="stats-overview-grid no-print" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    @foreach($stats as $stat)
    <div class="stat-card-premium" id="{{ $stat['id'] ?? '' }}">
        <div class="stat-card-content">
            <div class="stat-card-label">{{ $stat['label'] }}</div>
            <div class="stat-card-value">{{ $stat['value'] }}</div>
        </div>
        <div class="stat-card-icon">
            <i class="{{ $stat['icon'] }}"></i>
        </div>
    </div>
    @endforeach
</div>

<style>
    .stat-card-premium {
        background: white;
        padding: 1.5rem;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
    }
    .stat-card-label {
        font-size: 0.7rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }
    .stat-card-value {
        font-size: 1.5rem;
        font-weight: 900;
        color: #1e293b;
    }
    .stat-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #fff5f5;
        color: #741b1b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>
