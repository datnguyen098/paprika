@extends('admin.layouts.app')

@section('title', 'Hội thoại chat')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-bold text-slate-950">{{ $chat->visitor_name ?: 'Khách ghé thăm' }}</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="font-bold text-slate-500">Cơ sở</dt><dd>{{ $chat->branch?->name ?: 'Chưa chọn' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Số điện thoại</dt><dd>{{ $chat->phone ?: 'Không có' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Email</dt><dd>{{ $chat->email ?: 'Không có' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Trạng thái</dt><dd><span class="status-badge status-{{ $chat->status }}">{{ $chat->status }}</span></dd></div>
                <div><dt class="font-bold text-slate-500">Cập nhật</dt><dd>{{ optional($chat->last_message_at)->format('d/m/Y H:i') }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('admin.chats.update', $chat) }}" class="mt-6">
                @csrf
                @method('PATCH')
                <label for="status" class="admin-label">Đổi trạng thái</label>
                <select id="status" name="status" class="admin-input">
                    @foreach (['open', 'pending', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $chat->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="form-error">{{ $message }}</p> @enderror
                <button class="admin-btn-secondary mt-3">Cập nhật trạng thái</button>
            </form>
        </section>

        <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div
                class="max-h-[32rem] space-y-3 overflow-y-auto p-5"
                data-admin-chat-messages
                data-admin-chat-url="{{ route('admin.chats.messages', $chat) }}"
            >
                @foreach ($chat->messages as $message)
                    <div @class([
                        'max-w-[82%] rounded-2xl px-4 py-3 text-sm leading-6',
                        'ml-auto bg-emerald-700 text-white' => $message->sender === 'admin',
                        'bg-slate-100 text-slate-800' => $message->sender !== 'admin',
                    ])>
                        <p class="text-xs font-bold opacity-75">{{ $message->sender_name ?: $message->sender }} - {{ $message->created_at->format('H:i') }}</p>
                        <p class="mt-1 whitespace-pre-line">{{ $message->message }}</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.chats.reply', $chat) }}" class="border-t border-slate-200 p-5">
                @csrf
                <label for="message" class="admin-label">Phản hồi khách</label>
                <textarea id="message" name="message" rows="4" class="admin-input" required>{{ old('message') }}</textarea>
                @error('message') <p class="form-error">{{ $message }}</p> @enderror
                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="admin-btn-primary">Gửi phản hồi</button>
                    <a href="{{ route('admin.chats.index') }}" class="admin-btn-secondary">Quay lại</a>
                </div>
            </form>
        </section>
    </div>
@endsection
