<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTenants  = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $stoppedTenants = $totalTenants - $activeTenants;

        // 契約終了が30日以内のテナント
        $expiringSoon = Tenant::where('is_active', true)
            ->whereNotNull('contract_end')
            ->whereBetween('contract_end', [now(), now()->addDays(30)])
            ->orderBy('contract_end')
            ->get();

        // プラン別集計
        $planCounts = Tenant::where('is_active', true)
            ->selectRaw('plan, COUNT(*) as cnt')
            ->groupBy('plan')
            ->pluck('cnt', 'plan')
            ->toArray();

        // 各テナントのAI利用状況（上位利用者）
        $tenants = Tenant::where('is_active', true)->get();
        $totalAiUsage = 0;
        $totalStores = 0;
        $aiRankings = [];

        foreach ($tenants as $tenant) {
            try {
                config(['database.connections.tenant_check.driver' => 'mysql']);
                config(['database.connections.tenant_check.host' => config('database.connections.mysql.host')]);
                config(['database.connections.tenant_check.port' => config('database.connections.mysql.port')]);
                config(['database.connections.tenant_check.database' => $tenant->db_name]);
                config(['database.connections.tenant_check.username' => $tenant->db_username ?: config('database.connections.mysql.username')]);
                config(['database.connections.tenant_check.password' => $tenant->db_password ?: config('database.connections.mysql.password')]);

                $count = DB::connection('tenant_check')
                    ->table('ai_usage_logs')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();

                $storeCount = DB::connection('tenant_check')
                    ->table('stores')
                    ->count();

                DB::purge('tenant_check');

                $totalAiUsage += $count;
                $totalStores += $storeCount;
                $aiRankings[] = [
                    'tenant' => $tenant,
                    'used'   => $count,
                    'limit'  => $tenant->ai_monthly_limit,
                    'pct'    => $tenant->ai_monthly_limit > 0 ? round($count / $tenant->ai_monthly_limit * 100) : 0,
                ];
            } catch (\Exception $e) {
                // DB接続失敗は無視
            }
        }

        // 利用率でソート（降順）
        usort($aiRankings, fn ($a, $b) => $b['pct'] <=> $a['pct']);

        return view('super-admin.dashboard', compact(
            'totalTenants', 'activeTenants', 'stoppedTenants',
            'expiringSoon', 'planCounts', 'totalAiUsage', 'totalStores', 'aiRankings'
        ));
    }
}
