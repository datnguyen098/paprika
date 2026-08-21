@extends('admin.layouts.app')

@section('title', 'Sửa khung giờ món')

@section('content')
    <form method="POST" action="{{ route('admin.dish-time-slots.update', $slot) }}" class="admin-form">
        @csrf
        @method('PUT')
        @include('admin.dish-time-slots.form', ['slot' => $slot, 'branches' => $branches])
    </form>
@endsection
