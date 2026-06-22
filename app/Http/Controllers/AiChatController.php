<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Drive;

class AiChatController extends Controller
{
    private string $sheetRange  = 'A:B';
    private int    $cacheSeconds = 3600;

    // Tên chính xác của các sheet trong Google Spreadsheet
    private const SHEET_COMPANY  = 'Giới thiệu cty';
    private const SHEET_GUIDE    = 'Hướng dẫn sử dụng';

    private function getSpreadsheetId(): string
    {
        return env('AI_CHAT_SHEET_ID', '1KEhSLvbSfunKLGdl7wXunXi-ROWjqoLylyzGcvI58as');
    }

    public function index()
    {
        return view('ai-chat.index');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message'  => 'required|string|max:2000',
            'history'  => 'nullable|array|max:20',
            'history.*.role' => 'required|in:user,model',
            'history.*.text' => 'required|string|max:2000',
        ]);

        $message = $request->input('message');
        $knowledge = $this->buildKnowledge();
        
        // Kiểm tra nếu là câu hỏi về lương
        $salaryInfo = $this->handleSalaryQuery($message);
        if ($salaryInfo) {
            $knowledge .= "\n\n=== DỮ LIỆU LƯƠNG TRUY XUẤT THỜI GIAN THỰC ===\n" . $salaryInfo . "\n=== HẾT DỮ LIỆU LƯƠNG ===";
        }

        if (empty(trim($knowledge)) && !$salaryInfo) {
            return response()->json([
                'answer' => 'Xin lỗi, hiện tại chưa có dữ liệu kiến thức. Vui lòng kiểm tra kết nối Google Sheets.',
                'source' => 'error',
            ], 200);
        }

        [$answer, $provider] = $this->askAI(
            $request->input('message'),
            $knowledge,
            $request->input('history', [])
        );

        return response()->json([
            'answer' => $answer,
            'source' => $provider,
        ]);
    }

    public function refreshKnowledge()
    {
        // Xoá cache của cả 2 sheet
        Cache::forget('ai_chat_sheet_company');
        Cache::forget('ai_chat_sheet_guide');

        $knowledge = $this->buildKnowledge();
        $rowCount  = $knowledge ? count(array_filter(explode("\n", $knowledge))) : 0;

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật dữ liệu thành công! ({$rowCount} mục từ 2 sheet)",
        ]);
    }

    public function knowledgePreview()
    {
        return response()->json([
            'company_sheet'  => $this->fetchSheetRows(self::SHEET_COMPANY),
            'guide_sheet'    => $this->fetchSheetRows(self::SHEET_GUIDE),
            'cached_at'      => Cache::get('ai_chat_knowledge_cached_at'),
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Ghép dữ liệu từ 2 sheet thành 1 knowledge string có cấu trúc rõ ràng.
     */
    private function buildKnowledge(): string
    {
        $company = $this->getKnowledgeBySheet(self::SHEET_COMPANY,  'ai_chat_sheet_company');
        $guide   = $this->getKnowledgeBySheet(self::SHEET_GUIDE,    'ai_chat_sheet_guide');

        Cache::put('ai_chat_knowledge_cached_at', now()->format('d/m/Y H:i:s'), $this->cacheSeconds);

        $parts = [];

        if (!empty($company)) {
            $parts[] = "=== GIỚI THIỆU CÔNG TY ===\n" . $company . "\n=== HẾT GIỚI THIỆU CÔNG TY ===";
        }

        if (!empty($guide)) {
            $parts[] = "=== HƯỚNG DẪN SỬ DỤNG HỆ THỐNG ===\n" . $guide . "\n=== HẾT HƯỚNG DẪN SỬ DỤNG ===";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Lấy dữ liệu 1 sheet, có cache theo cacheKey.
     */
    private function getKnowledgeBySheet(string $sheetName, string $cacheKey): string
    {
        return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($sheetName) {
            $rows = $this->fetchSheetRows($sheetName);
            return $this->rowsToText($rows);
        });
    }

    /**
     * Đọc dữ liệu từ 1 sheet (theo tên tab) qua Google Sheets API.
     */
    private function fetchSheetRows(string $sheetName): array
    {
        try {
            $client  = $this->buildGoogleClient();
            $service = new Sheets($client);
            $range   = "'{$sheetName}'!{$this->sheetRange}";
            $response = $service->spreadsheets_values->get($this->getSpreadsheetId(), $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            Log::error("[AiChat] fetchSheetRows({$sheetName}) error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Chuyển mảng rows (Câu hỏi | Trả lời) thành chuỗi text.
     */
    private function rowsToText(array $rows): string
    {
        $lines = [];
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // bỏ header
            $question = trim($row[0] ?? '');
            $answer   = trim($row[1] ?? '');
            if ($question !== '' && $answer !== '') {
                $lines[] = "- {$question}: {$answer}";
            }
        }
        return implode("\n", $lines);
    }

    private function buildGoogleClient(): Client
    {
        $serviceAccountPath = base_path(
            env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON', 'storage/app/google/service-account.json')
        );

        $client = new Client();
        $client->setAuthConfig($serviceAccountPath);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $client->addScope(Drive::DRIVE_READONLY);

        return $client;
    }

    /**
     * Nhận diện câu hỏi lương và lấy dữ liệu
     */
    private function handleSalaryQuery(string $message): ?string
    {
        // Nhận diện từ khoá liên quan đến lương
        if (!preg_match('/lương|salary|thu nhập|Thực nhận/ui', $message)) return null;

        $month = null;
        $year = null;

        // 1. Tìm định dạng MM/YYYY hoặc MM-YYYY (ví dụ: 02/2026)
        if (preg_match('/(\d{1,2})[\/\-](\d{4})/', $message, $m)) {
            $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $year  = $m[2];
        }
        // 2. Tìm "tháng X năm Y" hoặc "tháng X/Y"
        elseif (preg_match('/tháng\s+(\d{1,2})(?:\s+năm\s+|\/|\s+)?\s*(\d{4})/ui', $message, $m)) {
            $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $year  = $m[2];
        }

        // Trích xuất địa điểm (Cơ sở)
        $locationCode = null;
        $locations = [
            'Bình Dương' => 'BD', 'BD' => 'BD',
            'Đồng Nai' => 'DN', 'DN' => 'DN',
            'Hồ Chí Minh' => 'HCM', 'HCM' => 'HCM', 'Sài Gòn' => 'HCM',
            'Bắc Ninh' => 'BN', 'BN' => 'BN'
        ];
        foreach ($locations as $name => $code) {
            if (preg_match('/' . preg_quote($name, '/') . '/ui', $message)) {
                $locationCode = $code;
                break;
            }
        }

        // Nếu người dùng không chỉ định cơ sở nhưng hỏi về Bình Dương theo yêu cầu cụ thể trước đó
        if (!$locationCode && str_contains(mb_strtolower($message), 'bình dương')) {
            $locationCode = 'BD';
        }

        // Trích xuất tên nhân viên
        $employeeName = null;
        // Tìm sau các từ khoá: "nhân viên", "của", "tên là", "lương"
        // Ví dụ: "lương Nguyễn Văn A", "nhân viên Nguyễn Văn A"
        if (preg_match('/(?:nhân viên|của|tên là|lương|cho)\s+([A-ZÀ-Ỹ][a-zà-ỹ]*(\s+[A-ZÀ-Ỹ][a-zà-ỹ]*)+)/u', $message, $m)) {
            $employeeName = trim($m[1]);
        }

        if (!$month || !$year || !$locationCode) {
            return null;
        }

        return $this->fetchSalaryFromDrive($locationCode, $month, $year, $employeeName);
    }

    private function fetchSalaryFromDrive(string $loc, string $mm, string $yyyy, ?string $targetName = null): string
    {
        try {
            // Mapping folder ID theo cơ sở
            $folderMappings = [
                'BD' => '1GNP-Gw1nPr3emQQd7pyIganuYo81EzN5', // Bình Dương
                'BN' => '11mVGRLTIFRKxx5ZadBEfvIKDpBKJMQJM', // Bắc Ninh
                'DEFAULT' => '1GNP-Gw1nPr3emQQd7pyIganuYo81EzN5'
            ];

            $folderId = $folderMappings[$loc] ?? $folderMappings['DEFAULT'];
            $fileName = "{$loc}.{$mm}.{$yyyy}";
            
            $client = $this->buildGoogleClient();
            $driveService = new Drive($client);
            
            // Tìm file trong folder
            $query = "name = '{$fileName}' and '{$folderId}' in parents and trashed = false";
            $files = $driveService->files->listFiles(['q' => $query, 'fields' => 'files(id, name)']);
            
            if (count($files->getFiles()) === 0) {
                return "Không tìm thấy file bảng lương cho {$loc} tháng {$mm}/{$yyyy} trên hệ thống.";
            }

            $fileId = $files->getFiles()[0]->id;
            $sheetService = new Sheets($client);
            
            // Lấy metadata của spreadsheet để tìm sheet phù hợp
            $spreadsheet = $sheetService->spreadsheets->get($fileId);
            $sheets = $spreadsheet->getSheets();
            $targetSheetName = null;
            
            // 1. Tìm sheet "Bảng lương" chính xác
            foreach ($sheets as $s) {
                $title = $s->getProperties()->getTitle();
                if (mb_strtolower(trim($title)) === 'bảng lương') {
                    $targetSheetName = $title;
                    break;
                }
            }
            
            // 2. Tìm sheet có chữ "lương"
            if (!$targetSheetName) {
                foreach ($sheets as $s) {
                    $title = $s->getProperties()->getTitle();
                    if (str_contains(mb_strtolower($title), 'lương')) {
                        $targetSheetName = $title;
                        break;
                    }
                }
            }
            
            // 3. Mặc định là sheet đầu tiên
            if (!$targetSheetName && !empty($sheets)) {
                $targetSheetName = $sheets[0]->getProperties()->getTitle();
            }
            
            if (!$targetSheetName) return "Không tìm thấy dữ liệu trong file {$fileName}.";

            $response = $sheetService->spreadsheets_values->get($fileId, "'{$targetSheetName}'!A:Z");
            $values = $response->getValues();

            if (empty($values)) return "File lương {$fileName} rỗng hoặc không có dữ liệu.";

            // Tìm cột Lương và cột Tên
            $header = $values[0] ?? [];
            $salaryColIndex = -1;
            $nameColIndex = -1;
            
            // Nhóm ưu tiên cho cột Lương
            $salaryKeywords = [
                ['thực nhận', 'tổng cộng', 'tổng số', 'tổng lương', 'thành tiền'],
                ['lương', 'thanh toán', 'số tiền']
            ];

            // Nhóm ưu tiên cho cột Tên
            $nameKeywords = ['họ và tên', 'tên nhân viên', 'nhân viên', 'họ tên', 'tên'];

            foreach ($header as $idx => $title) {
                $cleanTitle = mb_strtolower(trim($title));
                
                // Tìm cột lương (nếu chưa tìm thấy ở mức ưu tiên cao hơn)
                if ($salaryColIndex === -1) {
                    foreach ($salaryKeywords as $group) {
                        foreach ($group as $kw) {
                            if (str_contains($cleanTitle, $kw)) {
                                $salaryColIndex = $idx;
                                break 2;
                            }
                        }
                    }
                }

                // Tìm cột tên
                if ($nameColIndex === -1) {
                    foreach ($nameKeywords as $kw) {
                        if (str_contains($cleanTitle, $kw)) {
                            $nameColIndex = $idx;
                            break;
                        }
                    }
                }
            }
            
            // Mặc định cột lương nếu không tìm thấy
            if ($salaryColIndex === -1) {
                $salaryColIndex = count($header) > 3 ? 3 : (count($header) > 1 ? 1 : 0);
            }

            // Nếu hỏi đích danh một người
            if ($targetName && $nameColIndex !== -1) {
                $foundRows = [];
                foreach ($values as $idx => $row) {
                    if ($idx === 0) continue;
                    $rowName = trim($row[$nameColIndex] ?? '');
                    if (str_contains(mb_strtolower($rowName), mb_strtolower($targetName))) {
                        $salaryValue = trim($row[$salaryColIndex] ?? '0');
                        $foundRows[] = [
                            'name' => $rowName,
                            'salary' => $salaryValue
                        ];
                    }
                }

                if (!empty($foundRows)) {
                    $resultText = "Tìm thấy thông tin lương cho '**{$targetName}**' trong file {$fileName}:\n";
                    foreach ($foundRows as $item) {
                        // Làm sạch và định dạng số tiền
                        $cleanVal = preg_replace('/[^0-9.,]/', '', $item['salary']);
                        $num = $this->parseVietnameseNumber($cleanVal);
                        $formatted = number_format($num, 0, ',', '.') . ' VNĐ';
                        $resultText .= "- **{$item['name']}**: {$formatted}\n";
                    }
                    return $resultText;
                } else {
                    return "Không tìm thấy tên nhân viên nào khớp với '**{$targetName}**' trong file {$fileName} (cột '{$header[$nameColIndex]}').";
                }
            }

            Log::info("[AiChat] File {$fileName} - Dung cot index: {$salaryColIndex} (Tieu de: '" . ($header[$salaryColIndex] ?? 'N/A') . "')");

            $total = 0;
            $count = 0;
            $sampleValues = [];

            foreach ($values as $idx => $row) {
                if ($idx === 0) continue; // skip header
                
                $val = trim($row[$salaryColIndex] ?? '');
                if ($val === '' || $val === '0') continue;

                if (count($sampleValues) < 3) $sampleValues[] = $val;

                $num = $this->parseVietnameseNumber($val);

                if ($num > 0) {
                    $total += $num;
                    $count++;
                }
            }

            Log::info("[AiChat] Mau du lieu: " . implode(', ', $sampleValues) . " | Tong: {$total} | So dong: {$count}");

            $totalFormatted = number_format($total, 0, ',', '.') . ' VNĐ';
            if ($count === 0) {
                return "Đã tìm thấy file {$fileName} nhưng không trích xuất được số liệu từ cột '" . ($header[$salaryColIndex] ?? 'N/A') . "'. Các giá trị mẫu thấy được: " . (implode(', ', $sampleValues) ?: 'Trống');
            }

            return "Dữ liệu thực tế từ file {$fileName}: Tổng cộng có {$count} nhân viên, tổng lương là {$totalFormatted}.";

        } catch (\Exception $e) {
            Log::error('[AiChat] Salary fetch error: ' . $e->getMessage());
            return "Lỗi khi truy cập Google Drive: " . $e->getMessage();
        }
    }

    /**
     * Helper làm sạch và chuyển đổi số định dạng Việt Nam sang float
     */
    private function parseVietnameseNumber(string $val): float
    {
        $cleanVal = preg_replace('/[^0-9.,]/', '', $val);
        if (empty($cleanVal)) return 0;

        // Nếu là định dạng 1.234.567 (nhiều dấu chấm)
        if (substr_count($cleanVal, '.') > 1) {
            return (float)str_replace('.', '', $cleanVal);
        }
        // Nếu là định dạng 1,234,567 (nhiều dấu phẩy)
        if (substr_count($cleanVal, ',') > 1) {
            return (float)str_replace(',', '', $cleanVal);
        }

        // Xử lý trường hợp có 1 dấu chấm hoặc 1 dấu phẩy
        $lastDot = strrpos($cleanVal, '.');
        $lastComma = strrpos($cleanVal, ',');

        if ($lastDot !== false && $lastComma !== false) {
            // Có cả 2 loại dấu (vd: 1.234,56)
            if ($lastDot > $lastComma) {
                // Định dạng quốc tế 1,234.56
                return (float)str_replace(',', '', $cleanVal);
            } else {
                // Định dạng VN 1.234,56
                return (float)str_replace('.', '', str_replace(',', '.', $cleanVal));
            }
        } else {
            // Chỉ có 1 loại dấu hoặc không có dấu nào
            // Với tiền VNĐ thường không có xu, nên 1.000 hay 1,000 đều là một nghìn
            return (float)str_replace([',', '.'], ['', ''], $cleanVal);
        }
    }

    private function askAI(string $userMessage, string $knowledge, array $history = []): array
    {
        $answer = $this->askDeepSeek($userMessage, $knowledge, $history);
        return [$answer, 'deepseek'];
    }

    private function askDeepSeek(string $userMessage, string $knowledge, array $history = []): string
    {
        $apiKey   = config('services.deepseek.api_key');
        $model    = config('services.deepseek.model', 'deepseek-chat');
        $baseUrl  = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');

        // Xây dựng messages theo chuẩn OpenAI
        $systemPrompt = <<<SYSTEM
Bạn là trợ lý AI thông minh của công ty Vinh Phú. Nhiệm vụ của bạn là trả lời các câu hỏi của nhân viên và khách hàng về công ty dựa trên thông tin được cung cấp bên dưới.

=== THÔNG TIN CÔNG TY ===
{$knowledge}
=== HẾT THÔNG TIN ===

Hướng dẫn trả lời:
1. Chỉ trả lời dựa trên thông tin đã cung cấp ở trên.
2. Nếu câu hỏi không liên quan hoặc không có thông tin, hãy trả lời: "Tôi không có thông tin về vấn đề này. Vui lòng liên hệ bộ phận HCNS để được hỗ trợ."
3. Trả lời bằng tiếng Việt, thân thiện, ngắn gọn và chuyên nghiệp.
4. Không bịa đặt thông tin ngoài dữ liệu đã cung cấp.
SYSTEM;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // Thêm lịch sử hội thoại (role 'model' của Gemini → 'assistant' của OpenAI)
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $msg['text'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->post("{$baseUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.3,
                'max_tokens'  => 1024,
            ]);

        if ($response->failed()) {
            $code = $response->json()['error']['code'] ?? $response->status();
            Log::error('[AiChat] DeepSeek API error: ' . $response->body());
            return 'Xin lỗi, DeepSeek không khả dụng lúc này (lỗi ' . $code . '). Vui lòng thử lại sau.';
        }

        return $response->json()['choices'][0]['message']['content']
            ?? 'Không nhận được phản hồi từ AI. Vui lòng thử lại.';
    }
}
