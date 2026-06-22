<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Template;
use App\Models\TemplateItem;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\TemplateSelection;

class TemplateController extends Controller
{
    /**
     * Lấy disk động dựa trên env FILESYSTEM_DISK
     */
    private function getStorageDisk()
    {
        $diskName = env('FILESYSTEM_DISK', 'local');
        return Storage::disk($diskName);
    }

    /**
     * Hiển thị màn danh sách template mapping
     */
    public function index()
    {
        $savedSelections = TemplateSelection::latest()->take(20)->get();
        return view('templates.index', [
            'savedSelections' => $savedSelections,
        ]);
    }

    /**
     * Màn tạo template mới (form riêng)
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Màn tạo mapping template mới từ Excel (upload + editor)
     */
    public function mappingCreate()
    {
        // Mới vào chưa có file -> chỉ show màn upload + sẽ dùng view mapping chung
        return view('templates.mapping');
    }

    /**
     * Upload file Excel và lấy toàn bộ nội dung (tất cả sheet)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,xlsm|max:10240', // Max 10MB
        ]);

        $file = $request->file('excel_file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $templateDisk = $this->getStorageDisk();
        $diskName = env('FILESYSTEM_DISK', 'local');
        $filePath = $file->storeAs('templates', $fileName, $diskName);

        $tempFileToCleanup = null;
        
        // Handle GCS: download to temp file if needed
        if ($diskName === 'local') {
            try {
                $fullPath = $templateDisk->path($filePath);
            } catch (\Exception $e) {
                // Fallback: download từ GCS về temp file
                $fileContent = $templateDisk->get($filePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $fullPath = $tempFile;
                $tempFileToCleanup = $tempFile;
            }
        } else {
            // GCS: download về temp file
            $fileContent = $templateDisk->get($filePath);
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            file_put_contents($tempFile, $fileContent);
            $fullPath = $tempFile;
            $tempFileToCleanup = $tempFile;
        }
        
        $result = $this->readExcelSheet2Only($fullPath);
        
        // Cleanup temp file if created
        if ($tempFileToCleanup && file_exists($tempFileToCleanup)) {
            @unlink($tempFileToCleanup);
        }

        return view('templates.mapping', [
            'fileName' => $fileName,
            'filePath' => $filePath,
            'sheets' => $result['sheets'],
            'sheetNames' => $result['sheetNames'],
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Lưu template mới (màn tạo template riêng)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'company_name' => 'nullable|string|max:255',
            'document_code' => 'nullable|string|max:255',
            'issue_number' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'income_items' => 'required|array|min:1',
            'income_items.*.code' => 'nullable|string|max:50',
            'income_items.*.label' => 'required|string|max:255',
            'deduction_items' => 'nullable|array',
            'deduction_items.*.code' => 'nullable|string|max:50',
            'deduction_items.*.label' => 'nullable|string|max:255',
            'summary_items' => 'nullable|array',
            'summary_items.*.key' => 'nullable|string|max:50',
            'summary_items.*.code' => 'nullable|string|max:50',
            'summary_items.*.label' => 'nullable|string|max:255',
            'footer_items' => 'nullable|array',
            'footer_items.*.key' => 'nullable|string|max:50',
            'footer_items.*.code' => 'nullable|string|max:50',
            'footer_items.*.label' => 'nullable|string|max:255',
        ]);

        // Upload logo nếu có
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('templates/logos', 'public');
        }

        // Lưu bản ghi template chính
        $template = Template::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'logo_path' => $logoPath,
            'company_name' => $validated['company_name'] ?? null,
            'document_code' => $validated['document_code'] ?? null,
            'issue_number' => $validated['issue_number'] ?? null,
            'title' => $validated['title'] ?? 'PHIẾU LƯƠNG',
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);

        // Helper để tạo item theo section
        $createItems = function (array $items, string $section) use ($template) {
            $order = 1;
            foreach ($items as $item) {
                if (empty($item['label'] ?? null) && $section !== 'footer') {
                    continue;
                }

                TemplateItem::create([
                    'template_id' => $template->id,
                    'key' => $item['key'] ?? null,
                    'section' => $section,
                    'code' => $item['code'] ?? null,
                    'label' => $item['label'] ?? null,
                    'order' => $order++,
                    'is_active' => true,
                ]);
            }
        };

        // Thu nhập
        if (!empty($validated['income_items'])) {
            $createItems($validated['income_items'], 'income');
        }

        // Khấu trừ
        if (!empty($validated['deduction_items'])) {
            $createItems($validated['deduction_items'], 'deduction');
        }

        // Tổng hợp
        if (!empty($validated['summary_items'])) {
            $createItems($validated['summary_items'], 'summary');
        }

        // Footer
        if (!empty($validated['footer_items'])) {
            $createItems($validated['footer_items'], 'footer');
        }

        return redirect()
            ->route('templates.index')
            ->with('success', 'Tạo template mới thành công.');
    }

    /**
     * Chỉ đọc Sheet 2 (sheet thứ 2 trong file, index = 1)
     */
    private function readExcelSheet2Only(string $filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                Log::error("File không tồn tại: {$filePath}");
                return ['sheets' => [], 'sheetNames' => [], 'error' => 'File không tồn tại.'];
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheetNames = $spreadsheet->getSheetNames();

            if (count($sheetNames) < 1) {
                return [
                    'sheets' => [],
                    'sheetNames' => [],
                    'error' => 'File Excel không có sheet nào.',
                ];
            }

            // Ưu tiên Sheet 2 (index 1), nếu không có thì dùng Sheet 1 (index 0)
            $sheetIndex = count($sheetNames) >= 2 ? 1 : 0;
            $sheetName = $sheetNames[$sheetIndex];
            $worksheet = $spreadsheet->getSheet($sheetIndex);

            // Chỉ đọc từ cột A đến cột D (cột 1 đến 4)
            $highestRow = $worksheet->getHighestDataRow();
            $maxColumns = 4; // Chỉ đọc 4 cột: A, B, C, D

            $data = [];
            $styles = [];
            $rowHeights = []; // px per row (1-based row index)
            $columnWidths = []; // px per column (1-based column index)

            // Đọc độ rộng cột từ Excel (chỉ cột A-D)
            for ($col = 1; $col <= $maxColumns; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $colDimension = $worksheet->getColumnDimension($colLetter);
                $width = $colDimension->getWidth();
                
                // Nếu width = -1, sử dụng default width (thường là 8.43 characters)
                if ($width == -1) {
                    $width = 8.43; // Default Excel column width
                }
                
                // Chuyển đổi từ characters sang pixels
                // 1 character ≈ 7 pixels (với font Calibri 11pt)
                // Hoặc có thể tính chính xác hơn: width * 7 pixels
                $columnWidths[$col] = (int)round((float)$width * 7);
            }

            for ($row = 1; $row <= max(1, $highestRow); $row++) {
                $rowData = [];
                $rowStyles = [];

                // Chỉ đọc từ cột 1 (A) đến cột 4 (D)
                for ($col = 1; $col <= $maxColumns; $col++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellAddress);
                    $rowData[] = (string)($cell->getFormattedValue() ?? '');

                    $style = $worksheet->getStyle($cellAddress);
                    $rowStyles[] = $this->extractCellStyle($style);
                }

                $data[] = $rowData;
                $styles[] = $rowStyles;

                // Row height: PhpSpreadsheet returns points; convert to px (1pt ~= 1.333px)
                $rowDim = $worksheet->getRowDimension($row);
                $heightPt = $rowDim ? $rowDim->getRowHeight() : -1;
                if (is_numeric($heightPt) && $heightPt > 0) {
                    $rowHeights[$row] = (int)round(((float)$heightPt) * 1.3333333);
                }
            }

