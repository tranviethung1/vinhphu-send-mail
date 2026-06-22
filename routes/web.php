<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalaryFileController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\GoogleOAuthController;
use App\Models\SalaryFile;
use App\Models\Facility;
use App\Models\MailList;

Route::get('/', function () {
    $stats = [
        'salary_files_count' => SalaryFile::count(),
        'facilities_count'   => Facility::count(),
        'mail_lists_count'   => MailList::count(),
        'latest_salary_file' => SalaryFile::latest('created_at')->first(),
    ];

    return view('dashboard.index', compact('stats'));
})->name('dashboard');

// Routes tạo QR Code
Route::get('/qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');
Route::post('/qr-codes', [QrCodeController::class, 'store'])->name('qr-codes.store');
Route::delete('/qr-codes/{id}', [QrCodeController::class, 'destroy'])->name('qr-codes.destroy');

// Routes cho AI Chatbot
Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
Route::post('/ai-chat/chat', [AiChatController::class, 'chat'])->name('ai-chat.chat');
Route::post('/ai-chat/refresh', [AiChatController::class, 'refreshKnowledge'])->name('ai-chat.refresh');
Route::get('/ai-chat/preview', [AiChatController::class, 'knowledgePreview'])->name('ai-chat.preview');

// Health check endpoint for Cloud Run
Route::get('/health', function () {
    return response('healthy', 200)->header('Content-Type', 'text/plain');
});

// Google OAuth – lấy refresh token một lần (chạy /google/authorize rồi không cần nữa)
Route::get('/google/authorize', [GoogleOAuthController::class, 'authorize'])->name('google.authorize');
Route::get('/google/callback',  [GoogleOAuthController::class, 'callback'])->name('google.callback');

// Routes cho quản lý file lương
Route::get('/salary-files/{id}/download', [SalaryFileController::class, 'download'])->name('salary-files.download');
Route::post('/salary-files/{id}/copy', [SalaryFileController::class, 'copy'])->name('salary-files.copy');
Route::post('/salary-files/{id}/preview-pdf', [SalaryFileController::class, 'previewPdf'])->name('salary-files.preview-pdf');
Route::post('/salary-files/{id}/bulk-pdf', [SalaryFileController::class, 'bulkPdf'])->name('salary-files.bulk-pdf');
Route::get('/salary-files/{id}/bulk-history', [SalaryFileController::class, 'bulkHistory'])->name('salary-files.bulk-history');
Route::get('/salary-files/{id}/job-status', [SalaryFileController::class, 'checkJobStatus'])->name('salary-files.job-status');
Route::get('/salary-bulk-exports/{export}/download', [SalaryFileController::class, 'downloadBulkExport'])->name('salary-bulk-exports.download');
Route::post('/salary-bulk-exports/{export}/send-mails', [SalaryFileController::class, 'sendBulkExportMails'])->name('salary-bulk-exports.send-mails');
Route::get('/salary-bulk-exports/{id}/logs', [SalaryFileController::class, 'bulkExportLogs'])->name('salary-bulk-exports.logs');
Route::delete('/salary-bulk-exports/{id}/logs', [SalaryFileController::class, 'destroyBulkExportLogs'])->name('salary-bulk-exports.logs.destroy');
Route::delete('/salary-bulk-exports/{export}', [SalaryFileController::class, 'destroyBulkExport'])->name('salary-bulk-exports.destroy');
Route::resource('salary-files', SalaryFileController::class)->except(['show']);

// Routes quản lý cơ sở
Route::resource('facilities', FacilityController::class)->except(['show']);
Route::get('/facilities/check-default-mail-lists-drive-changes', [FacilityController::class, 'checkDefaultMailListsDriveChanges'])->name('facilities.check-default-mail-lists-drive-changes');
Route::get('/facilities/{facility}/salary-files', [FacilityController::class, 'salaryFiles'])->name('facilities.salary-files');
Route::get('/facilities/{facility}/mail-lists', [FacilityController::class, 'mailLists'])->name('facilities.mail-lists');
Route::post('/facilities/{facility}/mail-lists/default', [FacilityController::class, 'setDefaultMailList'])->name('facilities.mail-lists.default');

// Routes cho quản lý template (Excel mapping + màn tạo template mới)
Route::resource('templates', TemplateController::class)->only(['index', 'show', 'create']);
Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');
Route::get('/templates/mapping/create', [TemplateController::class, 'mappingCreate'])->name('templates.mapping.create');
Route::post('/templates/upload', [TemplateController::class, 'upload'])->name('templates.upload');
Route::post('/templates/save', [TemplateController::class, 'save'])->name('templates.save');
Route::post('/templates/{id}/upload', [TemplateController::class, 'replaceFile'])->name('templates.replaceFile');
Route::put('/templates/{id}', [TemplateController::class, 'update'])->name('templates.update');
Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');

// Routes cho quản lý mail (template + upload danh sách mail từ Excel)
Route::get('/mails', [MailController::class, 'index'])->name('mails.index');
Route::get('/mails/create', [MailController::class, 'create'])->name('mails.create');
Route::post('/mails', [MailController::class, 'store'])->name('mails.store');
Route::post('/mails/preview-sheet', [MailController::class, 'previewSheet'])->name('mails.preview-sheet');
Route::get('/mails/{mailList}/download', [MailController::class, 'download'])->name('mails.download');
Route::get('/mails/{mailList}/edit', [MailController::class, 'edit'])->name('mails.edit');
Route::put('/mails/{mailList}', [MailController::class, 'update'])->name('mails.update');
Route::post('/mails/{mailList}/sync-from-sheet', [MailController::class, 'syncFromSheet'])->name('mails.sync-from-sheet');
Route::post('/salary-files/{file}/sync-from-sheet', [SalaryFileController::class, 'syncFromSheet'])->name('salary-files.sync-from-sheet');
Route::get('/salary-files/{id}/check-drive-changes', [SalaryFileController::class, 'checkDriveChanges'])->name('salary-files.check-drive-changes');
Route::delete('/mails/{mailList}', [MailController::class, 'destroy'])->name('mails.destroy');
Route::get('/mails/{mailList}', [MailController::class, 'show'])->name('mails.show');