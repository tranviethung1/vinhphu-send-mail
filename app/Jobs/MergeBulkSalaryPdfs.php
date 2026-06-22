<?php

namespace App\Jobs;

use App\Models\SalaryFile;
use App\Models\TemplateSelection;
use App\Models\SalaryBulkExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class MergeBulkSalaryPdfs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalaryFile $file;
    public TemplateSelection $template;
    public array $rows;
    public string $batchId;
    public int|string $bulkExportId;

    public $timeout = 600;

    public function __construct(SalaryFile $file, TemplateSelection $template, array $rows, string $batchId, int|string $bulkExportId)
    {
        $this->file = $file;
        $this->template = $template;
        $this->rows = $rows;
        $this->batchId = $batchId;
        $this->bulkExportId = $bulkExportId;
    }

    private function getStorageDisk()
    {
        $diskName = env('FILESYSTEM_DISK', 'local');
        return Storage::disk($diskName);
    }

    public function handle(): void
    {
        $file = $this->file;
        $template = $this->template;
        $batchId = $this->batchId;
        
        Log::info('MergeBulkSalaryPdfs - Bắt đầu merge', [
            'salary_file_id' => $file->id,
            'template_id' => $template->id,
            'batch_id' => $batchId,
            'total_rows' => count($this->rows),
        ]);

        $tempDir = 'salary-bulk-temp/' . $batchId;
        $storageDisk = $this->getStorageDisk();
        
        if (!$storageDisk->exists($tempDir)) {
            Log::error('MergeBulkSalaryPdfs - temp directory not found', [
                'salary_file_id' => $file->id,
                'batch_id' => $batchId,
            ]);
            return;
        }

        // Tạo ZIP
        $zipPath = tempnam(sys_get_temp_dir(), 'salary_pdfs_');
        if ($zipPath === false) {
            Log::error('MergeBulkSalaryPdfs - cannot create temp file', ['salary_file_id' => $file->id]);
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);
            Log::error('MergeBulkSalaryPdfs - cannot open temp zip', ['salary_file_id' => $file->id]);
            return;
        }

        try {
            // Lấy tất cả PDFs từ temp directory
            $pdfFiles = $storageDisk->files($tempDir);
            $pdfCount = 0;
            
            foreach ($pdfFiles as $pdfPath) {
                // Chỉ lấy file .pdf
                if (!str_ends_with(strtolower($pdfPath), '.pdf')) {
                    continue;
                }
                
                $pdfContent = $storageDisk->get($pdfPath);
                $fileName = basename($pdfPath);
                $zip->addFromString($fileName, $pdfContent);
                $pdfCount++;
            }
            
            Log::info('MergeBulkSalaryPdfs - merged PDFs', [
                'salary_file_id' => $file->id,
                'batch_id' => $batchId,
                'pdf_count' => $pdfCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('MergeBulkSalaryPdfs - error while building zip', [
                'salary_file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        $zip->close();

        // Lưu ZIP vào storage
        $exportName = 'salary_pdfs_' . $file->id . '_' . now()->format('Ymd_His') . '.zip';
        $storageDir = 'salary-bulk';
        if (!$storageDisk->exists($storageDir)) {
            $storageDisk->makeDirectory($storageDir);
        }
        $storageRelative = $storageDir . '/' . $exportName;

        $zipContent = @file_get_contents($zipPath);
        if ($zipContent === false) {
            @unlink($zipPath);
            Log::error('MergeBulkSalaryPdfs - cannot read temp zip', ['salary_file_id' => $file->id]);
            return;
        }

        $storageDisk->put($storageRelative, $zipContent);
        @unlink($zipPath);

        // Cleanup temp directory
        try {
            $allFiles = $storageDisk->allFiles($tempDir);
            foreach ($allFiles as $filePath) {
                $storageDisk->delete($filePath);
            }
            // Xóa thư mục nếu có thể
            if (method_exists($storageDisk, 'deleteDirectory')) {
                $storageDisk->deleteDirectory($tempDir);
            }
        } catch (\Throwable $e) {
            Log::warning('MergeBulkSalaryPdfs - cannot cleanup temp directory', [
                'salary_file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Cố gắng set quyền thư mục + file để process web có thể đọc được (chỉ cho local disk)
        try {
            $diskName = env('FILESYSTEM_DISK', 'local');
            if ($diskName === 'local') {
                $absoluteStoragePath = method_exists($storageDisk, 'path') ? $storageDisk->path($storageRelative) : storage_path('app/private/' . $storageRelative);
                @chmod(dirname($absoluteStoragePath), 0755);
                @chmod($absoluteStoragePath, 0644);
            }
        } catch (\Throwable $e) {
            Log::warning('MergeBulkSalaryPdfs - cannot chmod storage file', [
                'salary_file_id' => $file->id,
                'relative' => $storageRelative,
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Sao chép thêm một bản ra project root để WSL thấy trực tiếp (không dùng cho download)
        $exportDir = base_path('bulk-exports');
        if (!is_dir($exportDir)) {
            @mkdir($exportDir, 0755, true);
        }
        $absoluteHostTarget = rtrim($exportDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $exportName;
        @file_put_contents($absoluteHostTarget, $zipContent);

        // Cập nhật lịch sử export
        try {
            SalaryBulkExport::where('id', $this->bulkExportId)->update([
                'file_path' => $storageRelative,
                'status' => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::error('MergeBulkSalaryPdfs - cannot persist history', [
                'salary_file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }

        $logPath = null;
        try {
            $diskName = env('FILESYSTEM_DISK', 'local');
            if ($diskName === 'local') {
                $logPath = $storageDisk->path($storageRelative);
            } else {
                $logPath = 'gs://' . env('GCS_BUCKET', 'mail-app-files') . '/' . $storageRelative;
            }
        } catch (\Exception $e) {
            $logPath = $storageRelative;
        }

        Log::info('MergeBulkSalaryPdfs - Hoàn thành merge', [
            'salary_file_id' => $file->id,
            'template_id' => $template->id,
            'export_path' => $logPath,
            'pdf_count' => $pdfCount,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('MergeBulkSalaryPdfs - failed', [
            'salary_file_id' => $this->file->id,
            'error' => $exception->getMessage(),
        ]);
        SalaryBulkExport::where('id', $this->bulkExportId)->update(['status' => 'fail']);
    }
}

