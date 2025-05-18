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

        // Count declarations by status
        $declarationStats = [
            'total' => Declaration::where('employer_id', $employer->id)->count(),
            'pending' => Declaration::where('employer_id', $employer->id)
                ->where('status', Declaration::STATUS_PENDING)
                ->count(),
            'approved' => Declaration::where('employer_id', $employer->id)
                ->where('status', Declaration::STATUS_APPROVED)
                ->count(),
            'rejected' => Declaration::where('employer_id', $employer->id)
                ->where('status', Declaration::STATUS_REJECTED)
                ->count(),
            'recent' => Declaration::where('employer_id', $employer->id)
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

        // Equipment statistics
        $equipmentStats = [
            'total' => Equipment::where('employer_id', $employer->id)->count(),
            'active' => Equipment::where('employer_id', $employer->id)
                ->where('status', 'active')
                ->count(),
            'on_hold' => Equipment::where('employer_id', $employer->id)
                ->where('status', 'on_hold')
                ->count(),
            'in_progress' => Equipment::where('employer_id', $employer->id)
                ->where('status', 'in_progress')
                ->count(),
            'recent' => Equipment::where('employer_id', $employer->id)
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

        // Intervention statistics for employer's equipment
        $equipmentIds = Equipment::where('employer_id', $employer->id)->pluck('id')->toArray();
        $interventionStats = [
            'total' => Intervention::whereIn('equipment_id', $equipmentIds)->count(),
            'recent' => Intervention::whereIn('equipment_id', $equipmentIds)
                ->with('equipment')
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

        return response()->json([
            'declarations' => $declarationStats,
            'equipment' => $equipmentStats,
            'interventions' => $interventionStats,
        ]);
    }

    /**
     * Get statistics for admin dashboard.
     *
     * @return JsonResponse
     */
    public function getAdminStatistics(): JsonResponse
    {
        // Count total users by role
        $userStats = [
            'total' => User::count(),
            'admins' => User::where('role', 'Admin')->count(),
            'employers' => User::where('role', 'Employer')->count(),
            'active_employers' => Employer::where('is_active', true)->count(),
            'inactive_employers' => Employer::where('is_active', false)->count(),
            'recent_users' => User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
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

        // Equipment statistics
        $equipmentStats = [
            'total' => Equipment::count(),
            'active' => Equipment::where('status', 'active')->count(),
            'on_hold' => Equipment::where('status', 'on_hold')->count(),
            'in_progress' => Equipment::where('status', 'in_progress')->count(),
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
            'backup_enabled_count' => Equipment::where('backup_enabled', true)->count(),
            'backup_disabled_count' => Equipment::where('backup_enabled', false)->count(),
            'recent' => Equipment::with('employer.user')
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

        // Declaration statistics
        $declarationStats = [
            'total' => Declaration::count(),
            'pending' => Declaration::where('status', Declaration::STATUS_PENDING)->count(),
            'approved' => Declaration::where('status', Declaration::STATUS_APPROVED)->count(),
            'rejected' => Declaration::where('status', Declaration::STATUS_REJECTED)->count(),
            'recent' => Declaration::with('employer.user')
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
            'by_month' => Intervention::select(
                DB::raw('MONTH(date) as month'),
                DB::raw('YEAR(date) as year'),
                DB::raw('count(*) as count')
            )
                ->whereYear('date', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'year' => $item->year,
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
            'declarations_by_month' => Declaration::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('count(*) as count')
            )
                ->whereYear('created_at', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'year' => $item->year,
                        'count' => $item->count,
                    ];
                }),
            'equipment_by_month' => Equipment::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('count(*) as count')
            )
                ->whereYear('created_at', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'year' => $item->year,
                        'count' => $item->count,
                    ];
                }),
            'users_by_month' => User::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('count(*) as count')
            )
                ->whereYear('created_at', date('Y'))
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'year' => $item->year,
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
    }
}
