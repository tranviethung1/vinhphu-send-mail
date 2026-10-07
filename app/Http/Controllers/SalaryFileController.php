<?php

namespace App\Http\Controllers;

use App\Models\SalaryFile;
use App\Models\TemplateSelection;
use App\Models\SalaryBulkExport;
use App\Models\Facility;
use App\Models\MailList;
use App\Jobs\MergeBulkSalaryPdfs;
use App\Jobs\SendBulkExportMails;
use App\Jobs\StartBulkSalaryPdfBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use ZipArchive;

class SalaryFileController extends Controller
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
     * Lấy disk name động
     */
    private function getStorageDiskName()
    {
        return env('FILESYSTEM_DISK', 'local');
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
     * Hiển thị danh sách các file lương đã upload
     */
    public function index()
    {
        $files = SalaryFile::with('facility')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('salary-files.index', compact('files'));
    }

    /**
     * Hiển thị form upload file lương mới
     */
    public function create(Request $request)
    {
        $selectedFacilityId = $request->query('facility_id');
        if ($selectedFacilityId !== null && $selectedFacilityId !== '') {
            $request->validate([
                'facility_id' => 'exists:facilities,id',
            ]);
        }

        $facilities = Facility::orderBy('name')->get();
        $selectedFacility = $selectedFacilityId
            ? $facilities->firstWhere('id', (int) $selectedFacilityId)
            : null;

        // Lấy danh sách file Google Sheets từ trường data_link của cơ sở
        // Hỗ trợ: mỗi dòng có thể là 1 link Google Sheet, hoặc 1 link thư mục Google Drive
        $facilitySheetOptions = [];
        if ($selectedFacility && $selectedFacility->data_link) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $selectedFacility->data_link);
            foreach ($lines as $line) {
                $url = trim($line);
                if ($url === '') {
                    continue;
                }

                // Nếu là link Google Sheet trực tiếp -> thêm thẳng
                if (preg_match('~https://docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)~', $url)) {
                    $facilitySheetOptions[] = [
                        'url' => $url,
                        'label' => $url,
                    ];
                    continue;
                }

                // Nếu là link thư mục Google Drive -> cố gắng lấy tất cả file Google Sheets trong đó
                if (str_contains($url, 'drive.google.com/drive/folders/')) {
                    $items = $this->fetchGoogleDriveFolderSheets($url);
                    foreach ($items as $item) {
                        $facilitySheetOptions[] = $item;
                    }
                    continue;
                }

                // Trường hợp khác: vẫn cho phép dùng trực tiếp như 1 URL
                $facilitySheetOptions[] = [
                    'url' => $url,
                    'label' => $url,
                ];
            }
        }

        $templates = TemplateSelection::latest()->get();

        return view('salary-files.create', [
            'facilities' => $facilities,
            'selectedFacilityId' => $selectedFacilityId,
            'selectedFacility' => $selectedFacility,
            'facilitySheetOptions' => $facilitySheetOptions,
            'templates' => $templates,
        ]);
    }

    /**
     * Xử lý tạo file lương (từ Excel upload hoặc Google Sheet)
     */
    public function store(Request $request)
    {
        set_time_limit(300); // Tăng timeout lên 300 giây cho xử lý file Excel
        ini_set('memory_limit', '512M'); // Tăng memory limit cho xử lý file lớn
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'month' => ['nullable', 'string', 'max:7', 'regex:/^(0[1-9]|1[0-2])-[0-9]{4}$/'],
            'data_source_type' => 'nullable|in:excel,sheet',
            'salary_file' => 'required_if:data_source_type,excel|nullable|mimes:xlsx,xls,xlsm|max:10240', // Max 10MB
            'google_sheet_url' => 'required_if:data_source_type,sheet|nullable|string|max:512',
            'template_selection_id' => 'nullable|exists:template_selections,id',
            'facility_id' => 'nullable|exists:facilities,id',
            'selected_sheet' => 'nullable|string', // Sheet name from wizard (Excel mode)
        ], [
            'month.regex' => 'Định dạng tháng không đúng. Vui lòng nhập theo định dạng mm-yyyy (ví dụ: 01-2026).',
            'salary_file.required_if' => 'Vui lòng chọn file Excel.',
            'google_sheet_url.required_if' => 'Vui lòng nhập link Google Sheets.',
        ]);

        $dataSourceType = $validated['data_source_type'] ?? 'excel';

        try {
            $disk = env('FILESYSTEM_DISK', 'local');

            if ($dataSourceType === 'sheet') {
                // Tạo file Excel từ Google Sheet (CSV export)
                $sheetUrl = html_entity_decode(trim((string)($validated['google_sheet_url'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $buildResult = $this->buildSalaryExcelFromGoogleSheet($sheetUrl);
                if (!$buildResult['success']) {
                    return redirect()->back()
                        ->with('error', $buildResult['message'] ?? 'Không thể đọc dữ liệu từ Google Sheets.')
                        ->withInput();
                }

                $fileName = $buildResult['file_name'];
                $filePath = $buildResult['file_path'];
                $fileSizeHuman = $this->formatFileSize($buildResult['file_size_bytes']);
                $localFullPath = $buildResult['local_full_path'];

                // Kiểm tra khớp danh sách nhân viên với mail list nếu có facility
                if ($request->facility_id) {
                    $validationResult = $this->validateEmployeeCountWithMailList($localFullPath, $request->facility_id, 'Bảng lương');
                    if (!$validationResult['valid']) {
                        // Nếu không hợp lệ thì xóa file đã tạo
                        Storage::disk($disk)->delete($filePath);
                        return redirect()->back()
                            ->with('error', $validationResult['message'])
                            ->withInput();
                    }
                }

                // Google Sheet chỉ có 1 sheet -> dùng luôn file này làm salary_sheet
                $salaryFile = SalaryFile::create([
                    'name' => $validated['name'],
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSizeHuman,
                    'salary_sheet_file_name' => $fileName,
                    'salary_sheet_path' => $filePath,
                    'salary_sheet_size' => $fileSizeHuman,
                    'month' => $validated['month'] ?? null,
                    'template_selection_id' => $validated['template_selection_id'] ?? null,
                    'facility_id' => $validated['facility_id'] ?? null,
                    'google_sheet_url' => $sheetUrl,
                ]);

                return redirect()->route('salary-files.edit', $salaryFile->id)
                    ->with('success', 'Tạo file lương từ Google Sheet thành công.');
            }

            // Mặc định: xử lý từ file Excel upload
            if ($request->hasFile('salary_file')) {
                $file = $request->file('salary_file');
                $selectedSheet = $request->input('selected_sheet');
                
                if ($request->facility_id) {
                    $validationResult = $this->validateEmployeeCountWithMailList($file, $request->facility_id, $selectedSheet);
                    if (!$validationResult['valid']) {
                        return redirect()->back()
                            ->with('error', $validationResult['message'])
                            ->withInput();
                    }
                }
                
                // Lưu file gốc
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('salary-files', $fileName, $disk);
                $fileSize = $this->formatFileSize($file->getSize());
                
                // Tách sheet đã chọn và lưu file riêng
                $extractResult = $this->extractSalarySheet($file, $selectedSheet);
                
                if (!$extractResult['success']) {
                    // Nếu không tách được sheet, xóa file gốc đã lưu và báo lỗi
                    Storage::disk($disk)->delete($filePath);
                    return redirect()->back()
                        ->with('error', $extractResult['message'])
                        ->withInput();
                }
                
                // Tạo record trong database với cả file gốc và file sheet
                $salaryFile = SalaryFile::create([
                    'name' => $validated['name'],
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'salary_sheet_file_name' => $extractResult['file_name'],
                    'salary_sheet_path' => $extractResult['file_path'],
                    'salary_sheet_size' => $extractResult['file_size'],
                    'month' => $validated['month'] ?? null,
                    'template_selection_id' => $validated['template_selection_id'] ?? null,
                    'facility_id' => $validated['facility_id'] ?? null,
                ]);

                return redirect()->route('salary-files.edit', $salaryFile->id)
                    ->with('success', 'Upload file lương thành công! Sheet "' . ($selectedSheet ?: 'Bảng lương') . '" đã được tách riêng để xử lý.');
            }

            return redirect()->back()
                ->with('error', 'Không thể upload file.')
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo file lương: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo file lương: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hiển thị form chỉnh sửa thông tin file
     */
    public function edit($id)
    {
        $requestStart = microtime(true);
        Log::debug("🔵 Bắt đầu edit salary file", ['id' => $id]);
        
        $file = SalaryFile::with('facility')->findOrFail($id);
        $templates = TemplateSelection::latest()->get();
        $employeeOptions = [];

        // Lấy danh sách file Google Sheets từ data_link của cơ sở (giống màn create)
        $facilitySheetOptions = [];
        $facility = $file->facility;
        if ($facility && $facility->data_link) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $facility->data_link);
            foreach ($lines as $line) {
                $url = trim($line);
                if ($url === '') {
                    continue;
                }

                // Nếu là link Google Sheet trực tiếp -> thêm thẳng
                if (preg_match('~https://docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)~', $url)) {
                    $facilitySheetOptions[] = [
                        'url' => $url,
                        'label' => $url,
                    ];
                    continue;
                }

                // Nếu là link thư mục Google Drive -> cố gắng lấy tất cả file Google Sheets trong đó
                if (str_contains($url, 'drive.google.com/drive/folders/')) {
                    $items = $this->fetchGoogleDriveFolderSheets($url);
                    foreach ($items as $item) {
                        $facilitySheetOptions[] = $item;
                    }
                    continue;
                }

                // Trường hợp khác: vẫn cho phép dùng trực tiếp như 1 URL
                $facilitySheetOptions[] = [
                    'url' => $url,
                    'label' => $url,
                ];
            }
        }

        // Đọc dữ liệu Excel để hiển thị ngay trong màn edit
        $data = [];
        $styles = [];
        $sheetName = null;

        try {
            // Ưu tiên đọc từ file sheet đã tách (nhanh hơn), fallback về file gốc
            $pathToRead = $file->salary_sheet_path ?? $file->file_path;
            
            $disk = $this->getStorageDisk();
            $diskName = $this->getStorageDiskName();
            
            // Normalize path
            $normalizedPath = $this->normalizeFilePath($pathToRead);
            
            if ($pathToRead && $disk->exists($normalizedPath)) {
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
                
                $excelStart = microtime(true);
                $result = $this->readExcelFile($fullPath);
                $excelTime = round((microtime(true) - $excelStart) * 1000, 2);
                
                $data = $result['data'] ?? [];
                $styles = $result['styles'] ?? [];
                $sheetName = $result['sheetName'] ?? null;
                
                $extractStart = microtime(true);
                $employeeOptions = $this->extractEmployeeList($data);
                $extractTime = round((microtime(true) - $extractStart) * 1000, 2);
                
                Log::debug("⏱️ Thời gian xử lý Excel trong edit()", [
                    'read_excel_time_ms' => $excelTime,
                    'extract_employees_time_ms' => $extractTime,
                    'using_sheet_file' => $file->salary_sheet_path ? true : false,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi đọc excel cho salary file edit: ' . $e->getMessage(), [
                'salary_file_id' => $file->id,
                'file_path' => $file->file_path,
                'salary_sheet_path' => $file->salary_sheet_path,
            ]);
        }

        $totalRequestTime = round((microtime(true) - $requestStart) * 1000, 2);
        Log::info("🟢 Hoàn thành edit salary file", [
            'id' => $id,
            'total_request_time_ms' => $totalRequestTime,
        ]);

        $hasPendingBulk = \App\Models\SalaryBulkExport::where('salary_file_id', $id)->where('status', 'pending')->exists();

        return view('salary-files.edit', compact('file', 'data', 'styles', 'sheetName', 'templates', 'employeeOptions', 'facilitySheetOptions', 'hasPendingBulk'));
    }

    /**
     * Cập nhật thông tin file
     */
    public function update(Request $request, $id)
    {
        set_time_limit(60); // Tăng timeout lên 45 giây cho xử lý file Excel
        
        $request->validate([
            'name' => 'required|string|max:255',
            'month' => ['nullable', 'string', 'max:7', 'regex:/^(0[1-9]|1[0-2])-[0-9]{4}$/'],
            'template_selection_id' => 'nullable|exists:template_selections,id',
            'salary_file' => 'nullable|mimes:xlsx,xls,xlsm|max:10240', // Max 10MB, optional
            'facility_id' => 'nullable|exists:facilities,id',
            'selected_sheet' => 'nullable|string', // Sheet name if uploading new file
        ], [
            'month.regex' => 'Định dạng tháng không đúng. Vui lòng nhập theo định dạng mm-yyyy (ví dụ: 01-2026).',
        ]);

        try {
            $file = SalaryFile::findOrFail($id);

            $updateData = [
                'name' => $request->name,
                'month' => $request->month,
                'template_selection_id' => $request->template_selection_id,
            ];

            // Chỉ cập nhật facility_id nếu request có gửi lên (tránh bị set null khi UI đã ẩn)
            if ($request->has('facility_id')) {
                $updateData['facility_id'] = $request->facility_id;
            }
            
            // Xử lý upload file mới nếu có
            if ($request->hasFile('salary_file')) {
                $newFile = $request->file('salary_file');
                
                // Xác định facility_id cần kiểm tra (ưu tiên request mới, sau đó file cũ)
                $facilityIdToCheck = $request->has('facility_id') ? $request->facility_id : $file->facility_id;
                
                // Kiểm tra số người trong file có khớp với mail list của cơ sở không
                if ($facilityIdToCheck) {
                    $validationResult = $this->validateEmployeeCountWithMailList($newFile, $facilityIdToCheck, $selectedSheet);
                    if (!$validationResult['valid']) {
                        return redirect()->back()
                            ->with('error', $validationResult['message'])
                            ->withInput();
                    }
                }
                
                // Lưu file gốc mới
                $newFileName = time() . '_' . $newFile->getClientOriginalName();
                $disk = env('FILESYSTEM_DISK', 'local');
                $newFilePath = $newFile->storeAs('salary-files', $newFileName, $disk);
                $newFileSize = $this->formatFileSize($newFile->getSize());
                
                // Tách sheet đã chọn và lưu file riêng (nếu có selected_sheet, nếu không dùng mặc định)
                $selectedSheet = $request->input('selected_sheet');
                $extractResult = $this->extractSalarySheet($newFile, $selectedSheet);
                
                if (!$extractResult['success']) {
                    // Nếu không tách được sheet, xóa file gốc đã lưu và báo lỗi
                    Storage::disk($disk)->delete($newFilePath);
                    return redirect()->back()
                        ->with('error', $extractResult['message'])
                        ->withInput();
                }
                
                // Xóa file cũ nếu tồn tại (cả file gốc và file sheet)
                $disk = env('FILESYSTEM_DISK', 'local');
                if ($file->file_path && Storage::disk($disk)->exists($file->file_path)) {
                    Storage::disk($disk)->delete($file->file_path);
                }
                if ($file->salary_sheet_path && Storage::disk($disk)->exists($file->salary_sheet_path)) {
                    Storage::disk($disk)->delete($file->salary_sheet_path);
                }
                
                // Cập nhật thông tin file
                $file->update(array_merge($updateData, [
                    'file_name' => $newFileName,
                    'file_path' => $newFilePath,
                    'file_size' => $newFileSize,
                    'salary_sheet_file_name' => $extractResult['file_name'],
                    'salary_sheet_path' => $extractResult['file_path'],
                    'salary_sheet_size' => $extractResult['file_size'],
                ]));
                
                // Refresh model để lấy dữ liệu mới nhất
                $file->refresh();
                
                return redirect()->route('salary-files.edit', $file->id)
                    ->with('success', 'Cập nhật thông tin và file thành công! Sheet "Bảng lương" đã được tách riêng để xử lý.');
            } else {
                // Chỉ cập nhật thông tin, không thay đổi file
                $file->update($updateData);

                return redirect()->route('salary-files.edit', $file->id)
                    ->with('success', 'Cập nhật thông tin file thành công!');
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật file lương: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Đồng bộ lại file lương từ Google Sheet (chọn từ danh sách data_link)
     */
    public function syncFromSheet(Request $request, SalaryFile $file)
    {
        $request->validate([
            'sheet_url' => 'required|string|max:512',
        ]);

        $sheetUrl = html_entity_decode(trim((string) $request->input('sheet_url')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $buildResult = $this->buildSalaryExcelFromGoogleSheet($sheetUrl);
            if (empty($buildResult['success'])) {
                return response()->json([
                    'success' => false,
                    'message' => $buildResult['message'] ?? 'Không thể đọc dữ liệu từ Google Sheets.',
                ], 422);
            }

            $disk = env('FILESYSTEM_DISK', 'local');

            $newFileName     = $buildResult['file_name'];
            $newFilePath     = $buildResult['file_path'];
            $newFileSizeHuman = $this->formatFileSize($buildResult['file_size_bytes']);
            $localFullPath   = $buildResult['local_full_path'];

            // ── Kiểm tra khớp danh sách nhân viên với mail list ──────────────
            if ($file->facility_id) {
                $validationResult = $this->validateEmployeeCountWithMailList(
                    $localFullPath,
                    $file->facility_id,
                    'Bảng lương'
                );

                if (!$validationResult['valid']) {
                    // Xoá file tạm vừa tạo, không lưu thay đổi
                    Storage::disk($disk)->delete($newFilePath);

                    return response()->json([
                        'success' => false,
                        'message' => 'Cảnh báo danh sách nhân viên không khớp với mail list: '
                            . $validationResult['message'],
                    ], 422);
                }
            }

            // ── Xoá file cũ và lưu file mới ─────────────────────────────────
            $oldFilePath  = $file->file_path;
            $oldSheetPath = $file->salary_sheet_path;
            if ($oldFilePath) {
                Storage::disk($disk)->delete($oldFilePath);
            }
            if ($oldSheetPath && $oldSheetPath !== $oldFilePath) {
                Storage::disk($disk)->delete($oldSheetPath);
            }

            $file->update([
                'file_name'              => $newFileName,
                'file_path'              => $newFilePath,
                'file_size'              => $newFileSizeHuman,
                'salary_sheet_file_name' => $newFileName,
                'salary_sheet_path'      => $newFilePath,
                'salary_sheet_size'      => $newFileSizeHuman,
                'google_sheet_url'       => $sheetUrl,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Đồng bộ thành công từ Google Sheet.',
                'redirect' => route('salary-files.edit', $file->id),
            ]);
        } catch (\Throwable $e) {
            Log::error('Sync salary file from Google Sheet failed', [
                'salary_file_id' => $file->id,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kiểm tra xem Google Sheet có thay đổi so với file hiện tại không
     */
    public function checkDriveChanges($id)
    {
        $file = SalaryFile::findOrFail($id);

        if (empty($file->google_sheet_url)) {
            return response()->json(['has_changes' => false]);
        }

        // ── Kiểm tra file hiện tại còn tồn tại không ─────────────────────
        $pathToRead  = $file->salary_sheet_path ?? $file->file_path;
        $disk        = $this->getStorageDisk();
        $normalized  = $this->normalizeFilePath($pathToRead);
        $fileExists  = $pathToRead && $disk->exists($normalized);

        if (!$fileExists) {
            // File bị mất — cần đồng bộ lại từ Drive
            Log::warning('checkDriveChanges: file không còn tồn tại trên storage', [
                'id'          => $id,
                'path'        => $pathToRead,
            ]);
            return response()->json(['has_changes' => true, 'file_missing' => true]);
        }

        // ── Fingerprint file hiện tại ──────────────────────────────────────
        $currentFingerprint = '';
        $tempCreated = null;
        try {
            $diskName = $this->getStorageDiskName();
            if ($diskName === 'local') {
                try {
                    $fullPath = $disk->path($normalized);
                } catch (\Exception $e) {
                    $tempCreated = tempnam(sys_get_temp_dir(), 'salary_chk_');
                    file_put_contents($tempCreated, $disk->get($normalized));
                    $fullPath = $tempCreated;
                }
            } else {
                $tempCreated = tempnam(sys_get_temp_dir(), 'salary_chk_');
                file_put_contents($tempCreated, $disk->get($normalized));
                $fullPath = $tempCreated;
            }

            $result = $this->readExcelFile($fullPath);
            $currentFingerprint = $this->computeDataFingerprint($result['data'] ?? []);

            if ($tempCreated && file_exists($tempCreated)) {
                @unlink($tempCreated);
            }
        } catch (\Throwable $e) {
            if ($tempCreated && file_exists($tempCreated)) {
                @unlink($tempCreated);
            }
            Log::error('checkDriveChanges: lỗi đọc file hiện tại', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['has_changes' => false]);
        }

        // ── Fetch CSV từ Google Sheet ──────────────────────────────────────
        $sheetUrl = trim((string) $file->google_sheet_url);
        if (!preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $sheetUrl, $m)) {
            return response()->json(['has_changes' => false]);
        }

        $spreadsheetId = $m[1];
        $gid = 0;
        if (preg_match('~[#&]gid=(\d+)~', $sheetUrl, $gidMatch)) {
            $gid = (int) $gidMatch[1];
        }

        $exportUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$gid}";

        try {
            $response = Http::timeout(20)->get($exportUrl);
        } catch (\Throwable $e) {
            Log::warning('checkDriveChanges: không kết nối được Google Sheet', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['has_changes' => false]);
        }

        if (!$response->successful()) {
            return response()->json(['has_changes' => false]);
        }

        $csvContent = $response->body();
        if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
            $csvContent = substr($csvContent, 3);
        }

        // Parse CSV → rows (cùng logic với buildSalaryExcelFromGoogleSheet)
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);
        $driveRows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $row = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $row);
            $hasData = false;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') { $hasData = true; break; }
            }
            if ($hasData) {
                $driveRows[] = $row;
            }
        }
        fclose($stream);

        $driveFingerprint = $this->computeDataFingerprint($driveRows);

        return response()->json([
            'has_changes' => ($driveFingerprint !== $currentFingerprint),
        ]);
    }

    /**
     * Tính fingerprint (md5) cho mảng rows dữ liệu (dùng để so sánh thay đổi)
     */
    private function computeDataFingerprint(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $parts[] = trim((string)$cell);
            }
        }
        return md5(implode("\x00", $parts));
    }

    /**
     * Xuất PDF xem thử theo template đã chọn (không lưu thay đổi)
     */
    public function previewPdf(Request $request, $id)
    {
        // Tăng timeout lên 120 giây cho xử lý PDF với hình ảnh
        set_time_limit(120);
        
        $request->validate([
            'template_selection_id' => 'nullable|exists:template_selections,id',
            'row_number' => 'nullable|integer|min:2',
        ]);
    
        $file = SalaryFile::findOrFail($id);
        $templateId = $request->input('template_selection_id', $file->template_selection_id);
        $template = $templateId ? TemplateSelection::find($templateId) : null;
    
        if (!$template) {
            return redirect()->back()->with('error', 'Vui lòng chọn template trước khi kiểm tra PDF.');
        }
    
        if (empty($template->file_name)) {
            return redirect()->back()->with('error', 'Template chưa có file Excel.');
        }
    
        $templatePath = 'templates/' . $template->file_name;
        $templateDisk = $this->getStorageDisk();
        if (!$templateDisk->exists($templatePath)) {
            return redirect()->back()->with('error', 'File template không tồn tại.');
        }
    
        // Handle GCS: download to temp file if needed
        $diskName = $this->getStorageDiskName();
        if ($diskName === 'local') {
            try {
                $fullTemplatePath = $templateDisk->path($templatePath);
            } catch (\Exception $e) {
                // Fallback: download từ GCS về temp file
                $fileContent = $templateDisk->get($templatePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $fullTemplatePath = $tempFile;
            }
        } else {
            // GCS: download về temp file
            $fileContent = $templateDisk->get($templatePath);
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            file_put_contents($tempFile, $fileContent);
            $fullTemplatePath = $tempFile;
        }
    
        try {
            $rowNumber = (int)($request->input('row_number') ?? 2);
            if ($rowNumber < 2) {
                $rowNumber = 2;
            }

            $pdfContent = $this->generatePdfContentForRow($file, $template, $rowNumber);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Lỗi xuất PDF Excel', [
                'salary_file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
    
            return redirect()->back()->with('error', 'Không thể xuất PDF: ' . $e->getMessage());
        }
    }

    /**
     * Tạo nhiều PDF theo danh sách dòng (trả về ZIP)
     */
    public function bulkPdf(Request $request, $id)
    {
        $request->validate([
            'template_selection_id' => 'nullable|exists:template_selections,id',
            'row_numbers' => 'nullable|array',
            'row_numbers.*' => 'integer|min:2',
        ]);

        $file = SalaryFile::findOrFail($id);
        $templateId = $request->input('template_selection_id', $file->template_selection_id);
        $template = $templateId ? TemplateSelection::find($templateId) : null;

        if (!$template) {
            return redirect()->back()->with('error', 'Vui lòng chọn template trước khi tạo PDF hàng loạt.');
        }
        if (empty($template->file_name)) {
            return redirect()->back()->with('error', 'Template chưa có file Excel.');
        }

        $rows = array_values(array_unique(array_map('intval', $request->input('row_numbers', []))));
        if (empty($rows)) {
            return redirect()->back()->with('error', 'Danh sách dòng cần tạo PDF đang trống.');
        }

        // Tạo batch ID duy nhất
        $batchId = 'batch_' . $file->id . '_' . now()->format('YmdHis') . '_' . uniqid();
        
        // Tạo lịch sử với trạng thái pending
        $bulkExport = SalaryBulkExport::create([
            'salary_file_id' => $file->id,
            'template_selection_id' => $template->id,
            'file_path' => null,
            'rows' => $rows,
            'rows_count' => count($rows),
            'status' => 'pending',
        ]);
        
        $bulkExportId = $bulkExport->id;

        StartBulkSalaryPdfBatch::dispatch($file, $template, $rows, $batchId, $bulkExportId)
            ->afterResponse();

        return redirect()
            ->back()
            ->with('success', 'Yêu cầu tạo nhiều PDF đã được đưa vào hàng đợi. Đang xử lý ' . count($rows) . '. Khi xử lý xong, file ZIP sẽ được lưu trong lịch sử xuất PDF. ');
    }

    /**
     * Xem lịch sử các lần xuất PDF hàng loạt cho 1 file lương.
     */
    public function bulkHistory($id)
    {
        $file = SalaryFile::with('facility')->findOrFail($id);
        $exports = SalaryBulkExport::with('templateSelection')
            ->where('salary_file_id', $file->id)
            ->orderByDesc('created_at')
            ->get();

        // Lấy danh sách email mặc định theo facility_id
        $defaultMailList = null;
        if ($file->facility_id) {
            $defaultMailList = \App\Models\MailList::where('facility_id', $file->facility_id)
                ->whereRaw('is_default = true')
                ->first();
        }

        // Kiểm tra xem có job nào đang chạy không
        $pendingJobs = $this->getPendingJobsForFile($file->id);

        return view('salary-files.bulk-history', compact('file', 'exports', 'defaultMailList', 'pendingJobs'));
    }

    /**
     * Kiểm tra các job đang chờ xử lý cho file lương
     */
    private function getPendingJobsForFile($fileId)
    {
        try {
            $jobs = \DB::table('jobs')
                ->where('queue', 'default')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $pendingJobs = [];
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                if (isset($payload['data']['commandName'])) {
                    if (str_contains($payload['data']['commandName'], 'MergeBulkSalaryPdfs') ||
                        str_contains($payload['data']['commandName'], 'GenerateSingleSalaryPdf') ||
                        str_contains($payload['data']['commandName'], 'StartBulkSalaryPdfBatch')) {
                        $command = unserialize($payload['data']['command']);
                        if (isset($command->file) && $command->file->id == $fileId) {
                            $pendingJobs[] = [
                                'id' => $job->id,
                                'created_at' => date('Y-m-d H:i:s', $job->created_at),
                                'attempts' => $job->attempts,
                                'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                                'status' => $job->reserved_at ? 'processing' : 'pending',
                            ];
                        }
                    }
                }
            }
            return $pendingJobs;
        } catch (\Exception $e) {
            Log::error('Lỗi khi kiểm tra pending jobs', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * API endpoint để check job status
     */
    public function checkJobStatus($fileId)
    {
        try {
            $pendingJobs = $this->getPendingJobsForFile($fileId);
            $failedJobs = \DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($job) use ($fileId) {
                    $payload = json_decode($job->payload, true);
                    if (isset($payload['data']['commandName']) &&
                        (str_contains($payload['data']['commandName'], 'MergeBulkSalaryPdfs') ||
                         str_contains($payload['data']['commandName'], 'GenerateSingleSalaryPdf') ||
                         str_contains($payload['data']['commandName'], 'StartBulkSalaryPdfBatch'))) {
                        $command = unserialize($payload['data']['command']);
                        if (isset($command->file) && $command->file->id == $fileId) {
                            return [
                                'id' => $job->id,
                                'failed_at' => $job->failed_at,
                                'exception' => substr($job->exception, 0, 200),
                            ];
                        }
                    }
                    return null;
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'has_pending' => count($pendingJobs) > 0,
                'has_failed' => count($failedJobs) > 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi check job status', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download 1 bản xuất PDF hàng loạt.
     */
    public function downloadBulkExport($exportId)
    {
        $export = SalaryBulkExport::findOrFail($exportId);
        $rawPath = trim((string)$export->file_path);
        Log::debug('downloadBulkExport - start', [
            'export_id' => $export->id,
            'salary_file_id' => $export->salary_file_id,
            'raw_file_path' => $rawPath,
        ]);

        // Trường hợp mới: lưu ở <project>/bulk-exports/...
        if (str_contains($rawPath, 'bulk-exports/')) {
            $pos = strpos($rawPath, 'bulk-exports/');
            $relative = substr($rawPath, $pos); // bulk-exports/xxx.zip
            $absolute = base_path($relative);

            Log::debug('downloadBulkExport - bulk-exports branch', [
                'relative' => $relative,
                'absolute' => $absolute,
                'is_file' => is_file($absolute),
                'is_readable' => is_readable($absolute),
            ]);

            if (!is_file($absolute)) {
                return redirect()->back()->with('error', 'File ZIP không tồn tại trên server.');
            }

            // Đảm bảo file đọc được (tránh lỗi BinaryFileResponse "File must be readable")
            if (!is_readable($absolute)) {
                @chmod($absolute, 0644);
            }
            if (!is_readable($absolute)) {
                return redirect()->back()->with('error', 'File ZIP không đọc được (permission). Vui lòng kiểm tra quyền truy cập trên server.');
            }

            return response()->download($absolute, basename($absolute));
        }

        // Trường hợp cũ: lưu trong disk (storage/app/private/salary-bulk/... hoặc GCS)
        $disk = $this->getStorageDisk();
        $diskName = $this->getStorageDiskName();
        $pos = strpos($rawPath, 'salary-bulk/');
        if ($pos !== false) {
            $path = substr($rawPath, $pos);
        } else {
            $path = ltrim($rawPath, '/');
        }

        // Remove any hidden control characters (e.g. \r, \n, \t, NULL)
        $path = preg_replace('/[[:cntrl:]]+/', '', (string)$path);
        $path = $this->normalizeFilePath($path);

        Log::debug('downloadBulkExport - storage disk branch', [
            'disk' => $diskName,
            'normalized_path' => $path,
            'exists' => $disk->exists($path),
            'path_len' => strlen($path),
        ]);

        if (!$disk->exists($path)) {
            return redirect()->back()->with('error', 'File ZIP không tồn tại trên server.');
        }

        // Nếu là local disk, thử dùng path() để download trực tiếp
        if ($diskName === 'local') {
            try {
                $absolutePath = $disk->path($path);
                if (is_file($absolutePath) && is_readable($absolutePath)) {
                    return response()->download($absolutePath, basename($path));
                }
            } catch (\Exception $e) {
                Log::debug('Cannot get absolute path, using disk download', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: Sử dụng disk download hoặc download từ GCS về temp file
        try {
            if (method_exists($disk, 'download')) {
                return $disk->download($path, basename($path));
            }
        } catch (\Exception $e) {
            Log::debug('disk->download() failed, downloading to temp file', [
                'error' => $e->getMessage(),
            ]);
        }

        // Nếu không có method download() (ví dụ GCS), download về temp file
        $fileContent = $disk->get($path);
        if ($fileContent === false) {
            return redirect()->back()->with('error', 'Không thể đọc file từ storage.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'bulk_export_');
        file_put_contents($tempFile, $fileContent);

        return response()->download($tempFile, basename($path))->deleteFileAfterSend(true);
    }

    /**
     * Xóa 1 bản xuất PDF hàng loạt (bao gồm record DB và file ZIP).
     */
    public function destroyBulkExport($exportId)
    {
        try {
            $export = SalaryBulkExport::findOrFail($exportId);
            $rawPath = trim((string)$export->file_path);

            // Xóa file nếu còn tồn tại trên ổ đĩa
            if ($rawPath !== '') {
                // Nhánh mới: file lưu ở <project>/bulk-exports/...
                if (str_contains($rawPath, 'bulk-exports/')) {
                    $pos = strpos($rawPath, 'bulk-exports/');
                    $relative = substr($rawPath, $pos);
                    $absolute = base_path($relative);

                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                } else {
                    // Nhánh cũ: lưu trong disk local (storage/app/private/salary-bulk/...)
                    $disk = Storage::disk('local');
                    $pos = strpos($rawPath, 'salary-bulk/');
                    if ($pos !== false) {
                        $path = substr($rawPath, $pos);
                    } else {
                        $path = ltrim($rawPath, '/');
                    }

                    $path = preg_replace('/[[:cntrl:]]+/', '', (string)$path);

                    if ($path !== '' && $disk->exists($path)) {
                        $disk->delete($path);
                    }
                }
            }

            $salaryFileId = $export->salary_file_id;
            $export->delete();

            return redirect()
                ->route('salary-files.bulk-history', $salaryFileId)
                ->with('success', 'Đã xoá lịch sử xuất PDF và file ZIP tương ứng.');
        } catch (\Throwable $e) {
            Log::error('Lỗi khi xoá lịch sử xuất PDF hàng loạt', [
                'export_id' => $exportId ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Không thể xoá lịch sử xuất PDF: ' . $e->getMessage());
        }
    }

    /**
     * Dùng chung cho preview và bulk: tạo PDF content cho 1 dòng trong file lương.
     */
    private function generatePdfContentForRow(SalaryFile $file, TemplateSelection $template, int $rowNumber): string
    {
        if ($rowNumber < 1) {
            $rowNumber = 1;
        }

        $templatePath = 'templates/' . $template->file_name;
        $templateDisk = $this->getStorageDisk();
        if (!$templateDisk->exists($templatePath)) {
            throw new \RuntimeException('File template không tồn tại.');
        }
        // Handle GCS: download to temp file if needed
        $diskName = $this->getStorageDiskName();
        if ($diskName === 'local') {
            try {
                $fullTemplatePath = $templateDisk->path($templatePath);
            } catch (\Exception $e) {
                // Fallback: download từ GCS về temp file
                $fileContent = $templateDisk->get($templatePath);
                $tempFile = tempnam(sys_get_temp_dir(), 'template_');
                file_put_contents($tempFile, $fileContent);
                $fullTemplatePath = $tempFile;
            }
        } else {
            // GCS: download về temp file
            $fileContent = $templateDisk->get($templatePath);
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            file_put_contents($tempFile, $fileContent);
            $fullTemplatePath = $tempFile;
        }

        // Load salary file (source data) to fetch values by column from selected row
        // Ưu tiên đọc từ file sheet đã tách (nhanh hơn), fallback về file gốc
        $salaryDataByColumn = [];
        $pathToRead = $file->salary_sheet_path ?? $file->file_path;
        
        $disk = $this->getStorageDisk();
        $diskName = $this->getStorageDiskName();
        $normalizedPath = $this->normalizeFilePath($pathToRead);
        
        if (!empty($pathToRead) && $disk->exists($normalizedPath)) {
            // Nếu là local disk, lấy absolute path
            if ($diskName === 'local') {
                try {
                    $fullSalaryPath = $disk->path($normalizedPath);
                } catch (\Exception $e) {
                    // Fallback: download từ GCS về temp file
                    $fileContent = $disk->get($normalizedPath);
                    $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
                    file_put_contents($tempFile, $fileContent);
                    $fullSalaryPath = $tempFile;
                }
            } else {
                // GCS: download về temp file
                $fileContent = $disk->get($normalizedPath);
                $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
                file_put_contents($tempFile, $fileContent);
                $fullSalaryPath = $tempFile;
            }
            $salarySpreadsheet = IOFactory::load($fullSalaryPath);

            // Nếu dùng file sheet đã tách, lấy sheet đầu tiên (chỉ có 1 sheet)
            // Nếu dùng file gốc, tìm sheet có tên "Bảng Lương"
            if ($file->salary_sheet_path) {
                $salarySheet = $salarySpreadsheet->getActiveSheet();
            } else {
                $salarySheet = $this->findSheetByName($salarySpreadsheet, 'Bảng Lương');
                if (!$salarySheet) {
                    throw new \RuntimeException('Không tìm thấy sheet có tên "Bảng Lương" trong file lương.');
                }
            }
            $maxDataRow = $salarySheet->getHighestDataRow();
            if ($rowNumber > $maxDataRow) {
                throw new \RuntimeException('Dòng được chọn không tồn tại trong file lương.');
            }

            // Preload values for any referenced columns to avoid repeated cell reads
            $selections = is_array($template->selections ?? null) ? $template->selections : [];
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

        // Load template Excel
        $spreadsheet = IOFactory::load($fullTemplatePath);

        $sheetCount = $spreadsheet->getSheetCount();
        if ($sheetCount < 1) {
            throw new \RuntimeException('File Excel không có sheet nào.');
        }

        $sheetIndex = $sheetCount >= 2 ? 1 : 0;
        $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet = $spreadsheet->getActiveSheet();

        // Gắn logo nếu có
        if ($logoPath = $this->resolveLogoPath()) {
            try {
                $headerFooter = $sheet->getHeaderFooter();
                $headerFooter->setOddHeader('&L&G');

                $logo = new HeaderFooterDrawing();
                $logo->setName('Logo');
                $logo->setPath($logoPath);
                $logo->setHeight(150);
                $headerFooter->addImage($logo);
            } catch (\Throwable $e) {
                Log::warning('Không thể gắn logo vào PDF', [
                    'logo_path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($logoPath);
                $drawing->setHeight(150);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(2);
                $drawing->setWorksheet($sheet);
            } catch (\Throwable $e) {
                Log::warning('Không thể chèn logo lên sheet trước khi xuất PDF', [
                    'logo_path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $selections = is_array($template->selections ?? null) ? $template->selections : [];

        // 1) non-formula first
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

        // 2) formulas
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

            $normalizedFormula = strtolower(preg_replace('/\s+/', '', $formula));

            // Công thức đặc biệt: =month('mm/yyyy') -> lấy từ SalaryFile.month, format lại thành mm/yyyy
            if ($normalizedFormula === "=month('mm/yyyy')") {
                $monthValue = (string)($file->month ?? '');
                $formattedMonth = '';
                if (preg_match('/^(0[1-9]|1[0-2])-(\\d{4})$/', $monthValue, $m)) {
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
                    if (preg_match('/\\b(0[1-9]|1[0-2])[\\/\\-](\\d{4})\\b/u', $sourceValue, $m)) {
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

        // Auto-merge cells where text content overflows into adjacent empty cells
        // (mimics Excel's behavior when wrapText is off)
        $this->autoMergeOverflowingCells($sheet);

        // Increase row heights
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

        // Padding via indent
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $indentIncrease = 1;
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                $style = $sheet->getStyle($cellAddress);
                $alignment = $style->getAlignment();

                $horizontal = $alignment->getHorizontal();
                $vertical = $alignment->getVertical() ?? Alignment::VERTICAL_BOTTOM;
                $currentIndent = $alignment->getIndent() ?? 0;

                if ($horizontal === null ||
                    $horizontal === Alignment::HORIZONTAL_GENERAL ||
                    $horizontal === Alignment::HORIZONTAL_LEFT ||
                    $horizontal === Alignment::HORIZONTAL_CENTER) {
                    $alignment->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $alignment->setVertical($vertical);
                    $alignment->setIndent($currentIndent + $indentIncrease);
                } elseif ($horizontal === Alignment::HORIZONTAL_RIGHT) {
                    $alignment->setHorizontal($horizontal);
                    $alignment->setVertical($vertical);
                    $alignment->setIndent($currentIndent + $indentIncrease);
                }
            }
        }

        // Page setup
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.5);
        $margins->setBottom(0.5);
        $margins->setLeft(0.6);
        $margins->setRight(0.4);

        $writer = new Mpdf($spreadsheet);
        $writer->setSheetIndex($sheetIndex);
        
        // Cấu hình mPDF để xử lý hình ảnh tốt hơn
        // Sử dụng reflection để truy cập instance mPDF bên trong (vì getMpdf() không còn tồn tại trong phiên bản mới)
        try {
            $reflection = new \ReflectionClass($writer);
            $mpdfProperty = $reflection->getProperty('mpdf');
            $mpdf = $mpdfProperty->getValue($writer);
            
            if ($mpdf && is_object($mpdf)) {
                $mpdf->setAutoTopMargin = 'stretch';
                $mpdf->SetDefaultBodyCSS('background', 'white');
                // Giảm độ phân giải hình ảnh để xử lý nhanh hơn (96 dpi thay vì 300)
                $mpdf->img_dpi = 96;
                // Tăng thời gian timeout cho cURL khi tải hình ảnh
                $mpdf->curlTimeout = 30;
            }
        } catch (\ReflectionException | \Exception $e) {
            // Nếu không thể truy cập mPDF instance, bỏ qua cấu hình
            Log::warning('Could not access mPDF instance for configuration', ['error' => $e->getMessage()]);
        }

        ob_start();
        $writer->save('php://output');
        return (string)ob_get_clean();
    }

    /**
     * Lấy tên nhân viên ở 1 dòng trong sheet salary (ưu tiên cột tiêu đề chứa "tên"/"name").
     */
    private function getEmployeeNameFromSalarySheet($salarySheet, int $rowNumber): string
    {
        try {
            $highestColumn = $salarySheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $nameCol = 1; // default A
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $addr = Coordinate::stringFromColumnIndex($col) . '1';
                $header = strtolower(trim((string)($salarySheet->getCell($addr)->getFormattedValue() ?? '')));
                if ($header !== '' && (str_contains($header, 'tên') || str_contains($header, 'name'))) {
                    $nameCol = $col;
                    break;
                }
            }

            $nameAddr = Coordinate::stringFromColumnIndex($nameCol) . $rowNumber;
            $name = trim((string)($salarySheet->getCell($nameAddr)->getFormattedValue() ?? ''));
            return $name;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        if ($name === '') return 'file.pdf';

        // Replace directory separators & control chars
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

    /**
     * Tìm đường dẫn logo để chèn vào PDF (ưu tiên storage/public rồi public path)
     */
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


    /**
     * Tự động merge các ô có nội dung tràn sang ô bên cạnh (giống Excel khi wrapText = false).
     * Duyệt qua tất cả các ô, nếu ô chứa chuỗi không có wrapText và nội dung rộng hơn cột,
     * thì merge với các ô bên phải liền kề còn trống cho đến khi đủ chỗ hoặc hết ô trống.
     */
    private function autoMergeOverflowingCells(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        // Xây dựng bản đồ các ô đã thuộc merge range
        $mergedCellMap = [];
        foreach ($sheet->getMergeCells() as $mergeRange) {
            foreach (Coordinate::extractAllCellReferencesInRange($mergeRange) as $cellRef) {
                $mergedCellMap[$cellRef] = $mergeRange;
            }
        }

        $highestRow = $sheet->getHighestDataRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $cellAddr   = $colLetter . $row;

                // Bỏ qua ô đã thuộc merge range
                if (isset($mergedCellMap[$cellAddr])) {
                    continue;
                }

                $cell  = $sheet->getCell($cellAddr);
                $value = $cell->getValue();

                // Chỉ xử lý ô có nội dung chuỗi không rỗng
                if ($value === null || $value === '' || !is_string($value)) {
                    continue;
                }

                // Bỏ qua ô có wrapText = true (nội dung cần xuống dòng trong ô)
                $style     = $sheet->getStyle($cellAddr);
                $alignment = $style->getAlignment();
                if ($alignment->getWrapText() === true) {
                    continue;
                }

                // Ước tính độ rộng nội dung (đơn vị: column-width character units)
                $fontSize = $style->getFont()->getSize() ?? 11;
                // Hệ số: mỗi ký tự (kể cả Unicode/tiếng Việt) ≈ 1.15 char-unit ở font mặc định
                $textWidthChars = mb_strlen($value) * ($fontSize / 11) * 1.15;

                $colWidth = $sheet->getColumnDimension($colLetter)->getWidth();
                if ($colWidth <= 0) {
                    $colWidth = 8.43; // độ rộng mặc định của Excel
                }

                // Nội dung vừa với ô, không cần merge
                if ($textWidthChars <= $colWidth) {
                    continue;
                }

                // Tìm các ô trống bên phải để merge vào
                $totalWidth = $colWidth;
                $endCol     = $col;

                for ($nextCol = $col + 1; $nextCol <= $highestColIndex; $nextCol++) {
                    $nextLetter = Coordinate::stringFromColumnIndex($nextCol);
                    $nextAddr   = $nextLetter . $row;

                    // Dừng nếu ô bên phải đã có nội dung
                    $nextValue = $sheet->getCell($nextAddr)->getValue();
                    if ($nextValue !== null && $nextValue !== '') {
                        break;
                    }

                    // Dừng nếu ô bên phải đã thuộc merge range khác
                    if (isset($mergedCellMap[$nextAddr])) {
                        break;
                    }

                    $nextWidth = $sheet->getColumnDimension($nextLetter)->getWidth();
                    if ($nextWidth <= 0) {
                        $nextWidth = 8.43;
                    }

                    $totalWidth += $nextWidth;
                    $endCol      = $nextCol;

                    // Đã đủ chỗ cho nội dung
                    if ($totalWidth >= $textWidthChars) {
                        break;
                    }
                }

                // Thực hiện merge nếu cần mở rộng thêm ít nhất 1 ô
                if ($endCol > $col) {
                    $endColLetter = Coordinate::stringFromColumnIndex($endCol);
                    $mergeRange   = $colLetter . $row . ':' . $endColLetter . $row;

                    try {
                        $sheet->mergeCells($mergeRange);
                        // Cập nhật bản đồ merge để các vòng lặp tiếp theo biết
                        foreach (Coordinate::extractAllCellReferencesInRange($mergeRange) as $ref) {
                            $mergedCellMap[$ref] = $mergeRange;
                        }
                    } catch (\Throwable $e) {
                        // Bỏ qua lỗi merge (ví dụ xung đột với merge range hiện có)
                    }
                }
            }
        }
    }

    /**
     * Chỉ đọc Sheet 2 (sheet thứ 2 trong file, index = 1) để in PDF theo template
     */
    private function readExcelSheet2Only(string $filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                return ['data' => [], 'sheetName' => null, 'error' => 'File không tồn tại: ' . $filePath];
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheetCount = $spreadsheet->getSheetCount();
            if ($sheetCount < 2) {
                return ['data' => [], 'sheetName' => null, 'error' => 'File template không có Sheet 2 để in.'];
            }

            $worksheet = $spreadsheet->getSheet(1);
            $sheetName = $worksheet->getTitle();

            $highestRow = $worksheet->getHighestDataRow();
            $highestColumnIndex = $worksheet->getHighestDataColumn();
            if ($highestRow < 1) {
                return ['data' => [], 'sheetName' => $sheetName, 'error' => null];
            }

            $highestColumnNumber = Coordinate::columnIndexFromString($highestColumnIndex);
            $data = [];

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnNumber; $col++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellAddress);
                    $value = $cell->getFormattedValue();
                    $rowData[] = ($value === null || trim((string)$value) === '') ? '' : $value;
                }
                $data[] = $rowData;
            }

            return ['data' => $data, 'sheetName' => $sheetName, 'error' => null];
        } catch (\Exception $e) {
            Log::error("Lỗi khi đọc Sheet 2 file template: {$filePath}", [
                'error' => $e->getMessage(),
            ]);
            return ['data' => [], 'sheetName' => null, 'error' => 'Lỗi khi đọc file template: ' . $e->getMessage()];
        }
    }

    /**
     * Trích danh sách nhân viên từ dữ liệu Excel
     * - Bắt đầu từ hàng có giá trị 1 ở cột A
     * - Kết thúc ở hàng có số cuối cùng ở cột A
     */
    private function extractEmployeeList(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Tìm cột tên: ưu tiên header chứa "tên" hoặc "name"
        // Mặc định cột B (index 1) vì thông thường cột A là STT, cột B là tên nhân viên
        $header = $data[0] ?? [];
        $nameColIndex = null;
        foreach ($header as $idx => $title) {
            $titleStr = strtolower(trim((string)$title));
            if ($titleStr !== '' && (str_contains($titleStr, 'tên') || str_contains($titleStr, 'name') || str_contains($titleStr, 'họ và tên') || str_contains($titleStr, 'ho va ten'))) {
                $nameColIndex = $idx;
                break;
            }
        }
        
        // Nếu không tìm thấy header phù hợp, fallback sang cột B (index 1)
        if ($nameColIndex === null) {
            $nameColIndex = 1;
            Log::debug('Không tìm thấy cột tên trong header, fallback sang cột B (index 1)');
        }

        // Tìm hàng bắt đầu (cột A = 1) và hàng kết thúc (cột A = số cuối cùng)
        $startRowIndex = null;
        $endRowIndex = null;
        
        foreach ($data as $rowIndex => $row) {
            $colAValue = isset($row[0]) ? trim((string)$row[0]) : '';
            
            // Kiểm tra nếu cột A là số
            if (is_numeric($colAValue)) {
                $numValue = (int)$colAValue;
                
                // Tìm hàng bắt đầu (cột A = 1)
                if ($numValue === 1 && $startRowIndex === null) {
                    $startRowIndex = $rowIndex;
                }
                
                // Cập nhật hàng kết thúc (hàng cuối cùng có số ở cột A)
                if ($startRowIndex !== null) {
                    $endRowIndex = $rowIndex;
                }
            } else {
                // Nếu gặp giá trị không phải số sau khi đã bắt đầu, dừng lại
                if ($startRowIndex !== null && $endRowIndex !== null) {
                    break;
                }
            }
        }

        // Nếu không tìm thấy hàng bắt đầu, trả về mảng rỗng
        if ($startRowIndex === null) {
            Log::warning('Không tìm thấy hàng có giá trị 1 ở cột A trong file lương');
            return [];
        }

        $employees = [];
        for ($rowIndex = $startRowIndex; $rowIndex <= $endRowIndex; $rowIndex++) {
            $row = $data[$rowIndex] ?? [];
            $excelRow = $rowIndex + 1; // số dòng trong Excel (1-based)
            
            $colAValue = isset($row[0]) ? trim((string)$row[0]) : '';
            
            // Chỉ lấy các hàng có số ở cột A và STT >= 1 (loại bỏ STT = 0)
            if (!is_numeric($colAValue)) {
                continue;
            }
            
            $stt = (int)$colAValue;
            if ($stt < 1) {
                Log::debug("Skip dòng $excelRow: STT không hợp lệ", ['stt' => $stt, 'row' => $excelRow, 'colA' => $colAValue]);
                continue;
            }

            $name = isset($row[$nameColIndex]) ? trim((string)$row[$nameColIndex]) : '';
            if ($name === '') {
                Log::debug("Skip dòng $excelRow: tên rỗng", ['stt' => $stt, 'row' => $excelRow]);
                continue;
            }
            
            // Kiểm tra xem tên có phải là header không
            if ($this->isHeaderName($name)) {
                Log::debug("Skip dòng $excelRow: tên là header", ['stt' => $stt, 'name' => $name, 'row' => $excelRow]);
                continue;
            }

            // Debug cho dòng 3 và 4
            if ($excelRow <= 4) {
                Log::debug("✅ Thêm dòng $excelRow vào danh sách", ['stt' => $stt, 'name' => $name, 'row' => $excelRow]);
            }

            $employees[] = [
                'row' => $excelRow,
                'name' => $name,
                'stt' => $stt,
            ];
        }

        Log::debug('Trích xuất danh sách nhân viên', [
            'start_row' => $startRowIndex + 1,
            'end_row' => $endRowIndex + 1,
            'total_employees' => count($employees),
        ]);

        return $employees;
    }

    /**
     * Kiểm tra xem tên có phải là dòng header không
     */
    private function isHeaderName(string $name): bool
    {
        // Loại bỏ tất cả khoảng trắng thừa và chuyển về lowercase
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)));
        
        // Các pattern chính xác (exact match)
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
        
        // Các từ khóa chỉ cần chứa (contains)
        $containsKeywords = [
            'họ và',
            'ho va',
        ];
        
        foreach ($containsKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }
        
        // Nếu chỉ có 1-2 từ ngắn và chứa từ khóa header
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

    /**
     * Tìm số hàng Excel theo STT (cột A)
     * @param SalaryFile $file
     * @param int $stt - Số thứ tự cần tìm
     * @return int|null - Số hàng Excel (1-based) hoặc null nếu không tìm thấy
     */
    private function findRowByStt(SalaryFile $file, int $stt): ?int
    {
        try {
            $pathToRead = $file->salary_sheet_path ?? $file->file_path;
            
            $disk = $this->getStorageDisk();
            $diskName = $this->getStorageDiskName();
            $normalizedPath = $this->normalizeFilePath($pathToRead);
            
            if (!$pathToRead || !$disk->exists($normalizedPath)) {
                return null;
            }

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
            $result = $this->readExcelFile($fullPath);
            $data = $result['data'] ?? [];

            if (empty($data)) {
                return null;
            }

            foreach ($data as $rowIndex => $row) {
                $colAValue = isset($row[0]) ? trim((string)$row[0]) : '';
                
                if (is_numeric($colAValue) && (int)$colAValue === $stt) {
                    $excelRow = $rowIndex; // Excel row là 1-based
                    Log::debug("Tìm thấy STT {$stt} ở hàng Excel {$excelRow}");
                    return $excelRow;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Lỗi khi tìm hàng theo STT: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Xóa file lương
     */
    public function destroy($id)
    {
        try {
            $file = SalaryFile::findOrFail($id);
            $facilityId = $file->facility_id;
            
            // Xóa file gốc trên server
            $disk = $this->getStorageDisk();
            $normalizedFilePath = $this->normalizeFilePath($file->file_path);
            if ($file->file_path && $disk->exists($normalizedFilePath)) {
                $disk->delete($normalizedFilePath);
            }
            
            // Xóa file sheet "Bảng lương" trên server
            $normalizedSheetPath = $this->normalizeFilePath($file->salary_sheet_path);
            if ($file->salary_sheet_path && $disk->exists($normalizedSheetPath)) {
                $disk->delete($normalizedSheetPath);
            }
            
            // Xóa record trong database
            $file->delete();

            if ($facilityId) {
                return redirect()
                    ->route('facilities.salary-files', ['facility' => $facilityId])
                    ->with('success', 'Xóa file lương thành công!');
            }

            return redirect()
                ->route('salary-files.index')
                ->with('success', 'Xóa file lương thành công!');
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa file lương: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa file: ' . $e->getMessage());
        }
    }

    /**
     * Download file lương
     */
    public function download($id)
    {
        try {
            $file = SalaryFile::findOrFail($id);
            
            // Sử dụng cùng disk như khi lưu file
            $diskName = env('FILESYSTEM_DISK', 'local');
            $disk = Storage::disk($diskName);
            
            $filePath = $file->file_path;
            
            // Normalize path - loại bỏ các prefix không cần thiết
            // Kiểm tra prefix dài hơn trước
            if (str_starts_with($filePath, 'storage/app/private/')) {
                $filePath = substr($filePath, strlen('storage/app/private/'));
            } elseif (str_starts_with($filePath, 'storage/app/')) {
                $filePath = substr($filePath, strlen('storage/app/'));
            }
            $filePath = ltrim($filePath, '/');
            
            Log::debug('download salary file', [
                'file_id' => $id,
                'original_path' => $file->file_path,
                'normalized_path' => $filePath,
                'disk' => $diskName,
                'exists' => $disk->exists($filePath),
            ]);
            
            if (!$disk->exists($filePath)) {
                Log::error('File không tồn tại trên server', [
                    'file_id' => $id,
                    'original_path' => $file->file_path,
                    'normalized_path' => $filePath,
                    'disk' => $diskName,
                ]);
                return redirect()->route('salary-files.index')
                    ->with('error', 'File không tồn tại trên server.');
            }

            // Nếu là local disk, thử dùng path() để lấy absolute path
            if ($diskName === 'local') {
                try {
                    $absolutePath = $disk->path($filePath);
                    if (is_file($absolutePath) && is_readable($absolutePath)) {
                        return response()->download($absolutePath, $file->file_name);
                    }
                } catch (\Exception $e) {
                    Log::debug('Cannot get absolute path, using disk download', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback: Sử dụng disk download (hoặc download từ GCS về temp file)
            try {
                // Thử dùng method download() nếu có
                if (method_exists($disk, 'download')) {
                    return $disk->download($filePath, $file->file_name);
                }
            } catch (\Exception $e) {
                Log::debug('disk->download() failed, downloading to temp file', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Nếu không có method download() (ví dụ GCS), download về temp file
            $fileContent = $disk->get($filePath);
            if ($fileContent === false) {
                throw new \Exception('Cannot read file from storage');
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'salary_');
            file_put_contents($tempFile, $fileContent);

            return response()->download($tempFile, $file->file_name)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Lỗi khi download file lương: ' . $e->getMessage(), [
                'file_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('salary-files.index')
                ->with('error', 'Có lỗi xảy ra khi tải file: ' . $e->getMessage());
        }
    }

    /**
     * Sao chép file lương (tạo bản sao).
     * Nhận name, month, sheet_name từ modal popup.
     * Nếu file gốc liên kết Google Sheet, tự động tạo bản sao Google Sheet
     * trong cùng thư mục Drive của cơ sở (data_link).
     */
    public function copy(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'month'      => ['nullable', 'string', 'max:7', 'regex:/^(0[1-9]|1[0-2])-[0-9]{4}$/'],
            'sheet_name' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Vui lòng nhập tên file lương.',
            'month.regex'   => 'Định dạng tháng không đúng (mm-yyyy).',
        ]);

        try {
            $original = SalaryFile::findOrFail($id);
            $disk = $this->getStorageDisk();

            // Tên mới lấy từ form (fallback về "(Copy)" nếu thiếu)
            $newName  = trim($request->input('name')) ?: ($original->name . ' (Copy)');
            $newMonth = $request->input('month') ?: $original->month;
            // Tên Google Sheet: ưu tiên sheet_name từ form (prefix.mm.yyyy)
            $googleSheetCopyName = trim((string) $request->input('sheet_name')) ?: $newName;

            // ── Validate: Google Sheet tên này đã tồn tại trong folder chưa? ──
            if ($original->google_sheet_url && $googleSheetCopyName !== '') {
                $targetFolderIdForCheck = null;
                if ($original->facility_id) {
                    $facilityForCheck = Facility::find($original->facility_id);
                    if ($facilityForCheck && $facilityForCheck->data_link) {
                        foreach (preg_split('/\r\n|\r|\n/', (string) $facilityForCheck->data_link) as $line) {
                            $line = trim($line);
                            if (str_contains($line, 'drive.google.com/drive/folders/') &&
                                preg_match('~/folders/([^/?]+)~', $line, $fm)) {
                                $targetFolderIdForCheck = $fm[1];
                                break;
                            }
                        }
                    }
                }

                if ($targetFolderIdForCheck) {
                    $existCheck = $this->checkGoogleSheetExistsInFolder(
                        $googleSheetCopyName,
                        $targetFolderIdForCheck
                    );

                    if ($existCheck['exists']) {
                        return redirect()->back()
                            ->with('error',
                                'File Google Sheet "' . $googleSheetCopyName . '" đã tồn tại trong thư mục Drive. '
                                . 'Vui lòng kiểm tra lại hoặc ấn vào button "Upload file" để tải file sẵn có lên.')
                            ->withInput();
                    }

                    if (!$existCheck['checked']) {
                        // API lỗi → cảnh báo nhưng cho phép tiếp tục
                        Log::warning('Không thể kiểm tra Drive folder, tiếp tục copy', [
                            'reason' => $existCheck['message'] ?? '',
                        ]);
                    }
                }
            }

            // ── Validate: so sánh danh sách nhân viên với mail list ────────
            if ($original->facility_id) {
                // Ưu tiên salary_sheet_path (sheet gốc), fallback sang file_path
                $pathCandidates = array_filter([
                    $original->salary_sheet_path,
                    $original->file_path,
                ]);

                $localPathForValidation = null;
                $tempFileForValidation  = null;

                foreach ($pathCandidates as $candidate) {
                    $normalized = $this->normalizeFilePath($candidate);

                    Log::info('copy() validation: kiểm tra file path', [
                        'original_id' => $original->id,
                        'raw_path'    => $candidate,
                        'normalized'  => $normalized,
                        'exists'      => $disk->exists($normalized),
                    ]);

                    if (!$disk->exists($normalized)) {
                        continue;
                    }

                    // Local disk → dùng absolute path trực tiếp
                    if ($this->getStorageDiskName() === 'local') {
                        try {
                            $abs = $disk->path($normalized);
                            if (is_file($abs)) {
                                $localPathForValidation = $abs;
                                break;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('copy() validation: không lấy được abs path', ['error' => $e->getMessage()]);
                        }
                    }

                    // GCS hoặc abs path thất bại → stream về temp file
                    $tempFileForValidation = tempnam(sys_get_temp_dir(), 'salary_val_');
                    file_put_contents($tempFileForValidation, $disk->get($normalized));
                    $localPathForValidation = $tempFileForValidation;
                    break;
                }

                if ($localPathForValidation) {
                    Log::info('copy() validation: bắt đầu so sánh mail list', [
                        'original_id' => $original->id,
                        'facility_id' => $original->facility_id,
                        'using_path'  => $localPathForValidation,
                    ]);

                    $validationResult = $this->validateEmployeeCountWithMailList(
                        $localPathForValidation,
                        $original->facility_id,
                        'Bảng lương'
                    );

                    if ($tempFileForValidation && is_file($tempFileForValidation)) {
                        @unlink($tempFileForValidation);
                    }

                    Log::info('copy() validation: kết quả', [
                        'original_id' => $original->id,
                        'valid'       => $validationResult['valid'],
                        'message'     => $validationResult['message'] ?? null,
                    ]);

                    if (!$validationResult['valid']) {
                        return redirect()->back()
                            ->with('error',
                                'Không thể sao chép: danh sách nhân viên không khớp với mail list. '
                                . $validationResult['message'])
                            ->withInput();
                    }
                } else {
                    Log::warning('copy() validation: không tìm thấy file Excel để validate, bỏ qua kiểm tra', [
                        'original_id'        => $original->id,
                        'file_path'          => $original->file_path,
                        'salary_sheet_path'  => $original->salary_sheet_path,
                    ]);
                }
            }

            // ── Copy file Excel chính ──────────────────────────────────────
            $newFilePath = null;
            $newFileName = null;
            $newFileSize = $original->file_size;

            if ($original->file_path) {
                $normalizedOrigPath = $this->normalizeFilePath($original->file_path);

                if ($disk->exists($normalizedOrigPath)) {
                    $ext = pathinfo($original->file_name ?? $normalizedOrigPath, PATHINFO_EXTENSION);
                    $newFileName = pathinfo($original->file_name ?? $normalizedOrigPath, PATHINFO_FILENAME) . '_copy_' . time() . '.' . $ext;
                    $newFilePath = 'salary-files/' . $newFileName;

                    $disk->copy($normalizedOrigPath, $newFilePath);
                    $newFileSize = $disk->size($newFilePath);
                }
            }

            // ── Copy salary sheet file nếu có ──────────────────────────────
            $newSheetPath = null;
            $newSheetFileName = null;
            $newSheetSize = $original->salary_sheet_size;

            if ($original->salary_sheet_path) {
                $normalizedSheetPath = $this->normalizeFilePath($original->salary_sheet_path);

                if ($disk->exists($normalizedSheetPath)) {
                    $sheetExt = pathinfo($original->salary_sheet_file_name ?? $normalizedSheetPath, PATHINFO_EXTENSION);
                    $newSheetFileName = pathinfo($original->salary_sheet_file_name ?? $normalizedSheetPath, PATHINFO_FILENAME) . '_copy_' . time() . '.' . $sheetExt;
                    $newSheetPath = 'salary-files/' . $newSheetFileName;

                    $disk->copy($normalizedSheetPath, $newSheetPath);
                    $newSheetSize = $disk->size($newSheetPath);
                }
            }

            // ── Copy Google Sheet nếu có ────────────────────────────────────
            $newGoogleSheetUrl = $original->google_sheet_url;
            $googleSheetNote   = null;

            if ($original->google_sheet_url && preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $original->google_sheet_url, $m)) {
                $originalSheetId = $m[1];

                // Tìm folder Drive từ data_link của cơ sở
                $targetFolderId = null;
                if ($original->facility_id) {
                    $facility = Facility::find($original->facility_id);
                    if ($facility && $facility->data_link) {
                        foreach (preg_split('/\r\n|\r|\n/', (string) $facility->data_link) as $line) {
                            $line = trim($line);
                            if (str_contains($line, 'drive.google.com/drive/folders/') &&
                                preg_match('~/folders/([^/?]+)~', $line, $fm)) {
                                $targetFolderId = $fm[1];
                                break;
                            }
                        }
                    }
                }

                $copyResult = $this->copyGoogleSheet($originalSheetId, $googleSheetCopyName, $targetFolderId);

                if ($copyResult['success']) {
                    $newGoogleSheetUrl = $copyResult['url'];
                    Log::info('Đã sao chép Google Sheet', [
                        'original_sheet_id' => $originalSheetId,
                        'new_sheet_id'      => $copyResult['id'],
                        'target_folder'     => $targetFolderId,
                    ]);
                } elseif (!empty($copyResult['auth_error'])) {
                    // Lỗi xác thực Google OAuth → huỷ toàn bộ, xoá file local đã copy, redirect authorize
                    Log::error('Lỗi xác thực Google Drive khi sao chép, huỷ copy', [
                        'reason' => $copyResult['message'] ?? '',
                    ]);

                    // Dọn file local đã copy (nếu có)
                    if ($newFilePath && $disk->exists($newFilePath)) {
                        $disk->delete($newFilePath);
                    }
                    if ($newSheetPath && $disk->exists($newSheetPath)) {
                        $disk->delete($newSheetPath);
                    }

                    return redirect(route('google.authorize'))
                        ->with('error',
                            'Xác thực Google Drive đã hết hạn. '
                            . 'Vui lòng đăng nhập lại để cấp quyền cho ứng dụng, sau đó thử sao chép lại.');
                } else {
                    // Lỗi khác (quota, permission...) → vẫn tạo bản sao local, ghi chú lỗi sheet
                    $googleSheetNote = $copyResult['message'] ?? 'Không sao chép được Google Sheet';
                    Log::warning('Không sao chép được Google Sheet, giữ nguyên URL gốc', [
                        'reason' => $googleSheetNote,
                    ]);
                }
            }

            // ── Tạo bản ghi mới ────────────────────────────────────────────
            $newFile = SalaryFile::create([
                'name'                    => $newName,
                'description'             => $original->description,
                'file_name'               => $newFileName ?? $original->file_name,
                'file_path'               => $newFilePath ?? $original->file_path,
                'file_size'               => $newFileSize,
                'salary_sheet_file_name'  => $newSheetFileName ?? $original->salary_sheet_file_name,
                'salary_sheet_path'       => $newSheetPath ?? $original->salary_sheet_path,
                'salary_sheet_size'       => $newSheetSize,
                'month'                   => $newMonth,
                'template_selection_id'   => $original->template_selection_id,
                'facility_id'             => $original->facility_id,
                'google_sheet_url'        => $newGoogleSheetUrl,
            ]);

            Log::info('Đã sao chép file lương', [
                'original_id'       => $original->id,
                'new_id'            => $newFile->id,
                'new_name'          => $newName,
                'google_sheet_url'  => $newGoogleSheetUrl,
            ]);

            $linkHtml = $newGoogleSheetUrl
                ? ' <a href="' . e($newGoogleSheetUrl) . '" target="_blank" rel="noopener noreferrer">Mở Google Sheet</a>'
                : '';
            $successMsg = 'Đã sao chép file "' . e($original->name) . '" thành công. Vui lòng kiểm tra lại trong folder Drive của cơ sở.' . $linkHtml;
            if ($googleSheetNote) {
                $successMsg .= ' (Lưu ý: ' . $googleSheetNote . ')';
            }

            if ($original->facility_id) {
                return redirect()
                    ->route('facilities.salary-files', $original->facility_id)
                    ->with('success', $successMsg);
            }

            return redirect()
                ->route('salary-files.index')
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            Log::error('Lỗi khi sao chép file lương: ' . $e->getMessage(), [
                'file_id' => $id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi sao chép file: ' . $e->getMessage());
        }
    }

    /**
     * Sao chép một Google Sheet sang tên mới, tuỳ chọn đặt vào folder Drive.
     *
     * Ưu tiên dùng OAuth refresh token (GOOGLE_OAUTH_REFRESH_TOKEN) vì copy
     * sẽ thuộc quota của tài khoản người dùng thật, tránh lỗi storageQuotaExceeded
     * của service account. Fallback sang service account nếu chưa có refresh token.
     */
    private function copyGoogleSheet(string $originalFileId, string $newName, ?string $targetFolderId = null): array
    {
        try {
            $client = $this->buildGoogleDriveClient();
            $service = new GoogleDrive($client);

            $driveFile = new \Google\Service\Drive\DriveFile();
            $driveFile->setName($newName);

            $params = ['fields' => 'id, name, webViewLink', 'supportsAllDrives' => true];
            if ($targetFolderId) {
                $driveFile->setParents([$targetFolderId]);
            }

            $copiedFile = $service->files->copy($originalFileId, $driveFile, $params);

            $newUrl = sprintf(
                'https://docs.google.com/spreadsheets/d/%s/edit?usp=drivesdk',
                $copiedFile->getId()
            );

            return [
                'success' => true,
                'url'     => $newUrl,
                'id'      => $copiedFile->getId(),
                'name'    => $copiedFile->getName(),
            ];
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            // Phát hiện lỗi 401 / UNAUTHENTICATED từ Google API
            $isAuthError = false;
            if ($e instanceof \Google\Service\Exception) {
                $isAuthError = $e->getCode() === 401;
            }
            if (!$isAuthError) {
                $isAuthError = str_contains($message, '401')
                    || str_contains($message, 'UNAUTHENTICATED')
                    || str_contains($message, 'CREDENTIALS_MISSING')
                    || str_contains($message, 'Login Required');
            }

            Log::error('Lỗi khi sao chép Google Sheet qua Drive API', [
                'original_file_id' => $originalFileId,
                'target_folder_id' => $targetFolderId,
                'auth_error'       => $isAuthError,
                'error'            => $message,
            ]);

            return ['success' => false, 'auth_error' => $isAuthError, 'message' => $message];
        }
    }

    /**
     * Khởi tạo GoogleClient với OAuth (ưu tiên) hoặc service account (fallback).
     *
     * - OAuth: dùng GOOGLE_OAUTH_CLIENT_ID + SECRET + REFRESH_TOKEN
     *   → file copy thuộc quota tài khoản người dùng thật, không bị storageQuotaExceeded.
     * - Service account: fallback khi chưa có refresh token.
     */
    private function buildGoogleDriveClient(): GoogleClient
    {
        $refreshToken = env('GOOGLE_OAUTH_REFRESH_TOKEN');
        $clientId     = env('GOOGLE_OAUTH_CLIENT_ID');
        $clientSecret = env('GOOGLE_OAUTH_CLIENT_SECRET');

        if ($refreshToken && $clientId && $clientSecret) {
            $client = new GoogleClient();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setAccessType('offline');
            $client->addScope(GoogleDrive::DRIVE);
            $client->fetchAccessTokenWithRefreshToken($refreshToken);

            Log::debug('buildGoogleDriveClient: dùng OAuth user credentials');
            return $client;
        }

        // Fallback: service account
        $configPath         = env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON');
        $absoluteConfigPath = base_path((string) $configPath);

        if (!$configPath || !is_file($absoluteConfigPath)) {
            throw new \RuntimeException(
                'Chưa cấu hình Google Drive: cần GOOGLE_OAUTH_REFRESH_TOKEN hoặc GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON hợp lệ.'
            );
        }

        $client = new GoogleClient();
        $client->setAuthConfig($absoluteConfigPath);
        $client->addScope(GoogleDrive::DRIVE);

        Log::debug('buildGoogleDriveClient: dùng service account');
        return $client;
    }

    /**
     * Kiểm tra xem đã có file Google Sheet trùng tên trong folder Drive chưa.
     *
     * Trả về:
     *   ['checked' => true,  'exists' => true,  'file' => ['id'=>..,'name'=>..]]  — đã tồn tại
     *   ['checked' => true,  'exists' => false]                                   — chưa tồn tại
     *   ['checked' => false, 'exists' => false, 'message' => ...]                 — không kiểm tra được (API lỗi)
     */
    private function checkGoogleSheetExistsInFolder(string $name, string $folderId): array
    {
        if ($name === '' || $folderId === '') {
            return ['checked' => false, 'exists' => false, 'message' => 'Thiếu tên hoặc folder ID'];
        }

        try {
            $client  = $this->buildGoogleDriveClient();
            $service = new GoogleDrive($client);

            // Escape dấu nháy đơn trong tên để tránh lỗi query
            $escapedName = str_replace("'", "\\'", $name);

            $query = sprintf(
                "'%s' in parents and name = '%s' and mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false",
                $folderId,
                $escapedName
            );

            $results = $service->files->listFiles([
                'q'                   => $query,
                'fields'              => 'files(id, name)',
                'pageSize'            => 1,
                'supportsAllDrives'   => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $results->getFiles();

            if (!empty($files)) {
                return [
                    'checked' => true,
                    'exists'  => true,
                    'file'    => ['id' => $files[0]->getId(), 'name' => $files[0]->getName()],
                ];
            }

            return ['checked' => true, 'exists' => false];

        } catch (\Throwable $e) {
            Log::warning('Không thể kiểm tra file tồn tại trong Drive', [
                'name'      => $name,
                'folder_id' => $folderId,
                'error'     => $e->getMessage(),
            ]);

            return ['checked' => false, 'exists' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Đọc dữ liệu từ sheet "Bảng Lương" (không extract styles để tối ưu tốc độ)
     */
    private function readExcelFile($filePath)
    {
        $startTime = microtime(true);
        
        try {
            if (!file_exists($filePath)) {
                Log::error("File không tồn tại: {$filePath}");
                return ['data' => [], 'styles' => [], 'sheetName' => null];
            }

            $loadStart = microtime(true);
            $spreadsheet = IOFactory::load($filePath);
            $loadTime = round((microtime(true) - $loadStart) * 1000, 2);
            Log::debug("⏱️ Load file Excel", ['time_ms' => $loadTime]);
            
            // Tìm sheet có tên "Bảng Lương"
            $worksheet = $this->findSheetByName($spreadsheet, 'Bảng Lương');
            
            if (!$worksheet) {
                Log::warning("Không tìm thấy sheet 'Bảng Lương', sử dụng sheet đầu tiên có dữ liệu");
                // Fallback: tìm sheet đầu tiên có dữ liệu
                $sheetNames = $spreadsheet->getSheetNames();
                foreach ($sheetNames as $sheetIndex => $sheetName) {
                    $tempWorksheet = $spreadsheet->getSheet($sheetIndex);
                    if ($tempWorksheet->getHighestDataRow() >= 1) {
                        $worksheet = $tempWorksheet;
                        break;
                    }
                }
                
                if (!$worksheet) {
                    return ['data' => [], 'styles' => [], 'sheetName' => null];
                }
            }

            $sheetUsed = $worksheet->getTitle();
            $highestRow = $worksheet->getHighestDataRow();
            $highestColumnIndex = $worksheet->getHighestDataColumn();
            
            if ($highestRow < 1) {
                return ['data' => [], 'styles' => [], 'sheetName' => $sheetUsed];
            }

            $highestColumnNumber = Coordinate::columnIndexFromString($highestColumnIndex);
            $data = [];

            // Đọc data không extract styles để tối ưu tốc độ
            $readStart = microtime(true);
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];

                for ($col = 1; $col <= $highestColumnNumber; $col++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellAddress);
                    $value = $cell->getFormattedValue();
                    $rowData[] = ($value === null || trim((string)$value) === '') ? '' : $value;
                }

                $hasData = false;
                foreach ($rowData as $cellValue) {
                    if (trim((string)$cellValue) !== '') {
                        $hasData = true;
                        break;
                    }
                }

                if ($hasData) {
                    $data[] = $rowData;
                }
            }
            $readTime = round((microtime(true) - $readStart) * 1000, 2);

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info("✅ Đọc sheet 'Bảng Lương' thành công", [
                'sheet_name' => $sheetUsed,
                'total_rows' => $highestRow,
                'total_cols' => $highestColumnNumber,
                'data_rows' => count($data),
                'read_data_time_ms' => $readTime,
                'total_time_ms' => $totalTime,
            ]);

            // Trả về styles rỗng để không ảnh hưởng view
            return ['data' => $data, 'styles' => [], 'sheetName' => $sheetUsed];
        } catch (\Exception $e) {
            Log::error("Lỗi khi đọc file Excel: {$filePath}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['data' => [], 'styles' => [], 'sheetName' => null];
        }
    }

    /**
     * Trích xuất các tham chiếu ô từ công thức Excel
     * Ví dụ: "D9+D10+D11" -> ["D9", "D10", "D11"]
     */
    private function extractCellReferencesFromFormula(string $formula): array
    {
        $references = [];
        
        // Remove leading "=" if present
        $formula = ltrim($formula, '=');
        
        // Pattern to match cell references: 1-3 letters followed by 1-7 digits
        // Examples: A1, B2, D9, D10, AA100, ZZZ9999999
        // Use word boundary to ensure we match complete cell references, not parts of other strings
        $pattern = '/\b([A-Z]{1,3}[0-9]{1,7})\b/i';
        
        if (preg_match_all($pattern, $formula, $matches)) {
            $references = array_unique(array_map('strtoupper', $matches[1]));
        }
        
        return $references;
    }

    /**
     * Trích xuất style từ cell style object
     */
    private function extractCellStyle($style)
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
                if ($horizontal) $result['textAlign'] = strtolower($horizontal);
                $vertical = $alignment->getVertical();
                if ($vertical) $result['verticalAlign'] = strtolower($vertical);
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
                    if (!empty($borderColors)) $result['borderColor'] = $borderColors[0];
                }
            }

            $numberFormat = $style->getNumberFormat();
            if ($numberFormat) $result['numberFormat'] = $numberFormat->getFormatCode();
        } catch (\Exception $e) {
            Log::warning("Lỗi khi trích xuất style: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Tạo file Excel lương từ Google Sheet (CSV export)
     */
    private function buildSalaryExcelFromGoogleSheet(string $sheetUrl): array
    {
        $sheetUrl = trim($sheetUrl);
        if ($sheetUrl === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập link Google Sheets.',
            ];
        }

        // Trích xuất spreadsheet ID: /d/ID/ hoặc /d/ID?
        if (!preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $sheetUrl, $m)) {
            return [
                'success' => false,
                'message' => 'Link Google Sheets không hợp lệ. Ví dụ: https://docs.google.com/spreadsheets/d/.../edit?usp=sharing',
            ];
        }

        $spreadsheetId = $m[1];
        $gid = 0;
        if (preg_match('~[#&]gid=(\d+)~', $sheetUrl, $gidMatch)) {
            $gid = (int)$gidMatch[1];
        }

        $exportUrl = sprintf(
            'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%d',
            $spreadsheetId,
            $gid
        );

        try {
            $response = Http::timeout(30)->get($exportUrl);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi gọi Google Sheets CSV', [
                'url' => $exportUrl,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Không tải được dữ liệu từ Google Sheets. Vui lòng kiểm tra lại kết nối.',
            ];
        }

        if (!$response->successful()) {
            Log::warning('Google Sheets export failed for salary files', [
                'status' => $response->status(),
                'url' => $exportUrl,
            ]);
            return [
                'success' => false,
                'message' => 'Không tải được dữ liệu từ Google Sheets. Kiểm tra link đã chia sẻ "Bất kỳ ai có link đều xem được" chưa.',
            ];
        }

        $csvContent = $response->body();
        // Bỏ BOM nếu có (UTF-8)
        if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
            $csvContent = substr($csvContent, 3);
        }

        // Parse CSV thành mảng rows (không giới hạn cột)
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [
                'success' => false,
                'message' => 'Không thể xử lý dữ liệu Google Sheets.',
            ];
        }
        fwrite($stream, $csvContent);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            // Trim các ô, giữ nguyên số lượng cột
            $row = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $row);
            // Bỏ qua dòng hoàn toàn trống
            $hasData = false;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $rows[] = $row;
            }
        }
        fclose($stream);

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'Sheet không có dữ liệu hoặc định dạng không đúng.',
            ];
        }

        // Tạo Spreadsheet với 1 sheet tên "Bảng lương"
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bảng lương');

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            foreach ($row as $colIndex => $cellValue) {
                $excelCol = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($excelCol . $excelRow, $cellValue);
            }
        }

        // Lưu tạm ra file xlsx rồi đẩy vào storage
        $tempName = 'salary_sheet_' . time() . '_' . uniqid() . '.xlsx';
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempName;

        try {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi ghi file Excel từ Google Sheet', [
                'error' => $e->getMessage(),
            ]);
            @unlink($tempPath);
            return [
                'success' => false,
                'message' => 'Không thể tạo file Excel từ Google Sheet.',
            ];
        }

        $disk = env('FILESYSTEM_DISK', 'local');
        $storagePath = 'salary-files/' . $tempName;
        $fileContents = @file_get_contents($tempPath);
        if ($fileContents === false) {
            @unlink($tempPath);
            return [
                'success' => false,
                'message' => 'Không thể đọc file Excel tạm.',
            ];
        }

        Storage::disk($disk)->put($storagePath, $fileContents);
        $fileSizeBytes = Storage::disk($disk)->size($storagePath);

        @unlink($tempPath);

        // Với disk local, có thể lấy absolute path để tái sử dụng cho validate
        $localFullPath = null;
        if ($disk === 'local') {
            try {
                $localFullPath = Storage::disk($disk)->path($storagePath);
            } catch (\Throwable $e) {
                $localFullPath = null;
            }
        }

        return [
            'success' => true,
            'file_name' => $tempName,
            'file_path' => $storagePath,
            'file_size_bytes' => $fileSizeBytes,
            'local_full_path' => $localFullPath ?? $storagePath,
        ];
    }

    /**
     * Lấy danh sách file Google Sheets (url + label) từ 1 thư mục Google Drive,
     * sử dụng Google Drive API với service account.
     */
    private function fetchGoogleDriveFolderSheets(string $folderUrl): array
    {
        $folderUrl = trim($folderUrl);
        if ($folderUrl === '') {
            return [];
        }

        // Tách folderId từ URL: .../folders/{ID}[?...]
        if (!preg_match('~/folders/([^/?]+)~', $folderUrl, $m)) {
            Log::warning('Không parse được folderId từ Google Drive URL', ['url' => $folderUrl]);
            return [];
        }
        $folderId = $m[1];

        return $this->listDriveSheetsInFolder($folderId);
    }

    /**
     * Dùng Google Drive API (service account) để liệt kê các file Google Sheets trong 1 thư mục.
     */
    private function listDriveSheetsInFolder(string $folderId): array
    {
        $configPath = env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON');
        if (!$configPath) {
            Log::warning('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON chưa được cấu hình trong .env');
            return [];
        }

        $absoluteConfigPath = base_path($configPath);
        if (!is_file($absoluteConfigPath)) {
            Log::warning('File service account JSON không tồn tại', [
                'config' => $configPath,
                'absolute' => $absoluteConfigPath,
            ]);
            return [];
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($absoluteConfigPath);
            $client->addScope(GoogleDrive::DRIVE_READONLY);

            $service = new GoogleDrive($client);

            $query = sprintf(
                "'%s' in parents and mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false",
                $folderId
            );

            $results = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'pageSize' => 100,
            ]);

            $items = [];
            foreach ($results->getFiles() as $file) {
                $sheetUrl = sprintf(
                    'https://docs.google.com/spreadsheets/d/%s/edit?usp=drivesdk',
                    $file->getId()
                );

                $items[] = [
                    'url' => $sheetUrl,
                    'label' => $file->getName(),
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            Log::error('Lỗi khi gọi Google Drive API để liệt kê Sheets trong thư mục', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Kiểm tra và đối chiếu danh sách nhân viên với mail list
     */
    private function validateEmployeeCountWithMailList($uploadedFileOrPath, $facilityId, $selectedSheet)
    {
        try {
            $facility = Facility::find($facilityId);
            $facilityName = $facility ? $facility->name : "ID $facilityId";
            
            // Lấy mail list mặc định của cơ sở
            $mailList = \App\Models\MailList::where('facility_id', $facilityId)
                ->whereRaw('is_default = true')
                ->first();

            if (!$mailList) {
                return [
                    'valid' => false,
                    'message' => 'Cơ sở này chưa có danh sách email mặc định. Vui lòng tạo mail list cho cơ sở trước khi upload file lương.',
                ];
            }

            // Lấy danh sách email và tên từ mail list
            $mailListHeaders = is_array($mailList->headers) ? $mailList->headers : [];
            $mailListRows = is_array($mailList->rows) ? $mailList->rows : [];
            $mailListCount = count($mailListRows);

            // Kiểm tra mail list có email không
            if ($mailListCount === 0) {
                return [
                    'valid' => false,
                    'message' => "Danh sách email của cơ sở $facilityName đang trống. Vui lòng thêm email vào danh sách trước khi upload file lương.",
                ];
            }

            // Xác định cột tên trong mail list
            $nameIndex = null;
            foreach ($mailListHeaders as $idx => $header) {
                $headerLower = mb_strtolower(trim($header), 'UTF-8');
                if ($nameIndex === null && (str_contains($headerLower, 'tên') || str_contains($headerLower, 'name') || str_contains($headerLower, 'họ'))) {
                    $nameIndex = $idx;
                    break;
                }
            }

            // Tạo danh sách tên từ mail list để so sánh
            $mailListNames = [];
            foreach ($mailListRows as $row) {
                $name = $nameIndex !== null && isset($row[$nameIndex]) ? trim($row[$nameIndex]) : '';
                $mailListNames[] = trim($name);
            }

            // Đọc file Excel
            if (is_string($uploadedFileOrPath)) {
                $filePath = $uploadedFileOrPath;
            } else {
                $filePath = $uploadedFileOrPath->getRealPath();
            }
            $spreadsheet = IOFactory::load($filePath);
            
            // Tìm sheet có tên "Bảng Lương"
            $sheet = $this->findSheetByName($spreadsheet, $selectedSheet ?? 'Bảng Lương');
            
            if (!$sheet) {
                return [
                    'valid' => false,
                    'message' => 'Không tìm thấy sheet có tên "Bảng Lương" trong file Excel. Vui lòng kiểm tra lại tên sheet.',
                ];
            }

            // Lấy tên toàn bộ nhân viên từ sheet "Bảng Lương"
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            $highestRow = $sheet->getHighestDataRow();
            
            // Tìm cột tên trong header (tìm ở cả dòng 1 và dòng 2)
            $salaryNameCol = null;
            foreach ([1, 2] as $checkRow) {
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $addr = Coordinate::stringFromColumnIndex($col) . $checkRow;
                    $header = mb_strtolower(trim((string)($sheet->getCell($addr)->getFormattedValue() ?? '')), 'UTF-8');
                    if ($header !== '' && (str_contains($header, 'tên') || str_contains($header, 'name') || str_contains($header, 'họ'))) {
                        $salaryNameCol = $col;
                        break 2; // Thoát cả 2 vòng lặp
                    }
                }
            }
            
            // Nếu không tìm thấy cột tên, mặc định dùng cột B (thường là tên sau STT ở cột A)
            if ($salaryNameCol === null) {
                $salaryNameCol = 2; // Cột B
            }
            
            // Lấy danh sách tên nhân viên từ sheet (chỉ lấy dòng có STT là số)
            $salarySheetNames = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                // Kiểm tra cột A (STT) có phải là số không
                $sttCell = $sheet->getCell('A' . $row);
                $sttRaw = $sttCell->getValue();
                $sttFormatted = trim((string)$sttCell->getFormattedValue());
                
                // Kiểm tra xem giá trị có phải số không (raw hoặc formatted)
                $isNumeric = is_numeric($sttRaw) || is_numeric($sttFormatted);
                
                if (!$isNumeric) {
                    continue; // Bỏ qua dòng không có STT là số
                }
                
                $sttNumber = is_numeric($sttRaw) ? (int)$sttRaw : (int)$sttFormatted;
                if ($sttNumber < 1) {
                    continue; // Bỏ qua nếu STT < 1
                }
                
                $nameAddr = Coordinate::stringFromColumnIndex($salaryNameCol) . $row;
                $name = trim((string)($sheet->getCell($nameAddr)->getFormattedValue() ?? ''));
                
                if ($name !== '') {
                    $salarySheetNames[] = $name;
                }
            }
            
            // So sánh hai danh sách tên
            // Chuẩn hóa tên để so sánh (loại bỏ khoảng trắng thừa, lowercase)
            $normalizeNameFn = function($name) {
                return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)), 'UTF-8');
            };
            
            $mailListNamesNormalized = array_map($normalizeNameFn, $mailListNames);
            $salarySheetNamesNormalized = array_map($normalizeNameFn, $salarySheetNames);
            
            // Tên có trong mail list nhưng không có trong bảng lương
            $inMailListOnly = [];
            foreach ($mailListNames as $idx => $name) {
                if (!in_array($mailListNamesNormalized[$idx], $salarySheetNamesNormalized)) {
                    $inMailListOnly[] = $name;
                }
            }
            
            // Tên có trong bảng lương nhưng không có trong mail list
            $inSalarySheetOnly = [];
            foreach ($salarySheetNames as $idx => $name) {
                if (!in_array($salarySheetNamesNormalized[$idx], $mailListNamesNormalized)) {
                    $inSalarySheetOnly[] = $name;
                }
            }
            
            // Nếu có sự khác biệt, tìm cặp tên tương tự và hiển thị
            if (!empty($inMailListOnly) || !empty($inSalarySheetOnly)) {
                $mismatchedPairs = [];
                $usedSalaryNames = [];
                
                // Ghép cặp tên từ Mail List với tên tương tự nhất từ Bảng Lương
                foreach ($inMailListOnly as $mailName) {
                    $bestMatch = null;
                    $bestSimilarity = 0;
                    
                    foreach ($inSalarySheetOnly as $salaryName) {
                        // Bỏ qua nếu đã được ghép cặp
                        if (in_array($salaryName, $usedSalaryNames)) {
                            continue;
                        }
                        
                        // Tính độ tương đồng
                        similar_text(
                            mb_strtolower($mailName, 'UTF-8'),
                            mb_strtolower($salaryName, 'UTF-8'),
                            $percent
                        );
                        
                        // Chọn tên có độ tương đồng cao nhất (>= 50%)
                        if ($percent > $bestSimilarity && $percent >= 50) {
                            $bestSimilarity = $percent;
                            $bestMatch = $salaryName;
                        }
                    }
                    
                    if ($bestMatch !== null) {
                        $mismatchedPairs[] = $mailName . ' => ' . $bestMatch . ';';
                        $usedSalaryNames[] = $bestMatch;
                    } else {
                        // Không tìm thấy tên tương tự trong Bảng Lương
                        $mismatchedPairs[] = $mailName . ' => (không có trong Bảng Lương);';
                    }
                }
                
                // Thêm các tên trong Bảng Lương chưa được ghép cặp
                foreach ($inSalarySheetOnly as $salaryName) {
                    if (!in_array($salaryName, $usedSalaryNames)) {
                        $mismatchedPairs[] = '(không có trong Mail List) => ' . $salaryName . ';';
                    }
                }
                
                $message = "Danh sách nhân viên không khớp:\n" . implode("\n", $mismatchedPairs);
                
                return [
                    'valid' => false,
                    'message' => $message,
                ];
            }
            
            return ['valid' => true];
        } catch (\Exception $e) {
            Log::error('Lỗi khi kiểm tra mail list: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Có lỗi khi kiểm tra dữ liệu: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Tách sheet "Bảng lương" từ file Excel và lưu thành file mới
     * 
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return array
     */
    private function extractSalarySheet($uploadedFile, $sheetName = 'Bảng lương')
    {
        try {
            // Load file Excel đã upload
            $filePath = $uploadedFile->getRealPath();
            $spreadsheet = IOFactory::load($filePath);
            
            // Nếu không truyền tên sheet, tìm sheet "Bảng lương" (backward compatibility)
            if (!$sheetName) {
                $salarySheet = $this->findSheetByName($spreadsheet, $sheetName);
                if (!$salarySheet) {
                    $salarySheet = $this->findSheetByName($spreadsheet, $sheetName);
                }
                if (!$salarySheet) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy sheet có tên "Bảng lương" trong file Excel. Vui lòng kiểm tra lại tên sheet.',
                    ];
                }
            } else {
                // Tìm sheet theo tên được chỉ định
                $salarySheet = $this->findSheetByName($spreadsheet, $sheetName);
                if (!$salarySheet) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy sheet có tên "' . $sheetName . '" trong file Excel.',
                    ];
                }
            }
            
            // Tạo spreadsheet mới chỉ chứa sheet "Bảng lương"
            $newSpreadsheet = new Spreadsheet();
            $newSheet = $newSpreadsheet->getActiveSheet();
            $newSheet->setTitle('Bảng lương');
            
            // Copy dữ liệu từ sheet gốc sang sheet mới (không dùng clone để tránh lỗi)
            $highestRow = $salarySheet->getHighestDataRow();
            $highestColumn = $salarySheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            
            Log::debug('Bắt đầu copy dữ liệu sheet', [
                'highest_row' => $highestRow,
                'highest_column' => $highestColumn,
            ]);
            
            // Copy từng cell - lấy giá trị đã tính thay vì công thức
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                    $sourceCell = $salarySheet->getCell($cellAddress);
                    $targetCell = $newSheet->getCell($cellAddress);
                    
                    // Kiểm tra nếu cell có công thức, lấy giá trị đã tính thay vì công thức
                    $cellValue = $sourceCell->getValue();
                    if (is_string($cellValue) && strpos($cellValue, '=') === 0) {
                        // Đây là công thức, lấy giá trị đã tính
                        try {
                            $calculatedValue = $sourceCell->getCalculatedValue();
                            $targetCell->setValue($calculatedValue);
                        } catch (\Exception $e) {
                            // Nếu không tính được, dùng formatted value hoặc 0
                            $formattedValue = $sourceCell->getFormattedValue();
                            $targetCell->setValue($formattedValue !== '' ? $formattedValue : 0);
                        }
                    } else {
                        // Không phải công thức, copy giá trị bình thường
                        $targetCell->setValue($cellValue);
                    }
                    
                    // Copy style (optional - comment out if causing issues)
                    try {
                        $targetCell->getStyle()->applyFromArray(
                            $sourceCell->getStyle()->exportArray()
                        );
                    } catch (\Exception $e) {
                        // Ignore style errors
                    }
                }
            }
            
            // Copy column widths
            foreach ($salarySheet->getColumnDimensions() as $col => $dimension) {
                $newSheet->getColumnDimension($col)->setWidth($dimension->getWidth());
            }
            
            // Copy row heights
            foreach ($salarySheet->getRowDimensions() as $row => $dimension) {
                $newSheet->getRowDimension($row)->setRowHeight($dimension->getRowHeight());
            }
            
            // Copy merged cells
            foreach ($salarySheet->getMergeCells() as $mergeCell) {
                $newSheet->mergeCells($mergeCell);
            }
            
            Log::debug('Hoàn thành copy dữ liệu sheet');
            
            // Tạo tên file mới
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $uploadedFile->getClientOriginalExtension();
            $fileName = time() . '_' . $originalName . '_bangluong.' . $extension;
            
            // Lưu file mới vào storage
            $tempPath = sys_get_temp_dir() . '/' . $fileName;
            $writer = IOFactory::createWriter($newSpreadsheet, ucfirst($extension === 'xls' ? 'Xls' : 'Xlsx'));
            $writer->save($tempPath);
            
            // Di chuyển file vào storage/app/private/salary-files
            $storagePath = 'salary-files/' . $fileName;
            $disk = env('FILESYSTEM_DISK', 'local');
            Storage::disk($disk)->put($storagePath, file_get_contents($tempPath));
            
            // Xóa file tạm
            @unlink($tempPath);
            
            // Lấy kích thước file
            $fileSize = Storage::disk($disk)->size($storagePath);
            
            Log::info('Đã tách sheet "Bảng lương" thành công', [
                'original_file' => $uploadedFile->getClientOriginalName(),
                'new_file' => $fileName,
                'file_size' => $this->formatFileSize($fileSize),
            ]);
            
            return [
                'success' => true,
                'file_name' => $fileName,
                'file_path' => $storagePath,
                'file_size' => $this->formatFileSize($fileSize),
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi tách sheet "Bảng lương": ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Có lỗi khi xử lý file Excel: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Tìm sheet theo tên trong spreadsheet
     * 
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param string $sheetName Tên sheet cần tìm
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet|null
     */
    private function findSheetByName($spreadsheet, string $sheetName)
    {
        try {
            return $spreadsheet->getSheetByName($sheetName);
        } catch (\Exception $e) {
            Log::warning("Không tìm thấy sheet có tên '{$sheetName}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Gửi mail cho các nhân viên từ bulk export
     */
    public function sendBulkExportMails(Request $request, $exportId)
    {
        $export = SalaryBulkExport::with('salaryFile')->findOrFail($exportId);
        $recipients = $request->input('recipients', []);

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách người nhận trống.',
            ], 400);
        }

        // Tạo log pending cho từng người nhận trước
        foreach ($recipients as $recipient) {
            $export->logs()->create([
                'status' => 'pending',
                'email' => $recipient['email'] ?? '',
                'name' => $recipient['name'] ?? '',
                'message' => 'Đang đưa vào hàng đợi gửi email...',
            ]);
        }

        // Dispatch job để gửi mail (queue)
        SendBulkExportMails::dispatch($export, $recipients);

        return response()->json([
            'success' => true,
            'message' => 'Đã bắt đầu gửi email cho ' . count($recipients) . ' người nhận. Vui lòng theo dõi tiến trình trong log.',
        ]);
    }

    /**
     * Lấy logs của 1 lần xuất PDF hàng loạt
     */
    public function bulkExportLogs($id)
    {
        $export = SalaryBulkExport::findOrFail($id);
        $logs = $export->logs()->orderBy('id')->get();
        return response()->json($logs);
    }
    /**
     * Xóa logs của 1 lần xuất PDF hàng loạt
     */
    public function destroyBulkExportLogs($id)
    {
        try {
            $export = SalaryBulkExport::findOrFail($id);
            $export->logs()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa lịch sử gửi email thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa log bulk export: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa lịch sử.'
            ], 500);
        }
    }
}
