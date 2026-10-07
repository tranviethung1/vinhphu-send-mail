<?php

namespace App\Jobs;

use App\Models\SalaryFile;
use App\Models\TemplateSelection;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class GenerateSingleSalaryPdf implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalaryFile $file;
    public TemplateSelection $template;
    /** @var int[] */
    public array $rowNumbers;
    public string $tempBatchId; // Dùng tên khác để tránh conflict với Batchable trait

    public $timeout = 300;

    public function __construct(SalaryFile $file, TemplateSelection $template, array $rowNumbers, string $tempBatchId)
    {
        $this->file = $file;
        $this->template = $template;
        $this->rowNumbers = array_values(array_unique(array_map('intval', $rowNumbers)));
        $this->tempBatchId = $tempBatchId;
    }

    private function getStorageDisk()
    {
        $diskName = env('FILESYSTEM_DISK', 'local');
        return Storage::disk($diskName);
    }

    private function normalizeFilePath($filePath)
    {
        if (empty($filePath)) {
            return $filePath;
        }
        
        $path = trim((string)$filePath);
        
        if (str_starts_with($path, 'storage/app/private/')) {
            $path = substr($path, strlen('storage/app/private/'));
        } elseif (str_starts_with($path, 'storage/app/')) {
            $path = substr($path, strlen('storage/app/'));
        }
        
        return ltrim($path, '/');
    }

    public function handle(): void
    {
        set_time_limit((int) $this->timeout);
        ini_set('max_execution_time', (string) $this->timeout);

        if ($this->rowNumbers === []) {
            return;
        }

        $file = $this->file;
        $template = $this->template;

        $salarySheet = $this->loadSalarySheet($file);
        $fullTemplatePath = $this->resolveTemplatePath($template);
        if ($fullTemplatePath === null) {
            return;
        }

        $selections = is_array($template->selections ?? null) ? $template->selections : [];
        $tempDir = 'salary-bulk-temp/' . $this->tempBatchId;
        $storageDisk = $this->getStorageDisk();
        if (!$storageDisk->exists($tempDir)) {
            $storageDisk->makeDirectory($tempDir);
        }

        foreach ($this->rowNumbers as $rowNumber) {
            $this->processRow(
                $file,
                $template,
                max(2, (int) $rowNumber),
                $salarySheet,
                $fullTemplatePath,
                $selections,
                $tempDir,
                $storageDisk
            );
        }
    }

    private function loadSalarySheet(SalaryFile $file)
    {
        $pathToRead = $file->salary_sheet_path ?? $file->file_path;
        $disk = $this->getStorageDisk();
        $normalizedPath = $this->normalizeFilePath($pathToRead);

        if (empty($pathToRead) || !$disk->exists($normalizedPath)) {
            return null;
        }

        $diskName = env('FILESYSTEM_DISK', 'local');
        if ($diskName === 'local') {
            try {
                $fullSalaryPath = $disk->path($normalizedPath);
            } catch (\Exception $e) {
                $fileContent = $disk->get($normalizedPath);
                $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
                file_put_contents($tempFile, $fileContent);
                $fullSalaryPath = $tempFile;
            }
        } else {
            $fileContent = $disk->get($normalizedPath);
            $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
            file_put_contents($tempFile, $fileContent);
            $fullSalaryPath = $tempFile;
        }

        $salarySpreadsheet = IOFactory::load($fullSalaryPath);

        if ($file->salary_sheet_path) {
            return $salarySpreadsheet->getActiveSheet();
        }

        return $salarySpreadsheet->getSheetByName('Bảng lương')
            ?? $salarySpreadsheet->getSheetByName('Bảng Lương')
            ?? $salarySpreadsheet->getSheet(0);
    }

    private function resolveTemplatePath(TemplateSelection $template): ?string
    {
        $templatePath = 'templates/' . $template->file_name;
        $templateDisk = $this->getStorageDisk();
        $diskName = env('FILESYSTEM_DISK', 'local');

        $existsOnCurrentDisk = $templateDisk->exists($templatePath);
        $existsOnLocalDisk = false;
        if ($diskName !== 'local') {
            $existsOnLocalDisk = Storage::disk('local')->exists($templatePath);
        }

        if (!$existsOnCurrentDisk && !$existsOnLocalDisk) {
            Log::error('GenerateSingleSalaryPdf - template file not found', [
                'salary_file_id' => $this->file->id,
                'template_id' => $template->id,
                'path' => $templatePath,
            ]);
            return null;
        }

        if (!$existsOnCurrentDisk && $existsOnLocalDisk) {
            $templateDisk = Storage::disk('local');
            $diskName = 'local';
        }

        if ($diskName === 'local') {
            try {
                return $templateDisk->path($templatePath);
            } catch (\Exception $e) {
                $fileContent = $templateDisk->get($templatePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                return $tempFile;
            }
        }

        $fileContent = $templateDisk->get($templatePath);
        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        file_put_contents($tempFile, $fileContent);

        return $tempFile;
    }

    private function processRow(
        SalaryFile $file,
        TemplateSelection $template,
        int $rowNumber,
        $salarySheet,
        string $fullTemplatePath,
        array $selections,
        string $tempDir,
        $storageDisk
    ): void {
        $employeeName = $salarySheet ? $this->getEmployeeNameFromSalarySheet($salarySheet, $rowNumber) : '';
        if ($employeeName === '' || $this->isHeaderRow($employeeName)) {
            Log::debug('GenerateSingleSalaryPdf - skipped invalid row', [
                'salary_file_id' => $file->id,
                'row' => $rowNumber,
                'name' => $employeeName,
            ]);
            return;
        }

        $stt = $salarySheet ? $this->getSttFromSalarySheet($salarySheet, $rowNumber) : 0;
        if ($stt <= 0) {
            $stt = $rowNumber - 1;
        }

        $pdfContent = $this->generatePdfContentForRow($file, $template, $rowNumber, $salarySheet, $fullTemplatePath, $selections);

        $sttFormatted = str_pad((string) $stt, 3, '0', STR_PAD_LEFT);
        $normalizedName = Str::slug($employeeName, '_');
        $fileName = $sttFormatted . '.' . $normalizedName . '.pdf';
        $safeName = $this->sanitizeFilename($fileName);

        $tempPath = $tempDir . '/' . $safeName;
        $storageDisk->put($tempPath, $pdfContent);

        Log::debug('GenerateSingleSalaryPdf - created PDF', [
            'salary_file_id' => $file->id,
            'row' => $rowNumber,
            'stt' => $stt,
            'name' => $employeeName,
            'filename' => $safeName,
            'temp_batch_id' => $this->tempBatchId,
            'batch_id' => $this->batch()?->id,
        ]);
    }

    // Copy các helper methods từ GenerateBulkSalaryPdfs
    private function generatePdfContentForRow(
        SalaryFile $file, 
        TemplateSelection $template, 
        int $rowNumber,
        $salarySheet = null,
        string $fullTemplatePath = '',
        array $selections = []
    ): string {
        if ($rowNumber < 2) {
            $rowNumber = 2;
        }

        if ($fullTemplatePath === '') {
            $templatePath = 'templates/' . $template->file_name;
            $templateDisk = $this->getStorageDisk();
            $diskName = env('FILESYSTEM_DISK', 'local');
            if ($diskName === 'local') {
                try {
                    $fullTemplatePath = $templateDisk->path($templatePath);
                } catch (\Exception $e) {
                    $fileContent = $templateDisk->get($templatePath);
                    $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                    file_put_contents($tempFile, $fileContent);
                    $fullTemplatePath = $tempFile;
                }
            } else {
                $fileContent = $templateDisk->get($templatePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $fullTemplatePath = $tempFile;
            }
        }

        if (empty($selections)) {
            $selections = is_array($template->selections ?? null) ? $template->selections : [];
        }

        $salaryDataByColumn = [];
        if ($salarySheet !== null) {
            $maxDataRow = $salarySheet->getHighestDataRow();
            if ($rowNumber > $maxDataRow) {
                throw new \RuntimeException('Dòng được chọn không tồn tại trong file lương.');
            }

            foreach ($selections as $sel) {
                $type = $sel['valueType'] ?? $sel['value_type'] ?? null;
                if ($type !== 'cell') continue;
                $col = strtoupper(trim((string)($sel['cellRef'] ?? $sel['cell_ref'] ?? '')));
                if ($col === '' || !preg_match('/^[A-Z]{1,3}$/', $col)) {
                    continue;
                }
                if (array_key_exists($col, $salaryDataByColumn)) {
                    continue;
                }

                $cellAddress = $col . $rowNumber;
                $value = (string)($salarySheet->getCell($cellAddress)->getFormattedValue() ?? '');
                $salaryDataByColumn[$col] = $value;
            }
        }

        $spreadsheet = IOFactory::load($fullTemplatePath);

        $sheetCount = $spreadsheet->getSheetCount();
        if ($sheetCount < 1) {
            throw new \RuntimeException('File Excel không có sheet nào.');
        }

        $sheetIndex = $sheetCount >= 2 ? 1 : 0;
        $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet = $spreadsheet->getActiveSheet();

        if ($logoPath = $this->resolveLogoPath()) {
            try {
                $headerFooter = $sheet->getHeaderFooter();
                $headerFooter->setOddHeader('&L&G');

                $logo = new HeaderFooterDrawing();
                $logo->setName('Logo');
                $logo->setPath($logoPath);
                $logo->setHeight(200);
                $headerFooter->addImage($logo);
            } catch (\Throwable $e) {
                Log::warning('GenerateSingleSalaryPdf - cannot attach logo to header', [
                    'logo_path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($logoPath);
                $drawing->setHeight(200);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(2);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable $e) {
                Log::warning('GenerateSingleSalaryPdf - cannot insert logo on sheet', [
                    'logo_path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($selections as $sel) {
            $address = strtoupper(trim((string)($sel['address'] ?? '')));
            if ($address === '' || !preg_match('/^[A-Z]{1,3}[0-9]{1,7}$/', $address)) {
                continue;
            }

            $type = $sel['valueType'] ?? $sel['value_type'] ?? 'value';
            if ($type === 'formula') {
                continue;
            }

            if ($type === 'cell') {
                $col = strtoupper(trim((string)($sel['cellRef'] ?? $sel['cell_ref'] ?? '')));
                if ($col === '' || !preg_match('/^[A-Z]{1,3}$/', $col)) {
                    continue;
                }

                $rawValue = $salaryDataByColumn[$col] ?? '';
                $normalizedValue = $rawValue;
                if (is_string($rawValue)) {
                    $trimmed = trim($rawValue);
                    if ($trimmed === '' || $trimmed === '-' || strtolower($trimmed) === 'null') {
                        $normalizedValue = 0;
                    } elseif (is_numeric(str_replace([',', ' '], '', $trimmed))) {
                        $normalizedValue = (float)str_replace([',', ' '], '', $trimmed);
                    }
                }

                $sheet->setCellValue($address, $normalizedValue);
            } else {
                $value = (string)($sel['value'] ?? '');
                $sheet->setCellValue($address, $value);
            }
        }

        foreach ($selections as $sel) {
            $address = strtoupper(trim((string)($sel['address'] ?? '')));
            if ($address === '' || !preg_match('/^[A-Z]{1,3}[0-9]{1,7}$/', $address)) {
                continue;
            }

            $type = $sel['valueType'] ?? $sel['value_type'] ?? 'value';
            if ($type !== 'formula') {
                continue;
            }

            $formula = trim((string)($sel['formula'] ?? ''));
            if ($formula === '') {
                continue;
            }
            if ($formula[0] !== '=') {
                $formula = '=' . $formula;
            }

            // Custom xử lý các hàm đặc biệt giống bên controller (preview)
            $normalizedFormula = strtolower(preg_replace('/\s+/', '', $formula));

            // Công thức đặc biệt: =month('mm/yyyy') -> lấy từ SalaryFile.month, format lại thành mm/yyyy
            if ($normalizedFormula === "=month('mm/yyyy')") {
                $monthValue = (string)($file->month ?? '');
                $formattedMonth = '';
                if (preg_match('/^(0[1-9]|1[0-2])-(\d{4})$/', $monthValue, $m)) {
                    $formattedMonth = $m[1] . '/' . $m[2];
                }
                $sheet->setCellValue($address, 'Tháng: ' . $formattedMonth);
                continue;
            }

            if (preg_match("/^=find\\('mm\\/yyyy',([a-z]{1,3}[0-9]{0,7})\\)$/i", $normalizedFormula, $matches)) {
                $rawRef = strtoupper($matches[1]); // Có thể là "A" hoặc "A1"

                if (preg_match('/^[A-Z]{1,3}$/', $rawRef)) {
                    $sourceAddress = $rawRef . $rowNumber;
                } else {
                    $sourceAddress = $rawRef;
                }

                $sourceValue = '';
                try {
                    if (isset($salarySheet) && $salarySheet) {
                        $cell = $salarySheet->getCell($sourceAddress);
                        $sourceValue = (string)($cell->getFormattedValue() ?? '');
                    } elseif (preg_match('/^[A-Z]{1,3}$/', $rawRef) && array_key_exists($rawRef, $salaryDataByColumn)) {
                        $sourceValue = (string)$salaryDataByColumn[$rawRef];
                    }
                } catch (\Throwable $e) {
                }

                // Tìm chuỗi có dạng mm/yyyy hoặc mm-yyyy trong text
                $extracted = '';
                if (is_string($sourceValue) && $sourceValue !== '') {
                    if (preg_match('/\b(0[1-9]|1[0-2])[\/\-](\d{4})\b/u', $sourceValue, $m)) {
                        // Giữ nguyên separator như trong chuỗi gốc (m[0])
                        $extracted = $m[0];
                    }
                }

                // Gán kết quả vào ô template (nếu không bắt được pattern thì để trống)
                $sheet->setCellValue($address, $extracted);
                continue;
            }

            $cellReferences = $this->extractCellReferencesFromFormula($formula);
            foreach ($cellReferences as $cellRef) {
                try {
                    $cell = $sheet->getCell($cellRef);
                    $cellValue = $cell->getValue();
                    $formattedValue = $cell->getFormattedValue();

                    if (is_string($cellValue) && strpos($cellValue, '=') === 0) {
                        continue;
                    }

                    $isNonNumeric = false;
                    if ($cellValue === null || $cellValue === '') {
                        $isNonNumeric = true;
                    } elseif (is_string($formattedValue)) {
                        $trimmed = trim($formattedValue);
                        if ($trimmed === '' || $trimmed === '-' || strtolower($trimmed) === 'null' || !is_numeric(str_replace([',', ' '], '', $trimmed))) {
                            $isNonNumeric = true;
                        }
                    } elseif (!is_numeric($cellValue)) {
                        $isNonNumeric = true;
                    }

                    if ($isNonNumeric) {
                        $sheet->setCellValue($cellRef, 0);
                    }
                } catch (\Throwable $e) {
                    $sheet->setCellValue($cellRef, 0);
                }
            }

            $sheet->setCellValue($address, $formula);

            try {
                $calculated = $sheet->getCell($address)->getCalculatedValue();
                if ($calculated !== null && $calculated !== '') {
                    if (is_numeric($calculated)) {
                        $sheet->setCellValueExplicit($address, (float)$calculated, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValue($address, $calculated);
                    }
                }
            } catch (\Throwable $e) {
                // ignore calc errors; keep formula value
            }
        }

        $this->autoMergeOverflowingCells($sheet);

        $highestRow = $sheet->getHighestDataRow();
        $heightIncreasePt = 5 / 1.3333333;
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowDimension = $sheet->getRowDimension($row);
            $currentHeight = $rowDimension->getRowHeight();
            if ($currentHeight == -1) {
                $currentHeight = 15;
            }
            $rowDimension->setRowHeight($currentHeight + $heightIncreasePt);
        }

        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $indentIncrease = 1;
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                $style = $sheet->getStyle($cellAddress);
                $alignment = $style->getAlignment();

                $horizontal = $alignment->getHorizontal();
                $vertical = $alignment->getVertical() ?? \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM;
                $currentIndent = $alignment->getIndent() ?? 0;

                if ($horizontal === null ||
                    $horizontal === \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_GENERAL ||
                    $horizontal === \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT ||
                    $horizontal === \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER) {
                    $alignment->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $alignment->setVertical($vertical);
                    $alignment->setIndent($currentIndent + $indentIncrease);
                } elseif ($horizontal === \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT) {
                    $alignment->setHorizontal($horizontal);
                    $alignment->setVertical($vertical);
                    $alignment->setIndent($currentIndent + $indentIncrease);
                }
            }
        }

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.5);
        $margins->setBottom(0.5);
        $margins->setLeft(0.4);
        $margins->setRight(0.4);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf($spreadsheet);
        $writer->setSheetIndex($sheetIndex);

        ob_start();
        $writer->save('php://output');
        return (string)ob_get_clean();
    }

    private function resolveLogoPath(): ?string
    {
        $candidates = [
            public_path('logo_vinh_phu.png'),
            storage_path('app/public/logo_vinh_phu.png'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function autoMergeOverflowingCells(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $mergedCellMap = [];
        foreach ($sheet->getMergeCells() as $mergeRange) {
            foreach (\PhpOffice\PhpSpreadsheet\Cell\Coordinate::extractAllCellReferencesInRange($mergeRange) as $cellRef) {
                $mergedCellMap[$cellRef] = $mergeRange;
            }
        }

        $highestRow      = $sheet->getHighestDataRow();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellAddr  = $colLetter . $row;

                if (isset($mergedCellMap[$cellAddr])) continue;

                $value = $sheet->getCell($cellAddr)->getValue();
                if ($value === null || $value === '' || !is_string($value)) continue;

                $style     = $sheet->getStyle($cellAddr);
                $alignment = $style->getAlignment();
                if ($alignment->getWrapText() === true) continue;

                $fontSize       = $style->getFont()->getSize() ?? 11;
                $textWidthChars = mb_strlen($value) * ($fontSize / 11) * 1.15;
                $colWidth       = $sheet->getColumnDimension($colLetter)->getWidth();
                if ($colWidth <= 0) $colWidth = 8.43;

                if ($textWidthChars <= $colWidth) continue;

                $totalWidth = $colWidth;
                $endCol     = $col;

                for ($nextCol = $col + 1; $nextCol <= $highestColIndex; $nextCol++) {
                    $nextLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nextCol);
                    $nextAddr   = $nextLetter . $row;

                    $nextValue = $sheet->getCell($nextAddr)->getValue();
                    if ($nextValue !== null && $nextValue !== '') break;
                    if (isset($mergedCellMap[$nextAddr])) break;

                    $nextWidth = $sheet->getColumnDimension($nextLetter)->getWidth();
                    if ($nextWidth <= 0) $nextWidth = 8.43;

                    $totalWidth += $nextWidth;
                    $endCol      = $nextCol;

                    if ($totalWidth >= $textWidthChars) break;
                }

                if ($endCol > $col) {
                    $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol);
                    $mergeRange   = $colLetter . $row . ':' . $endColLetter . $row;
                    try {
                        $sheet->mergeCells($mergeRange);
                        foreach (\PhpOffice\PhpSpreadsheet\Cell\Coordinate::extractAllCellReferencesInRange($mergeRange) as $ref) {
                            $mergedCellMap[$ref] = $mergeRange;
                        }
                    } catch (\Throwable $e) {}
                }
            }
        }
    }

    private function getSttFromSalarySheet($salarySheet, int $rowNumber): int
    {
        try {
            $sttValue = trim((string)($salarySheet->getCell('A' . $rowNumber)->getValue() ?? ''));
            return is_numeric($sttValue) ? (int)$sttValue : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getEmployeeNameFromSalarySheet($salarySheet, int $rowNumber): string
    {
        try {
            $highestColumn = $salarySheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $nameCol = null;
            
            foreach ([1, 2] as $headerRow) {
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $addr = Coordinate::stringFromColumnIndex($col) . $headerRow;
                    $header = strtolower(trim((string)($salarySheet->getCell($addr)->getFormattedValue() ?? '')));
                    if ($header !== '' && (str_contains($header, 'tên') || str_contains($header, 'name') || str_contains($header, 'họ'))) {
                        $nameCol = $col;
                        break 2;
                    }
                }
            }
            
            if ($nameCol === null) {
                $nameCol = 2;
            }

            $nameAddr = Coordinate::stringFromColumnIndex($nameCol) . $rowNumber;
            $name = trim((string)($salarySheet->getCell($nameAddr)->getFormattedValue() ?? ''));
            return $name;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function isHeaderRow(string $name): bool
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)));
        
        $exactPatterns = [
            'họ và tên',
            'ho va ten',
            'họ tên',
            'ho ten',
            'full name',
            'employee name',
            'tên nhân viên',
            'stt',
            'no.',
            'số tt',
        ];
        
        foreach ($exactPatterns as $pattern) {
            if ($normalized === $pattern) {
                return true;
            }
        }
        
        $containsKeywords = [
            'họ và',
            'ho va',
        ];
        
        foreach ($containsKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }
        
        $shortKeywords = ['tên', 'name', 'employee', 'nhân viên'];
        $words = explode(' ', $normalized);
        if (count($words) <= 2) {
            foreach ($shortKeywords as $keyword) {
                if (in_array($keyword, $words)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        if ($name === '') return 'file.pdf';

        $name = preg_replace('/[\/\\\\]+/', '-', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/[^\pL\pN\.\-\_\s]+/u', '', $name);
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return 'file.pdf';
        }
        return $name;
    }

    private function extractCellReferencesFromFormula(string $formula): array
    {
        $references = [];
        $formula = ltrim($formula, '=');
        $pattern = '/\b([A-Z]{1,3}[0-9]{1,7})\b/i';
        if (preg_match_all($pattern, $formula, $matches)) {
            $references = array_unique(array_map('strtoupper', $matches[1]));
        }
        return $references;
    }
}

