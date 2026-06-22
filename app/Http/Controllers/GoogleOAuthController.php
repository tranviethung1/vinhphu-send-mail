<?php

namespace App\Http\Controllers;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller một lần dùng để lấy Google OAuth refresh token.
 *
 * Quy trình:
 *   1. Truy cập GET /google/authorize  → chuyển hướng đến Google đăng nhập
 *   2. Google redirect về GET /google/callback?code=...
 *   3. Controller đổi code lấy token, lưu GOOGLE_OAUTH_REFRESH_TOKEN vào .env
 */
class GoogleOAuthController extends Controller
{
    private function buildClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_OAUTH_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_OAUTH_CLIENT_SECRET'));
        $client->setRedirectUri(url('/google/callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Bắt buộc Google trả về refresh_token mỗi lần
        $client->addScope(GoogleDrive::DRIVE);
        return $client;
    }

    /**
     * Bước 1: Tạo URL xác thực và redirect đến Google.
     */
    public function authorize()
    {
        if (!env('GOOGLE_OAUTH_CLIENT_ID') || !env('GOOGLE_OAUTH_CLIENT_SECRET')) {
            return response('GOOGLE_OAUTH_CLIENT_ID hoặc GOOGLE_OAUTH_CLIENT_SECRET chưa được cấu hình trong .env', 500);
        }

        $authUrl = $this->buildClient()->createAuthUrl();

        return redirect($authUrl);
    }

    /**
     * Bước 2: Google callback — đổi code lấy token và lưu vào .env.
     */
    public function callback(Request $request)
    {
        $code  = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            return response()->json(['error' => $error], 400);
        }

        if (!$code) {
            return response('Không nhận được authorization code từ Google.', 400);
        }

        try {
            $client = $this->buildClient();
            $token  = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return response()->json(['error' => $token['error'], 'detail' => $token['error_description'] ?? ''], 400);
            }

            $refreshToken = $token['refresh_token'] ?? null;

            if (!$refreshToken) {
                return response(
                    'Google không trả về refresh_token. '
                    . 'Vào https://myaccount.google.com/permissions, thu hồi quyền app này rồi thử lại.',
                    400
                );
            }

            // Lưu vào file .env
            $this->writeEnvValue('GOOGLE_OAUTH_REFRESH_TOKEN', $refreshToken);

            Log::info('Đã lưu GOOGLE_OAUTH_REFRESH_TOKEN vào .env');

            return view('google-oauth.success', ['refreshToken' => $refreshToken]);

        } catch (\Throwable $e) {
            Log::error('Lỗi khi lấy Google OAuth token', ['error' => $e->getMessage()]);
            return response('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Ghi / cập nhật một giá trị key=value trong file .env.
     */
    private function writeEnvValue(string $key, string $value): void
    {
        $envPath    = base_path('.env');
        $envContent = file_get_contents($envPath);

        // Escape value nếu có ký tự đặc biệt
        $escapedValue = str_contains($value, ' ') ? '"' . $value . '"' : $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $envContent)) {
            // Cập nhật dòng đã có
            $envContent = preg_replace(
                '/^' . preg_quote($key, '/') . '=.*/m',
                $key . '=' . $escapedValue,
                $envContent
            );
        } else {
            // Thêm dòng mới vào cuối
            $envContent .= PHP_EOL . $key . '=' . $escapedValue;
        }

        file_put_contents($envPath, $envContent);
    }
}
