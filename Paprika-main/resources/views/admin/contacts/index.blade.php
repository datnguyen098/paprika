@extends('admin.layouts.app')

@section('title', 'Liên hệ')

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
                @foreach (['new', 'read', 'processed'] as $status)
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
                    <th>Nội dung</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contacts as $contact)
                    <tr>
                        <td class="font-semibold">{{ $contact->name }}</td>
                        <td>{{ $contact->branch?->name ?: 'Chưa chọn' }}</td>
                        <td>
                            <p>{{ $contact->phone ?: 'Không có SĐT' }}</p>
                            <p class="text-xs text-slate-500">{{ $contact->email }}</p>
                        </td>
                        <td class="max-w-md truncate">{{ $contact->message }}</td>
                        <td><span class="status-badge status-{{ $contact->status }}">{{ $contact->status }}</span></td>
                        <td>{{ $contact->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.contacts.show', $contact) }}" class="admin-btn-mini">Chi tiết</a>
                                <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" data-confirm="Xóa liên hệ này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có liên hệ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $contacts->links() }}</div>
@endsection
