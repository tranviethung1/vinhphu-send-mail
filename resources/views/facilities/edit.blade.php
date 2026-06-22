@extends('layouts.admin')

@section('title', 'Chỉnh sửa cơ sở')
@section('page-title', 'Chỉnh sửa cơ sở')

@push('styles')
<style>
    .card {background:#fff;border-radius:0.5rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);}
    .form-group {margin-bottom:1rem;}
    .label {display:block;font-weight:600;margin-bottom:0.35rem;color:#374151;}
    .input {width:100%;padding:0.65rem;border:1px solid #d1d5db;border-radius:0.375rem;font-size:0.95rem;}
    .input:focus {outline:none;border-color:#4299e1;box-shadow:0 0 0 3px rgba(66,153,225,0.15);}
    .actions {display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;}
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .error {color:#ef4444;font-size:0.85rem;margin-top:0.25rem;}
    .wrapper {max-width:640px;}
</style>
@endpush

@section('content')
<div class="wrapper">
    <div class="card">
        <form action="{{ route('facilities.update', $facility) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="label">Tên cơ sở <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" class="input" value="{{ old('name', $facility->name) }}" required>
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="label">Prefix</label>
                <input type="text" name="prefix" class="input" value="{{ old('prefix', $facility->prefix) }}" placeholder="Ví dụ: VPC, HN, ...">
                @error('prefix')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="label">Địa chỉ</label>
                <input type="text" name="address" class="input" value="{{ old('address', $facility->address) }}">
                @error('address')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label class="label">Data - Link</label>
                <input type="url" name="data_link" class="input" value="{{ old('data_link', $facility->data_link) }}" placeholder="https://...">
                @error('data_link')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div class="actions">
                <a href="{{ route('facilities.index') }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
