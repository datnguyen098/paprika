@extends('admin.layouts.app')

@section('title', 'Chat online')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm tên, SĐT, email..." class="admin-input">
            <select name="branch_id" class="admin-input">
                <option value="">Tất cả cơ sở</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                @foreach (['open', 'pending', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Khách</th>
                    <th>Cơ sở</th>
                    <th>Liên hệ</th>
                    <th>Tin chưa đọc</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($chats as $chat)
                    <tr>
                        <td class="font-semibold">{{ $chat->visitor_name ?: 'Khách ghé thăm' }}</td>
                        <td>{{ $chat->branch?->name ?: 'Chưa chọn' }}</td>
                        <td>
                            <p>{{ $chat->phone ?: 'Không có SĐT' }}</p>
                            <p class="text-xs text-slate-500">{{ $chat->email }}</p>
                        </td>
                        <td>
                            <span class="status-badge {{ $chat->unread_count ? 'status-pending' : 'status-read' }}">{{ $chat->unread_count }}</span>
                        </td>
                        <td><span class="status-badge status-{{ $chat->status }}">{{ $chat->status }}</span></td>
                        <td>{{ optional($chat->last_message_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.chats.show', $chat) }}" class="admin-btn-mini">Mở chat</a>
                                <form method="POST" action="{{ route('admin.chats.destroy', $chat) }}" data-confirm="Xóa hội thoại này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có hội thoại.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $chats->links() }}</div>
@endsection
