<?php

namespace App\Console\Commands;

use App\Models\GoogleReview;
use App\Models\Store;
use App\Services\GeminiService;
use App\Services\GoogleBusinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoReplyGoogleReviews extends Command
{
    protected $signature = 'google:auto-reply {--store= : 特定の店舗IDのみ処理} {--dry-run : 実際に返信せず生成結果のみ表示}';
    protected $description = 'Google口コミを同期し、未返信の口コミにAI自動返信を行う';

    public function handle(GoogleBusinessService $google, GeminiService $gemini): int
    {
        if (!$google->isConnected()) {
            $this->error('Google連携が設定されていません。');
            Log::error('AutoReplyGoogleReviews: Google連携未設定');
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        $storeId = $this->option('store');

        // 対象店舗を取得
        $storesQuery = Store::whereNotNull('google_location_name')
            ->where('google_location_name', '!=', '')
            ->where('is_active', true);

        if ($storeId) {
            $storesQuery->where('id', $storeId);
        }

        $stores = $storesQuery->get();

        if ($stores->isEmpty()) {
            $this->warn('対象店舗が見つかりません。');
            return 0;
        }

        // ステップ1: 口コミ同期
        $this->info('=== 口コミ同期開始 ===');
        $totalSynced = 0;

        foreach ($stores as $store) {
            $synced = $google->syncReviews($store);
            $totalSynced += $synced;
            $this->line("  {$store->name}: {$synced}件同期");
        }

        $this->info("同期完了: {$totalSynced}件");

        // ステップ2: 未返信口コミに自動返信
        $this->info('=== 自動返信開始 ===');

        $unrepliedQuery = GoogleReview::with(['store', 'store.businessType'])
            ->whereNull('reply_comment')
            ->where('reviewed_at', '>=', now()->subYear())
            ->whereIn('store_id', $stores->pluck('id'));

        $unrepliedReviews = $unrepliedQuery->orderBy('reviewed_at', 'asc')->get();

        if ($unrepliedReviews->isEmpty()) {
            $this->info('未返信の口コミはありません。');
            Log::info('AutoReplyGoogleReviews: 未返信の口コミなし');
            return 0;
        }

        $this->info("未返信口コミ: {$unrepliedReviews->count()}件");

        $replied = 0;
        $failed = 0;

        foreach ($unrepliedReviews as $review) {
            $storeName = $review->store->name ?? '不明';
            $this->line("  処理中: [{$storeName}] {$review->reviewer_name} ★{$review->rating}");

            // AI で顧客タイプを判定
            $customerType = $gemini->detectCustomerType($review->comment ?? '');
            $this->line("    顧客タイプ判定: {$customerType}");

            // AI で返信文を生成（カテゴリ・キーワードなし）
            $replyText = $gemini->generateReplyComment(
                $review->store,
                $review->rating,
                $review->comment ?? '',
                '',   // category なし
                [],   // keywords なし
                $review->reviewer_name ?? '',
                $customerType
            );

            if (!$replyText) {
                $this->warn("    返信文の生成に失敗しました。スキップします。");
                Log::warning('AutoReplyGoogleReviews: 返信生成失敗', [
                    'review_id' => $review->id,
                    'store' => $storeName,
                ]);
                $failed++;
                continue;
            }

            if ($isDryRun) {
                $this->line("    [DRY-RUN] 生成された返信:");
                $this->line("    " . str_replace("\n", "\n    ", $replyText));
                $replied++;
                continue;
            }

            // Google API に返信を投稿
            $success = $google->replyToReview($review, $replyText);

            if ($success) {
                $this->info("    返信完了");
                $replied++;
            } else {
                $this->warn("    返信投稿に失敗しました。");
                Log::warning('AutoReplyGoogleReviews: 返信投稿失敗', [
                    'review_id' => $review->id,
                    'store' => $storeName,
                ]);
                $failed++;
            }

            // API レートリミット対策: 各返信間に少し待機
            usleep(500000); // 0.5秒
        }

        $this->info("=== 完了 ===");
        $this->info("返信成功: {$replied}件 / 失敗: {$failed}件");

        Log::info('AutoReplyGoogleReviews: 完了', [
            'synced' => $totalSynced,
            'replied' => $replied,
            'failed' => $failed,
            'dry_run' => $isDryRun,
        ]);

        return 0;
    }
}
