<?php

namespace App\Jobs;

use App\Models\SalaryBulkExport;
use App\Mail\SalaryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class SendBulkExportMails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalaryBulkExport $export;
    public array $recipients;

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
    public function __construct(SalaryBulkExport $export, array $recipients)
    {
        $this->export = $export;
        $this->recipients = $recipients;
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
     * Execute the job.
     */
    public function handle(): void
    {
        $export = $this->export;
        $salaryFile = $export->salaryFile;

        $templateType = $salaryFile->templateSelection->type ?? 'salary';

        Log::info('SendBulkExportMails - Bắt đầu gửi mail', [
            'export_id' => $export->id,
            'salary_file_id' => $salaryFile->id,
            'type' => $templateType,
            'total_recipients' => count($this->recipients),
        ]);

        // Log start to DB
        $export->logs()->create([
            'status' => 'info',
            'message' => 'Bắt đầu gửi mail',
            'details' => [
                'total_recipients' => count($this->recipients),
                'salary_file' => $salaryFile->name,
            ],
        ]);

        // Lấy đường dẫn ZIP file và mở file
        $zip = $this->openZipFile($export);
        if (!$zip) {
            $errorMsg = 'File không tồn tại trên server. Export ID: ' . $export->id . ', File path: ' . $export->file_path;
            Log::error('SendBulkExportMails - Cannot open ZIP file', [
                'export_id' => $export->id,
                'file_path' => $export->file_path,
            ]);
            
            $export->logs()->create([
                'status' => 'error',
                'message' => 'Không thể mở file ZIP',
                'details' => ['path' => $export->file_path],
            ]);
            
            throw new \Exception($errorMsg);
        }

        $successCount = 0;
        $failCount = 0;

        // Debug: List tất cả files trong ZIP
        $zipFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zipFiles[] = $zip->getNameIndex($i);
        }
        Log::debug('SendBulkExportMails - Files in ZIP', [
            'total_files' => count($zipFiles),
            'files' => $zipFiles,
        ]);

        try {
            foreach ($this->recipients as $recipient) {
                $email = $recipient['email'] ?? '';
                $name = $recipient['name'] ?? '';
                $index = $recipient['index'] ?? null;

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('SendBulkExportMails - Invalid email', ['email' => $email]);
                    
                    $export->logs()->create([
                        'status' => 'warning',
                        'email' => $email,
                        'name' => $name,
                        'message' => 'Email không hợp lệ hoặc bị bỏ trống',
                    ]);
                    
                    $failCount++;
                    continue;
                }

                try {
                    // Tìm PDF file tương ứng với tên người nhận và index
                    $pdfFileName = $this->findPdfInZip($zip, $name, $index);
                    
                    if (!$pdfFileName) {
                        Log::warning('SendBulkExportMails - PDF not found for recipient', [
                            'name' => $name,
                            'email' => $email,
                            'index' => $index,
                            'normalized_name' => $this->normalizeName($name),
                        ]);
                        
                        $export->logs()->create([
                            'status' => 'warning',
                            'email' => $email,
                            'name' => $name,
                            'message' => 'Không tìm thấy file PDF',
                            'details' => [
                                'index' => $index,
                                'normalized_name' => $this->normalizeName($name),
                            ],
                        ]);
                        
                        $failCount++;
                        continue;
                    }

                    // Đọc nội dung PDF từ ZIP
                    $pdfContent = $zip->getFromName($pdfFileName);
                    if ($pdfContent === false) {
                        Log::error('SendBulkExportMails - Cannot read PDF from ZIP', [
                            'pdf_file' => $pdfFileName,
                        ]);
                        
                        $export->logs()->create([
                            'status' => 'error',
                            'email' => $email,
                            'name' => $name,
                            'message' => 'Lỗi đọc file PDF từ ZIP',
                            'details' => ['file' => $pdfFileName],
                        ]);
                        
                        $failCount++;
                        continue;
                    }

                    // Tạo tên file PDF cho attachment
                    $attachmentName = $this->sanitizeFileName($name) . '_' . ($salaryFile->month ?? 'salary') . '.pdf';

                    // Gửi email
                    Log::debug("SendBulkExportMails - Sending mail to {$email}");
                    
                    Mail::to($email)->send(new SalaryMail(
                        $name,
                        $salaryFile->name,
                        $salaryFile->month,
                        $pdfContent,
                        $attachmentName,
                        $templateType,
                        $salaryFile->templateSelection->description ?? null,
                        $salaryFile->templateSelection->name ?? null
                    ));

                    $successCount++;
                    Log::info("SendBulkExportMails - ✅ Email sent successfully", [
                        'name' => $name,
                        'email' => $email,
                    ]);
                    
                    $export->logs()->create([
                        'status' => 'success',
                        'email' => $email,
                        'name' => $name,
                        'message' => 'Đã gửi email thành công',
                        'details' => ['attachment' => $attachmentName],
                    ]);

                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("SendBulkExportMails - Error sending mail", [
                        'name' => $name,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $export->logs()->create([
                        'status' => 'error',
                        'email' => $email,
                        'name' => $name,
                        'message' => 'Lỗi khi gửi mail: ' . $e->getMessage(),
                    ]);
                }
            }
        } finally {
            $zip->close();
            // Xóa file tạm nếu có (khi download từ GCS)
            if (isset($this->tempZipPath) && file_exists($this->tempZipPath)) {
                @unlink($this->tempZipPath);
            }
        }

        Log::info('SendBulkExportMails - Hoàn thành', [
            'export_id' => $export->id,
            'success' => $successCount,
            'fail' => $failCount,
        ]);
        
        $export->logs()->create([
            'status' => 'info',
            'message' => 'Hoàn thành gửi mail',
            'details' => [
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ],
        ]);
    }

    /**
     * Temp file path cho ZIP file khi download từ GCS
     */
    private ?string $tempZipPath = null;

    /**
     * Mở ZIP file từ nhiều nguồn (local, GCS, bulk-exports)
     */
    private function openZipFile(SalaryBulkExport $export): ?ZipArchive
    {
        $rawPath = trim((string)$export->file_path);
        
        Log::debug('SendBulkExportMails - openZipFile', [
            'export_id' => $export->id,
            'raw_path' => $rawPath,
        ]);

        // Trường hợp 1: File trong bulk-exports/ (dùng base_path)
        if (str_contains($rawPath, 'bulk-exports/')) {
            $pos = strpos($rawPath, 'bulk-exports/');
            $relative = substr($rawPath, $pos); // bulk-exports/xxx.zip
            $absolute = base_path($relative);

            Log::debug('SendBulkExportMails - bulk-exports branch', [
                'relative' => $relative,
                'absolute' => $absolute,
                'is_file' => is_file($absolute),
            ]);

            if (!is_file($absolute)) {
                Log::error('SendBulkExportMails - File không tồn tại trong bulk-exports', [
                    'path' => $absolute,
                ]);
                return null;
            }

            $zip = new ZipArchive();
            if ($zip->open($absolute) !== true) {
                Log::error('SendBulkExportMails - Cannot open ZIP from bulk-exports', [
                    'path' => $absolute,
                ]);
                return null;
            }

            return $zip;
        }

        // Trường hợp 2: File trong storage disk (storage/app/private/salary-bulk/... hoặc GCS)
        $disk = $this->getStorageDisk();
        
        // Xử lý các định dạng path khác nhau
        $path = $rawPath;
        
        // Loại bỏ prefix storage/app/private/ trước (dài hơn)
        if (str_starts_with($path, 'storage/app/private/')) {
            $path = substr($path, strlen('storage/app/private/'));
        }
        // Loại bỏ prefix storage/app/ nếu có
        elseif (str_starts_with($path, 'storage/app/')) {
            $path = substr($path, strlen('storage/app/'));
        }
        
        // Tìm salary-bulk/ trong path
        $pos = strpos($path, 'salary-bulk/');
        if ($pos !== false) {
            $path = substr($path, $pos);
        } else {
            $path = ltrim($path, '/');
        }

        // Remove any hidden control characters
        $path = preg_replace('/[[:cntrl:]]+/', '', (string)$path);

        Log::debug('SendBulkExportMails - local disk branch', [
            'normalized_path' => $path,
            'exists' => $disk->exists($path),
        ]);

        if ($disk->exists($path)) {
            // Kiểm tra xem có phải local filesystem không
            try {
                $fullPath = $disk->path($path);
                if (is_file($fullPath)) {
                    $zip = new ZipArchive();
                    if ($zip->open($fullPath) === true) {
                        return $zip;
                    }
                }
            } catch (\Exception $e) {
                // Nếu không phải local filesystem (ví dụ GCS), sẽ download về temp file
                Log::debug('SendBulkExportMails - Not local filesystem, downloading to temp', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Download từ GCS về temp file
            try {
                $zipContent = $disk->get($path);
                if ($zipContent === false) {
                    Log::error('SendBulkExportMails - Cannot read file from storage', [
                        'path' => $path,
                    ]);
                    return null;
                }

                // Tạo temp file
                $this->tempZipPath = tempnam(sys_get_temp_dir(), 'zip_');
                file_put_contents($this->tempZipPath, $zipContent);

                $zip = new ZipArchive();
                if ($zip->open($this->tempZipPath) === true) {
                    Log::info('SendBulkExportMails - Downloaded ZIP from GCS to temp file', [
                        'temp_path' => $this->tempZipPath,
                    ]);
                    return $zip;
                } else {
                    @unlink($this->tempZipPath);
                    $this->tempZipPath = null;
                    Log::error('SendBulkExportMails - Cannot open temp ZIP file');
                    return null;
                }
            } catch (\Exception $e) {
                Log::error('SendBulkExportMails - Error downloading file from storage', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }

        Log::error('SendBulkExportMails - File không tồn tại trên server', [
            'raw_path' => $rawPath,
            'normalized_path' => $path,
        ]);
        return null;
    }

    /**
     * Tìm PDF file trong ZIP dựa trên tên người nhận và index (STT)
     */
    private function findPdfInZip(ZipArchive $zip, string $name, ?string $index = null): ?string
    {
        // Nếu có index, tìm chính xác theo format giống lúc tạo file
        if ($index !== null && !empty($name)) {
            // Format giống GenerateBulkSalaryPdfs: STT.slug_name.pdf
            $sttFormatted = str_pad((string)$index, 3, '0', STR_PAD_LEFT); // 096
            $normalizedName = Str::slug($name, '_'); // pham_van_thuong
            $expectedFileName = $sttFormatted . '.' . $normalizedName . '.pdf'; // 096.pham_van_thuong.pdf
            
            Log::debug('SendBulkExportMails - Tìm file chính xác theo STT và tên', [
                'search_name' => $name,
                'index' => $index,
                'stt_formatted' => $sttFormatted,
                'normalized_name' => $normalizedName,
                'expected_file' => $expectedFileName,
            ]);
            
            // Tìm file chính xác trong ZIP
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                
                // So sánh tên file (case insensitive)
                if (strcasecmp(basename($fileName), $expectedFileName) === 0) {
                    Log::info('SendBulkExportMails - ✅ Tìm thấy file chính xác', [
                        'expected' => $expectedFileName,
                        'found' => $fileName,
                    ]);
                    return $fileName;
                }
            }
            
            Log::warning('SendBulkExportMails - ⚠️ Không tìm thấy file chính xác, fallback sang tìm fuzzy', [
                'expected_file' => $expectedFileName,
            ]);
        }
        
        // Fallback: Tìm theo tên nếu không có index hoặc không tìm thấy file chính xác
        $normalizedSearchName = $this->normalizeName($name);
        
        Log::debug('SendBulkExportMails - Tìm file theo tên (fuzzy matching)', [
            'search_name' => $name,
            'normalized_search' => $normalizedSearchName,
        ]);
        
        $candidates = [];
        $bestMatch = null;
        $bestSimilarity = 0;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            
            // Bỏ qua thư mục
            if (substr($fileName, -1) === '/') {
                continue;
            }
            
            // Chỉ xử lý file PDF
            if (!str_ends_with(strtolower($fileName), '.pdf')) {
                continue;
            }
            
            // Lấy tên file không có extension và đường dẫn
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            
            // Loại bỏ số thứ tự đầu file (vd: "001.", "002.")
            $cleanedBaseName = preg_replace('/^\d+\./', '', $baseName);
            
            $normalizedFileName = $this->normalizeName($cleanedBaseName);
            
            // Tính similarity
            similar_text($normalizedSearchName, $normalizedFileName, $similarity);
            
            $candidates[] = [
                'file' => $fileName,
                'base' => $baseName,
                'cleaned' => $cleanedBaseName,
                'normalized' => $normalizedFileName,
                'similarity' => round($similarity, 2),
            ];
            
            // Track best match
            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestMatch = $fileName;
            }
            
            // So sánh tên với nhiều strategies
            // 1. Khớp chính xác
            if ($normalizedFileName === $normalizedSearchName) {
                Log::debug('SendBulkExportMails - Found exact match', ['file' => $fileName]);
                return $fileName;
            }
            
            // 2. Tên file chứa tên tìm kiếm
            if (str_contains($normalizedFileName, $normalizedSearchName)) {
                Log::debug('SendBulkExportMails - Found partial match (contains)', ['file' => $fileName]);
                return $fileName;
            }
            
            // 3. Tên tìm kiếm chứa tên file
            if (str_contains($normalizedSearchName, $normalizedFileName)) {
                Log::debug('SendBulkExportMails - Found partial match (contained)', ['file' => $fileName]);
                return $fileName;
            }
        }
        
        // Sắp xếp candidates theo similarity để log top matches
        usort($candidates, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        $topMatches = array_slice($candidates, 0, 5);
        
        Log::warning('SendBulkExportMails - No exact match found', [
            'search_name' => $name,
            'normalized_search' => $normalizedSearchName,
            'best_similarity' => round($bestSimilarity, 2),
            'best_match' => $bestMatch,
            'top_5_matches' => $topMatches,
        ]);
        
        // Nếu similarity > 80%, tự động sử dụng best match
        if ($bestSimilarity >= 80) {
            Log::info('SendBulkExportMails - Using fuzzy match', [
                'search_name' => $name,
                'matched_file' => $bestMatch,
                'similarity' => round($bestSimilarity, 2),
            ]);
            return $bestMatch;
        }
        
        return null;
    }

    /**
     * Chuẩn hóa tên để so sánh
     */
    private function normalizeName(string $name): string
    {
        // Loại bỏ ký tự đặc biệt, chỉ giữ chữ cái và số
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/\s+/', '', $name);
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
}
