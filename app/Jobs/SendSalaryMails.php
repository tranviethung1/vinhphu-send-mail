<?php

namespace App\Jobs;

use App\Models\SalaryFile;
use App\Models\TemplateSelection;
use App\Models\MailList;
use App\Mail\SalaryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SendSalaryMails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalaryFile $salaryFile;
    public TemplateSelection $template;
    public MailList $mailList;

    /**
     * Timeout cho job (10 phút)
     */
    public $timeout = 600;

    /**
     * Số lần thử lại khi fail
     */
    public $tries = 5;

    /**
     * Số giây chờ giữa mỗi lần thử lại (lần 1: 60s, lần 2: 120s, lần 3: 300s, ...)
     */
    public $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(SalaryFile $salaryFile, TemplateSelection $template, MailList $mailList)
    {
        $this->salaryFile = $salaryFile;
        $this->template = $template;
        $this->mailList = $mailList;
    }

    /**
     * Lấy disk động dựa trên env FILESYSTEM_DISK
     */
    private function getStorageDisk()
    {
        $diskName = env('FILESYSTEM_DISK', 'local');
        return Storage::disk($diskName);
    }

    /**
     * Normalize file path - loại bỏ các prefix không cần thiết
     */
    private function normalizeFilePath($filePath)
    {
        if (empty($filePath)) {
            return $filePath;
        }
        
        $path = trim((string)$filePath);
        
        // Loại bỏ prefix storage/app/private/ trước (dài hơn)
        if (str_starts_with($path, 'storage/app/private/')) {
            $path = substr($path, strlen('storage/app/private/'));
        }
        // Loại bỏ prefix storage/app/ nếu có
        elseif (str_starts_with($path, 'storage/app/')) {
            $path = substr($path, strlen('storage/app/'));
        }
        
        return ltrim($path, '/');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $salaryFile = $this->salaryFile;
        $template = $this->template;
        $mailList = $this->mailList;

        Log::info('SendSalaryMails - Bắt đầu gửi mail', [
            'salary_file_id' => $salaryFile->id,
            'template_id' => $template->id,
            'mail_list_id' => $mailList->id,
        ]);

        // Kiểm tra template
        if (empty($template->file_name)) {
            Log::error('SendSalaryMails - template has no file_name', [
                'salary_file_id' => $salaryFile->id,
                'template_id' => $template->id,
            ]);
            return;
        }

        $templatePath = 'templates/' . $template->file_name;
        $templateDisk = $this->getStorageDisk();
        if (!$templateDisk->exists($templatePath)) {
            Log::error('SendSalaryMails - template file not found', [
                'salary_file_id' => $salaryFile->id,
                'path' => $templatePath,
            ]);
            return;
        }

        // Đọc dữ liệu lương
        Log::debug('📧 SendSalaryMails - Đang đọc dữ liệu lương...');
        $salaryData = $this->loadSalaryData($salaryFile);
        if (empty($salaryData)) {
            Log::error('📧 SendSalaryMails - salary data empty', [
                'salary_file_id' => $salaryFile->id,
            ]);
            return;
        }
        Log::debug('📧 SendSalaryMails - Đã đọc được dữ liệu lương', [
            'total_rows' => count($salaryData),
        ]);

        // Lấy danh sách email từ mail list
        $mailRows = $mailList->rows ?? [];
        $headers = $mailList->headers ?? [];

        Log::debug('📧 SendSalaryMails - Thông tin mail list', [
            'headers' => $headers,
            'total_mail_rows' => count($mailRows),
            'sample_rows' => array_slice($mailRows, 0, 2),
        ]);

        // Tìm index cột email và tên
        $emailColIndex = $this->findColumnIndex($headers, ['email', 'mail', 'e-mail']);
        $nameColIndex = $this->findColumnIndex($headers, ['tên', 'name', 'họ và tên', 'ho va ten', 'họ tên']);

        Log::debug('📧 SendSalaryMails - Index cột', [
            'emailColIndex' => $emailColIndex,
            'nameColIndex' => $nameColIndex,
        ]);

        if ($emailColIndex === null) {
            Log::error('📧 SendSalaryMails - không tìm thấy cột email trong mail list', [
                'mail_list_id' => $mailList->id,
                'headers' => $headers,
            ]);
            return;
        }

        $successCount = 0;
        $failCount = 0;

        Log::info('📧 SendSalaryMails - Bắt đầu vòng lặp gửi mail', [
            'total_to_process' => count($mailRows),
        ]);

        foreach ($mailRows as $idx => $row) {
            $email = trim($row[$emailColIndex] ?? '');
            $name = $nameColIndex !== null ? trim($row[$nameColIndex] ?? '') : '';

            Log::debug("📧 SendSalaryMails - Xử lý dòng {$idx}", [
                'email' => $email,
                'name' => $name,
                'row_data' => $row,
            ]);

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning("📧 SendSalaryMails - Bỏ qua dòng {$idx}: email không hợp lệ", ['email' => $email]);
                $failCount++;
                continue;
            }

            // Tìm hàng dữ liệu lương theo tên
            $salaryRowNumber = $this->findSalaryRowByName($salaryData, $name);
            Log::debug("📧 SendSalaryMails - Kết quả tìm kiếm lương cho '{$name}'", [
                'salary_row_number' => $salaryRowNumber,
            ]);

            if ($salaryRowNumber === null) {
                Log::warning("📧 SendSalaryMails - Không tìm thấy dữ liệu lương cho: {$name}", [
                    'email' => $email,
                ]);
                $failCount++;
                continue;
            }

            try {
                Log::debug("📧 SendSalaryMails - Đang tạo PDF cho '{$name}' (row {$salaryRowNumber})...");
                
                // Tạo PDF cho nhân viên này
                $pdfContent = $this->generatePdfContentForRow($salaryFile, $template, $salaryData, $salaryRowNumber);

                Log::debug("📧 SendSalaryMails - PDF đã tạo, size: " . strlen($pdfContent) . " bytes");

                // Gửi email
                $fileName = $this->sanitizeFileName($name) . '_' . ($salaryFile->month ?? 'salary') . '.pdf';
                
                Log::debug("📧 SendSalaryMails - Đang gửi mail đến {$email}...");
                
                Mail::to($email)->send(new SalaryMail(
                    $name,
                    $salaryFile->name,
                    $salaryFile->month,
                    $pdfContent,
                    $fileName,
                    $template->type ?? 'salary',
                    $template->description ?? null,
                    $template->name ?? null
                ));

                // Kiểm tra xem mail có gửi thất bại không
                $failures = Mail::failures();
                if (!empty($failures)) {
                    Log::error("📧 SendSalaryMails - ❌ Mail gửi thất bại cho {$name}", [
                        'email' => $email,
                        'failures' => $failures,
                        'recipient' => $email,
                    ]);
                    $failCount++;
                } else {
                    Log::info("📧 SendSalaryMails - ✅ Mail đã gửi thành công đến {$email}", [
                        'name' => $name,
                        'email' => $email,
                        'file_name' => $fileName,
                        'pdf_size' => strlen($pdfContent) . ' bytes',
                    ]);
                    $successCount++;
                }

            } catch (\Exception $e) {
                $failCount++;
                Log::error("SendSalaryMails - Lỗi gửi mail cho {$name}", [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SendSalaryMails - Hoàn thành', [
            'salary_file_id' => $salaryFile->id,
            'success' => $successCount,
            'fail' => $failCount,
        ]);
    }

    /**
     * Tìm index cột theo tên header
     */
    private function findColumnIndex(array $headers, array $keywords): ?int
    {
        foreach ($headers as $idx => $header) {
            $headerLower = strtolower(trim((string)$header));
            foreach ($keywords as $keyword) {
                if (str_contains($headerLower, strtolower($keyword))) {
                    return $idx;
                }
            }
        }
        return null;
    }

    /**
     * Load dữ liệu lương từ file
     */
    private function loadSalaryData(SalaryFile $file): array
    {
        $pathToRead = $file->salary_sheet_path ?? $file->file_path;

        $disk = $this->getStorageDisk();
        $diskName = env('FILESYSTEM_DISK', 'local');
        $normalizedPath = $this->normalizeFilePath($pathToRead);

        if (!$pathToRead || !$disk->exists($normalizedPath)) {
            return [];
        }

        try {
            // Nếu là local disk, lấy absolute path
            if ($diskName === 'local') {
                try {
                    $fullPath = $disk->path($normalizedPath);
                } catch (\Exception $e) {
                    // Fallback: download từ GCS về temp file
                    $fileContent = $disk->get($normalizedPath);
                    $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
                    file_put_contents($tempFile, $fileContent);
                    $fullPath = $tempFile;
                }
            } else {
                // GCS: download về temp file
                $fileContent = $disk->get($normalizedPath);
                $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
                file_put_contents($tempFile, $fileContent);
                $fullPath = $tempFile;
            }
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestDataRow();
            $highestCol = $worksheet->getHighestDataColumn();
            $highestColIndex = Coordinate::columnIndexFromString($highestCol);

            $data = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellAddress);
                    $value = $cell->getFormattedValue();
                    $rowData[] = ($value === null || trim((string)$value) === '') ? '' : $value;
                }
                $data[] = $rowData;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('SendSalaryMails - Lỗi đọc file lương: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tìm hàng lương theo tên (normalize và so sánh)
     */
    private function findSalaryRowByName(array $data, string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $normalizedName = $this->normalizeName($name);

        // Tìm cột tên trong bảng lương (thường là cột B hoặc cột có header chứa "tên")
        $header = $data[0] ?? [];
        $nameColIndex = 1; // Mặc định cột B

        foreach ($header as $idx => $title) {
            $titleStr = strtolower(trim((string)$title));
            if (str_contains($titleStr, 'tên') || str_contains($titleStr, 'name') || str_contains($titleStr, 'họ và tên')) {
                $nameColIndex = $idx;
                break;
            }
        }

        // Tìm hàng có tên khớp
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 0) continue; // Bỏ qua header

            $rowName = isset($row[$nameColIndex]) ? trim((string)$row[$nameColIndex]) : '';
            if (empty($rowName)) continue;

            $normalizedRowName = $this->normalizeName($rowName);

            if ($normalizedName === $normalizedRowName) {
                return $rowIndex + 1; // Trả về số hàng Excel (1-based)
            }
        }

        return null;
    }

    /**
     * Chuẩn hóa tên để so sánh
     */
    private function normalizeName(string $name): string
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        return $name;
    }

    /**
     * Tạo tên file an toàn
     */
    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $name);
        $name = preg_replace('/\s+/', '_', $name);
        return $name ?: 'salary';
    }

    /**
     * Tạo PDF content cho 1 dòng dữ liệu
     */
    private function generatePdfContentForRow(SalaryFile $file, TemplateSelection $template, array $salaryData, int $rowNumber): string
    {
        $templatePath = 'templates/' . $template->file_name;
        $templateDisk = $this->getStorageDisk();
        // For GCS, we need to download to temp file first
        $diskName = env('FILESYSTEM_DISK', 'local');
        if ($diskName === 'gcs') {
            $tempPath = tempnam(sys_get_temp_dir(), 'template_');
            file_put_contents($tempPath, $templateDisk->get($templatePath));
            $fullTemplatePath = $tempPath;
        } else {
            $fullTemplatePath = $templateDisk->path($templatePath);
        }

        $spreadsheet = IOFactory::load($fullTemplatePath);
        $sheetCount = $spreadsheet->getSheetCount();

        if ($sheetCount < 2) {
            throw new \Exception('Template không có Sheet 2 để in.');
        }

        $salarySheet1 = $spreadsheet->getSheet(0);
        $printSheet = $spreadsheet->getSheet(1);

        // Lấy dữ liệu từ hàng $rowNumber (0-based index trong array)
        $rowIndex = $rowNumber - 1;
        $rowData = $salaryData[$rowIndex] ?? [];

        // Ghi dữ liệu vào Sheet 1 của template (hàng 2)
        foreach ($rowData as $colIndex => $value) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $cellAddress = $colLetter . '2';

            $cell = $salarySheet1->getCell($cellAddress);
            if (is_numeric($value) && strpos($value, '.') === false && strlen($value) < 15) {
                $cell->setValueExplicit((int)$value, DataType::TYPE_NUMERIC);
            } else {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
            }
        }

        // Tính toán lại công thức
        $spreadsheet->getCalculationEngine()?->clearCalculationCache();

        // Thiết lập in ấn cho Sheet 2
        $printSheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $printSheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $printSheet->getPageSetup()->setFitToPage(true);
        $printSheet->getPageSetup()->setFitToWidth(1);
        $printSheet->getPageSetup()->setFitToHeight(0);

        // Xóa các sheet khác, chỉ giữ sheet in
        $spreadsheet->setActiveSheetIndex(1);
        for ($i = $sheetCount - 1; $i >= 0; $i--) {
            if ($i !== 1) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }

        // Xuất PDF
        $tempFile = sys_get_temp_dir() . '/salary_' . uniqid() . '.pdf';

        $writer = IOFactory::createWriter($spreadsheet, 'Mpdf');
        $writer->save($tempFile);

        $pdfContent = file_get_contents($tempFile);
        unlink($tempFile);

        return $pdfContent;
    }
}
