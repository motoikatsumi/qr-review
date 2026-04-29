<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\GoogleReview;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\Store;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    private const SETTING_KEY = 'onboarding_completed_at';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * ウィザード画面（5ステップを 1 つの画面にまとめてタブ切替で表示）
     */
    public function index(Request $request)
    {
        $step = (int) $request->input('step', $this->detectNextStep());
        $step = max(1, min(5, $step));

        $businessTypes = BusinessType::where('is_active', true)->orderBy('sort_order')->get();
        $stores = Store::orderBy('id')->get();
        $progress = $this->checkProgress();
        $completedAt = SiteSetting::get(self::SETTING_KEY);

        return view('admin.onboarding.index', compact(
            'step', 'businessTypes', 'stores', 'progress', 'completedAt'
        ));
    }

    /**
     * 「ウィザードを完了済みとしてマーク」アクション
     */
    public function complete(Request $request)
    {
        SiteSetting::set(self::SETTING_KEY, now()->toDateTimeString());
        return redirect('/admin/dashboard')->with('success', '🎉 初期セットアップが完了しました！運用を開始できます。');
    }

    /**
     * 「あとで」「もう表示しない」アクション
     */
    public function skip(Request $request)
    {
        SiteSetting::set(self::SETTING_KEY, 'skipped:' . now()->toDateTimeString());
        return redirect('/admin/dashboard')->with('success', 'セットアップをスキップしました。「設定 → 初期セットアップ」からいつでも再開できます。');
    }

    /**
     * 「もう一度ウィザードを表示」アクション（リセット）
     */
    public function reset()
    {
        SiteSetting::set(self::SETTING_KEY, '');
        return redirect('/admin/onboarding');
    }

    /**
     * 完了済みかどうかを判定（dashboard やレイアウトから呼ぶためのヘルパ）
     */
    public static function isCompleted(): bool
    {
        $val = SiteSetting::get(self::SETTING_KEY);
        return !empty($val); // skipped: も含めて完了扱い
    }

    /**
     * 各ステップの進捗を判定
     */
    private function checkProgress(): array
    {
        $hasBusinessType = BusinessType::where('is_active', true)->exists();
        $hasStore = Store::exists();
        $firstStore = Store::first();

        $hasIntegration = false;
        $hasAiSettings = false;
        $hasReview = false;

        if ($firstStore) {
            $hasIntegration = $firstStore->integrations()->exists()
                || !empty($firstStore->google_review_url);
            $hasAiSettings = !empty($firstStore->ai_store_description)
                || !empty($firstStore->ai_custom_instruction)
                || !empty($firstStore->ai_service_keywords);
            $hasReview = Review::where('store_id', $firstStore->id)->exists()
                || GoogleReview::where('store_id', $firstStore->id)->exists();
        }

        return [
            1 => $hasBusinessType,
            2 => $hasStore,
            3 => $hasIntegration,
            4 => $hasAiSettings,
            5 => $hasReview,
        ];
    }

    /**
     * 次に進むべきステップを自動判定
     */
    private function detectNextStep(): int
    {
        $progress = $this->checkProgress();
        for ($i = 1; $i <= 5; $i++) {
            if (!$progress[$i]) return $i;
        }
        return 5;
    }
}
