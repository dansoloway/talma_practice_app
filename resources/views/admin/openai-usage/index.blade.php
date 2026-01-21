@extends('layouts.admin')

@section('title', 'OpenAI API Usage & Cost')

@section('content')
<div class="container">
    <div class="page-header">
        <h1 class="page-title">OpenAI API Usage & Cost</h1>
    </div>

    @if(isset($error))
        <div class="alert alert-error">
            {{ $error }}
        </div>
    @endif

    <!-- Cost Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; opacity: 0.9;">Total Cost</h3>
                <div style="font-size: 2.5rem; font-weight: 700; margin: 0;">
                    ${{ number_format($stats['total_cost'], 4) }}
                </div>
                <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.5rem;">
                    {{ $stats['total_requests'] }} request{{ $stats['total_requests'] !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <div class="card-body">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; opacity: 0.9;">Avg Cost/Request</h3>
                <div style="font-size: 2rem; font-weight: 700; margin: 0;">
                    ${{ number_format($stats['average_cost_per_request'], 6) }}
                </div>
                <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.5rem;">
                    Per API call
                </div>
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <div class="card-body">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; opacity: 0.9;">Total Tokens</h3>
                <div style="font-size: 2rem; font-weight: 700; margin: 0;">
                    {{ number_format($stats['total_tokens']) }}
                </div>
                <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.5rem;">
                    {{ number_format($stats['total_prompt_tokens']) }} prompt + {{ number_format($stats['total_completion_tokens']) }} completion
                </div>
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
            <div class="card-body">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; opacity: 0.9;">Avg Tokens/Request</h3>
                <div style="font-size: 2rem; font-weight: 700; margin: 0;">
                    {{ number_format($stats['average_tokens_per_request']) }}
                </div>
                <div style="font-size: 0.875rem; opacity: 0.8; margin-top: 0.5rem;">
                    Per API call
                </div>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.openai-usage') }}" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}" class="form-control">
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo ?? '' }}" class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.openai-usage') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cost Breakdown by Model -->
    @if(!empty($stats['cost_by_model']))
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">Cost Breakdown by Model</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; gap: 1rem;">
                @foreach($stats['cost_by_model'] as $model => $cost)
                    @php
                        $percentage = $stats['total_cost'] > 0 ? ($cost / $stats['total_cost']) * 100 : 0;
                        $requests = $stats['requests_by_model'][$model] ?? 0;
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div>
                                <strong>{{ $model }}</strong>
                                <span style="color: var(--color-text-muted); font-size: 0.875rem; margin-left: 0.5rem;">
                                    ({{ $requests }} request{{ $requests !== 1 ? 's' : '' }})
                                </span>
                            </div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-primary);">
                                ${{ number_format($cost, 4) }}
                            </div>
                        </div>
                        <div style="background: var(--color-gray-100); border-radius: var(--radius-sm); height: 8px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, var(--color-primary), var(--color-info)); height: 100%; width: {{ $percentage }}%; transition: width 0.3s;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;">
                            {{ number_format($percentage, 1) }}% of total cost
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Cost Breakdown by Operation -->
    @if(!empty($stats['cost_by_operation']))
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">Cost Breakdown by Operation</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; gap: 1rem;">
                @foreach($stats['cost_by_operation'] as $operation => $cost)
                    @php
                        $percentage = $stats['total_cost'] > 0 ? ($cost / $stats['total_cost']) * 100 : 0;
                        $requests = $stats['requests_by_operation'][$operation] ?? 0;
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div>
                                <strong style="text-transform: capitalize;">{{ str_replace('_', ' ', $operation) }}</strong>
                                <span style="color: var(--color-text-muted); font-size: 0.875rem; margin-left: 0.5rem;">
                                    ({{ $requests }} request{{ $requests !== 1 ? 's' : '' }})
                                </span>
                            </div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--color-primary);">
                                ${{ number_format($cost, 4) }}
                            </div>
                        </div>
                        <div style="background: var(--color-gray-100); border-radius: var(--radius-sm); height: 8px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, var(--color-success), var(--color-info)); height: 100%; width: {{ $percentage }}%; transition: width 0.3s;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;">
                            {{ number_format($percentage, 1) }}% of total cost
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Requests -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent API Requests</h2>
        </div>
        <div class="card-body">
            @if(empty($logs))
                <div class="empty-state" style="padding: 2rem; text-align: center;">
                    <p>No API requests found in the selected date range.</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Operation</th>
                                <th>Model</th>
                                <th>Tokens</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($logs, 0, 100) as $log)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($log['timestamp'])->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <span style="text-transform: capitalize;">
                                            {{ str_replace('_', ' ', $log['operation'] ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>
                                        <code style="background: var(--color-gray-50); padding: 0.25rem 0.5rem; border-radius: var(--radius-sm);">
                                            {{ $log['model'] ?? 'unknown' }}
                                        </code>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;">
                                            <strong>{{ number_format($log['total_tokens'] ?? 0) }}</strong>
                                            <div style="color: var(--color-text-muted); font-size: 0.75rem;">
                                                {{ number_format($log['prompt_tokens'] ?? 0) }} + {{ number_format($log['completion_tokens'] ?? 0) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--color-primary);">
                                            ${{ number_format($log['cost_usd'] ?? 0, 6) }}
                                        </strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($logs) > 100)
                    <div style="margin-top: 1rem; text-align: center; color: var(--color-text-muted);">
                        Showing first 100 of {{ count($logs) }} requests
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
