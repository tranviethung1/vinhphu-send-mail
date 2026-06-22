<?php

namespace App\Http\Controllers;

use App\Models\MailList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MailController extends Controller
{
    /**
     * Danh sách mail list đã lưu
     */
    public function index()
    {
        $facilityId = request()->query('facility_id');

        $lists = MailList::query()
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->with('facility')
            ->latest()
            ->get();

        return view('mails.index', [
            'lists' => $lists,
            'facilityId' => $facilityId,
        ]);
    }

    /**
     * Màn tạo mail list (upload Excel + đặt tên)
     */
    public function create(Request $request)
    {
        $selectedFacilityId = $request->query('facility_id');
        if ($selectedFacilityId !== null && $selectedFacilityId !== '') {
            $request->validate([
                'facility_id' => 'exists:facilities,id',
            ]);
        }

        $facilities = \App\Models\Facility::orderBy('name')->get();
        $selectedFacility = $selectedFacilityId
            ? $facilities->firstWhere('id', (int) $selectedFacilityId)
            : null;

        return view('mails.create', [
            'selectedFacilityId' => $selectedFacilityId,
            'selectedFacility' => $selectedFacility,
            'facilities' => $facilities,
        ]);
    }

    /**
     * Lưu mail list (đọc Excel -> header + rows)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'facility_id' => 'required|exists:facilities,id',
            'data_source_type' => 'nullable|in:excel,sheet',
            'excel_file' => 'required_if:data_source_type,excel|nullable|mimes:xlsx,xls,xlsm|max:10240',
            'google_sheet_url' => 'required_if:data_source_type,sheet|nullable|string|max:512',
        ]);

        $dataSourceType = $validated['data_source_type'] ?? 'excel';

        if ($dataSourceType === 'sheet') {
            $sheetUrl = html_entity_decode(trim((string)($validated['google_sheet_url'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $sheetResult = $this->extractGoogleSheetData($sheetUrl);
            if (!empty($sheetResult['error'])) {
                return back()->withInput()->with([
                    'uploadError' => $sheetResult['error'],
                ]);
            }

            $headers = $sheetResult['headers'] ?? [];
            $rows = $sheetResult['rows'] ?? [];

            $mailList = MailList::create([
                'name' => $validated['name'],
                'facility_id' => $validated['facility_id'] ?? null,
                'original_filename' => 'Google Sheet',
                'google_sheet_url' => $sheetUrl,
                'headers' => $headers,
                'rows' => $rows,
            ]);
        } else {
            $file = $request->file('excel_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $diskName = env('FILESYSTEM_DISK', 'local');
            $filePath = $file->storeAs('mail_uploads', $fileName, $diskName);
            $disk = Storage::disk($diskName);
            try {
                $fullPath = $disk->path($filePath);
            } catch (\Exception $e) {
                // Nếu không phải local disk, download về temp file
                $fileContent = $disk->get($filePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'mail_upload_');
                file_put_contents($tempFile, $fileContent);
                $fullPath = $tempFile;
            }

            $result = $this->extractSheetData($fullPath);

            if (!empty($result['error'])) {
                return back()->withInput()->with([
                    'uploadError' => $result['error'],
                ]);
            }

            $headers = [];
            $rows = $result['rows'] ?? [];

            // Nếu có dữ liệu, lấy dòng đầu tiên làm header và bỏ khỏi danh sách hiển thị
            if (!empty($rows)) {
                $headers = array_shift($rows);
            }

            // Ẩn các dòng mà cột B, C và D đều trống (chỉ giữ nếu B/C/D có ít nhất 1 giá trị)
            $rows = array_values(array_filter($rows, function ($row) {
                $b = trim((string)($row[1] ?? ''));
                $c = trim((string)($row[2] ?? ''));
                $d = trim((string)($row[3] ?? ''));
                return $b !== '' || $c !== '' || $d !== '';
            }));

            $mailList = MailList::create([
                'name' => $validated['name'],
                'facility_id' => $validated['facility_id'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'headers' => $headers,
                'rows' => $rows,
            ]);
        }

        if ($mailList->facility_id) {
            return redirect()->route('facilities.mail-lists', $mailList->facility_id);
        }

        return redirect()->route('mails.show', $mailList);
    }

    /**
     * Xem chi tiết 1 mail list đã lưu
     */
    public function show(MailList $mailList)
    {
        return view('mails.show', compact('mailList'));
    }

    /**
     * Màn edit mail list (update tên và file)
     */
    public function edit(MailList $mailList)
    {
        return view('mails.edit', compact('mailList'));
    }

    /**
     * Cập nhật tên và/hoặc upload Excel mới
     */
    public function update(Request $request, MailList $mailList)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'excel_file' => 'nullable|mimes:xlsx,xls,xlsm|max:10240',
            'google_sheet_url' => 'nullable|string|max:512',
        ]);

        // Cập nhật tên và link Google Sheet (nếu gửi lên)
        $sheetUrl = isset($validated['google_sheet_url']) ? trim($validated['google_sheet_url']) : $mailList->google_sheet_url;
        $mailList->update([
            'name' => $validated['name'],
            'google_sheet_url' => $sheetUrl !== '' ? $sheetUrl : null,
        ]);

        // Nếu có upload file mới, xử lý cập nhật dữ liệu
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $diskName = env('FILESYSTEM_DISK', 'local');
            $filePath = $file->storeAs('mail_uploads', $fileName, $diskName);
            $disk = Storage::disk($diskName);
            try {
                $fullPath = $disk->path($filePath);
            } catch (\Exception $e) {
                // Nếu không phải local disk, download về temp file
                $fileContent = $disk->get($filePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'mail_upload_');
                file_put_contents($tempFile, $fileContent);
                $fullPath = $tempFile;
            }

            $result = $this->extractSheetData($fullPath);
            if (!empty($result['error'])) {
                return back()->with([
                    'uploadError' => $result['error'],
                ]);
            }

            $headers = [];
            $rows = $result['rows'] ?? [];

            if (!empty($rows)) {
                $headers = array_shift($rows);
            }

            $rows = array_values(array_filter($rows, function ($row) {
                $b = trim((string)($row[1] ?? ''));
                $c = trim((string)($row[2] ?? ''));
                $d = trim((string)($row[3] ?? ''));
                return $b !== '' || $c !== '' || $d !== '';
            }));

            $mailList->update([
                'original_filename' => $file->getClientOriginalName(),
                'headers' => $headers,
                'rows' => $rows,
            ]);
        }

        return redirect()->route('mails.show', $mailList)
            ->with('success', 'Cập nhật danh sách email thành công');
    }

    /**
     * Đồng bộ dữ liệu từ Google Sheets (CSV export) vào mail list
     */
    public function syncFromSheet(Request $request, MailList $mailList)
    {
        $request->validate([
            'sheet_url' => 'required|string|max:512',
        ]);

        try {
            $sheetUrl = html_entity_decode(trim((string)$request->input('sheet_url')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $sheetResult = $this->extractGoogleSheetData($sheetUrl);
            if (!empty($sheetResult['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $sheetResult['error'],
                ], 422);
            }

            $mailList->update([
                'google_sheet_url' => $sheetUrl,
                'original_filename' => 'Google Sheet',
                'headers' => $sheetResult['headers'] ?? [],
                'rows' => $sheetResult['rows'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ thành công. Số dòng: ' . count($sheetResult['rows'] ?? []) . '.',
                'redirect' => route('mails.edit', $mailList),
            ]);
        } catch (\Throwable $e) {
            Log::error('Sync from Google Sheet failed', [
                'mail_list_id' => $mailList->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview/đọc thử dữ liệu từ Google Sheet (không tạo record)
     */
    public function previewSheet(Request $request)
    {
        $request->validate([
            'sheet_url' => 'required|string|max:512',
        ]);

        try {
            $sheetUrl = html_entity_decode(trim((string)$request->input('sheet_url')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $sheetResult = $this->extractGoogleSheetData($sheetUrl);
            if (!empty($sheetResult['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $sheetResult['error'],
                ], 422);
            }

            $count = count($sheetResult['rows'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Đọc sheet thành công. Số dòng: ' . $count . '.',
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('Preview Google Sheet failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đọc sheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy dữ liệu từ Google Sheet (CSV export) và trả về headers + rows (tối đa 4 cột)
     */
    private function extractGoogleSheetData(string $sheetUrl): array
    {
        $sheetUrl = trim($sheetUrl);
        if ($sheetUrl === '') {
            return ['headers' => [], 'rows' => [], 'error' => 'Vui lòng nhập link Google Sheets.'];
        }

        // Trích xuất spreadsheet ID: /d/ID/ hoặc /d/ID?
        if (!preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $sheetUrl, $m)) {
            return ['headers' => [], 'rows' => [], 'error' => 'Link Google Sheets không hợp lệ. Ví dụ: https://docs.google.com/spreadsheets/d/.../edit?usp=sharing'];
        }
        $spreadsheetId = $m[1];
        $gid = 0;
        if (preg_match('~[#&]gid=(\d+)~', $sheetUrl, $gidMatch)) {
            $gid = (int) $gidMatch[1];
        }

        $exportUrl = sprintf(
            'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%d',
            $spreadsheetId,
            $gid
        );

        $response = Http::timeout(30)->get($exportUrl);
        if (!$response->successful()) {
            Log::warning('Google Sheets export failed', ['status' => $response->status(), 'url' => $exportUrl]);
            return [
                'headers' => [],
                'rows' => [],
                'error' => 'Không tải được dữ liệu từ Google Sheets. Kiểm tra link có chia sẻ "Bất kỳ ai có link đều xem được" chưa.',
            ];
        }

        $csvContent = $response->body();
        // Bỏ BOM nếu có (UTF-8)
        if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
            $csvContent = substr($csvContent, 3);
        }

        $rows = $this->parseCsvToRows($csvContent, 4);
        if (empty($rows)) {
            return [
                'headers' => [],
                'rows' => [],
                'error' => 'Sheet không có dữ liệu hoặc định dạng không đúng.',
            ];
        }

        $headers = array_shift($rows);
        // Chỉ giữ dòng có ít nhất một trong các cột B, C, D (index 1, 2, 3) có giá trị
        $rows = array_values(array_filter($rows, function ($row) {
            $b = trim((string) ($row[1] ?? ''));
            $c = trim((string) ($row[2] ?? ''));
            $d = trim((string) ($row[3] ?? ''));
            return $b !== '' || $c !== '' || $d !== '';
        }));

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Parse chuỗi CSV thành mảng rows, mỗi row tối đa $maxColumns cột
     */
    private function parseCsvToRows(string $csvContent, int $maxColumns = 4): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }
        fwrite($stream, $csvContent);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $row = array_slice(array_map('trim', $row), 0, $maxColumns);
            $row = array_pad($row, $maxColumns, '');
            if (collect($row)->contains(fn ($v) => $v !== '')) {
                $rows[] = $row;
            }
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Xóa mail list
     */
    public function destroy(MailList $mailList)
    {
        $facilityId = $mailList->facility_id;

        $mailList->delete();

        if ($facilityId) {
            return redirect()->route('facilities.mail-lists', $facilityId)
                ->with('success', 'Đã xóa danh sách email.');
        }

        return redirect()->route('mails.index')
            ->with('success', 'Đã xóa danh sách email.');
    }

    /**
     * Đọc file Excel và lấy dữ liệu cột A-D trên tất cả sheet
     */
    private function extractSheetData(string $filePath): array
    {
        $rows = [];

        try {
            if (!file_exists($filePath)) {
                return ['rows' => [], 'error' => 'File không tồn tại.'];
            }

            $spreadsheet = IOFactory::load($filePath);
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $highestRow = $sheet->getHighestDataRow();
                $highestColumn = $sheet->getHighestDataColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                // Chỉ lấy tối đa 4 cột đầu (A-D), không bỏ trùng, giữ nguyên thứ tự
                $maxColumns = min(4, $highestColumnIndex);

                for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                    $rowData = [];
                    for ($col = 1; $col <= $maxColumns; $col++) {
                        $cellAddress = Coordinate::stringFromColumnIndex($col) . $rowIndex;
                        $cellValue = (string)($sheet->getCell($cellAddress)->getFormattedValue() ?? '');
                        $rowData[] = $cellValue;
                    }

                    // Giữ dòng nếu có ít nhất 1 ô có dữ liệu
                    $hasData = collect($rowData)->contains(fn($v) => trim((string)$v) !== '');
                    if ($hasData) {
                        $rows[] = $rowData;
                    }
                }
            }

            return [
                'rows' => $rows,
            ];
        } catch (\Throwable $e) {
            Log::error('Lỗi khi đọc file Excel', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'rows' => [],
                'error' => 'Không thể đọc file Excel, vui lòng thử lại.',
            ];
        }
    }
    /**
     * Download mail list as Excel (regenerated from stored data)
     */
    public function download(MailList $mailList)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        if (!empty($mailList->headers) && is_array($mailList->headers)) {
            $col = 1;
            foreach ($mailList->headers as $header) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $header);
                $col++;
            }
        }

        // Add rows
        if (!empty($mailList->rows) && is_array($mailList->rows)) {
            $rowIdx = 2; // Start from row 2 (after header)
            foreach ($mailList->rows as $row) {
                $col = 1;
                foreach ($row as $cellValue) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $rowIdx, $cellValue);
                    $col++;
                }
                $rowIdx++;
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $fileName = 'mail_list_' . $mailList->id . '.xlsx';
        if ($mailList->original_filename) {
             $fileName = $mailList->original_filename;
             // Ensure extension is xlsx if not present (simple check)
             if (!preg_match('/\.xlsx$/i', $fileName)) {
                 $fileName .= '.xlsx';
             }
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }
}
