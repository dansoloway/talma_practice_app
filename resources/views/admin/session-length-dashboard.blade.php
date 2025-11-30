@extends('layouts.admin')

@section('title', 'Session Length Analytics')

@section('content')
<div class="container">
    <h1 class="page-title">Average Session Length Dashboard</h1>

    <!-- Filters -->
    <div class="dashboard-section" style="margin-bottom: 2rem;">
        <h2>Filters</h2>
        <form method="GET" action="{{ route('admin.session-length') }}" class="filters-form">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="city">City</label>
                    <select name="city" id="city" class="form-control">
                        <option value="">All Cities</option>
                        @foreach($cities as $cityOption)
                            <option value="{{ $cityOption }}" {{ $city === $cityOption ? 'selected' : '' }}>
                                {{ $cityOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}">
                </div>
                <div class="filter-group">
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}">
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    @if($city || $startDate || $endDate)
                        <a href="{{ route('admin.session-length') }}" class="btn" style="margin-left: 0.5rem;">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Main Metric Display -->
    <div class="dashboard-section highlight-metric">
        <div class="metric-display">
            <div class="metric-label">Average Session Length</div>
            <div class="metric-value">{{ $formatDuration((int) round($averageSessionLength)) }}</div>
            <div class="metric-details">
                <div class="metric-detail-item">
                    <span class="detail-label">Total Sessions:</span>
                    <span class="detail-value">{{ number_format($sessionCount) }}</span>
                </div>
                <div class="metric-detail-item">
                    <span class="detail-label">Total Time Spent:</span>
                    <span class="detail-value">{{ $formatDuration($totalTimeSeconds) }}</span>
                </div>
                @if($city)
                    <div class="metric-detail-item">
                        <span class="detail-label">Filtered by:</span>
                        <span class="detail-value">{{ $city }}</span>
                    </div>
                @endif
                @if($startDate || $endDate)
                    <div class="metric-detail-item">
                        <span class="detail-label">Date Range:</span>
                        <span class="detail-value">
                            {{ $startDate ? $startDate->format('M j, Y') : 'Start' }} - 
                            {{ $endDate ? $endDate->format('M j, Y') : 'End' }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($sessionCount === 0)
        <div class="dashboard-section">
            <p class="empty-text">No session data found for the selected filters.</p>
        </div>
    @endif
</div>

@push('styles')
<style>
.filters-form {
    margin-top: 1rem;
}
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    align-items: end;
}
.filter-group {
    display: flex;
    flex-direction: column;
}
.filter-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-text-light);
    font-size: 0.9rem;
}
.form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    background: #fff;
}
.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.highlight-metric {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
}
.metric-display {
    text-align: center;
}
.metric-label {
    font-size: 1.1rem;
    font-weight: 600;
    opacity: 0.9;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.metric-value {
    font-size: 4rem;
    font-weight: 700;
    margin-bottom: 2rem;
    line-height: 1;
}
.metric-details {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}
.metric-detail-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}
.detail-label {
    font-size: 0.85rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.detail-value {
    font-size: 1.25rem;
    font-weight: 600;
}
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.btn-primary {
    background: var(--color-primary);
    color: white;
}
.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.btn:not(.btn-primary) {
    background: #f3f4f6;
    color: var(--color-text);
}
.btn:not(.btn-primary):hover {
    background: #e5e7eb;
}
@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    .metric-value {
        font-size: 2.5rem;
    }
    .metric-details {
        flex-direction: column;
        gap: 1.5rem;
    }
}
</style>
@endpush
@endsection

