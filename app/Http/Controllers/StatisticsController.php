<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use App\Models\Employer;
use App\Models\Equipment;
use App\Models\Intervention;
use App\Models\License;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * Get statistics for employer dashboard.
     *
     * @return JsonResponse
     */
    public function getEmployerStatistics(): JsonResponse
    {
        $user = auth()->user();
        $employer = $user->employer;

        // Cache the statistics for 5 minutes to improve performance
        // Use a cache key that includes the employer ID to ensure each employer gets their own stats
        return Cache::remember('employer_statistics_' . $employer->id, 300, function () use ($employer) {
            // Optimize declaration statistics with fewer queries
            // Get declaration status counts in a single query for this employer
            $declarationStatusCounts = Declaration::select('status', DB::raw('count(*) as count'))
                ->where('employer_id', $employer->id)
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $declarationStats = [
                'total' => array_sum($declarationStatusCounts),
                'pending' => $declarationStatusCounts[Declaration::STATUS_PENDING] ?? 0,
                'approved' => $declarationStatusCounts[Declaration::STATUS_APPROVED] ?? 0,
                'rejected' => $declarationStatusCounts[Declaration::STATUS_REJECTED] ?? 0,
                'recent' => Declaration::where('employer_id', $employer->id)
                    ->select('id', 'issue_title', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($declaration) {
                        return [
                            'id' => $declaration->id,
                            'issue_title' => $declaration->issue_title,
                            'status' => $declaration->status,
                            'created_at' => $declaration->created_at,
                        ];
                    }),
            ];

            // Optimize equipment statistics with fewer queries
            // Get equipment status counts in a single query for this employer
            $equipmentStatusCounts = Equipment::select('status', DB::raw('count(*) as count'))
                ->where('employer_id', $employer->id)
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $equipmentStats = [
                'total' => array_sum($equipmentStatusCounts),
                'active' => $equipmentStatusCounts['active'] ?? 0,
                'on_hold' => $equipmentStatusCounts['on_hold'] ?? 0,
                'in_progress' => $equipmentStatusCounts['in_progress'] ?? 0,
                'recent' => Equipment::where('employer_id', $employer->id)
                    ->select('id', 'name', 'type', 'status')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($equipment) {
                        return [
                            'id' => $equipment->id,
                            'name' => $equipment->name,
                            'type' => $equipment->type,
                            'status' => $equipment->status,
                        ];
                    }),
            ];

            // Optimize intervention statistics with fewer queries
            // Get equipment IDs in a single query
            $equipmentIds = Equipment::where('employer_id', $employer->id)->pluck('id')->toArray();

            // Only proceed with intervention queries if there are equipment IDs
            if (!empty($equipmentIds)) {
                $interventionStats = [
                    'total' => Intervention::whereIn('equipment_id', $equipmentIds)->count(),
                    'recent' => Intervention::whereIn('equipment_id', $equipmentIds)
                        ->with(['equipment' => function ($query) {
                            $query->select('id', 'name');
                        }])
                        ->select('id', 'date', 'technician_name', 'equipment_id')
                        ->orderBy('date', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function ($intervention) {
                            return [
                                'id' => $intervention->id,
                                'date' => $intervention->date,
                                'technician_name' => $intervention->technician_name,
                                'equipment_name' => $intervention->equipment->name,
                            ];
                        }),
                ];
            } else {
                // If no equipment, return empty stats
                $interventionStats = [
                    'total' => 0,
                    'recent' => [],
                ];
            }

            return response()->json([
                'declarations' => $declarationStats,
                'equipment' => $equipmentStats,
                'interventions' => $interventionStats,
            ]);
        });
    }

    /**
     * Get statistics for admin dashboard.
     *
     * @return JsonResponse
     */
    public function getAdminStatistics(): JsonResponse
    {
        // Cache the statistics for 5 minutes to improve performance
        return Cache::remember('admin_statistics', 300, function () {
            // Optimize user statistics with a single query for role counts
            $userRoleCounts = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            // Get employer active status counts in a single query
            $employerStatusCounts = Employer::select('is_active', DB::raw('count(*) as count'))
                ->groupBy('is_active')
                ->pluck('count', 'is_active')
                ->toArray();

            $userStats = [
                'total' => array_sum($userRoleCounts),
                'admins' => $userRoleCounts['Admin'] ?? 0,
                'employers' => $userRoleCounts['Employer'] ?? 0,
                'active_employers' => $employerStatusCounts[1] ?? 0,
                'inactive_employers' => $employerStatusCounts[0] ?? 0,
                'recent_users' => User::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'full_name', 'email', 'role', 'created_at'])
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'full_name' => $user->full_name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'created_at' => $user->created_at,
                        ];
                    }),
                'employers_by_service' => DB::table('employers')
                    ->join('services', 'employers.service_id', '=', 'services.id')
                    ->select('services.name', DB::raw('count(*) as count'))
                    ->groupBy('services.name')
                    ->get()
                    ->pluck('count', 'name')
                    ->toArray(),
            ];

            // Optimize equipment statistics with fewer queries
            // Get equipment status counts in a single query
            $equipmentStatusCounts = Equipment::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Get backup enabled counts in a single query
            $backupEnabledCounts = Equipment::select('backup_enabled', DB::raw('count(*) as count'))
                ->groupBy('backup_enabled')
                ->pluck('count', 'backup_enabled')
                ->toArray();

            $equipmentStats = [
                'total' => array_sum($equipmentStatusCounts),
                'active' => $equipmentStatusCounts['active'] ?? 0,
                'on_hold' => $equipmentStatusCounts['on_hold'] ?? 0,
                'in_progress' => $equipmentStatusCounts['in_progress'] ?? 0,
                'by_type' => Equipment::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_brand' => Equipment::select('brand', DB::raw('count(*) as count'))
                    ->groupBy('brand')
                    ->get()
                    ->pluck('count', 'brand')
                    ->toArray(),
                'backup_enabled_count' => $backupEnabledCounts[1] ?? 0,
                'backup_disabled_count' => $backupEnabledCounts[0] ?? 0,
                'recent' => Equipment::with(['employer.user' => function ($query) {
                        $query->select('id', 'full_name');
                    }])
                    ->select('id', 'name', 'type', 'status', 'employer_id', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($equipment) {
                        return [
                            'id' => $equipment->id,
                            'name' => $equipment->name,
                            'type' => $equipment->type,
                            'status' => $equipment->status,
                            'employer_name' => $equipment->employer->user->full_name,
                        ];
                    }),
            ];

        // Service statistics
        $serviceStats = [
            'total' => Service::count(),
            'with_employers' => Service::has('employers')->count(),
            'without_employers' => Service::doesntHave('employers')->count(),
            'employers_distribution' => Service::withCount('employers')
                ->orderBy('employers_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'employers_count' => $service->employers_count,
                    ];
                }),
        ];

            // Optimize declaration statistics with fewer queries
            // Get declaration status counts in a single query
            $declarationStatusCounts = Declaration::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $declarationStats = [
                'total' => array_sum($declarationStatusCounts),
                'pending' => $declarationStatusCounts[Declaration::STATUS_PENDING] ?? 0,
                'approved' => $declarationStatusCounts[Declaration::STATUS_APPROVED] ?? 0,
                'rejected' => $declarationStatusCounts[Declaration::STATUS_REJECTED] ?? 0,
                'recent' => Declaration::with(['employer.user' => function ($query) {
                        $query->select('id', 'full_name');
                    }])
                    ->select('id', 'issue_title', 'status', 'employer_id', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($declaration) {
                        return [
                            'id' => $declaration->id,
                            'issue_title' => $declaration->issue_title,
                            'status' => $declaration->status,
                            'employer_name' => $declaration->employer->user->full_name,
                            'created_at' => $declaration->created_at,
                        ];
                    }),
            ];

        // Intervention statistics
        $interventionStats = [
            'total' => Intervention::count(),
            'recent' => Intervention::with('equipment')
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($intervention) {
                    return [
                        'id' => $intervention->id,
                        'date' => $intervention->date,
                        'technician_name' => $intervention->technician_name,
                        'equipment_name' => $intervention->equipment->name,
                    ];
                }),
            'by_month' => Intervention::selectRaw("MONTH(date) as month, YEAR(date) as year, count(*) as count")
                ->whereRaw("YEAR(date) = ?", [date('Y')])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => (int)$item->month,
                        'year' => (int)$item->year,
                        'count' => $item->count,
                    ];
                }),
        ];

        // License statistics
        $licenseStats = [
            'total' => License::count(),
            'expiring_soon' => License::where('expiration_date', '<=', now()->addMonths(1))
                ->where('expiration_date', '>=', now())
                ->count(),
            'expired' => License::where('expiration_date', '<', now())->count(),
            'by_type' => License::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray(),
        ];

        // Time-based statistics
        $timeStats = [
            'declarations_by_month' => Declaration::selectRaw("MONTH(created_at) as month, YEAR(created_at) as year, count(*) as count")
                ->whereRaw("YEAR(created_at) = ?", [date('Y')])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => (int)$item->month,
                        'year' => (int)$item->year,
                        'count' => $item->count,
                    ];
                }),
            'equipment_by_month' => Equipment::selectRaw("MONTH(created_at) as month, YEAR(created_at) as year, count(*) as count")
                ->whereRaw("YEAR(created_at) = ?", [date('Y')])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => (int)$item->month,
                        'year' => (int)$item->year,
                        'count' => $item->count,
                    ];
                }),
            'users_by_month' => User::selectRaw("MONTH(created_at) as month, YEAR(created_at) as year, count(*) as count")
                ->whereRaw("YEAR(created_at) = ?", [date('Y')])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => (int)$item->month,
                        'year' => (int)$item->year,
                        'count' => $item->count,
                    ];
                }),
        ];

            return response()->json([
                'users' => $userStats,
                'equipment' => $equipmentStats,
                'services' => $serviceStats,
                'declarations' => $declarationStats,
                'interventions' => $interventionStats,
                'licenses' => $licenseStats,
                'time_stats' => $timeStats,
            ]);
        });
    }
}
