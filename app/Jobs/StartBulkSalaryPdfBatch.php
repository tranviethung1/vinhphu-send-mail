<?php

namespace App\Jobs;

use App\Models\SalaryBulkExport;
use App\Models\SalaryFile;
use App\Models\TemplateSelection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class StartBulkSalaryPdfBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalaryFile $file;
    public TemplateSelection $template;
    public array $rows;
    public string $batchId;
    public int|string $bulkExportId;

    public $timeout = 120;

    public function __construct(
        SalaryFile $file,
        TemplateSelection $template,
        array $rows,
        string $batchId,
        int|string $bulkExportId
    ) {
        $this->file = $file;
        $this->template = $template;
        $this->rows = $rows;
        $this->batchId = $batchId;
        $this->bulkExportId = $bulkExportId;
    }

    public function handle(): void
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $file = $this->file;
        $template = $this->template;
        $batchId = $this->batchId;
        $bulkExportId = $this->bulkExportId;
        $rows = $this->rows;

        $chunkSize = 10;
        $chunks = array_chunk($rows, $chunkSize);
        $jobs = [];

        foreach ($chunks as $chunk) {
            $jobs[] = new GenerateSingleSalaryPdf($file, $template, $chunk, $batchId);
        }

        $batch = Bus::batch($jobs)
            ->name("Generate PDFs for Salary File #{$file->id}")
            ->allowFailures()
            ->then(function () use ($file, $template, $rows, $batchId, $bulkExportId) {
                MergeBulkSalaryPdfs::dispatch($file, $template, $rows, $batchId, $bulkExportId);
            })
            ->catch(function (\Throwable $e) use ($file, $batchId, $bulkExportId) {
                Log::error('Batch failed for Generate PDFs', [
                    'salary_file_id' => $file->id,
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
                SalaryBulkExport::where('id', $bulkExportId)->update(['status' => 'fail']);
            })
            ->finally(function () use ($file, $batchId) {
                Log::info('Batch finished for Generate PDFs', [
                    'salary_file_id' => $file->id,
                    'batch_id' => $batchId,
                ]);
            })
            ->dispatch();

        Log::info('Đã dispatch batch job tạo PDF hàng loạt', [
            'salary_file_id' => $file->id,
            'template_id' => $template->id,
            'total_rows' => count($rows),
            'total_jobs' => count($jobs),
            'batch_id' => $batch->id,
            'batch_uuid' => $batchId,
            'rows_sample' => array_slice($rows, 0, 5),
        ]);
    }
}
