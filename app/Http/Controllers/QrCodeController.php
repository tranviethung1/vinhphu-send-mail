<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function index()
    {
        $qrCodes = QrCode::latest()->get();
        return view('qr-codes.index', compact('qrCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url'         => 'required|string|max:2000',
            'label'       => 'nullable|string|max:255',
            'frame_type'  => 'nullable|string|in:none,gold,bubble',
            'bubble_text' => 'nullable|string|max:255',
        ]);

        QrCode::create($validated);

        return redirect()->route('qr-codes.index')->with('success', 'Đã lưu QR code thành công.');
    }

    public function destroy($id)
    {
        QrCode::findOrFail($id)->delete();
        return redirect()->route('qr-codes.index')->with('success', 'Đã xóa QR code.');
    }
}
