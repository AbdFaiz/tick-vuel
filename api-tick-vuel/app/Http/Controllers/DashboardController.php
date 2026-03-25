<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $currentMonth = Carbon::now()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $query = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth]);
        if ($user->role == 'user') {
            $query->where('user_id', $user->id);
        }

        $totalTickets = (clone $query)->count();

        $activeTickets = (clone $query)->where('status', '!=', 'resolved')
                    ->count();

        $resolvedTickets = (clone $query)->where('status', 'resolved')
                    ->count();

        $avgResolutionTime = (clone $query)->where('status', 'resolved')
                    ->whereNotNull('completed_at')
                    ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_time'))
                    ->value('avg_time') ?? 0;

        $statusDistribution = [
            'open' => (clone $query)->where('status', 'open')->count(),
            'onprogress' => (clone $query)->where('status', 'onprogress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $dashboardData = [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTickets,
            'avg_resolution_time' => round($avgResolutionTime / 60, 1),
            'status_distribution' => $statusDistribution
        ];

        return response()->json([
            'message' => 'Dashboard statistic fetched successfully',
            'data' => new DashboardResource($dashboardData)
        ], 201);
    }
}