            return [
                'sheets' => [[
                    'name' => $sheetName,
                    'data' => $data,
                    'styles' => $styles,
                    'rowHeights' => $rowHeights,
                    'columnWidths' => $columnWidths,
                ]],
                'sheetNames' => $sheetNames,
            ];
        } catch (\Throwable $e) {
            Log::error("Lỗi khi đọc file Excel: {$filePath}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['sheets' => [], 'sheetNames' => [], 'error' => 'Lỗi khi đọc file Excel.'];
        }
    }

    /**
     * Trích xuất style từ cell style object (copy từ ExcelUploadController để hiển thị giống Excel)
     */
    private function extractCellStyle($style): array
    {
        $result = [
            'backgroundColor' => '#FFFFFF',
            'fontColor' => '#000000',
            'fontSize' => '11pt',
            'fontFamily' => 'Calibri, Arial, sans-serif',
            'fontBold' => false,
            'fontItalic' => false,
            'fontUnderline' => false,
            'textAlign' => 'left',
            'verticalAlign' => 'middle',
            'border' => 'none',
            'borderColor' => '#000000',
            'numberFormat' => 'General',
        ];

        try {
            $fill = $style->getFill();
            if ($fill->getFillType() !== Fill::FILL_NONE) {
                $fillColor = $fill->getStartColor();
                if ($fillColor && $fillColor->getRGB()) {
                    $result['backgroundColor'] = '#' . $fillColor->getRGB();
                }
            }

            $font = $style->getFont();
            if ($font) {
                if ($font->getColor() && $font->getColor()->getRGB()) {
                    $result['fontColor'] = '#' . $font->getColor()->getRGB();
                }
                if ($font->getSize()) {
                    $result['fontSize'] = $font->getSize() . 'pt';
                }
                if ($font->getName()) {
                    $result['fontFamily'] = $font->getName() . ', sans-serif';
                }
                $result['fontBold'] = $font->getBold();
                $result['fontItalic'] = $font->getItalic();
                $result['fontUnderline'] = $font->getUnderline() !== \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE;
            }

            $alignment = $style->getAlignment();
            if ($alignment) {
                $horizontal = $alignment->getHorizontal();
                if ($horizontal) {
                    $result['textAlign'] = strtolower($horizontal);
                }
                $vertical = $alignment->getVertical();
                if ($vertical) {
                    $result['verticalAlign'] = strtolower($vertical);
                }
            }

            $borders = $style->getBorders();
            if ($borders) {
                $borderStyles = [];
                $borderColors = [];

                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    $border = $borders->{'get' . ucfirst($side)}();
                    if ($border && $border->getBorderStyle() !== Border::BORDER_NONE) {
                        $borderStyles[] = $border->getBorderStyle();
                        if ($border->getColor() && $border->getColor()->getRGB()) {
                            $borderColors[] = '#' . $border->getColor()->getRGB();
                        }
                    }
                }

                if (!empty($borderStyles)) {
                    $result['border'] = '1px solid';
                    if (!empty($borderColors)) {
                        $result['borderColor'] = $borderColors[0];
                    }
                }
            }

            $numberFormat = $style->getNumberFormat();
            if ($numberFormat) {
                $result['numberFormat'] = $numberFormat->getFormatCode();
            }
        } catch (\Throwable $e) {
            Log::warning("Lỗi khi trích xuất style: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Lưu selections vào DB
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:salary,bonus',
            'description' => 'nullable|string',
            'file_name' => 'nullable|string|max:255',
            'selections' => 'required|array|min:1',
            'selections.*.address' => 'nullable|string|max:50',
            'selections.*.valueType' => 'nullable|string|max:50',
            'selections.*.value' => 'nullable|string',
            'selections.*.cellRef' => 'nullable|string|max:10',
            'selections.*.formula' => 'nullable|string',
            'selections.*.note' => 'nullable|string',
            'selections.*.color' => 'nullable|string|max:20',
        ]);

        $record = TemplateSelection::create([
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? 'salary',
            'description' => $validated['description'] ?? null,
            'file_name' => $validated['file_name'] ?? null,
            'selections' => $validated['selections'],
        ]);

        return response()->json([
            'success' => true,
            'id' => $record->id,
        ]);
    }

    /**
     * Hiển thị template để edit
     */
    public function show($id)
    {
        $template = TemplateSelection::findOrFail($id);

        // Load lại file Excel từ fileName đã lưu
        if ($template->file_name) {
            $templateDisk = $this->getStorageDisk();
            $templatePath = 'templates/' . $template->file_name;
            $diskName = env('FILESYSTEM_DISK', 'local');
            $tempFileToCleanup = null;
            
            // Handle GCS: download to temp file if needed
            if ($diskName === 'local') {
                try {
                    $filePath = $templateDisk->path($templatePath);
                } catch (\Exception $e) {
                    // Fallback: download từ GCS về temp file
                    $fileContent = $templateDisk->get($templatePath);
                    $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                    file_put_contents($tempFile, $fileContent);
                    $filePath = $tempFile;
                    $tempFileToCleanup = $tempFile;
                }
            } else {
                // GCS: download về temp file
                $fileContent = $templateDisk->get($templatePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $filePath = $tempFile;
                $tempFileToCleanup = $tempFile;
            }
            
            if (file_exists($filePath)) {
                $result = $this->readExcelSheet2Only($filePath);
                
                // Cleanup temp file if created
                if ($tempFileToCleanup && file_exists($tempFileToCleanup)) {
                    @unlink($tempFileToCleanup);
                }

                return view('templates.mapping', [
                    'fileName' => $template->file_name,
                    'filePath' => 'templates/' . $template->file_name,
                    'sheets' => $result['sheets'],
                    'sheetNames' => $result['sheetNames'],
                    'error' => $result['error'] ?? null,
                    'editingTemplate' => $template, // Truyền template để restore selections
                ]);
            }
        }

        // Nếu không có file, quay lại danh sách với thông báo lỗi
        return redirect()
            ->route('templates.index')
            ->with('error', 'File Excel không tồn tại.');
    }

    /**
     * Cập nhật template
     */
    public function update(Request $request, $id)
    {
        $template = TemplateSelection::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:salary,bonus',
            'description' => 'nullable|string',
            'selections' => 'required|array|min:1',
            'selections.*.address' => 'nullable|string|max:50',
            'selections.*.valueType' => 'nullable|string|max:50',
            'selections.*.value' => 'nullable|string',
            'selections.*.cellRef' => 'nullable|string|max:10',
            'selections.*.formula' => 'nullable|string',
            'selections.*.note' => 'nullable|string',
            'selections.*.color' => 'nullable|string|max:20',
        ]);

        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'type' => $validated['type'] ?? $template->type,
            'description' => $validated['description'] ?? $template->description,
            'selections' => $validated['selections'],
        ]);

        return response()->json([
            'success' => true,
            'id' => $template->id,
        ]);
    }

    /**
     * Upload file Excel mới cho template mapping đã tồn tại (đổi file)
     */
    public function replaceFile(Request $request, $id)
    {
        $template = TemplateSelection::findOrFail($id);

        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,xlsm|max:10240', // Max 10MB
        ]);

        $file = $request->file('excel_file');
        $fileName = time() . '_' . $file->getClientOriginalName();

        $templateDisk = $this->getStorageDisk();
        $diskName = env('FILESYSTEM_DISK', 'local');
        $filePath = $file->storeAs('templates', $fileName, $diskName);
        $tempFileToCleanup = null;

        // Handle GCS: download to temp file nếu cần
        if ($diskName === 'local') {
            try {
                $fullPath = $templateDisk->path($filePath);
            } catch (\Exception $e) {
                // Fallback: download từ storage về temp file
                $fileContent = $templateDisk->get($filePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $fullPath = $tempFile;
                $tempFileToCleanup = $tempFile;
            }
        } else {
            // GCS hoặc disk khác: download về temp file
            $fileContent = $templateDisk->get($filePath);
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            file_put_contents($tempFile, $fileContent);
            $fullPath = $tempFile;
            $tempFileToCleanup = $tempFile;
        }

        $result = $this->readExcelSheet2Only($fullPath);
        
        // Cleanup temp file if created
        if ($tempFileToCleanup && file_exists($tempFileToCleanup)) {
            @unlink($tempFileToCleanup);
        }

        // Cập nhật file_name của template mapping
        $template->file_name = $fileName;
        $template->save();

        return view('templates.mapping', [
            'fileName' => $fileName,
            'filePath' => $filePath,
            'sheets' => $result['sheets'],
            'sheetNames' => $result['sheetNames'],
            'error' => $result['error'] ?? null,
            'editingTemplate' => $template,
        ]);
    }

    /**
     * Xoá template mapping
     */
    public function destroy($id)
    {
        $template = TemplateSelection::findOrFail($id);
        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with('success', 'Xoá template mapping thành công.');
    }
}