<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class OpenAiUsageController extends Controller
{
    /**
     * Display OpenAI API usage logs with cost breakdown.
     */
    public function index(Request $request)
    {
        $logFile = storage_path('logs/openai_usage.log');
        
        if (!File::exists($logFile)) {
            return view('admin.openai-usage.index', [
                'logs' => [],
                'stats' => $this->getEmptyStats(),
                'error' => 'Log file not found. No API usage has been recorded yet.',
            ]);
        }

        // Read all log entries
        $lines = File::lines($logFile);
        $logs = [];
        
        foreach ($lines as $line) {
            $data = json_decode(trim($line), true);
            if ($data && isset($data['timestamp'])) {
                $logs[] = $data;
            }
        }

        // Reverse to show most recent first
        $logs = array_reverse($logs);

        // Calculate statistics
        $stats = $this->calculateStats($logs);

        // Filter by date range if provided
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        if ($dateFrom || $dateTo) {
            $logs = array_filter($logs, function($log) use ($dateFrom, $dateTo) {
                $logDate = date('Y-m-d', strtotime($log['timestamp']));
                if ($dateFrom && $logDate < $dateFrom) {
                    return false;
                }
                if ($dateTo && $logDate > $dateTo) {
                    return false;
                }
                return true;
            });
            
            // Recalculate stats for filtered data
            $stats = $this->calculateStats(array_values($logs));
        }

        return view('admin.openai-usage.index', [
            'logs' => array_values($logs),
            'stats' => $stats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Calculate statistics from logs.
     */
    protected function calculateStats(array $logs): array
    {
        $totalCost = 0;
        $totalRequests = count($logs);
        $totalTokens = 0;
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        
        $costByModel = [];
        $costByOperation = [];
        $requestsByModel = [];
        $requestsByOperation = [];
        
        foreach ($logs as $log) {
            $cost = $log['cost_usd'] ?? 0;
            $totalCost += $cost;
            
            $totalTokens += $log['total_tokens'] ?? 0;
            $totalPromptTokens += $log['prompt_tokens'] ?? 0;
            $totalCompletionTokens += $log['completion_tokens'] ?? 0;
            
            $model = $log['model'] ?? 'unknown';
            $operation = $log['operation'] ?? 'unknown';
            
            $costByModel[$model] = ($costByModel[$model] ?? 0) + $cost;
            $costByOperation[$operation] = ($costByOperation[$operation] ?? 0) + $cost;
            $requestsByModel[$model] = ($requestsByModel[$model] ?? 0) + 1;
            $requestsByOperation[$operation] = ($requestsByOperation[$operation] ?? 0) + 1;
        }

        // Sort by cost descending
        arsort($costByModel);
        arsort($costByOperation);

        return [
            'total_cost' => round($totalCost, 6),
            'total_requests' => $totalRequests,
            'total_tokens' => $totalTokens,
            'total_prompt_tokens' => $totalPromptTokens,
            'total_completion_tokens' => $totalCompletionTokens,
            'average_cost_per_request' => $totalRequests > 0 ? round($totalCost / $totalRequests, 6) : 0,
            'average_tokens_per_request' => $totalRequests > 0 ? round($totalTokens / $totalRequests, 0) : 0,
            'cost_by_model' => $costByModel,
            'cost_by_operation' => $costByOperation,
            'requests_by_model' => $requestsByModel,
            'requests_by_operation' => $requestsByOperation,
        ];
    }

    /**
     * Get empty stats structure.
     */
    protected function getEmptyStats(): array
    {
        return [
            'total_cost' => 0,
            'total_requests' => 0,
            'total_tokens' => 0,
            'total_prompt_tokens' => 0,
            'total_completion_tokens' => 0,
            'average_cost_per_request' => 0,
            'average_tokens_per_request' => 0,
            'cost_by_model' => [],
            'cost_by_operation' => [],
            'requests_by_model' => [],
            'requests_by_operation' => [],
        ];
    }
}
