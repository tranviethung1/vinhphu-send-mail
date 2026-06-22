@extends('layouts.admin')

@section('title', ($mailList->name ?? 'Mail List') . ' — Chi tiết')

@section('page-breadcrumb')
<nav aria-label="breadcrumb" style="display:flex;align-items:center;gap:0;line-height:1.2;margin-top:0.35rem;">
    <ol style="display:flex;align-items:center;gap:0;list-style:none;margin:0;padding:0;flex-wrap:wrap;">
        <li style="display:flex;align-items:center;">
            <a href="{{ route('facilities.index') }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                Cơ sở
            </a>
        </li>
        @if($mailList->facility)
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('facilities.mail-lists', $mailList->facility->id) }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="{{ $mailList->facility->name }}">
                {{ $mailList->facility->name }}
            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('facilities.mail-lists', $mailList->facility->id) }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                Danh sách email
            </a>
        </li>
        @endif
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                  title="{{ $mailList->name }}">{{ $mailList->name }}</span>
        </li>
    </ol>
</nav>
@endsection

@push('styles')
<style>
    .mail-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
    .mail-card { background: #ffffff; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); border: 1px solid #e5e7eb; }
    .card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
    .card-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; color: #111827; }
    .card-header p { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.95rem; }
    .badge { display: inline-flex; align-items: center; gap: 0.35rem; background: #f3f4f6; color: #111827; border-radius: 9999px; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
    .badge.success { background: #ecfdf3; color: #166534; }
    .table-wrapper { margin-top: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; }
    table.mail-table { width: 100%; border-collapse: collapse; }
    table.mail-table thead { background: #f9fafb; }
    table.mail-table th, table.mail-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; color: #111827; }
    table.mail-table tbody tr:nth-child(even) { background: #f9fafb; }
    table.mail-table tbody tr:hover { background: #f3f4f6; }
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .alert-local { padding: 0.85rem 1rem; border-radius: 0.65rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; margin-bottom: 0.75rem; }
    .update-box { margin-top: 1rem; padding: 1rem; border: 1px dashed #cbd5e0; border-radius: 0.75rem; background: #f9fafb; }
    .update-row { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
    @media (max-width: 768px) { .card-header { flex-direction: column; } }
</style>
@endpush

@section('content')
@php
    $headers = $mailList->headers ?: ['Cột A', 'Cột B', 'Cột C', 'Cột D'];
    $rows = $mailList->rows ?: [];
@endphp

<div class="mail-grid">
    <div class="mail-card">
        <div class="card-header">
            <div>
                <h2>{{ $mailList->name }}</h2>
                <p>File: {{ $mailList->original_filename ?? '-' }}</p>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                <div class="badge success">Số dòng: {{ count($rows) }}</div>
                <a class="btn btn-secondary" href="{{ route('mails.edit', $mailList) }}">Sửa</a>
                @if($mailList->facility_id)
                    <a class="btn btn-secondary" href="{{ route('facilities.mail-lists', $mailList->facility_id) }}">Quay lại</a>
                @else
                    <a class="btn btn-secondary" href="{{ route('mails.index') }}">Quay lại</a>
                @endif
            </div>
        </div>

        <div class="table-wrapper">
            <table class="mail-table">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row[0] ?? '' }}</td>
                            <td>{{ $row[1] ?? '' }}</td>
                            <td>{{ $row[2] ?? '' }}</td>
                            <td>{{ $row[3] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" style="color:#6b7280;">Không có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

