@extends('layouts.admin')

@section('title', '初期セットアップ')

@push('styles')
<style>
    .wizard-container { max-width: 880px; margin: 0 auto; }
    .wizard-header {
        text-align: center;
        padding: 30px 20px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 14px 14px 0 0;
        margin-bottom: 0;
    }
    .wizard-header h1 { margin: 0 0 8px; font-size: 1.6rem; }
    .wizard-header p { margin: 0; opacity: 0.9; font-size: 0.95rem; }

    .step-bar {
        display: flex;
        background: white;
        padding: 18px 20px;
        gap: 8px;
        border-bottom: 1px solid #e5e7eb;
        overflow-x: auto;
    }
    .step-pill {
        flex: 1;
        min-width: 130px;
        padding: 10px 14px;
        border-radius: 10px;
        background: #f3f4f6;
        text-align: center;
        font-size: 0.83rem;
        font-weight: 600;
        color: #6b7280;
        border: 2px solid transparent;
        transition: all 0.15s;
        cursor: pointer;
        text-decoration: none;
    }
    .step-pill .step-num {
        display: inline-block;
        width: 22px;
        height: 22px;
        line-height: 22px;
        border-radius: 50%;
        background: #d1d5db;
        color: white;
        font-size: 0.75rem;
        margin-right: 5px;
    }
    .step-pill.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    }
    .step-pill.active .step-num { background: rgba(255,255,255,0.3); }
    .step-pill.done {
        background: #d1fae5;
        color: #047857;
        border-color: #10b981;
    }
    .step-pill.done .step-num { background: #10b981; }

    .wizard-body {
        background: white;
        padding: 30px 30px 24px;
        min-height: 400px;
        border-radius: 0 0 14px 14px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .wizard-body h2 {
        margin: 0 0 14px;
        font-size: 1.3rem;
        color: #1e1b4b;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 10px;
    }
    .wizard-body h2 .check {
        color: #10b981;
        margin-left: 8px;
        font-size: 1.1rem;
    }

    .info-box {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 0.88rem;
        color: #92400e;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    .success-box {
        background: #d1fae5;
        border-left: 4px solid #10b981;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 0.88rem;
        color: #047857;
        margin-bottom: 20px;
    }
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 16px 0;
    }
    .feature-list li {
        padding: 8px 0 8px 28px;
        position: relative;
        font-size: 0.92rem;
        color: #1f2937;
    }
    .feature-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #10b981;
        font-weight: bold;
    }

    .step-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    .step-actions .secondary-actions {
        display: flex;
        gap: 8px;
    }
    .skip-link {
        font-size: 0.83rem;
        color: #9ca3af;
        text-decoration: none;
        padding: 8px 12px;
    }
    .skip-link:hover { color: #6b7280; text-decoration: underline; }

    .action-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: all 0.15s;
    }
    .action-card.done {
        background: #d1fae5;
        border-color: #10b981;
    }
    .action-card-icon {
        font-size: 1.6rem;
        width: 44px;
        height: 44px;
        line-height: 44px;
        text-align: center;
        background: white;
        border-radius: 10px;
        flex-shrink: 0;
    }
    .action-card-body { flex: 1; min-width: 0; }
    .action-card-title { font-weight: 600; font-size: 0.95rem; color: #1f2937; }
    .action-card-desc { font-size: 0.8rem; color: #6b7280; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="wizard-container">
    {{-- ヘッダー --}}
    <div class="wizard-header">
        <h1>🚀 初期セットアップウィザード</h1>
        <p>QRレビューを使い始めるための 5 ステップガイド</p>
    </div>

    {{-- ステップバー --}}
    <div class="step-bar">
        @php
            $stepLabels = [
                1 => ['業種を選ぶ', '🏢'],
                2 => ['店舗を作る', '🏪'],
                3 => ['連携設定', '🔗'],
                4 => ['AI設定', '🤖'],
                5 => ['動作確認', '✨'],
            ];
        @endphp
        @foreach($stepLabels as $n => [$label, $icon])
            @php
                $cls = 'step-pill';
                if ($n == $step) $cls .= ' active';
                elseif (!empty($progress[$n])) $cls .= ' done';
            @endphp
            <a href="/admin/onboarding?step={{ $n }}" class="{{ $cls }}">
                <span class="step-num">{{ !empty($progress[$n]) ? '✓' : $n }}</span>
                {{ $icon }} {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- 本文 --}}
    <div class="wizard-body">
        @if($step == 1)
            {{-- STEP 1: 業種選択 --}}
            <h2>STEP 1: 業種を選択する @if($progress[1])<span class="check">✓ 設定済み</span>@endif</h2>
            <p style="color:#555;font-size:0.92rem;line-height:1.7;">
                まず、お店の業種を登録します。業種ごとに「お客様が口コミを書く時の質問項目（性別・年代など）」や「AI が口コミ・返信を生成する時の参考情報」が変わります。
            </p>

            @if($businessTypes->isNotEmpty())
                <div class="success-box">
                    ✅ 既に <strong>{{ $businessTypes->count() }}</strong> 件の業種が登録されています。
                </div>
                <ul class="feature-list">
                    @foreach($businessTypes->take(5) as $bt)
                    <li><strong>{{ $bt->name }}</strong> @if($bt->base_context) — {{ \Illuminate\Support\Str::limit($bt->base_context, 50) }} @endif</li>
                    @endforeach
                </ul>
            @else
                <div class="info-box">
                    ⚠️ まだ業種が登録されていません。下のボタンから「飲食店」「美容院」「質屋」など、お店の業種を 1 つ以上登録してください。
                </div>
            @endif

            <div class="action-card {{ $progress[1] ? 'done' : '' }}">
                <div class="action-card-icon">🏢</div>
                <div class="action-card-body">
                    <div class="action-card-title">業種マスタを開く</div>
                    <div class="action-card-desc">「✨ AI で業種設定を自動生成」ボタンを使うと、業種名を入れるだけで AI が必要な項目を埋めてくれます。</div>
                </div>
                <a href="/admin/business-types" class="btn btn-primary btn-sm">開く →</a>
            </div>

        @elseif($step == 2)
            {{-- STEP 2: 店舗作成 --}}
            <h2>STEP 2: 店舗を作成する @if($progress[2])<span class="check">✓ 設定済み</span>@endif</h2>
            <p style="color:#555;font-size:0.92rem;line-height:1.7;">
                次に、最初の店舗を登録します。1 つの会社で複数店舗を運営する場合は、店舗ごとに登録します（2 店舗目以降は「📋 複製」ボタンで設定をコピーできます）。
            </p>

            @if($stores->isNotEmpty())
                <div class="success-box">
                    ✅ 既に <strong>{{ $stores->count() }}</strong> 件の店舗が登録されています。
                </div>
                <ul class="feature-list">
                    @foreach($stores->take(5) as $s)
                    <li><strong>{{ $s->name }}</strong>{{ $s->businessType ? ' — ' . $s->businessType->name : '' }}</li>
                    @endforeach
                </ul>
            @else
                <div class="info-box">
                    ⚠️ まだ店舗が登録されていません。下のボタンから店舗を 1 つ以上登録してください。
                </div>
            @endif

            <div class="action-card {{ $progress[2] ? 'done' : '' }}">
                <div class="action-card-icon">🏪</div>
                <div class="action-card-body">
                    <div class="action-card-title">店舗を新規追加</div>
                    <div class="action-card-desc">店舗名、Google レビュー URL、通知先メールを設定します。</div>
                </div>
                <a href="/admin/stores/create" class="btn btn-primary btn-sm">作成 →</a>
            </div>

        @elseif($step == 3)
            {{-- STEP 3: 連携設定 --}}
            <h2>STEP 3: 外部サービスとの連携設定 @if($progress[3])<span class="check">✓ 設定済み</span>@endif</h2>
            <p style="color:#555;font-size:0.92rem;line-height:1.7;">
                各店舗の「連携設定」タブから、Google・Instagram・Facebook・WordPress を接続します。連携すると以下が自動化されます：
            </p>
            <ul class="feature-list">
                <li><strong>Google 連携：</strong>Google マップの口コミを一覧表示し、AI で返信文を生成・投稿</li>
                <li><strong>Instagram / Facebook 連携：</strong>買取投稿を自動で SNS にも投稿</li>
                <li><strong>WordPress 連携：</strong>ブログとして買取記事を自動公開</li>
            </ul>

            @if($stores->isEmpty())
                <div class="info-box">
                    ⚠️ 連携設定を行うには先に STEP 2 で店舗を作成してください。
                </div>
            @else
                @foreach($stores->take(3) as $s)
                <div class="action-card">
                    <div class="action-card-icon">🔗</div>
                    <div class="action-card-body">
                        <div class="action-card-title">{{ $s->name }} の連携設定</div>
                        <div class="action-card-desc">SNS 連携、Google 連携などをまとめて管理</div>
                    </div>
                    <a href="/admin/stores/{{ $s->id }}/edit?tab=integrations" class="btn btn-primary btn-sm">開く →</a>
                </div>
                @endforeach
            @endif

            <div class="info-box" style="margin-top:16px;">
                💡 連携は<strong>あとから設定しても OK</strong> です。とりあえず Google だけでも繋ぐと、口コミの自動返信が使えます。
            </div>

            <div style="background:#fff7ed;border-left:5px solid #f59e0b;border-radius:10px;padding:14px 18px;margin-top:14px;font-size:0.85rem;color:#78350f;line-height:1.7;">
                ⏳ <strong>Facebook / Instagram 連携について：</strong>
                現在 Meta 社の審査申請中（通常 2〜4 週間）。それまではお客様の Facebook アカウントを個別にテスター招待する必要があります。
                希望される場合は <a href="mailto:info@assist-grp.jp?subject=Meta連携テスター招待希望" style="color:#92400e;font-weight:600;">info@assist-grp.jp</a> までご連絡ください。
            </div>

        @elseif($step == 4)
            {{-- STEP 4: AI 設定 --}}
            <h2>STEP 4: AI 設定（口コミ・返信の品質を上げる） @if($progress[4])<span class="check">✓ 設定済み</span>@endif</h2>
            <p style="color:#555;font-size:0.92rem;line-height:1.7;">
                各店舗の「AI 設定」タブで、店舗の特徴・取扱商品・エリア名などを登録すると、AI が生成する口コミ・返信に自然に反映されます。
            </p>
            <ul class="feature-list">
                <li><strong>店舗自己紹介：</strong>「〇〇店のスタッフ」など、AI が口コミを書く時の主語</li>
                <li><strong>サービスキーワード：</strong>「高価買取」「個室席」など、口コミ・返信に含めたい言葉</li>
                <li><strong>エリアキーワード：</strong>「鹿児島市」「天文館」など、地域 SEO で含めたい場所</li>
                <li><strong>NGワード：</strong>競合店名・個人名など、絶対に含めたくない言葉</li>
            </ul>

            <div class="info-box">
                ✨ <strong>初心者の方へ：</strong>「AI で詳細設定を自動入力」ボタンを使うと、業種と店舗名から AI がすべての項目を自動で埋めてくれます。生成後に内容を確認・修正してください。
            </div>

            @if($stores->isEmpty())
                <div class="info-box">⚠️ AI 設定を行うには先に STEP 2 で店舗を作成してください。</div>
            @else
                @foreach($stores->take(3) as $s)
                <div class="action-card">
                    <div class="action-card-icon">🤖</div>
                    <div class="action-card-body">
                        <div class="action-card-title">{{ $s->name }} の AI 設定</div>
                        <div class="action-card-desc">店舗の特徴を AI に教えて、より自然な文章を生成</div>
                    </div>
                    <a href="/admin/stores/{{ $s->id }}/edit?tab=ai" class="btn btn-primary btn-sm">開く →</a>
                </div>
                @endforeach
            @endif

        @elseif($step == 5)
            {{-- STEP 5: 動作確認 --}}
            <h2>STEP 5: 動作確認 @if($progress[5])<span class="check">✓ 確認済み</span>@endif</h2>
            <p style="color:#555;font-size:0.92rem;line-height:1.7;">
                最後に、お店の運用を始める前に動作を確認します。以下の 3 つのうち、できるものから試してください。
            </p>

            <div class="action-card">
                <div class="action-card-icon">📱</div>
                <div class="action-card-body">
                    <div class="action-card-title">QR コードを表示・印刷する</div>
                    <div class="action-card-desc">お客様にスキャンしてもらう QR コードをダウンロードします。</div>
                </div>
                @if($stores->isNotEmpty())
                <a href="/admin/stores/{{ $stores->first()->id }}/qrcode" class="btn btn-primary btn-sm">QR を見る →</a>
                @else
                <span style="font-size:0.78rem;color:#aaa;">店舗を作成後</span>
                @endif
            </div>

            <div class="action-card">
                <div class="action-card-icon">🔍</div>
                <div class="action-card-body">
                    <div class="action-card-title">AI 返信を試してみる</div>
                    <div class="action-card-desc">サンプルの口コミに対して、現在の設定でどんな返信が生成されるか確認できます。</div>
                </div>
                <a href="/admin/ai-reply-preview" class="btn btn-primary btn-sm">プレビュー →</a>
            </div>

            <div class="action-card">
                <div class="action-card-icon">📦</div>
                <div class="action-card-body">
                    <div class="action-card-title">買取投稿を 1 件作ってみる</div>
                    <div class="action-card-desc">テスト投稿で SNS 連携と AI 文章生成の動作を確認します。</div>
                </div>
                <a href="/admin/purchase-posts/create" class="btn btn-primary btn-sm">投稿を作る →</a>
            </div>

            <div class="success-box" style="margin-top:24px;">
                🎉 ここまでお疲れさまでした！下の「セットアップ完了」ボタンを押すとダッシュボードに戻ります。
            </div>

            <form method="POST" action="/admin/onboarding/complete" style="text-align:center;margin-top:14px;">
                @csrf
                <button type="submit" class="btn btn-success" style="padding:12px 30px;font-size:1rem;background:linear-gradient(135deg,#10b981 0%,#059669 100%);">🎉 セットアップ完了</button>
            </form>
        @endif

        {{-- ステップ間ナビゲーション --}}
        <div class="step-actions">
            <div>
                @if($step > 1)
                    <a href="/admin/onboarding?step={{ $step - 1 }}" class="btn btn-secondary">← 前のステップ</a>
                @endif
            </div>
            <div class="secondary-actions">
                <form method="POST" action="/admin/onboarding/skip" style="display:inline;">
                    @csrf
                    <button type="submit" class="skip-link" onclick="return confirm('セットアップをスキップしますか？\n後からダッシュボードの「初期セットアップを再開」リンクから戻れます。')">あとで（スキップ）</button>
                </form>
                @if($step < 5)
                    <a href="/admin/onboarding?step={{ $step + 1 }}" class="btn btn-primary">次のステップ →</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
