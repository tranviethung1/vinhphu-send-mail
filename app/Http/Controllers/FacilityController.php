<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\SalaryFile;
use App\Models\MailList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FacilityController extends Controller
{
    /**
     * Danh sách cơ sở kèm số file lương.
     */
    public function index()
    {
        $facilities = Facility::with(['salaryFiles' => function ($query) {
                $query->latest()->take(5);
            }])
            ->with(['defaultMailList'])
            ->withCount('salaryFiles')
            ->orderBy('name')
            ->get();

        return view('facilities.index', compact('facilities'));
    }

    /**
     * Form tạo mới cơ sở.
     */
    public function create()
    {
        return view('facilities.create');
    }

    /**
     * Lưu cơ sở mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'data_link' => 'nullable|string|max:1000',
        ]);

        Facility::create($validated);

        return redirect()->route('facilities.index')
            ->with('success', 'Tạo cơ sở thành công');
    }

    /**
     * Form chỉnh sửa cơ sở.
     */
    public function edit(Facility $facility)
    {
        return view('facilities.edit', compact('facility'));
    }

    /**
     * Danh sách file lương theo từng cơ sở.
     */
    public function salaryFiles(Facility $facility)
    {
        $files = SalaryFile::where('facility_id', $facility->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('facilities.salary-files', compact('facility', 'files'));
    }

    /**
     * Danh sách email.
     */
    public function mailLists(Facility $facility)
    {
        $mailLists = MailList::where('facility_id', $facility->id)
            ->latest()
            ->get();

        return view('facilities.mail-lists', compact('facility', 'mailLists'));
    }

    /**
     * Đặt mail list mặc định cho cơ sở.
     */
    public function setDefaultMailList(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'mail_list_id' => 'nullable|exists:mail_lists,id',
        ]);

        $mailListId = $validated['mail_list_id'] ?? null;

        if ($mailListId) {
            $mailList = MailList::where('id', $mailListId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$mailList) {
                return back()->with('error', 'Mail list không thuộc cơ sở này.');
            }
        }

        // Clear old defaults
        MailList::where('facility_id', $facility->id)->update(['is_default' => DB::raw('false')]);
        // Set new default if provided
        if ($mailListId) {
            MailList::where('id', $mailListId)->update(['is_default' => DB::raw('true')]);
        }

        return back()->with('success', 'Đã cập nhật mail list mặc định.');
    }

    /**
     * Cập nhật cơ sở.
     */
    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'data_link' => 'nullable|string|max:1000',
        ]);

        $facility->update($validated);

        return redirect()->route('facilities.index')
            ->with('success', 'Cập nhật cơ sở thành công');
    }

    /**
     * Xóa cơ sở (sẽ nullify facility_id ở salary_files do FK nullOnDelete).
     */
    public function destroy(Facility $facility)
    {
        $facility->delete();

        return redirect()->route('facilities.index')
            ->with('success', 'Đã xóa cơ sở');
    }

    /**
     * Kiểm tra thay đổi từ Google Drive cho các danh sách mail mặc định.
     */
    public function checkDefaultMailListsDriveChanges()
    {
        $facilities = Facility::with('defaultMailList')->get();
        $changedFacilities = [];

        foreach ($facilities as $facility) {
            $mailList = $facility->defaultMailList;
            if (!$mailList || empty($mailList->google_sheet_url)) {
                continue;
            }

            $sheetUrl = trim((string)$mailList->google_sheet_url);
            if (!preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $sheetUrl, $m)) {
                continue;
            }
            $spreadsheetId = $m[1];
            $gid = 0;
            if (preg_match('~[#&]gid=(\d+)~', $sheetUrl, $gidMatch)) {
                $gid = (int) $gidMatch[1];
            }
            $exportUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$gid}";

            try {
                $response = Http::timeout(10)->get($exportUrl);
                if (!$response->successful()) continue;

                $csvContent = $response->body();
                if (str_starts_with($csvContent, "\xEF\xBB\xBF")) {
                    $csvContent = substr($csvContent, 3);
                }

                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $csvContent);
                rewind($stream);

                $driveRows = [];
                while (($row = fgetcsv($stream)) !== false) {
                    $row = array_slice(array_map('trim', $row), 0, 4);
                    $row = array_pad($row, 4, '');
                    if (collect($row)->contains(fn ($v) => $v !== '')) {
                        $driveRows[] = $row;
                    }
                }
                fclose($stream);

                if (empty($driveRows)) continue;
                $headers = array_shift($driveRows);

                $driveRows = array_values(array_filter($driveRows, function ($row) {
                    $b = trim((string) ($row[1] ?? ''));
                    $c = trim((string) ($row[2] ?? ''));
                    $d = trim((string) ($row[3] ?? ''));
                    return $b !== '' || $c !== '' || $d !== '';
                }));

                $currentHeaders = $mailList->headers ?? [];
                $currentRows = $mailList->rows ?? [];

                $currentFingerprint = md5(json_encode(['headers' => $currentHeaders, 'rows' => $currentRows]));
                $driveFingerprint = md5(json_encode(['headers' => $headers, 'rows' => $driveRows]));

                if ($currentFingerprint !== $driveFingerprint) {
                    $changedFacilities[] = $facility->id;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return response()->json([
            'changed_facilities' => $changedFacilities
        ]);
    }
}

