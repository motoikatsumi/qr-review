@extends('layouts.super-admin')

@section('title', 'マニュアル — 運営管理機能')

@section('content')
<style>
  /* ============================
     基本スタイル
     ============================ */
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: "Hiragino Kaku Gothic ProN", "Yu Gothic", "Meiryo", sans-serif;
    line-height: 1.8;
    color: #333;
    background: #f5f7fa;
    font-size: 15px;
  }

  .container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    padding: 40px 50px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
  }

  /* ============================
     表紙
     ============================ */
  .cover {
    text-align: center;
    padding: 80px 20px;
    border-bottom: 3px solid #2563eb;
    margin-bottom: 40px;
    page-break-after: always;
  }

  .cover h1 {
    font-size: 32px;
    color: #1e40af;
    margin-bottom: 10px;
    letter-spacing: 2px;
  }

  .cover .subtitle {
    font-size: 20px;
    color: #64748b;
    margin-bottom: 40px;
  }

  .cover .version {
    font-size: 14px;
    color: #94a3b8;
    margin-top: 30px;
  }

  .cover .logo-icon {
    font-size: 64px;
    margin-bottom: 20px;
  }

  /* ============================
     目次
     ============================ */
  .toc {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px 40px;
    margin-bottom: 40px;
    page-break-after: always;
  }

  .toc h2 {
    font-size: 22px;
    color: #1e40af;
    margin-bottom: 20px;
    border-bottom: 2px solid #dbeafe;
    padding-bottom: 10px;
  }

  .toc ol {
    counter-reset: toc-counter;
    list-style: none;
  }

  .toc > ol > li {
    counter-increment: toc-counter;
    margin-bottom: 6px;
  }

  .toc > ol > li > a {
    display: flex;
    align-items: baseline;
    text-decoration: none;
    color: #334155;
    padding: 6px 0;
    border-bottom: 1px dotted #cbd5e1;
    transition: color 0.2s;
  }

  .toc > ol > li > a:hover { color: #2563eb; }

  .toc > ol > li > a::before {
    content: counter(toc-counter) ".";
    font-weight: bold;
    color: #2563eb;
    min-width: 30px;
    margin-right: 8px;
  }

  .toc .toc-sub {
    list-style: none;
    padding-left: 38px;
  }

  .toc .toc-sub li a {
    text-decoration: none;
    color: #64748b;
    font-size: 14px;
    display: block;
    padding: 2px 0;
  }

  .toc .toc-sub li a:hover { color: #2563eb; }

  /* ============================
     見出し
     ============================ */
  h2 {
    font-size: 24px;
    color: #1e40af;
    border-left: 5px solid #2563eb;
    padding-left: 15px;
    margin-top: 50px;
    margin-bottom: 20px;
    page-break-before: always;
  }

  h2:first-of-type { page-break-before: auto; }

  h3 {
    font-size: 18px;
    color: #1e3a8a;
    margin-top: 30px;
    margin-bottom: 12px;
    padding-bottom: 5px;
    border-bottom: 1px solid #e2e8f0;
  }

  h4 {
    font-size: 16px;
    color: #475569;
    margin-top: 20px;
    margin-bottom: 8px;
  }

  /* ============================
     本文コンテンツ
     ============================ */
  p { margin-bottom: 12px; }

  .section { margin-bottom: 40px; }

  /* 手順リスト */
  .steps {
    list-style: none;
    counter-reset: step-counter;
    margin: 16px 0;
    padding: 0;
  }

  .steps > li {
    counter-increment: step-counter;
    position: relative;
    padding: 14px 16px 14px 60px;
    margin-bottom: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    min-height: 48px;
  }

  .steps > li::before {
    content: counter(step-counter);
    position: absolute;
    left: 14px;
    top: 12px;
    background: #2563eb;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
  }

  /* 通常のリスト */
  ul, ol { margin: 12px 0 12px 24px; }
  li { margin-bottom: 6px; }

  /* 注意・ポイントボックス */
  .note {
    background: #fffbeb;
    border: 1px solid #fbbf24;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 16px 0;
  }

  .note::before {
    content: "💡 ポイント";
    display: block;
    font-weight: bold;
    color: #b45309;
    margin-bottom: 6px;
  }

  .warning {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-left: 4px solid #ef4444;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 16px 0;
  }

  .warning::before {
    content: "⚠️ ご注意";
    display: block;
    font-weight: bold;
    color: #dc2626;
    margin-bottom: 6px;
  }

  .info {
    background: #eff6ff;
    border: 1px solid #93c5fd;
    border-left: 4px solid #3b82f6;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 16px 0;
  }

  .info::before {
    content: "ℹ️ 補足";
    display: block;
    font-weight: bold;
    color: #1d4ed8;
    margin-bottom: 6px;
  }

  .google-required {
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-left: 4px solid #22c55e;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 16px 0;
  }

  .google-required::before {
    content: "🔗 Google連携が必要な機能";
    display: block;
    font-weight: bold;
    color: #15803d;
    margin-bottom: 6px;
  }

  .new-badge {
    display: inline-block;
    background: #dc2626;
    color: #fff;
    padding: 1px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 6px;
    vertical-align: middle;
  }

  /* テーブル */
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
    font-size: 14px;
  }

  th {
    background: #1e40af;
    color: #fff;
    padding: 10px 14px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
  }

  td {
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
  }

  tr:nth-child(even) td { background: #f8fafc; }

  /* キーワード・ボタン表記 */
  .btn {
    display: inline-block;
    background: #2563eb;
    color: #fff;
    padding: 3px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
  }

  .btn-secondary {
    background: #64748b;
  }

  .btn-green {
    background: #16a34a;
  }

  .btn-orange {
    background: #ea580c;
  }

  .menu-path {
    display: inline-block;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #cbd5e1;
  }

  .menu-path .sep { color: #94a3b8; margin: 0 4px; }

  kbd {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 1px 6px;
    font-size: 13px;
    font-family: inherit;
  }

  code {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    color: #e11d48;
  }

  /* 画面イメージ枠 */
  .screen-image {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 30px;
    margin: 16px 0;
    background: #f8fafc;
    text-align: center;
    color: #94a3b8;
    font-size: 14px;
  }

  /* フロー図 */
  .flow {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin: 20px 0;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
  }

  .flow-step {
    background: #dbeafe;
    color: #1e40af;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
  }

  .flow-arrow {
    color: #94a3b8;
    font-size: 20px;
    font-weight: bold;
  }

  .flow-step.highlight {
    background: #2563eb;
    color: #fff;
  }

  /* 区切り線 */
  hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 30px 0;
  }

  /* ============================
     印刷用スタイル
     ============================ */
  @media print {
    body { background: #fff; font-size: 12pt; }
    .container { box-shadow: none; padding: 0; max-width: 100%; }
    .cover { padding: 60px 20px; }
    h2 { page-break-before: always; font-size: 18pt; }
    h2:first-of-type { page-break-before: auto; }
    .steps > li, .note, .warning, .info, .google-required { break-inside: avoid; }
    table { break-inside: avoid; }
    a { color: #333; text-decoration: none; }
    .toc > ol > li > a::after { content: none; }
    .no-print { display: none; }
  }

  @media (max-width: 768px) {
    .container { padding: 20px; }
    .cover { padding: 40px 10px; }
    .cover h1 { font-size: 24px; }
    h2 { font-size: 20px; }
    .flow { flex-direction: column; }
    .flow-arrow { transform: rotate(90deg); }
  }

    /* マニュアル用スクリーンショット */
    .manual-screenshot {
      margin: 18px 0 24px;
      text-align: center;
    }
    .manual-screenshot a {
      display: inline-block;
      max-width: 100%;
    }
    .manual-screenshot img {
      max-width: 100%;
      height: auto;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .manual-screenshot a:hover img {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    }
    .manual-screenshot figcaption {
      font-size: 0.85rem;
      color: #6b7280;
      margin-top: 6px;
    }
    @media print {
      .manual-screenshot img { box-shadow: none; }
    }

</style>

<div style="padding:0 8px 40px;max-width:980px;margin:0 auto;">
  <div class="page-header" style="border-bottom:1px solid #e5e7eb;margin-bottom:18px;">
    <h1 style="font-size:1.4rem;">📖 運営管理マニュアル(Super Admin)</h1>
    <p style="font-size:0.85rem;color:#6b7280;">本マニュアルは運営管理者(Super Admin)向けの機能解説です。一般のテナント管理者には共有されません。</p>
  </div>
<!-- ====================================================================
     1. Super Admin（テナント管理）
     ==================================================================== -->
<div class="section" id="super-admin">
  <h2>1. Super Admin（テナント管理）</h2>

  <h3 id="super-admin-about">1-1. Super Adminとは</h3>
  <p>
    Super Adminは、<strong>システム全体を管理するための最上位の管理者画面</strong>です。<br>
    契約テナント（利用企業）の追加・編集・削除、AI利用量の監視、代理ログインなどを行います。
  </p>
  <p>
    通常の管理画面（Admin）とは<strong>完全に別のログイン画面</strong>を使用します。<br>
    Super Adminのデータはマスターデータベース（<code>qr_master</code>）に保存されており、各テナントのデータとは分離されています。
  </p>

  <table>
    <thead>
      <tr><th>項目</th><th>Super Admin</th><th>テナント管理者（Admin）</th></tr>
    </thead>
    <tbody>
      <tr><td>ログインURL</td><td><code>/super-admin/login</code></td><td><code>/admin/login</code></td></tr>
      <tr><td>管理範囲</td><td>全テナント</td><td>自社テナントのみ</td></tr>
      <tr><td>データベース</td><td>マスターDB</td><td>テナント専用DB</td></tr>
      <tr><td>主な操作</td><td>テナント追加・削除・監視</td><td>店舗・口コミ・連携管理</td></tr>
    </tbody>
  </table>

  <hr>

  <h3 id="super-admin-login">1-2. ログイン</h3>
  <div class="step-box">
    <h4>Super Adminログイン手順</h4>
    <ol class="steps">
      <li>ブラウザで <code>https://{ドメイン}/super-admin/login</code> にアクセスします。<br>
          ローカル環境の場合は <code>http://127.0.0.1:8000/super-admin/login</code> です。</li>
      <li>メールアドレスとパスワードを入力してログインします。</li>
      <li>ログイン成功後、<strong>ダッシュボード</strong>が表示されます。</li>
    </ol>
  </div>
  <div class="warning">
    セキュリティのため、ログインは<strong>1分間に5回まで</strong>に制限されています。連続で失敗した場合は、1分待ってから再試行してください。
  </div>

  <hr>

  <h3 id="super-admin-dashboard">1-3. ダッシュボード</h3>
  <figure class="manual-screenshot">
    <a href="/manual-assets/images/19-super-admin-dashboard.png" target="_blank" rel="noopener">
      <img src="/manual-assets/images/19-super-admin-dashboard.png" alt="運営ダッシュボード" loading="lazy">
    </a>
    <figcaption>運営ダッシュボード</figcaption>
  </figure>
  <p>
    ログイン後最初に表示される画面です。システム全体の状況を一目で確認できます。
  </p>

  <h4>統計カード</h4>
  <table>
    <thead>
      <tr><th>カード</th><th>表示内容</th></tr>
    </thead>
    <tbody>
      <tr><td>総テナント数</td><td>登録されている全テナントの数</td></tr>
      <tr><td>有効テナント</td><td>現在アクティブなテナントの数</td></tr>
      <tr><td>停止テナント</td><td>無効化されているテナントの数</td></tr>
      <tr><td>総店舗数</td><td>全テナントの店舗数合計</td></tr>
      <tr><td>AI利用合計（今月）</td><td>全テナントの今月のAI利用回数合計</td></tr>
    </tbody>
  </table>

  <h4>AI利用状況</h4>
  <p>テナントごとのAI利用回数・上限・利用率がランキング形式で表示されます。利用率が高いテナントが上に表示され、<span style="color:#dc2626;">90%以上は赤</span>、<span style="color:#f59e0b;">70%以上は黄色</span>のバーで警告されます。</p>

  <h4>契約終了まもなく</h4>
  <p>契約終了日が30日以内のテナントが一覧表示されます。残日数が7日以下のテナントは赤字で強調されます。</p>

  <h4>プラン別テナント数</h4>
  <p>ライト・スタンダード・プロの各プランに何社が契約しているかを表示します。</p>

  <hr>

  <h3 id="super-admin-tenants">1-4. テナント管理</h3>
  <figure class="manual-screenshot">
    <a href="/manual-assets/images/20-super-admin-tenants.png" target="_blank" rel="noopener">
      <img src="/manual-assets/images/20-super-admin-tenants.png" alt="テナント管理画面" loading="lazy">
    </a>
    <figcaption>テナント管理画面</figcaption>
  </figure>

  <h4>テナント一覧</h4>
  <p>ナビバーの「テナント一覧」をクリックすると、全テナントの一覧が表示されます。</p>
  <table>
    <thead>
      <tr><th>列</th><th>内容</th></tr>
    </thead>
    <tbody>
      <tr><td>会社名</td><td>テナントの会社名</td></tr>
      <tr><td>サブドメイン</td><td>テナントのサブドメイン（URL識別子）</td></tr>
      <tr><td>プラン</td><td>ライト / スタンダード / プロ</td></tr>
      <tr><td>店舗</td><td>登録店舗数（ホバーで店舗名一覧を表示）</td></tr>
      <tr><td>AI利用（今月）</td><td>今月の利用回数 / 月間上限</td></tr>
      <tr><td>状態</td><td>有効 / 停止</td></tr>
      <tr><td>契約開始</td><td>契約開始日</td></tr>
      <tr><td>操作</td><td>編集・AI詳細・代理ログイン</td></tr>
    </tbody>
  </table>

  <h4>テナントを追加する</h4>
  <div class="step-box">
    <h4>追加手順</h4>
    <ol class="steps">
      <li>テナント一覧画面で「<strong>＋ 新規テナント追加</strong>」ボタンをクリック。</li>
      <li>以下の情報を入力します：
        <ul>
          <li><strong>会社名</strong>（必須）</li>
          <li><strong>サブドメイン</strong>（必須）：英小文字・数字・ハイフンのみ</li>
          <li><strong>データベース名</strong>（必須）：テナント専用DB名</li>
          <li><strong>プラン</strong>（必須）：ライト / スタンダード / プロ</li>
          <li><strong>連絡先メール</strong>（必須）</li>
          <li>担当者名、契約開始日・終了日、AI月間上限、備考（任意）</li>
        </ul>
      </li>
      <li>「追加」ボタンをクリック。</li>
      <li>追加後、テナント専用データベースの作成とマイグレーションはエンジニアが実施します。</li>
    </ol>
  </div>

  <h4>テナントを編集する</h4>
  <p>テナント一覧の「編集」ボタンから、会社名・プラン・AI上限・契約情報などを変更できます。「テナント有効」のチェックを外すと、そのテナントのシステムへのアクセスが停止されます。</p>

  <h4>テナントを削除する</h4>
  <p>テナント編集画面の下部「⚠️ 危険な操作」エリアから削除できます。</p>
  <div class="warning">
    <strong>削除するとマスターDBからテナント情報が除去されます。</strong><br>
    安全のため、テナントのデータベース自体は削除されません。データベースの削除が必要な場合は、別途手動で行ってください。
  </div>

  <hr>

  <h3 id="super-admin-impersonate">1-5. 代理ログイン</h3>
  <p>
    テナント一覧の「<strong>代理ログイン</strong>」ボタンをクリックすると、そのテナントの管理者としてログインできます。
  </p>
  <div class="step-box">
    <h4>使い方</h4>
    <ol class="steps">
      <li>テナント一覧で対象テナントの「代理ログイン」をクリック。</li>
      <li>確認ダイアログで「OK」をクリック。</li>
      <li>テナントの管理画面（<code>/admin/stores</code>）に自動でリダイレクトされます。</li>
      <li>テナント内の操作（店舗追加、ユーザー作成、連携設定など）を行えます。</li>
    </ol>
  </div>
  <div class="info">
    代理ログイン中はテナントの管理者として操作しています。Super Adminに戻るには、ログアウトしてから <code>/super-admin/login</code> に再度アクセスしてください。
  </div>

  <hr>

  <h3 id="super-admin-ai-usage">1-6. AI利用ログ</h3>
  <p>テナント一覧の「<strong>AI詳細</strong>」ボタンから、テナントのAI利用状況を詳しく確認できます。</p>

  <h4>表示内容</h4>
  <table>
    <thead>
      <tr><th>セクション</th><th>内容</th></tr>
    </thead>
    <tbody>
      <tr><td>サマリーカード</td><td>今月の利用回数・月間上限・利用率</td></tr>
      <tr><td>日別利用数</td><td>過去30日間の日ごとの利用回数（バーグラフ付き）</td></tr>
      <tr><td>アクション別</td><td>今月の利用をアクションタイプ別に集計</td></tr>
      <tr><td>最近のログ</td><td>直近50件のAI利用ログ（日時・アクション・店舗ID・トークン数）</td></tr>
    </tbody>
  </table>

  <hr>

  <h3 id="super-admin-password">1-7. パスワード変更</h3>
  <div class="step-box">
    <h4>パスワード変更手順</h4>
    <ol class="steps">
      <li>ナビバー右側の「<strong>パスワード変更</strong>」をクリック。</li>
      <li>現在のパスワードを入力。</li>
      <li>新しいパスワード（8文字以上）を入力。</li>
      <li>新しいパスワード（確認）を再入力。</li>
      <li>「パスワードを変更」ボタンをクリック。</li>
    </ol>
  </div>
  <div class="warning">
    初期パスワード（<code>password</code>）は必ず変更してください。
  </div>


</div>
@endsection
