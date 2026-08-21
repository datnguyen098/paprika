@extends('admin.layouts.app')

@section('title', 'Chi tiết liên hệ')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-bold text-slate-950">{{ $contact->name }}</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="font-bold text-slate-500">Số điện thoại</dt><dd>{{ $contact->phone ?: 'Không có' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Cơ sở</dt><dd>{{ $contact->branch?->name ?: 'Chưa chọn' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Email</dt><dd>{{ $contact->email ?: 'Không có' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Ngày gửi</dt><dd>{{ $contact->created_at->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="font-bold text-slate-500">Nội dung</dt><dd class="whitespace-pre-line leading-7">{{ $contact->message }}</dd></div>
            </dl>
        </section>

        <form method="POST" action="{{ route('admin.contacts.update', $contact) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            @method('PUT')
            <div>
                <label for="status" class="admin-label">Trạng thái</label>
                <select id="status" name="status" class="admin-input" required>
                    @foreach (['new', 'read', 'processed'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $contact->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="mt-5">
                <label for="admin_note" class="admin-label">Ghi chú nội bộ</label>
                <textarea id="admin_note" name="admin_note" rows="8" class="admin-input">{{ old('admin_note', $contact->admin_note) }}</textarea>
                @error('admin_note') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button class="admin-btn-primary">Cập nhật</button>
                <a href="{{ route('admin.contacts.index') }}" class="admin-btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
@endsection
