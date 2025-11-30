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

    <!-- Daily Breakdown -->
    @if(!empty($dailyBreakdown))
        <div class="dashboard-section">
            <h2>Daily Breakdown</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Sessions</th>
                        <th class="text-right">Total Time</th>
                        <th class="text-right">Avg Session Length</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyBreakdown as $day)
                        <tr class="clickable-row" data-date="{{ $day['date'] }}" data-city="{{ $city ?? '' }}" style="cursor: pointer;">
                            <td>{{ $day['date_formatted'] }}</td>
                            <td class="text-right">{{ number_format($day['sessions']) }}</td>
                            <td class="text-right">{{ $formatDuration($day['total_time_seconds']) }}</td>
                            <td class="text-right">
                                @if($day['sessions'] > 0)
                                    {{ $formatDuration((int) round($day['average_session_length'])) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Activity Breakdown Modal -->
    <div id="activityModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Activity Breakdown</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modalLoading" style="text-align: center; padding: 2rem;">
                    <p>Loading...</p>
                </div>
                <div id="modalContent" style="display: none;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th class="text-right">Students Started</th>
                                <th class="text-right">Students Finished</th>
                                <th class="text-right">Total Time</th>
                                <th class="text-right">Avg Time</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                        </tbody>
                    </table>
                </div>
                <div id="modalError" style="display: none; text-align: center; padding: 2rem; color: #dc2626;">
                    <p>Error loading activity breakdown.</p>
                </div>
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
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
.table thead {
    background: #f3f4f6;
}
.table th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--color-text-light);
    border-bottom: 2px solid #e5e7eb;
}
.table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.table tbody tr:hover {
    background: #f9fafb;
}
.text-right {
    text-align: right;
}
.empty-text {
    color: var(--color-text-muted);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}
.clickable-row:hover {
    background: #f0f9ff !important;
}
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}
.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 900px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}
.modal-close {
    font-size: 2rem;
    font-weight: 300;
    color: #6b7280;
    cursor: pointer;
    line-height: 1;
}
.modal-close:hover {
    color: #111827;
}
.modal-body {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
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
    .table {
        font-size: 0.9rem;
    }
    .table th,
    .table td {
        padding: 0.5rem;
    }
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    .modal-header,
    .modal-body {
        padding: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('activityModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalLoading = document.getElementById('modalLoading');
    const modalContent = document.getElementById('modalContent');
    const modalError = document.getElementById('modalError');
    const modalTableBody = document.getElementById('modalTableBody');
    const closeBtn = document.querySelector('.modal-close');
    const clickableRows = document.querySelectorAll('.clickable-row');
    
    // Format duration helper (matches PHP function)
    function formatDuration(seconds) {
        if (!seconds || seconds <= 0) {
            return '—';
        }
        const minutes = Math.floor(seconds / 60);
        const remaining = seconds % 60;
        if (minutes === 0) {
            return remaining + 's';
        }
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const parts = [];
        if (hours > 0) {
            parts.push(hours + 'h');
        }
        if (mins > 0) {
            parts.push(mins + 'm');
        }
        if (remaining > 0 && hours === 0) {
            parts.push(remaining + 's');
        }
        return parts.join(' ');
    }
    
    // Open modal and load data
    clickableRows.forEach(row => {
        row.addEventListener('click', function() {
            const date = this.dataset.date;
            const city = this.dataset.city || '';
            
            // Show modal
            modal.style.display = 'block';
            modalTitle.textContent = 'Activity Breakdown - ' + this.cells[0].textContent.trim();
            modalLoading.style.display = 'block';
            modalContent.style.display = 'none';
            modalError.style.display = 'none';
            
            // Fetch data
            const url = new URL('{{ route("admin.session-length.day-breakdown") }}', window.location.origin);
            url.searchParams.append('date', date);
            if (city) {
                url.searchParams.append('city', city);
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    modalLoading.style.display = 'none';
                    
                    if (data.error) {
                        modalError.style.display = 'block';
                        return;
                    }
                    
                    // Populate table
                    modalTableBody.innerHTML = '';
                    
                    if (data.breakdown && data.breakdown.length > 0) {
                        data.breakdown.forEach(activity => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><strong>${activity.activity_name}</strong></td>
                                <td class="text-right">${activity.total_students_started.toLocaleString()}</td>
                                <td class="text-right">${activity.total_students_finished.toLocaleString()}</td>
                                <td class="text-right">${activity.total_time_formatted || formatDuration(Math.round(activity.total_time_seconds))}</td>
                                <td class="text-right">${activity.avg_time_formatted || formatDuration(Math.round(activity.avg_time_seconds))}</td>
                            `;
                            modalTableBody.appendChild(row);
                        });
                    } else {
                        modalTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">No activity data for this day.</td></tr>';
                    }
                    
                    modalContent.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalLoading.style.display = 'none';
                    modalError.style.display = 'block';
                });
        });
    });
    
    // Close modal
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>
@endpush
@endsection

