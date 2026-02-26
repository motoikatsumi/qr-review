<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>【低評価口コミ通知】{{ $storeName }}</title>
</head>
<body style="font-family: 'Hiragino Sans', sans-serif; background: #f5f5f5; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
        <div style="background: linear-gradient(135deg, #ef4444, #dc2626); padding: 20px 24px; color: white;">
            <h1 style="margin: 0; font-size: 1.1rem;">⚠️ 低評価口コミ通知</h1>
        </div>
        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888; width: 100px; font-size: 0.9rem;">店舗名</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-weight: 600;">{{ $storeName }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888; font-size: 0.9rem;">評価</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 1.2rem; color: #ef4444;">{{ $stars }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888; font-size: 0.9rem;">投稿日時</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f0f0f0;">{{ $createdAt }}</td>
                </tr>
            </table>

            <div style="margin-top: 20px;">
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 8px;">コメント内容</p>
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; border-radius: 0 8px 8px 0; line-height: 1.7;">
                    {{ $comment }}
                </div>
            </div>

            <p style="margin-top: 24px; color: #888; font-size: 0.8rem; text-align: center;">
                ※ この口コミはGoogleマップには投稿されていません
            </p>
        </div>
    </div>
</body>
</html>
