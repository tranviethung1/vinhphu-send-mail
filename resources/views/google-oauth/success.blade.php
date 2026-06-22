<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Google OAuth – Thành công</title>
    <style>
        body { font-family: sans-serif; max-width: 640px; margin: 60px auto; padding: 0 1rem; color: #111; }
        h1  { color: #16a34a; }
        .box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 1.25rem 1.5rem; margin-top: 1rem; }
        code { word-break: break-all; font-size: 0.85rem; background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        .note { color: #6b7280; font-size: 0.875rem; margin-top: 1rem; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>✓ Đã lưu Refresh Token thành công</h1>
    <div class="box">
        <p><strong>GOOGLE_OAUTH_REFRESH_TOKEN</strong> đã được ghi vào file <code>.env</code>.</p>
        <p>Token: <code>{{ $refreshToken }}</code></p>
    </div>
    <p class="note">
        Từ bây giờ khi sao chép Google Sheet, hệ thống sẽ dùng tài khoản Google của bạn
        (không dùng service account) nên sẽ không bị lỗi <em>storageQuotaExceeded</em> nữa.
    </p>
    <p class="note">
        Trang này chỉ dùng một lần. Bạn có thể
        <a href="{{ route('dashboard') }}">quay về Dashboard</a>.
    </p>
</body>
</html>
